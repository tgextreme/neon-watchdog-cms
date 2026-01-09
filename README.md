# 🐺 Neon Watchdog CMS

Sistema de gestión web para el monitoreo y control de servicios del sistema Neon Watchdog desarrollado en Go.

## 📋 Descripción

CMS completo desarrollado en PHP con interfaz estilo WordPress que permite gestionar servicios monitoreados, generar configuraciones YAML, visualizar notificaciones y administrar usuarios con control de roles.

## ✨ Características

### 🔐 Autenticación y Seguridad
- Sistema de login con sesiones seguras
- Contraseñas encriptadas con bcrypt (cost 12)
- Control de acceso basado en roles (admin, operator, viewer, monitor)
- Tokens de sesión únicos con validación

### 📊 Dashboard
- Estadísticas en tiempo real de servicios
- Contador de servicios activos/inactivos
- Panel de notificaciones recientes
- Registro de reinicios del día

### 🛠️ Gestión de Servicios
- CRUD completo de servicios monitoreados
- Tipos de chequeo: systemd, tcp_port, http, process
- Configuración de intervalos de monitoreo
- Activación/desactivación individual de servicios

### 📄 Generador YAML
- Exportación de configuración para Neon Watchdog (Go)
- Vista previa con syntax highlighting (Prism.js)
- Descarga directa de archivo config.yaml
- Compatible con el watchdog original

### 🔔 Centro de Notificaciones
- Visualización de alertas y eventos
- Badges de severidad (critical, high, medium, low)
- Marcado de notificaciones leídas/no leídas
- Filtrado por severidad y servicio

### 👥 Gestión de Usuarios (Admin)
- Crear, editar y eliminar usuarios
- Asignación de roles y permisos
- Registro de último login
- Control de usuarios activos/inactivos

### ⚙️ Configuración del Sistema (Admin)
- Puerto del dashboard
- Configuración SMTP (email)
- Webhooks (Slack/Discord)
- Parámetros de monitoreo (timeouts, reintentos)
- Reinicio automático de servicios

### 📋 Logs de Auditoría
- Registro completo de acciones
- Filtros por usuario, acción y fecha
- Información de IP y User Agent
- Exportación a CSV

### 🔑 API Keys
- Generación de claves para acceso programático
- Permisos granulares (read, write, delete)
- Fechas de expiración opcionales
- Revocación y regeneración de keys

### 🌐 REST API
- Endpoints para autenticación
- CRUD de servicios
- Consulta de estadísticas del dashboard
- Documentación completa en API-REST.md

## 🗄️ Base de Datos

MySQL/MariaDB con 9 tablas:
- `users` - Usuarios del sistema
- `sessions` - Sesiones activas
- `monitored_services` - Servicios a monitorear
- `service_status_history` - Historial de estados
- `restart_events` - Eventos de reinicio
- `notifications` - Alertas del sistema
- `system_config` - Configuración global
- `audit_logs` - Registro de auditoría
- `api_keys` - Claves de API

## 🛠️ Requisitos

- **PHP** 8.0+ (desarrollado con PHP 8.4.16)
- **Apache** 2.4+ con mod_rewrite
- **MySQL/MariaDB** 5.7+ (desarrollado con MariaDB 11.8.3)
- **Extensiones PHP:**
  - PDO MySQL
  - mbstring
  - session
  - bcrypt

## 📦 Instalación

### 1. Clonar el repositorio

```bash
git clone https://github.com/tgextreme/neon-watchdog-cms.git
cd neon-watchdog-cms
```

### 2. Configurar la base de datos

```bash
# Importar el esquema de base de datos
mysql -u root -p < DATABASE.md

# O ejecutar manualmente el script SQL incluido
```

### 3. Configurar Apache

Crear un VirtualHost o Alias:

```apache
Alias /app-gestion-neon-watchdogs /ruta/a/neon-watchdog-cms

<Directory /ruta/a/neon-watchdog-cms>
    Options Indexes FollowSymLinks
    AllowOverride All
    Require all granted
    
    # Habilitar .htaccess
    <IfModule mod_rewrite.c>
        RewriteEngine On
    </IfModule>
</Directory>
```

### 4. Configurar credenciales de base de datos

Editar `config/database.php`:

```php
$host = 'localhost';
$dbname = 'neon_watchdog_cms';
$username = 'tu_usuario';
$password = 'tu_contraseña';
```

### 5. Establecer permisos

```bash
sudo chown -R www-data:www-data .
chmod 755 -R .
chmod 644 config/database.php
```

### 6. Reiniciar Apache

```bash
sudo systemctl restart apache2
```

## 🚀 Uso

### Acceso al Sistema

URL: `http://localhost/app-gestion-neon-watchdogs/login.php`

**Credenciales por defecto:**
- Usuario: `admin`
- Contraseña: `admin123`

⚠️ **Cambiar la contraseña después del primer login**

### Usuarios de Prueba

| Usuario | Contraseña | Rol | Permisos |
|---------|------------|-----|----------|
| admin | admin123 | admin | Acceso total |
| operator | admin123 | operator | Gestión de servicios |
| viewer | admin123 | viewer | Solo lectura |
| monitor | admin123 | monitor | Monitoreo básico |

### Estructura de Roles

- **admin**: Acceso completo, gestión de usuarios y configuración
- **operator**: Gestión de servicios, notificaciones y logs
- **viewer**: Solo visualización, sin edición
- **monitor**: Monitoreo de servicios y alertas

## 📁 Estructura del Proyecto

```
neon-watchdog-cms/
├── api/                    # REST API endpoints
│   ├── auth.php           # Autenticación
│   ├── dashboard.php      # Estadísticas
│   ├── services.php       # CRUD servicios
│   └── helpers.php        # Funciones auxiliares
├── assets/
│   ├── css/
│   │   └── style.css     # Estilos personalizados
│   └── js/
│       └── main.js       # JavaScript global
├── config/
│   ├── database.php      # Configuración DB + funciones
│   └── error_config.php  # Configuración de errores
├── includes/
│   ├── navbar.php        # Barra de navegación
│   └── sidebar.php       # Menú lateral
├── api-keys.php          # Gestión de API Keys
├── audit-logs.php        # Logs de auditoría
├── index.php             # Dashboard principal
├── login.php             # Página de login
├── logout.php            # Cerrar sesión
├── notifications.php     # Centro de notificaciones
├── services.php          # Gestión de servicios
├── settings.php          # Configuración del sistema
├── users.php             # Gestión de usuarios
├── yaml-generator.php    # Generador de config YAML
├── .htaccess            # Configuración Apache
├── API-REST.md          # Documentación API
├── API-TESTS.md         # Tests de API
├── DATABASE.md          # Esquema de base de datos
└── README.md            # Este archivo
```

## 🔌 API REST

### Autenticación

```bash
POST /api/auth.php
Content-Type: application/json

{
  "username": "admin",
  "password": "admin123"
}
```

### Obtener Servicios

```bash
GET /api/services.php
Authorization: Bearer YOUR_API_KEY
```

### Estadísticas del Dashboard

```bash
GET /api/dashboard.php
Authorization: Bearer YOUR_API_KEY
```

Ver [API-REST.md](API-REST.md) para documentación completa.

## 🎨 Tecnologías Utilizadas

- **Backend**: PHP 8.4
- **Base de Datos**: MySQL/MariaDB
- **Frontend**: 
  - Bootstrap 5.3.0
  - Font Awesome 6.4.0
  - jQuery 3.6.0
  - Prism.js (syntax highlighting)
- **Servidor Web**: Apache 2.4

## 🔗 Integración con Neon Watchdog (Go)

El CMS genera archivos YAML compatibles con el sistema Neon Watchdog original:

1. Configurar servicios en el CMS
2. Ir a **Generador YAML**
3. Descargar `neon-watchdog-config.yaml`
4. Copiar a directorio del watchdog Go
5. Reiniciar watchdog: `./watchdog -config config.yaml`

## 📝 Notas de Desarrollo

- **Errores PHP**: Activados en desarrollo (`display_errors=On`)
- **Sesiones**: Almacenadas en tabla `sessions` (no en archivos)
- **Seguridad**: Bcrypt con cost 12, preparación de consultas SQL
- **Cache**: Considerar habilitar OpCache en producción

## 🐛 Solución de Problemas

### Error: "Undefined array key 'check_interval'"

Limpiar caché de Apache y PHP:
```bash
sudo systemctl reload apache2
```

Hacer hard refresh en navegador: `Ctrl+Shift+R`

### Páginas en blanco

Verificar errores en:
```bash
tail -f /var/log/apache2/error.log
```

Activar display_errors en `php.ini` o en archivos PHP:
```php
error_reporting(E_ALL);
ini_set('display_errors', 1);
```

### Problemas de permisos

```bash
sudo chown -R www-data:www-data /ruta/a/neon-watchdog-cms
sudo chmod 755 -R /ruta/a/neon-watchdog-cms
```

## 📄 Licencia

Este proyecto es de código abierto. Usar bajo tu propia responsabilidad.

## 👨‍💻 Autor

**tgextreme**
- GitHub: [@tgextreme](https://github.com/tgextreme)

## 🤝 Contribuciones

Las contribuciones son bienvenidas. Por favor:

1. Fork el proyecto
2. Crear una rama (`git checkout -b feature/nueva-funcionalidad`)
3. Commit cambios (`git commit -m 'Agregar nueva funcionalidad'`)
4. Push a la rama (`git push origin feature/nueva-funcionalidad`)
5. Abrir un Pull Request

## 📧 Soporte

Para reportar bugs o solicitar funcionalidades, abrir un [issue](https://github.com/tgextreme/neon-watchdog-cms/issues).

---

⚡ **Desarrollado con PHP, Bootstrap y ❤️**
