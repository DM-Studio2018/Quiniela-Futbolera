# Plugin Quiniela FIFA 2026

Plugin completo de WordPress para gestionar quinielas/pollas de fútbol multi-torneo con sistema de puntos, premios y clasificación en tiempo real.

## 📋 Características Principales

### ✅ Multi-torneo
- Soporte para múltiples competiciones simultáneas
- Mundial FIFA 2026 configurado por defecto
- Adaptable a ligas locales, copas regionales, etc.

### ✅ Gestión de Partidos
- Importación masiva vía CSV
- Entrada manual partido por partido
- Actualización de resultados en tiempo real
- Cierre automático 10 minutos antes del partido

### ✅ Sistema de Pronósticos
- Interface visual con banderas de equipos
- Pronósticos de marcadores
- Predicciones de posiciones finales (campeón, subcampeón, etc.)
- Preguntas de trivia personalizables
- Guardado automático cada 2 minutos

### ✅ Puntuación Configurable
- 5 puntos por acertar ganador/empate
- 5 puntos por acertar un marcador
- 10 puntos por acertar ambos marcadores
- Puntos adicionales por predicciones finales
- Puntos extra por trivia

### ✅ Tabla de Posiciones
- Actualización en tiempo real
- Destacado del usuario actual
- Paginación automática
- Podio con colores oro/plata/bronce

### ✅ Sistema de Premios
- Gestión del bote acumulado
- Tope de 10,000,000 COP
- 70% campeón, 30% subcampeón (configurable)
- Contenido multimedia editable

### ✅ Panel de Administración
- Dashboard con estadísticas
- CRUD completo de torneos, equipos y partidos
- Gestión de participantes y pagos
- Creación de preguntas de trivia
- Exportación de datos a CSV
- Importador inteligente de CSV

## 🚀 Instalación

### Requisitos
- WordPress 5.8 o superior
- PHP 7.4 o superior
- MySQL 5.6 o superior

### Pasos de Instalación

1. **Descargar el plugin**
   ```bash
   git clone https://github.com/tuusuario/quiniela-fifa-2026.git
   ```

2. **Subir a WordPress**
   - Sube la carpeta completa a `/wp-content/plugins/`
   - O sube el archivo ZIP desde el panel de WordPress

3. **Activar el plugin**
   - Ve a Plugins → Plugins instalados
   - Busca "Quiniela FIFA 2026"
   - Haz clic en "Activar"

4. **Configuración inicial**
   - El plugin creará automáticamente las páginas necesarias:
     - Mis Pronósticos
     - Tabla de Posiciones
     - Premios
     - Reglas
   - También creará el torneo Mundial 2026 por defecto

## 📁 Estructura del Plugin

```
quiniela-fifa-2026/
├── quiniela-fifa-2026.php          # Archivo principal
├── includes/
│   ├── class-quiniela-db.php       # Gestión de base de datos
│   ├── class-quiniela-tournament.php
│   ├── class-quiniela-match.php
│   ├── class-quiniela-prediction.php
│   ├── class-quiniela-scoring.php  # Sistema de puntuación
│   └── class-quiniela-prizes.php   # Gestión de premios
├── admin/
│   ├── class-quiniela-admin.php    # Panel de administración
│   └── class-quiniela-csv-importer.php  # Importador CSV
├── public/
│   ├── class-quiniela-frontend.php # Frontend público
│   └── class-quiniela-shortcodes.php    # Shortcodes
├── assets/
│   ├── css/
│   │   ├── public.css              # Estilos públicos
│   │   └── admin.css               # Estilos del admin
│   ├── js/
│   │   ├── public.js               # JavaScript público
│   │   └── admin.js                # JavaScript del admin
│   └── images/
└── languages/                      # Archivos de traducción
```

## 🎯 Uso del Plugin

### Para Administradores

#### 1. Crear un Torneo
1. Ve a **Quiniela → Torneos**
2. Clic en "Agregar Nuevo"
3. Completa el formulario:
   - Nombre: "Copa América 2027"
   - Fechas de inicio y fin
   - Número de equipos
   - Formato de competición
4. Guarda el torneo

#### 2. Agregar Equipos
**Opción A: Manual**
1. Ve a **Quiniela → Equipos**
2. Clic en "Agregar Nuevo"
3. Ingresa nombre, código de país, grupo

**Opción B: Importar CSV**
1. Ve a **Quiniela → Importar CSV**
2. Selecciona archivo con formato:
```csv
name,short_name,country_code,group
Argentina,ARG,AR,A
Brasil,BRA,BR,A
Colombia,COL,CO,B
```

#### 3. Cargar Partidos
**Formato CSV requerido:**
```csv
match_number,date,time,phase,group,team1,team2,venue
1,11/06/2026,15:00,Fase de Grupos,A,México,Canadá,Estadio Azteca
2,11/06/2026,18:00,Fase de Grupos,A,Estados Unidos,Uruguay,SoFi Stadium
```

1. Ve a **Quiniela → Importar CSV**
2. Sube el archivo
3. El sistema creará automáticamente los equipos si no existen

#### 4. Actualizar Resultados
1. Ve a **Quiniela → Partidos**
2. Busca el partido finalizado
3. Ingresa los marcadores
4. Cambia estado a "Finalizado"
5. **Los puntos se calculan automáticamente**

#### 5. Crear Preguntas de Trivia
1. Ve a **Quiniela → Trivia**
2. Clic en "Agregar Nueva"
3. Escribe la pregunta
4. Agrega opciones de respuesta
5. Define puntos y fecha límite

#### 6. Configurar Sistema de Puntos
1. Ve a **Quiniela → Configuración**
2. Ajusta valores:
   - Puntos por acertar ganador
   - Puntos por marcador exacto
   - Puntos por predicciones finales
3. Guarda cambios

### Para Usuarios

#### 1. Inscribirse
1. Crear cuenta en el sitio
2. Completar el pago (integración WooCommerce)
3. Acceso automático al sistema

#### 2. Hacer Pronósticos
1. Ve a **Mis Pronósticos**
2. **Partidos:**
   - Ingresa goles predichos para cada equipo
   - Filas verdes = editables
   - Filas rojas = cerradas
3. **Predicciones Finales:**
   - Selecciona campeón, subcampeón, etc.
   - Escribe nombre del goleador
4. **Trivia:**
   - Responde preguntas extra
5. Clic en **"Guardar Pronósticos"**

#### 3. Ver Clasificación
1. Ve a **Tabla de Posiciones**
2. Tu posición está destacada
3. Actualización en tiempo real

#### 4. Consultar Premios
1. Ve a **Premios**
2. Revisa bote acumulado
3. Distribución de premios

## 🎨 Shortcodes Disponibles

### `[quiniela_predictions]`
Muestra el formulario completo de pronósticos del usuario

### `[quiniela_standings]`
Muestra la tabla de posiciones actualizada

### `[quiniela_prizes]`
Muestra información de premios y bote acumulado

### `[quiniela_rules]`
Muestra las reglas del torneo

### Ejemplos de Uso
```php
// En cualquier página o post
[quiniela_predictions]

// Con atributos personalizados
[quiniela_standings tournament_id="1"]
```

## ⚙️ Configuración Avanzada

### Integración con WooCommerce
1. Instala WooCommerce
2. Crea producto "Inscripción Quiniela"
3. Precio: valor de inscripción
4. En **Quiniela → Configuración**, vincula el producto

### Personalizar Estilos
Edita `/assets/css/public.css` o agrega CSS personalizado:

```css
/* Cambiar colores principales */
:root {
    --quiniela-primary: #tu-color;
    --quiniela-success: #tu-color;
}
```

### Hooks Disponibles

**Actions:**
```php
// Después de guardar pronóstico
do_action('quiniela_prediction_saved', $user_id, $match_id);

// Después de actualizar resultado
do_action('quiniela_match_result_updated', $match_id);

// Después de calcular puntos
do_action('quiniela_points_calculated', $user_id, $tournament_id);
```

**Filters:**
```php
// Modificar puntos calculados
apply_filters('quiniela_calculated_points', $points, $prediction, $result);

// Personalizar tabla de posiciones
apply_filters('quiniela_standings_data', $standings, $tournament_id);
```

## 📊 Base de Datos

### Tablas Creadas
- `wp_quiniela_tournaments` - Torneos
- `wp_quiniela_teams` - Equipos
- `wp_quiniela_matches` - Partidos
- `wp_quiniela_predictions` - Pronósticos de partidos
- `wp_quiniela_final_predictions` - Predicciones finales
- `wp_quiniela_trivia_questions` - Preguntas
- `wp_quiniela_trivia_answers` - Respuestas
- `wp_quiniela_user_payments` - Pagos

## 🔒 Seguridad

- Validación de nonces en todos los formularios
- Sanitización de entradas
- Verificación de permisos
- Protección CSRF
- Consultas preparadas (prepared statements)

## 🌐 Traducciones

El plugin está preparado para traducciones. Archivos POT incluidos en `/languages/`

Para traducir:
1. Usa Poedit o Loco Translate
2. Genera archivos .po y .mo
3. Coloca en `/wp-content/languages/plugins/`

## 🐛 Solución de Problemas

### Los pronósticos no se guardan
- Verifica que el usuario haya pagado
- Comprueba que no haya pasado el deadline
- Revisa permisos de base de datos

### Las banderas no aparecen
- Verifica URL de banderas en equipos
- Cambia servicio en `get_flag_url()` si es necesario

### Los puntos no se calculan
- Asegúrate de marcar partido como "Finalizado"
- Verifica que los resultados estén guardados
- Revisa configuración de puntos

### Error al importar CSV
- Verifica formato de archivo
- Revisa encoding (UTF-8)
- Comprueba nombres de columnas

## 📈 Roadmap

- [ ] Integración con API de partidos en vivo
- [ ] Notificaciones push
- [ ] App móvil nativa
- [ ] Sistema de ligas privadas
- [ ] Chat entre participantes
- [ ] Estadísticas avanzadas
- [ ] Modo multijugador por equipos

## 👥 Contribuir

¿Quieres contribuir? 
1. Fork del repositorio
2. Crea una rama: `git checkout -b feature/nueva-caracteristica`
3. Commit: `git commit -m 'Agregar nueva característica'`
4. Push: `git push origin feature/nueva-caracteristica`
5. Abre un Pull Request

## 📝 Licencia

GPL v2 or later

## 💬 Soporte

- **Email:** soporte@tudominio.com
- **Documentación:** https://docs.tudominio.com/quiniela
- **Issues:** https://github.com/tuusuario/quiniela-fifa/issues

## 🙏 Créditos

- Banderas cortesía de [Flagcdn](https://flagcdn.com)
- Datos del Mundial FIFA 2026 de fuentes oficiales
- Desarrollado con ❤️ para la comunidad de WordPress

---

**Versión:** 1.0.0  
**Última actualización:** 2025  
**Autor:** Tu Nombre  
**Requiere:** WordPress 5.8+, PHP 7.4+
