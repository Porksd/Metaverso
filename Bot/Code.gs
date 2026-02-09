/** ====== CONFIG ====== **/
const ASSISTANT_ID   = 'asst_aC3SxhN5BW9O4R9g1oDCJqtX'; // <-- tu assistant_id
const USE_SHEET_LOG  = true;   // guardar historial en hoja "ChatLog"
const MEMORY_TTL_S   = 60 * 60; // 1 hora (cache de thread)
// (Opcional) si usas varias orgs/proyectos y te lo piden:
// const OPENAI_ORG_ID = 'org_xxx'; // si aplica
/** ===================== **/

function doGet() {
  return HtmlService.createHtmlOutputFromFile('index')
    .setTitle('Chatbot MetaversOtec')
    .setXFrameOptionsMode(HtmlService.XFrameOptionsMode.ALLOWALL);
}

function _getApiKey_(){
  const key = PropertiesService.getScriptProperties().getProperty('OPENAI_API_KEY');
  if (!key) throw new Error('Falta OPENAI_API_KEY en Propiedades del script.');
  return key;
}

function _headers_() {
  const h = {
    Authorization: `Bearer ${_getApiKey_()}`,
    'Content-Type': 'application/json',
    'OpenAI-Beta': 'assistants=v2',
  };
  // if (typeof OPENAI_ORG_ID !== 'undefined') h['OpenAI-Organization'] = OPENAI_ORG_ID;
  return h;
}

/** ====== LOG OPCIONAL ====== **/
function _getSheet_() {
  const ss = SpreadsheetApp.getActive();
  let sh = ss.getSheetByName('ChatLog');
  if (!sh) sh = ss.insertSheet('ChatLog').appendRow(['timestamp','sessionId','role','message']);
  return sh;
}
function _log_(sessionId, role, message) {
  if (!USE_SHEET_LOG) return;
  try { _getSheet_().appendRow([new Date(), sessionId, role, message]); } catch(e){}
}

/** ====== THREAD POR SESIÓN ====== **/
function _threadCacheKey_(sessionId){ return `thread:${sessionId}`; }
function _getThreadId_(sessionId){
  const cache = CacheService.getScriptCache();
  const cached = cache.get(_threadCacheKey_(sessionId));
  if (cached) return cached;

  const res = UrlFetchApp.fetch('https://api.openai.com/v1/threads', {
    method:'post',
    headers:_headers_(),
    payload: JSON.stringify({}),
    muteHttpExceptions:true
  });
  const code = res.getResponseCode();
  const text = res.getContentText();
  if (code >= 400) throw new Error(`Crear thread falló (${code}): ${text}`);

  const json = JSON.parse(text || '{}');
  const threadId = json?.id;
  if (!threadId) throw new Error(`No se obtuvo id de thread. Respuesta: ${text}`);

  cache.put(_threadCacheKey_(sessionId), threadId, MEMORY_TTL_S);
  return threadId;
}

function resetSession(sessionId){
  const cache = CacheService.getScriptCache();
  cache.remove(_threadCacheKey_(sessionId));
  return {ok:true};
}

/** ====== ASSISTANTS API ====== **/
function _assistantReply_(sessionId, userMsg) {
  if (!ASSISTANT_ID) throw new Error('Falta ASSISTANT_ID.');
  const headers = _headers_();
  const threadId = _getThreadId_(sessionId);

  // 1) Agregar mensaje del usuario
  let res = UrlFetchApp.fetch(`https://api.openai.com/v1/threads/${threadId}/messages`, {
    method:'post',
    headers,
    payload: JSON.stringify({ role: 'user', content: userMsg }),
    muteHttpExceptions:true
  });
  let code = res.getResponseCode();
  if (code >= 400) throw new Error(`Agregar mensaje falló (${code}): ${res.getContentText()}`);

  // 2) Iniciar run con tu assistant (usa sus archivos/herramientas)
  res = UrlFetchApp.fetch(`https://api.openai.com/v1/threads/${threadId}/runs`, {
    method:'post',
    headers,
    payload: JSON.stringify({ assistant_id: ASSISTANT_ID }),
    muteHttpExceptions:true
  });
  code = res.getResponseCode();
  if (code >= 400) throw new Error(`Iniciar run falló (${code}): ${res.getContentText()}`);
  const runId = JSON.parse(res.getContentText() || '{}')?.id;
  if (!runId) throw new Error(`No se obtuvo runId: ${res.getContentText()}`);

  // 3) Polling hasta completar
  const maxWaitMs = 25000, stepMs = 1200;
  let waited = 0, status = 'queued';
  while (waited <= maxWaitMs) {
    Utilities.sleep(stepMs); waited += stepMs;
    const stRes = UrlFetchApp.fetch(`https://api.openai.com/v1/threads/${threadId}/runs/${runId}`, {
      method:'get',
      headers,
      muteHttpExceptions:true
    });
    const stCode = stRes.getResponseCode();
    if (stCode >= 400) throw new Error(`Consultar run falló (${stCode}): ${stRes.getContentText()}`);
    const st = JSON.parse(stRes.getContentText() || '{}');
    status = st?.status;
    if (status === 'completed') break;
    if (['failed','cancelled','expired'].includes(status)) {
      throw new Error(`Run ${status}. Detalle: ${stRes.getContentText()}`);
    }
  }
  if (status !== 'completed') {
    return 'Sigo procesando la información. Intenta de nuevo en unos segundos 🙏';
  }

  // 4) Leer la última respuesta del assistant
  const msgListRes = UrlFetchApp.fetch(`https://api.openai.com/v1/threads/${threadId}/messages?limit=10&order=desc`, {
    method:'get',
    headers,
    muteHttpExceptions:true
  });
  const listCode = msgListRes.getResponseCode();
  if (listCode >= 400) throw new Error(`Listar mensajes falló (${listCode}): ${msgListRes.getContentText()}`);

  const listJson = JSON.parse(msgListRes.getContentText() || '{}');
  const msgs = listJson?.data || [];
  const assistantMsg = msgs.find(m => m.role === 'assistant');

  return _extractAssistantText_(assistantMsg) || 'No obtuve contenido del assistant.';
}

function _extractAssistantText_(msg){
  try{
    if (!msg?.content) return '';
    let out = '';
    msg.content.forEach(part => {
      if (part?.type === 'text' && part?.text?.value) out += (out ? '\n\n' : '') + part.text.value;
      else if (part?.type === 'output_text' && part?.text) out += (out ? '\n\n' : '') + part.text;
    });
    return out.trim();
  }catch(e){ return ''; }
}

/** ====== ENDPOINT PARA LA UI ====== **/
function replyTo(message, sessionId) {
  try {
    if (!message || !sessionId) return {ok:false, error:'Mensaje o sesión vacíos'};
    _log_(sessionId, 'user', message);
    const reply = _assistantReply_(sessionId, message);
    _log_(sessionId, 'assistant', reply);
    return {ok:true, reply};
  } catch (err) {
    return {ok:true, reply: 'Error consultando el assistant: ' + (err?.message || err)};
  }
}

/** ====== PRUEBA RÁPIDA (menú Ejecutar) ====== **/
function testOpenAI(){
  // Verifica que la key está bien y el header beta se envía
  const res = UrlFetchApp.fetch('https://api.openai.com/v1/threads', {
    method:'post',
    headers:_headers_(),
    payload: JSON.stringify({}),
    muteHttpExceptions:true
  });
  Logger.log(res.getResponseCode() + ' ' + res.getContentText());
}
