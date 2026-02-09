# 🎓 Guía de Uso: Sistema de Evaluaciones con Certificado

## ✅ Implementación Completa

Se ha implementado exitosamente el sistema de evaluación completo para todos los cursos del Metaverso. Ahora todos los cursos pueden tener las mismas capacidades que el "Curso Trabajo en Altura".

---

## 🎯 Características Implementadas

### 1. **Creación de Módulo de Evaluación**
- ✅ Botón "Crear Módulo de Evaluación Final" aparece automáticamente
- ✅ Se muestra solo si no existe un módulo de evaluación
- ✅ Crea automáticamente 3 componentes: SCORM → Quiz → Firma Digital

### 2. **Componentes de Evaluación**

#### 📦 SCORM (Actividad Interactiva)
- Ponderación configurable (por defecto 20%)
- Uploader integrado para paquetes .zip
- Visualización del paquete actual
- Tracking automático de progreso

#### 📝 Quiz (Preguntas y Respuestas)
- Ponderación configurable (por defecto 80%)
- Editor visual de preguntas
- Soporte para 4 opciones (A, B, C, D)
- Corrección automática
- Vista previa de preguntas en el editor

#### ✍️ Firma Digital
- Canvas HTML5 para captura de firma
- Almacenamiento automático en BD
- Incluida en certificado PDF
- Validación de autenticidad

### 3. **Configuración de Puntajes**
- **Puntaje Mínimo**: Define el % necesario para aprobar (default: 90%)
- **Peso Quiz**: Porcentaje del puntaje total del quiz (default: 80%)
- **Peso SCORM**: Se calcula automáticamente (100% - Quiz%)
- **Firma Digital**: Toggle ON/OFF para requerir firma
- **Guardar Config**: Botón para persistir cambios en BD

### 4. **Fórmula de Evaluación**
```
Puntaje Final = (Quiz × 80%) + (SCORM × 20%)
Si Puntaje Final ≥ 90% → ✅ APROBADO → Certificado
```

---

## 📚 Cómo Usar el Sistema

### **Paso 1: Crear un Curso Nuevo**
1. Ve a `/admin/metaverso/cursos`
2. Haz clic en "Crear Nuevo Curso"
3. Completa los datos básicos del curso

### **Paso 2: Agregar Contenido**
1. Entra al editor del curso (`/admin/metaverso/cursos/[id]/contenido`)
2. Agrega módulos de contenido (slides) haciendo clic en "Crear Nuevo Slide de Contenido"
3. En cada slide, agrega videos, imágenes, texto, etc.

### **Paso 3: Crear Módulo de Evaluación**
1. Una vez tengas al menos 1 módulo de contenido, aparecerá el botón **"+ Crear Módulo de Evaluación Final"**
2. Haz clic en el botón morado con gradiente
3. Confirma en el diálogo que aparecerá
4. Se creará automáticamente un módulo con 3 items:
   - ✅ SCORM (vacío, listo para subir)
   - ✅ Quiz (con 1 pregunta de ejemplo)
   - ✅ Firma Digital (configurada)

### **Paso 4: Configurar Puntajes**
1. En el módulo de evaluación, verás un panel de configuración con 4 campos:
   - **Puntaje Mínimo**: Ajusta el % requerido para aprobar
   - **Peso Quiz**: Ajusta el % del quiz (el SCORM se calcula automáticamente)
   - **Peso SCORM**: Se muestra en gris, auto-calculado
   - **Firma Digital**: Activa/desactiva el requisito de firma
2. Haz los cambios que necesites
3. **¡IMPORTANTE!** Haz clic en "Guardar Config" para persistir los cambios

### **Paso 5: Subir Paquete SCORM**
1. En el item "ACTIVIDAD SCORM", haz clic en "📦 Subir Paquete SCORM (.zip)"
2. Selecciona tu archivo .zip con el paquete SCORM
3. Espera a que se suba (verás el path en naranja cuando esté listo)
4. El sistema usará automáticamente `index.html` como punto de entrada

### **Paso 6: Editar Preguntas del Quiz**
1. En el item "PREGUNTAS DEL QUIZ", haz clic en "Editar Preguntas"
2. Se abrirá el editor visual de preguntas (QuizBuilder)
3. Puedes:
   - Agregar nuevas preguntas
   - Editar preguntas existentes
   - Marcar la opción correcta
   - Reordenar preguntas
4. Las preguntas se guardan automáticamente

### **Paso 7: Verificar Firma Digital**
1. El item "✍️ FIRMA DIGITAL DEL ALUMNO" está preconfigurado
2. No requiere configuración adicional
3. Los alumnos verán un canvas para firmar al finalizar
4. La firma se guarda automáticamente en `students.digital_signature_url`

### **Paso 8: Probar el Curso**
1. Asigna el curso a un alumno o empresa
2. Entra al portal del alumno
3. Completa el curso:
   - Termina todos los módulos de contenido
   - Completa la actividad SCORM
   - Responde el quiz
   - Firma digitalmente
4. Si el puntaje ≥ 90%, se generará automáticamente el certificado PDF

---

## 🎨 Diseño Visual

### Botón de Creación
- **Color**: Gradiente purple → brand → orange
- **Icono**: PenTool (pluma)
- **Animación**: Scale en hover
- **Texto**: "Crear Módulo de Evaluación Final"

### Panel de Configuración
- **Puntaje Mínimo**: Purple border, input grande centrado
- **Peso Quiz**: Blue border, auto-calcula SCORM
- **Peso SCORM**: Orange border, disabled (auto)
- **Firma Digital**: Green border, botón toggle

### Items de Evaluación
- **Quiz**: Blue gradient, lista de preguntas expandible
- **SCORM**: Orange gradient, uploader de archivos .zip
- **Firma Digital**: Green gradient, descripción explicativa

---

## 🔄 Flujo del Alumno

```
1. Alumno entra al curso
   ↓
2. Completa slides de contenido (videos, imágenes, texto)
   ↓
3. Llega al módulo de evaluación
   ↓
4. Completa actividad SCORM (ej: simulador de trabajo en altura)
   ↓
5. Responde quiz con preguntas
   ↓
6. Sistema calcula: (Quiz 80%) + (SCORM 20%) = Puntaje Final
   ↓
7. Si Puntaje ≥ 90% → Pasa a firma
   Si Puntaje < 90% → Puede reintentar (máx 3 intentos)
   ↓
8. Firma digitalmente en canvas
   ↓
9. Sistema genera certificado PDF automáticamente
   ↓
10. Certificado disponible para descarga
```

---

## 🗄️ Almacenamiento en Base de Datos

### Tabla: `course_modules`
```json
{
  "type": "evaluation",
  "settings": {
    "min_score": 90,
    "quiz_percentage": 80,
    "scorm_percentage": 20,
    "requires_signature": true,
    "max_attempts": 3
  }
}
```

### Tabla: `module_items`
```json
// Item SCORM
{
  "type": "scorm",
  "content": {
    "package_path": "/uploads/courses/[id]/scorm_package.zip",
    "entry_point": "index.html",
    "description": "Actividad práctica interactiva"
  }
}

// Item Quiz
{
  "type": "quiz",
  "content": {
    "questions": [
      {
        "id": "1",
        "question": "¿Cuál es la altura mínima para usar arnés?",
        "options": [
          { "id": "A", "text": "1.5 metros", "isCorrect": false },
          { "id": "B", "text": "1.8 metros", "isCorrect": true },
          { "id": "C", "text": "2.0 metros", "isCorrect": false },
          { "id": "D", "text": "2.5 metros", "isCorrect": false }
        ]
      }
    ]
  }
}

// Item Firma
{
  "type": "signature",
  "content": {
    "title": "Firma Digital",
    "description": "Firma digitalmente para validar tu participación"
  }
}
```

### Tabla: `enrollments`
```json
{
  "quiz_score": 85,        // Puntaje del quiz (0-100)
  "scorm_score": 95,       // Puntaje del SCORM (0-100)
  "detailed_scores": {
    "quiz": 85,
    "scorm": 95,
    "final": 87,           // (85*0.8) + (95*0.2) = 87
    "passed": false        // 87 < 90
  }
}
```

### Tabla: `students`
```json
{
  "digital_signature_url": "/uploads/signatures/student_123.png"
}
```

---

## ⚠️ Notas Importantes

1. **El módulo de evaluación SIEMPRE es el último**: Los slides de contenido van primero, la evaluación al final.

2. **Solo puede haber 1 módulo de evaluación por curso**: El botón desaparece después de crear uno.

3. **Quiz y SCORM suman 100%**: Si cambias el peso del quiz, el SCORM se ajusta automáticamente.

4. **La firma es opcional**: Puedes desactivarla si no la necesitas para ciertos cursos.

5. **Los certificados se generan automáticamente**: No necesitas hacer nada después de que el alumno apruebe.

6. **Los puntajes se guardan en `enrollments.detailed_scores`**: Puedes consultarlos para analytics.

7. **El sistema usa Row Level Security (RLS)**: Solo el admin puede editar cursos, los alumnos solo pueden ver su progreso.

---

## 🎯 Diferencias con "Curso Trabajo en Altura"

El "Curso Trabajo en Altura" tiene su estructura hardcodeada en la carpeta `/public/courses/`. 

**Los nuevos cursos dinámicos son superiores porque**:
- ✅ Todo se configura desde el panel admin (no necesitas editar archivos)
- ✅ Los puntajes se ajustan sin programar
- ✅ Las preguntas del quiz se editan visualmente
- ✅ Los SCORM se suben con drag & drop
- ✅ Todo se guarda en base de datos (más escalable)
- ✅ Puedes duplicar módulos fácilmente
- ✅ Reordenar contenido con drag & drop

---

## 🚀 Próximos Pasos Recomendados

1. **Crear cursos de prueba**: Haz varios cursos para familiarizarte con el sistema
2. **Subir SCORMs reales**: Reemplaza el ejemplo con tus paquetes SCORM de producción
3. **Personalizar certificados**: Edita el template en `/components/CertificateGenerator.tsx`
4. **Agregar analytics**: Usa `enrollments.detailed_scores` para dashboards
5. **Configurar emails**: Envía notificaciones cuando se generen certificados

---

## ❓ Preguntas Frecuentes

**Q: ¿Puedo cambiar el puntaje mínimo después de que alumnos ya empezaron?**
A: Sí, pero solo afectará a intentos futuros. Los puntajes ya guardados no cambian.

**Q: ¿Qué pasa si subo un SCORM que no tiene tracking?**
A: El sistema asignará automáticamente 100% al quiz y 0% al SCORM.

**Q: ¿Puedo tener solo quiz sin SCORM?**
A: Sí, simplemente no subas ningún paquete SCORM y pon 100% al quiz.

**Q: ¿Cuántas preguntas puedo tener en el quiz?**
A: Sin límite, pero recomendamos 10-15 para mantener engagement.

**Q: ¿Los certificados incluyen QR codes?**
A: No en esta versión, pero se puede agregar editando `CertificateGenerator.tsx`.

---

## 📞 Soporte

Si tienes dudas o problemas:
1. Revisa esta guía
2. Consulta `INSTRUCCIONES_EVALUACION.md` para detalles técnicos
3. Verifica los logs del navegador (F12 → Console)
4. Revisa los errores en el panel admin

---

**¡Todo listo! Ahora puedes crear cursos con evaluaciones completas igual que "Trabajo en Altura"** 🎉
