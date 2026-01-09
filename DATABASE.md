# 🗄️ Base de Datos - Neon Watchdog CMS

## 📊 Información General

**Base de datos:** `neon_watchdog_cms`  
**Motor:** MariaDB 11.8.3  
**Charset:** UTF8MB4  
**Collation:** utf8mb4_unicode_ci

---

## 👥 Usuarios de Acceso

### Usuarios de Base de Datos

| Usuario | Contraseña | Permisos |
|---------|-----------|----------|
| admin   | admin123  | ALL PRIVILEGES |
| root    | (sin contraseña con sudo) | ALL PRIVILEGES |

### Usuarios de la Aplicación

| Username | Email | Contraseña | Rol | Descripción |
|----------|-------|------------|-----|-------------|
| admin | admin@neonwatchdog.local | admin123 | admin | Administrador del Sistema |
| operator | operator@neonwatchdog.local | admin123 | operator | Operador de Servicios |
| viewer | viewer@neonwatchdog.local | admin123 | viewer | Usuario Solo Lectura |
| monitor | monitor@neonwatchdog.local | admin123 | operator | Monitor Automatizado |

---

## 📋 Estructura de Tablas

### 1. 👤 users
Tabla de usuarios del sistema con roles y autenticación.

```sql
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    full_name VARCHAR(100),
    role ENUM('admin', 'operator', 'viewer') DEFAULT 'viewer',
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    last_login TIMESTAMP NULL
);
```

**Roles:**
- `admin`: Control total del sistema
- `operator`: Puede gestionar servicios y reinicios
- `viewer`: Solo lectura

---

### 2. 🔐 sessions
Gestión de sesiones de usuario activas.

```sql
CREATE TABLE sessions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    token VARCHAR(255) UNIQUE NOT NULL,
    ip_address VARCHAR(45),
    user_agent TEXT,
    expires_at TIMESTAMP NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);
```

---

### 3. 🖥️ monitored_services
Servicios monitoreados por el watchdog.

```sql
CREATE TABLE monitored_services (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) UNIQUE NOT NULL,
    display_name VARCHAR(150),
    description TEXT,
    enabled BOOLEAN DEFAULT TRUE,
    check_type ENUM('systemd', 'tcp_port', 'http', 'process', 'command') NOT NULL,
    check_config JSON NOT NULL,
    action_type ENUM('systemd_restart', 'command', 'webhook') NOT NULL,
    action_config JSON NOT NULL,
    max_failures INT DEFAULT 3,
    check_interval_seconds INT DEFAULT 30,
    restart_delay_seconds INT DEFAULT 5,
    created_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
);
```

**Tipos de Check:**
- `systemd`: Verificar estado de servicio systemd
- `tcp_port`: Comprobar puerto TCP abierto
- `http`: Request HTTP/HTTPS
- `process`: Verificar proceso en ejecución
- `command`: Ejecutar comando personalizado

**Tipos de Action:**
- `systemd_restart`: Reiniciar servicio systemd
- `command`: Ejecutar comando shell
- `webhook`: Llamar webhook HTTP

---

### 4. 📈 service_status_history
Historial de comprobaciones de estado.

```sql
CREATE TABLE service_status_history (
    id INT AUTO_INCREMENT PRIMARY KEY,
    service_id INT NOT NULL,
    is_healthy BOOLEAN NOT NULL,
    consecutive_failures INT DEFAULT 0,
    message TEXT,
    response_time_ms INT,
    checked_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (service_id) REFERENCES monitored_services(id) ON DELETE CASCADE
);
```

---

### 5. 🔄 restart_events
Registro de eventos de reinicio de servicios.

```sql
CREATE TABLE restart_events (
    id INT AUTO_INCREMENT PRIMARY KEY,
    service_id INT NOT NULL,
    restart_reason VARCHAR(255),
    success BOOLEAN NOT NULL,
    error_message TEXT,
    triggered_by ENUM('automatic', 'manual', 'api') DEFAULT 'automatic',
    user_id INT NULL,
    restarted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (service_id) REFERENCES monitored_services(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
);
```

---

### 6. 🔔 notifications
Sistema de notificaciones y alertas.

```sql
CREATE TABLE notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    service_id INT,
    type ENUM('service_down', 'service_up', 'restart_failed', 'restart_success', 'info') NOT NULL,
    title VARCHAR(255) NOT NULL,
    message TEXT,
    severity ENUM('low', 'medium', 'high', 'critical') DEFAULT 'medium',
    is_read BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (service_id) REFERENCES monitored_services(id) ON DELETE CASCADE
);
```

---

### 7. ⚙️ system_config
Configuración global del sistema.

```sql
CREATE TABLE system_config (
    id INT AUTO_INCREMENT PRIMARY KEY,
    config_key VARCHAR(100) UNIQUE NOT NULL,
    config_value TEXT,
    description VARCHAR(255),
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);
```

**Configuraciones disponibles:**
- `dashboard_port`: Puerto del dashboard web (8080)
- `dashboard_enabled`: Estado del dashboard (true/false)
- `email_notifications`: Habilitar emails (true/false)
- `smtp_host`, `smtp_port`: Configuración SMTP
- `alert_email`: Email para alertas
- `webhook_url`: URL para webhooks
- `max_restart_attempts`: Máximo de intentos de reinicio
- `log_retention_days`: Días de retención de logs
- `api_rate_limit`: Límite de peticiones API/minuto

---

### 8. 📝 audit_logs
Registro de auditoría de acciones.

```sql
CREATE TABLE audit_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    action VARCHAR(100) NOT NULL,
    entity_type VARCHAR(50),
    entity_id INT,
    details JSON,
    ip_address VARCHAR(45),
    user_agent TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
);
```

---

### 9. 🔑 api_keys
Claves API para integraciones.

```sql
CREATE TABLE api_keys (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    key_name VARCHAR(100) NOT NULL,
    api_key VARCHAR(255) UNIQUE NOT NULL,
    permissions JSON,
    is_active BOOLEAN DEFAULT TRUE,
    expires_at TIMESTAMP NULL,
    last_used_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);
```

---

## 📊 Datos de Ejemplo Incluidos

### Servicios Monitoreados
- ✅ **apache** - Apache Web Server (systemd)
- ✅ **mariadb** - MariaDB Database (tcp_port:3306)
- ✅ **ssh** - SSH Server (tcp_port:22)
- ❌ **nginx** - Nginx Web Server (deshabilitado)

### API Keys Disponibles
```
Admin API Key:    nwk_admin_1a2b3c4d5e6f7g8h9i0j
Operator Key:     nwk_operator_k9j8h7g6f5e4d3c2b1a
Monitor Bot:      nwk_monitor_zyx9876543210abc
```

---

## 🔍 Consultas Útiles

### Ver todos los usuarios
```sql
SELECT id, username, email, role, is_active, last_login 
FROM users 
ORDER BY role, username;
```

### Servicios activos con estado
```sql
SELECT 
    ms.name,
    ms.display_name,
    ms.enabled,
    ms.check_type,
    COUNT(ssh.id) as checks_count,
    SUM(ssh.is_healthy) as healthy_checks
FROM monitored_services ms
LEFT JOIN service_status_history ssh ON ms.id = ssh.service_id
WHERE ms.enabled = TRUE
GROUP BY ms.id;
```

### Últimos 10 eventos de reinicio
```sql
SELECT 
    ms.display_name,
    re.restart_reason,
    re.success,
    re.triggered_by,
    u.username as triggered_by_user,
    re.restarted_at
FROM restart_events re
JOIN monitored_services ms ON re.service_id = ms.id
LEFT JOIN users u ON re.user_id = u.id
ORDER BY re.restarted_at DESC
LIMIT 10;
```

### Notificaciones no leídas
```sql
SELECT 
    n.type,
    n.title,
    n.message,
    n.severity,
    ms.display_name as service,
    n.created_at
FROM notifications n
LEFT JOIN monitored_services ms ON n.service_id = ms.id
WHERE n.is_read = FALSE
ORDER BY n.severity DESC, n.created_at DESC;
```

### Logs de auditoría del día
```sql
SELECT 
    u.username,
    al.action,
    al.entity_type,
    al.ip_address,
    al.created_at
FROM audit_logs al
LEFT JOIN users u ON al.user_id = u.id
WHERE DATE(al.created_at) = CURDATE()
ORDER BY al.created_at DESC;
```

### Estado de salud de servicios
```sql
SELECT 
    ms.display_name,
    ssh.is_healthy,
    ssh.consecutive_failures,
    ssh.message,
    ssh.response_time_ms,
    ssh.checked_at
FROM monitored_services ms
LEFT JOIN service_status_history ssh ON ms.id = ssh.service_id
WHERE ssh.checked_at IN (
    SELECT MAX(checked_at) 
    FROM service_status_history 
    WHERE service_id = ms.id
)
AND ms.enabled = TRUE
ORDER BY ssh.is_healthy ASC, ms.display_name;
```

---

## 🔧 Comandos de Mantenimiento

### Backup de la base de datos
```bash
sudo mysqldump neon_watchdog_cms > backup_$(date +%Y%m%d_%H%M%S).sql
```

### Restaurar backup
```bash
sudo mysql neon_watchdog_cms < backup_20260109_220000.sql
```

### Limpiar logs antiguos (>30 días)
```sql
DELETE FROM service_status_history WHERE checked_at < DATE_SUB(NOW(), INTERVAL 30 DAY);
DELETE FROM audit_logs WHERE created_at < DATE_SUB(NOW(), INTERVAL 30 DAY);
```

### Limpiar sesiones expiradas
```sql
DELETE FROM sessions WHERE expires_at < NOW();
```

### Optimizar tablas
```sql
OPTIMIZE TABLE service_status_history;
OPTIMIZE TABLE audit_logs;
OPTIMIZE TABLE sessions;
```

---

## 🔐 Seguridad

### Cambiar contraseña de usuario
```sql
-- Generar hash con PHP:
-- php -r "echo password_hash('nueva_contraseña', PASSWORD_BCRYPT);"

UPDATE users 
SET password_hash = '$2y$10$...' 
WHERE username = 'admin';
```

### Revocar API Key
```sql
UPDATE api_keys 
SET is_active = FALSE 
WHERE api_key = 'nwk_admin_1a2b3c4d5e6f7g8h9i0j';
```

### Ver intentos de login
```sql
SELECT 
    u.username,
    al.details,
    al.ip_address,
    al.created_at
FROM audit_logs al
JOIN users u ON al.user_id = u.id
WHERE al.action = 'user_login'
AND DATE(al.created_at) = CURDATE()
ORDER BY al.created_at DESC;
```

---

## 📱 Acceso a phpMyAdmin

**URL:** http://localhost/phpmyadmin/  
**Usuario:** admin  
**Contraseña:** admin123

---

## 🚀 Conexión desde PHP

```php
<?php
$host = 'localhost';
$dbname = 'neon_watchdog_cms';
$username = 'admin';
$password = 'admin123';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "Conexión exitosa";
} catch(PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>
```

---

## 📞 Soporte

Para más información sobre el API REST, consulta [API-REST.md](API-REST.md)
