# Dashboard Mejorado - MetaversOtec LMS

## 🎯 Descripción General

Dashboard corporativo avanzado basado en el diseño de Google Apps Script (carpeta `/Dashboards`), implementado con Next.js, React y Chart.js para análisis en tiempo real de datos de capacitación.

---

## ✨ Características Implementadas

### 📊 Métricas Principales (KPIs)

1. **Estudiantes Únicos**: Total de colaboradores inscritos
2. **Cursos Activos**: Cantidad de cursos disponibles
3. **Tasa de Completitud**: Porcentaje de cursos completados vs inscritos
4. **Promedio Global**: Calificación promedio de todos los cursos completados
5. **Estudiantes con 8+ cursos**: Métrica especial para reconocer alto compromiso

### 📈 Gráficos Interactivos

#### 1. **Actividad Diaria**
- **Tipo**: Gráfico de líneas dual
- **Métricas**:
  - Estudiantes únicos activos por día
  - Cursos completados por día
- **Interactividad**: Líneas suavizadas con animaciones

#### 2. **Distribución de Cursos por Estudiante**
- **Tipo**: Histograma de barras
- **Métrica**: Cantidad de estudiantes agrupados por número de cursos inscritos
- **Insight**: Identifica patrones de participación

#### 3. **Análisis por Edad**
- **Tipo**: Gráfico de barras agrupadas
- **Métricas**:
  - Promedio de calificaciones por rango etario
  - Mediana de calificaciones por rango etario
- **Rangos**: <18, 18-24, 25-34, 35-49, 50+

#### 4. **Análisis por Género**
- **Tipo**: Gráfico de barras agrupadas
- **Métricas**:
  - Promedio de calificaciones por género
  - Mediana de calificaciones por género
- **Comparación**: Permite identificar brechas de rendimiento

### 🔍 Funcionalidades Avanzadas

#### **Filtros Temporales**
- Filtro por fecha específica
- Botón de limpieza rápida
- Recalculo automático de todas las métricas

#### **Exportación de Datos**
- **JSON**: Payload completo con todas las métricas calculadas
- **CSV - Cursos**: Tabla detallada por curso con:
  - Nombre y código del curso
  - Inscritos, en progreso, completados
  - Promedio y mediana de calificaciones
  - Tasa de completitud
- **Descarga automática**: Archivos nombrados con empresa y fecha

### 📋 Tabla Detallada de Cursos

Visualización completa con:
- Nombre y código del curso
- Total de inscritos
- Estudiantes en progreso (badge azul)
- Estudiantes completados (badge verde)
- Promedio de calificaciones (destacado)
- Mediana de calificaciones
- Barra de progreso de completitud con porcentaje

### 🏆 Top Estudiantes

Ranking de los 6 estudiantes con más cursos inscritos, mostrando:
- Nombre completo
- RUT
- Total de cursos
- Cursos completados
- Posición en el ranking

### 💡 Insights y Recomendaciones Automáticas

Sistema inteligente que analiza y alerta sobre:

1. **Tasa de completitud baja** (<50%)
   - Sugerencia de enviar recordatorios

2. **Promedio general bajo** (<70%)
   - Recomendación de revisar dificultad o material de apoyo

3. **Cursos en progreso vs completados** (ratio >2:1)
   - Motivación para finalizar cursos iniciados

4. **Alto compromiso** (estudiantes con 8+ cursos)
   - Sugerencia de reconocimiento

5. **Sin inscripciones**
   - Guía para comenzar a asignar cursos

---

## 🎨 Diseño Visual

### Paleta de Colores (Dark Mode)
- **Background**: `#0A0A0A` con gradientes radiales
- **Superficie**: `#0f172a` (glass effect)
- **Brand Primary**: `#31D22D` (verde neón)
- **Accent Blue**: `#60a5fa`
- **Accent Cyan**: `#22d3ee`
- **Warning**: `#f59e0b`
- **Success**: `#22c55e`

### Componentes UI
- **Glass effect**: Fondo translúcido con blur
- **Border glow**: Bordes suaves con opacidad
- **Animaciones**: Framer Motion para transiciones suaves
- **Responsive**: Grid adaptable de 1 a 4 columnas

---

## 🔧 Tecnologías Utilizadas

- **Next.js 16.1.4**: Framework React
- **React 19**: Biblioteca UI
- **Chart.js 4.4.0**: Biblioteca de gráficos
- **react-chartjs-2**: Wrapper React para Chart.js
- **Framer Motion**: Animaciones
- **Supabase**: Base de datos y backend
- **TypeScript**: Tipado estático
- **Tailwind CSS**: Estilos utility-first

---

## 📊 Estructura de Datos

### Enrollment (Inscripción)
```typescript
interface Enrollment {
    id: string;
    student_id: string;
    course_id: string;
    status: 'not_started' | 'in_progress' | 'completed';
    best_score: string;
    completed_at: string | null;
    created_at: string;
    students: {
        rut: string;
        first_name: string;
        last_name: string;
        age: number;
        gender: string;
        company_name: string;
    };
    courses: {
        name: string;
        code: string;
    };
}
```

### Estadísticas Calculadas
- **Actividad diaria**: Agrupación por fecha con conteo de estudiantes únicos
- **Distribución**: Histograma de cursos por estudiante
- **Demografía**: Promedios y medianas por edad/género
- **Rendimiento por curso**: Métricas completas de cada curso

---

## 🚀 Uso

### Acceso al Dashboard
1. Navegar a `/admin/empresa`
2. Seleccionar **"Vista Gerente"**
3. El dashboard carga automáticamente

### Filtrar por Fecha
1. Click en botón **"Filtros"**
2. Seleccionar fecha en el picker
3. Las métricas se recalculan automáticamente
4. Click en **X** para limpiar filtro

### Exportar Datos
1. Click en botón **"Exportar Datos"**
2. Descarga automática de:
   - `dashboard-{empresa}-{fecha}.json`
   - `cursos-{empresa}-{fecha}.csv`

---

## 📈 Mejoras Propuestas Adicionales

### Implementadas ✅
- Gráficos interactivos con Chart.js
- Filtros temporales dinámicos
- Exportación multi-formato
- Análisis demográfico avanzado
- Sistema de insights automáticos
- Top estudiantes con ranking

### Futuras Mejoras Sugeridas 🔮

1. **Filtros Avanzados**
   - Rango de fechas (inicio-fin)
   - Filtro por curso específico
   - Filtro por cargo/departamento
   - Filtro por estado de completitud

2. **Visualizaciones Adicionales**
   - Gráfico de embudo (inscrito → en progreso → completado)
   - Mapa de calor de actividad semanal
   - Gráfico de tendencia temporal (línea de tiempo)
   - Comparativa mes a mes

3. **Análisis Predictivo**
   - Predicción de tasa de completitud
   - Identificación de estudiantes en riesgo de abandono
   - Recomendaciones de cursos basadas en perfil

4. **Notificaciones y Alertas**
   - Email automático con resumen semanal
   - Alertas cuando un KPI cae bajo umbral
   - Notificaciones de logros (badges)

5. **Interactividad**
   - Click en gráfico de actividad diaria para filtrar
   - Drill-down de curso específico al hacer click
   - Comparación entre múltiples cursos

6. **Exportación Avanzada**
   - PDF del dashboard completo
   - Excel con múltiples hojas
   - Programación de reportes automáticos

7. **Gamificación**
   - Badges y medallas para estudiantes destacados
   - Leaderboard público (opcional)
   - Sistema de puntos acumulados

---

## 🔗 Integración con el Sistema

### Conexión con Supabase
- Query con JOIN a `students`, `courses`, `enrollments`
- Filtro automático por `company_name`
- Recarga automática de datos

### Compatibilidad
- Funciona con cualquier empresa (parámetro `companyName`)
- Respeta permisos de rol (manager/trainer)
- Compatible con sistema de firmas digitales existente

---

## 📝 Notas Técnicas

### Optimizaciones
- **useMemo** para cálculos pesados (evita re-renders)
- **Filtrado eficiente** con Sets para RUTs únicos
- **Sorting en memoria** sin queries adicionales

### Configuración de Chart.js
```typescript
// Tema dark aplicado globalmente
Chart.defaults.color = "#cbd5e1";
Chart.defaults.font.family = "ui-sans-serif, system-ui";
Chart.defaults.datasets.bar.borderRadius = 6;
```

### Responsive Design
- Grid adaptable: 1 col (mobile) → 2 cols (tablet) → 4 cols (desktop)
- Gráficos con `maintainAspectRatio: false`
- Overflow controlado con scrollbars personalizados

---

## 🎯 Impacto en el Negocio

### Para Gerentes
- Visión global del estado de capacitación
- Identificación rápida de áreas de mejora
- Datos para toma de decisiones estratégicas
- Exportación para reportes ejecutivos

### Para Capacitadores
- Seguimiento de progreso en tiempo real
- Identificación de estudiantes que necesitan apoyo
- Métricas de efectividad de cursos
- Análisis de rendimiento demográfico

### Para RR.HH.
- Reportes de cumplimiento normativo
- Análisis de ROI de capacitaciones
- Identificación de brechas de habilidades
- Planificación de capacitaciones futuras

---

## 📞 Contacto y Soporte

Para consultas o mejoras adicionales, contactar al equipo de desarrollo de MetaversOtec.

**Versión**: 2.0 Enhanced Dashboard  
**Última actualización**: Enero 2026  
**Autor**: MetaversOtec Development Team
