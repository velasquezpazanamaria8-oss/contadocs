# ContaDocs PHP — Instalación en Hostinger

## Subir a Hostinger en 5 pasos

### 1. Crear base de datos en Hostinger
1. Entra a **hPanel → Bases de datos → Administrador MySQL**
2. Crea una nueva base de datos y anota:
   - Nombre de BD
   - Usuario
   - Contraseña
3. Abre **phpMyAdmin**, selecciona tu BD
4. Ve a la pestaña **SQL**, pega todo el contenido de `database.sql` y ejecuta

### 2. Configurar el sistema
Abre `config/config.php` y edita:
```php
define('DB_HOST', 'localhost');
define('DB_NAME', 'tu_base_de_datos');
define('DB_USER', 'tu_usuario');
define('DB_PASS', 'tu_password');
define('APP_URL', 'https://docs.tudominio.com.pe');
define('APP_SECRET', 'pon-aqui-un-texto-largo-y-secreto');
```

### 3. Subir archivos por FTP
Usa FileZilla o el Administrador de archivos de Hostinger:
- Conéctate a tu Hostinger por FTP
- Sube TODOS los archivos a la carpeta de tu subdominio
  - Si tu subdominio es `docs.tudominio.com.pe`, la carpeta sería `public_html/docs/` o una carpeta aparte según cómo lo configures en Hostinger
- Asegúrate de subir también la carpeta `uploads/` (vacía está bien)

### 4. Dar permisos a la carpeta uploads
En el Administrador de archivos de Hostinger:
- Clic derecho en la carpeta `uploads/`
- Cambiar permisos a **755**

### 5. Primer acceso
Entra a: `https://docs.tudominio.com.pe/login.php`
- Email: `admin@contadocs.pe`
- Clave: `Admin2025#`
- **Cambia la contraseña inmediatamente**

---

## Estructura de archivos
```
contadocs-php/
├── login.php              ← Página de login
├── logout.php             ← Cerrar sesión
├── index.php              ← Redirección automática
├── cambiar-password.php   ← Primer login
├── bootstrap.php          ← Carga inicial
├── database.sql           ← Script de BD
├── .htaccess              ← Seguridad
├── config/
│   ├── config.php         ← ⚠️ EDITAR ESTE ARCHIVO
│   └── database.php       ← Conexión MySQL
├── app/
│   ├── Auth.php           ← Autenticación
│   └── Views/layouts/
│       └── sidebar.php    ← Menú lateral
├── admin/
│   └── dashboard.php      ← Panel superadmin
├── contador/
│   ├── clientes.php       ← Lista de clientes
│   ├── subir.php          ← Subir documentos
│   └── categorias.php     ← Tipos de documento
├── cliente/
│   ├── documentos.php     ← Portal del cliente
│   ├── descargar.php      ← Descarga segura
│   └── historial.php      ← Historial
├── assets/
│   └── css/app.css        ← Estilos
└── uploads/               ← PDFs guardados aquí
    └── .htaccess          ← Protección
```

## Crear subdominio en Hostinger
1. hPanel → Dominios → Subdominios
2. Crea `docs.tudominio.com.pe`
3. Apunta la carpeta raíz a donde subiste los archivos

## Seguridad importante
- Nunca subas el archivo `config/config.php` a GitHub
- La carpeta `uploads/` tiene `.htaccess` que bloquea ejecución de PHP
- Los PDFs solo se sirven a través de `descargar.php` (verificación de sesión)
