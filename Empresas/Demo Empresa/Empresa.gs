function getCfg_() {
  const p = PropertiesService.getScriptProperties();
  return {
    rootFolderId: p.getProperty('EMPRESAS_ROOT_FOLDER_ID'),
    dbId: p.getProperty('EMPRESAS_DB_SHEET_ID'),
  };
}

function db_() {
  const { dbId } = getCfg_();
  return SpreadsheetApp.openById(dbId);
}

function uid_(prefix) {
  return (prefix || 'id') + '_' + Utilities.getUuid().slice(0,8);
}

function createEmpresa_(data) {
  // data: {nombre,rut,direccion,fono,mail}
  const cfg = getCfg_();
  const root = DriveApp.getFolderById(cfg.rootFolderId);

  const empresaId = uid_('emp');
  const folderName = `${data.rut} - ${data.nombre}`.trim();
  const empresaFolder = root.createFolder(folderName);

  // subcarpetas
  const datos = empresaFolder.createFolder('01_DATOS');
  const cursos = empresaFolder.createFolder('02_CURSOS');
  const certs = empresaFolder.createFolder('03_CERTIFICADOS');

  const sh = db_().getSheetByName('Empresas') || db_().insertSheet('Empresas');
  if (sh.getLastRow() === 0) {
    sh.appendRow(['empresaId','nombre','rut','direccion','fono','mail','folderId','createdAt','activo']);
  }

  sh.appendRow([
    empresaId,
    data.nombre,
    data.rut,
    data.direccion,
    data.fono,
    data.mail,
    empresaFolder.getId(),
    new Date(),
    1
  ]);

  return {
    ok: true,
    empresaId,
    folderId: empresaFolder.getId(),
    folders: {
      datos: datos.getId(),
      cursos: cursos.getId(),
      certificados: certs.getId()
    }
  };
}
