# Reserva Fácil

Aplicación web para explorar restaurantes, consultar menús y gestionar reservas,
mesas y administración.

## Tecnologías

- PHP con MySQL (PDO y MySQLi)
- HTML, CSS y JavaScript
- Node.js y el paquete `mysql` para la utilidad existente en `public/assets/js/funciones/conexion.js`

## Instalación local con XAMPP

1. Coloca el proyecto en `C:\xampp\htdocs\reserva_facil`.
2. Inicia Apache y MySQL desde el panel de XAMPP.
3. Revisa los parámetros de conexión en `src/config/conexion.php`. La configuración
   actual usa `127.0.0.1`, puerto `3307`, base de datos `restaurantes_db` y el usuario
   `root` sin contraseña. Ajusta esos valores a tu instalación.
4. La conexión inicializa la base de datos y las tablas requeridas. Los scripts de
   mantenimiento están en `database/`.
5. Abre `http://localhost/reserva_facil/public/` en el navegador.

La utilidad opcional de conexión escrita en Node.js requiere `npm install` antes de
ejecutar `node public/assets/js/funciones/conexion.js`.

Para servir el sitio desde otro entorno, configura el directorio `public/` como
document root o adapta la URL base de la aplicación y las rutas de recursos.

## Estructura

- `public/`: páginas HTML y recursos estáticos.
- `src/config/`: conexión y configuración de PHP.
- `src/helpers/`: validaciones y funciones compartidas.
- `src/api/`: endpoints organizados por módulo.
- `database/`: scripts de inicialización y mantenimiento.
- `tests/`: pruebas y utilidades de diagnóstico PHP.
- `tests/html/`: páginas HTML archivadas y pruebas manuales.
- `public/assets/css/`, `public/assets/js/` y `public/assets/img/`: hojas de estilo, JavaScript e imágenes.
