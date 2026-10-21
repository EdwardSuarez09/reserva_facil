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
3. Importa `database/restaurantes_db.sql` desde phpMyAdmin o la CLI de MariaDB. El
   archivo contiene el esquema, no cuentas ni datos personales de ejemplo.
4. La conexión usa las variables `RESERVA_DB_HOST`, `RESERVA_DB_PORT`,
   `RESERVA_DB_NAME`, `RESERVA_DB_USER` y `RESERVA_DB_PASSWORD`. En XAMPP local, si
   no se definen, se usan `127.0.0.1:3307`, `restaurantes_db` y `root` sin contraseña.
   Define las variables en la configuración de Apache/PHP para cualquier entorno
   que no sea local.
5. Crea el primer superadministrador desde PowerShell. Sustituye los valores por
   credenciales únicas y guarda la contraseña en un gestor seguro:

   ```powershell
   $env:SUPERADMIN_EMAIL = "admin@ejemplo.com"
   $env:SUPERADMIN_NAME = "Admin"
   $env:SUPERADMIN_PASSWORD = "una-frase-secreta-larga-y-unica"
   C:\xampp\php\php.exe scripts\crear_superadmin.php
   Remove-Item Env:\SUPERADMIN_PASSWORD
   ```

   El script solo funciona por CLI, requiere una contraseña de al menos 12
   caracteres y no crea otra cuenta si ya existe un superadministrador.
   Para actualizar una cuenta administrativa existente, ejecuta desde
   PowerShell con el correo correspondiente y una contraseña nueva y única:

   ```powershell
   $env:ADMIN_EMAIL = "admin@ejemplo.com"
   $env:ADMIN_NEW_PASSWORD = "una-frase-secreta-larga-y-unica"
   C:\xampp\php\php.exe scripts\resetear_password_admin.php
   Remove-Item Env:\ADMIN_EMAIL
   Remove-Item Env:\ADMIN_NEW_PASSWORD
   ```

   Ese script solo funciona por CLI y guarda la contraseña como hash.
6. Abre `http://localhost/reserva_facil/public/` en el navegador.

### Actualizar una base existente

Haz un respaldo de la base antes de migrar. Ejecuta
`C:\xampp\php\php.exe scripts\migrar_usuarios_cliente.php` desde la raíz del
proyecto para mover las cuentas antiguas con rol `usuario` a `usuarios_cliente`
y retirar ese rol de la tabla `admin`. Si encuentra un correo duplicado o
restaurantes asignados a una cuenta heredada, se detiene sin cambiar datos;
resuelve esos conflictos y vuelve a ejecutarla. La inicialización del esquema
ya no ocurre durante las peticiones web.
La migración conserva las contraseñas válidas y convierte las que estaban en
texto plano a hashes; no cambia las contraseñas conocidas de cuentas existentes.
Rota las credenciales heredadas antes de exponer una base existente a Internet.

### Pruebas

Ejecuta las pruebas automatizadas de reglas de reserva con:

```powershell
C:\xampp\php\php.exe tests\run.php
```

La prueba de integración opcional requiere MariaDB activo y una mesa existente.
Inserta una reserva temporal dentro de una transacción y la revierte al terminar:

```powershell
C:\xampp\php\php.exe tests\integration_reservas.php
```

Los scripts de diagnóstico heredados se conservaron bajo `tests/manual/legacy/`;
no forman parte del conjunto automatizado.

La utilidad opcional de conexión escrita en Node.js requiere `npm install` antes de
ejecutar `node public/assets/js/funciones/conexion.js`.

Para servir el sitio desde otro entorno, configura el directorio `public/` como
document root o adapta la URL base de la aplicación y las rutas de recursos.

## Estructura

- `public/`: páginas HTML y recursos estáticos.
- `src/config/`: conexión y configuración de PHP.
- `src/helpers/`: validaciones y funciones compartidas.
- `src/api/`: endpoints organizados por módulo.
- `database/`: esquema inicial para una base nueva y scripts de mantenimiento.
- `scripts/`: herramientas CLI de instalación.
- `tests/`: pruebas automatizadas.
- `tests/manual/legacy/`: scripts antiguos de diagnóstico conservados como archivo.
- `tests/html/`: páginas HTML archivadas y pruebas manuales.
- `public/assets/css/`, `public/assets/js/` y `public/assets/img/`: hojas de estilo, JavaScript e imágenes.
