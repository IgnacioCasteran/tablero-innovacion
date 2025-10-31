# Tablero PJ

Aplicación web en PHP para gestión interna del Poder Judicial: tablero de accesos, carga/edición de informes, módulos de coordinación y normativa, y administración básica de sesiones.

## Objetivo

Centralizar accesos y operaciones frecuentes del personal (carga de informes, consulta de archivos, secciones por oficinas/circunscripciones) con una interfaz simple y rápida.

## Tecnologías

- **PHP 8+** 
- **HTML5, CSS3 (Bootstrap 5), JavaScript**
- **MySQL/MariaDB** (script: `informes_pj.sql`)
- **GitLab** para versionado

## Estructura de carpetas
├── api/ # Endpoints PHP (AJAX) para operaciones de datos
├── css/ # Estilos (Bootstrap + hojas propias)
├── icons/ # Íconos del tablero
├── img/ # Imágenes generales
├── js/ # Lógica de UI (filtros, validaciones, fetch)
├── login/ # Pantallas y helpers de autenticación
├── secciones/ # Vistas por módulo (agenda, normativa, oficinas, etc.)
├── uploads/ # Archivos cargados por usuarios (PDF, etc.)
├── # Script de base de datos (informes_pj.sql)
├── index.php # Tablero principal (home)
└── README.md


> **Nota de seguridad**: `uploads/` almacena documentos. 

## Autenticación

- **Login** simple basado en sesión PHP.
- Páginas protegidas verifican `$_SESSION['usuario']`; si no existe, redirigen a `login.html`.

## Módulos principales

- **Informes registrados**
  - Listado con filtros por categoría/rubro/fecha.
  - Carga/edición/eliminación de informes (`carga_informe.php`, `editar_informe.php`, `eliminar_informe.php`).
  - API asociada para operaciones (carpeta `api/`).

- **Coordinación de Oficinas Judiciales**
  - Navegación por **circunscripción** → **oficina**.
  - Acciones por oficina: acceso a **Carga Informe Periódico** y **Protocolos**.

- **Oficina de Objetos Secuestrados**
  - Subida y visualización de **PDF** directamente en el sistema.
  - Búsqueda por nombre de archivo.

- **Normativa y SGC**
  - Secciones de referencia (maquetadas) preparadas para integrar datos dinámicos.

## API (resumen)

- `api/obtener_informes.php`  
  Retorna informes para el listado (con filtros por query string).
- `api/guardar_informe.php`  
  Alta/edición de informe.
- `api/eliminar_informe.php`  
  Baja lógica/física según configuración.
- `api/api-agenda.php` y relacionados  
  Endpoints para agenda/eventos si corresponde.

> Las respuestas se entregan en JSON y se consumen desde `js/*.js` con `fetch()`.

## Base de datos

- Script de creación y datos iniciales: **`informes_pj.sql`**.
- Entidades típicas:
  - `usuarios` (autenticación)
  - `informes` (datos principales del informe)
  - Tablas de soporte para estados/categorías/rubros según el caso

> Ajustar nombres y relaciones según el script actual.

## Configuración de conexión

- Archivo `conexion.php` centraliza la conexión a MySQL/MariaDB.
- Variables a parametrizar: host, base, usuario, password.

## Requerimientos de ambiente

El proyecto fue desarrollado y probado en el siguiente entorno:

- **Sistema operativo (desarrollo):** Windows 10/11 con XAMPP
- **Servidor web:** Apache 2.4.x
- **PHP:** 8.2.x
- **Base de datos:** MySQL/MariaDB 10.4+
- **Extensiones PHP necesarias:** pdo, pdo_mysql, mbstring, openssl, json
- **Control de versiones:** Git 2.46
- **Frontend:** Bootstrap 5.3, JavaScript nativo

> El script de base de datos se encuentra en `informes_pj.sql`.

## Estilos y UX

- **Bootstrap 5** para layout y componentes.
- Ajustes específicos en `css/estilos.css`.
- Íconos propios en `icons/` para una UI consistente con el Poder Judicial.

## Buenas prácticas incluidas

- Validaciones en cliente y servidor para formularios.
- Rutas de subida controladas, normalización de nombres de archivos.
- Estructura por módulos para facilitar mantenimiento/escala.
- Comentarios en el código para facilitar revisión.

---

**Autor:** Ignacio Casteran  
**Contacto:** ignaciocasteran18@gmail.com

