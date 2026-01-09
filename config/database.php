<?php
/**
 * Neon Watchdog CMS - Configuración de Base de Datos
 * 
 * Este archivo contiene la configuración de conexión a la base de datos
 * y funciones de utilidad para autenticación y gestión.
 */

// Configuración de la base de datos
define('DB_HOST', 'localhost');
define('DB_NAME', 'neon_watchdog_cms');
define('DB_USER', 'admin');
define('DB_PASS', 'admin123');
define('DB_CHARSET', 'utf8mb4');

// Configuración de sesiones
define('SESSION_DURATION', 3600 * 24); // 24 horas
define('SESSION_NAME', 'NEON_WATCHDOG_SESSION');

/**
 * Obtener conexión PDO a la base de datos
 */
function getDBConnection() {
    static $pdo = null;
    
    if ($pdo === null) {
        try {
            $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ];
            $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            error_log("Error de conexión: " . $e->getMessage());
            throw new Exception("Error de conexión a la base de datos");
        }
    }
    
    return $pdo;
}

/**
 * Autenticar usuario
 */
function authenticateUser($username, $password) {
    $pdo = getDBConnection();
    
    $stmt = $pdo->prepare("
        SELECT id, username, email, password_hash, full_name, role, is_active 
        FROM users 
        WHERE username = ? AND is_active = TRUE
    ");
    $stmt->execute([$username]);
    $user = $stmt->fetch();
    
    if (!$user) {
        return false;
    }
    
    if (!password_verify($password, $user['password_hash'])) {
        return false;
    }
    
    // Actualizar último login
    $updateStmt = $pdo->prepare("UPDATE users SET last_login = NOW() WHERE id = ?");
    $updateStmt->execute([$user['id']]);
    
    // Registrar en audit log
    logAudit($user['id'], 'user_login', 'user', $user['id'], ['success' => true]);
    
    return $user;
}

/**
 * Crear sesión de usuario
 */
function createSession($userId) {
    $pdo = getDBConnection();
    
    $token = bin2hex(random_bytes(32));
    $expiresAt = date('Y-m-d H:i:s', time() + SESSION_DURATION);
    $ipAddress = $_SERVER['REMOTE_ADDR'] ?? null;
    $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? null;
    
    $stmt = $pdo->prepare("
        INSERT INTO sessions (user_id, token, ip_address, user_agent, expires_at)
        VALUES (?, ?, ?, ?, ?)
    ");
    $stmt->execute([$userId, $token, $ipAddress, $userAgent, $expiresAt]);
    
    return $token;
}

/**
 * Validar sesión
 */
function validateSession($token) {
    $pdo = getDBConnection();
    
    $stmt = $pdo->prepare("
        SELECT s.*, u.username, u.email, u.full_name, u.role, u.is_active
        FROM sessions s
        JOIN users u ON s.user_id = u.id
        WHERE s.token = ? AND s.expires_at > NOW() AND u.is_active = TRUE
    ");
    $stmt->execute([$token]);
    
    return $stmt->fetch();
}

/**
 * Cerrar sesión
 */
function destroySession($token) {
    $pdo = getDBConnection();
    
    $stmt = $pdo->prepare("DELETE FROM sessions WHERE token = ?");
    $stmt->execute([$token]);
}

/**
 * Verificar permisos de usuario
 */
function checkPermission($userId, $requiredRole) {
    $roles = ['viewer' => 1, 'operator' => 2, 'admin' => 3];
    
    $pdo = getDBConnection();
    $stmt = $pdo->prepare("SELECT role FROM users WHERE id = ?");
    $stmt->execute([$userId]);
    $user = $stmt->fetch();
    
    if (!$user) {
        return false;
    }
    
    return $roles[$user['role']] >= $roles[$requiredRole];
}

/**
 * Registrar en audit log
 */
function logAudit($userId, $action, $entityType = null, $entityId = null, $details = null) {
    $pdo = getDBConnection();
    
    $ipAddress = $_SERVER['REMOTE_ADDR'] ?? null;
    $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? null;
    $detailsJson = $details ? json_encode($details) : null;
    
    $stmt = $pdo->prepare("
        INSERT INTO audit_logs (user_id, action, entity_type, entity_id, details, ip_address, user_agent)
        VALUES (?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute([$userId, $action, $entityType, $entityId, $detailsJson, $ipAddress, $userAgent]);
}

/**
 * Obtener servicios monitoreados
 */
function getMonitoredServices($enabledOnly = false) {
    $pdo = getDBConnection();
    
    $sql = "SELECT * FROM monitored_services";
    if ($enabledOnly) {
        $sql .= " WHERE enabled = TRUE";
    }
    $sql .= " ORDER BY name";
    
    $stmt = $pdo->query($sql);
    return $stmt->fetchAll();
}

/**
 * Obtener servicio por nombre
 */
function getServiceByName($name) {
    $pdo = getDBConnection();
    
    $stmt = $pdo->prepare("SELECT * FROM monitored_services WHERE name = ?");
    $stmt->execute([$name]);
    
    return $stmt->fetch();
}

/**
 * Crear nuevo servicio
 */
function createService($data, $userId) {
    $pdo = getDBConnection();
    
    $stmt = $pdo->prepare("
        INSERT INTO monitored_services 
        (name, display_name, description, enabled, check_type, check_config, 
         action_type, action_config, max_failures, check_interval_seconds, 
         restart_delay_seconds, created_by)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    
    $result = $stmt->execute([
        $data['name'],
        $data['display_name'] ?? $data['name'],
        $data['description'] ?? null,
        $data['enabled'] ?? true,
        $data['check_type'],
        json_encode($data['check_config']),
        $data['action_type'],
        json_encode($data['action_config']),
        $data['max_failures'] ?? 3,
        $data['check_interval_seconds'] ?? 30,
        $data['restart_delay_seconds'] ?? 5,
        $userId
    ]);
    
    if ($result) {
        logAudit($userId, 'service_created', 'monitored_service', $pdo->lastInsertId(), ['service_name' => $data['name']]);
    }
    
    return $result;
}

/**
 * Actualizar servicio
 */
function updateService($name, $data, $userId) {
    $pdo = getDBConnection();
    
    $service = getServiceByName($name);
    if (!$service) {
        return false;
    }
    
    $stmt = $pdo->prepare("
        UPDATE monitored_services 
        SET display_name = ?, description = ?, enabled = ?, check_type = ?, 
            check_config = ?, action_type = ?, action_config = ?, 
            max_failures = ?, check_interval_seconds = ?, restart_delay_seconds = ?
        WHERE name = ?
    ");
    
    $result = $stmt->execute([
        $data['display_name'] ?? $service['display_name'],
        $data['description'] ?? $service['description'],
        $data['enabled'] ?? $service['enabled'],
        $data['check_type'] ?? $service['check_type'],
        json_encode($data['check_config'] ?? json_decode($service['check_config'], true)),
        $data['action_type'] ?? $service['action_type'],
        json_encode($data['action_config'] ?? json_decode($service['action_config'], true)),
        $data['max_failures'] ?? $service['max_failures'],
        $data['check_interval_seconds'] ?? $service['check_interval_seconds'],
        $data['restart_delay_seconds'] ?? $service['restart_delay_seconds'],
        $name
    ]);
    
    if ($result) {
        logAudit($userId, 'service_updated', 'monitored_service', $service['id'], ['service_name' => $name]);
    }
    
    return $result;
}

/**
 * Eliminar servicio
 */
function deleteService($name, $userId) {
    $pdo = getDBConnection();
    
    $service = getServiceByName($name);
    if (!$service) {
        return false;
    }
    
    $stmt = $pdo->prepare("DELETE FROM monitored_services WHERE name = ?");
    $result = $stmt->execute([$name]);
    
    if ($result) {
        logAudit($userId, 'service_deleted', 'monitored_service', $service['id'], ['service_name' => $name]);
    }
    
    return $result;
}

/**
 * Toggle estado de servicio
 */
function toggleService($name, $userId) {
    $pdo = getDBConnection();
    
    $service = getServiceByName($name);
    if (!$service) {
        return false;
    }
    
    $newState = !$service['enabled'];
    
    $stmt = $pdo->prepare("UPDATE monitored_services SET enabled = ? WHERE name = ?");
    $result = $stmt->execute([$newState, $name]);
    
    if ($result) {
        logAudit($userId, 'service_toggled', 'monitored_service', $service['id'], [
            'service_name' => $name,
            'new_state' => $newState
        ]);
    }
    
    return ['success' => $result, 'enabled' => $newState];
}

/**
 * Registrar evento de reinicio
 */
function logRestartEvent($serviceName, $reason, $success, $errorMessage = null, $triggeredBy = 'automatic', $userId = null) {
    $pdo = getDBConnection();
    
    $service = getServiceByName($serviceName);
    if (!$service) {
        return false;
    }
    
    $stmt = $pdo->prepare("
        INSERT INTO restart_events (service_id, restart_reason, success, error_message, triggered_by, user_id)
        VALUES (?, ?, ?, ?, ?, ?)
    ");
    
    return $stmt->execute([$service['id'], $reason, $success, $errorMessage, $triggeredBy, $userId]);
}

/**
 * Obtener notificaciones no leídas
 */
function getUnreadNotifications($limit = 10) {
    $pdo = getDBConnection();
    
    $stmt = $pdo->prepare("
        SELECT n.*, ms.display_name as service_name
        FROM notifications n
        LEFT JOIN monitored_services ms ON n.service_id = ms.id
        WHERE n.is_read = FALSE
        ORDER BY n.severity DESC, n.created_at DESC
        LIMIT ?
    ");
    $stmt->execute([$limit]);
    
    return $stmt->fetchAll();
}

/**
 * Marcar notificación como leída
 */
function markNotificationAsRead($notificationId) {
    $pdo = getDBConnection();
    
    $stmt = $pdo->prepare("UPDATE notifications SET is_read = TRUE WHERE id = ?");
    return $stmt->execute([$notificationId]);
}

/**
 * Obtener configuración del sistema
 */
function getSystemConfig($key = null) {
    $pdo = getDBConnection();
    
    if ($key) {
        $stmt = $pdo->prepare("SELECT config_value FROM system_config WHERE config_key = ?");
        $stmt->execute([$key]);
        $result = $stmt->fetch();
        return $result ? $result['config_value'] : null;
    }
    
    $stmt = $pdo->query("SELECT config_key, config_value FROM system_config");
    $config = [];
    while ($row = $stmt->fetch()) {
        $config[$row['config_key']] = $row['config_value'];
    }
    
    return $config;
}

/**
 * Actualizar configuración del sistema
 */
function updateSystemConfig($key, $value) {
    $pdo = getDBConnection();
    
    $stmt = $pdo->prepare("
        INSERT INTO system_config (config_key, config_value) 
        VALUES (?, ?)
        ON DUPLICATE KEY UPDATE config_value = ?
    ");
    
    return $stmt->execute([$key, $value, $value]);
}

/**
 * Obtener estadísticas del dashboard
 */
function getDashboardStats() {
    $pdo = getDBConnection();
    
    // Total de servicios
    $totalServices = $pdo->query("SELECT COUNT(*) as count FROM monitored_services")->fetch()['count'];
    
    // Servicios activos
    $activeServices = $pdo->query("SELECT COUNT(*) as count FROM monitored_services WHERE enabled = TRUE")->fetch()['count'];
    
    // Último estado de servicios
    $stmt = $pdo->query("
        SELECT ms.name, ssh.is_healthy
        FROM monitored_services ms
        LEFT JOIN service_status_history ssh ON ms.id = ssh.service_id
        WHERE ms.enabled = TRUE
        AND ssh.checked_at IN (
            SELECT MAX(checked_at) 
            FROM service_status_history 
            WHERE service_id = ms.id
        )
    ");
    
    $healthyCount = 0;
    $unhealthyCount = 0;
    
    while ($row = $stmt->fetch()) {
        if ($row['is_healthy']) {
            $healthyCount++;
        } else {
            $unhealthyCount++;
        }
    }
    
    // Total de reinicios hoy
    $restartsToday = $pdo->query("
        SELECT COUNT(*) as count 
        FROM restart_events 
        WHERE DATE(restarted_at) = CURDATE()
    ")->fetch()['count'];
    
    // Notificaciones no leídas
    $unreadNotifications = $pdo->query("SELECT COUNT(*) as count FROM notifications WHERE is_read = FALSE")->fetch()['count'];
    
    return [
        'total_services' => $totalServices,
        'active_services' => $activeServices,
        'healthy_services' => $healthyCount,
        'unhealthy_services' => $unhealthyCount,
        'restarts_today' => $restartsToday,
        'unread_notifications' => $unreadNotifications
    ];
}
