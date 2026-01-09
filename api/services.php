<?php
/**
 * API de Servicios Monitoreados
 */

require_once '../config/database.php';

header('Content-Type: application/json');

// Validar autenticación
$authHeader = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
if (preg_match('/Bearer\s+(.*)$/i', $authHeader, $matches)) {
    $token = $matches[1];
} else {
    http_response_code(401);
    echo json_encode(['error' => 'No authorization token provided']);
    exit;
}

$session = validateSession($token);

if (!$session) {
    http_response_code(401);
    echo json_encode(['error' => 'Invalid or expired token']);
    exit;
}

$userId = $session['user_id'];
$userRole = $session['role'];

// Obtener datos
$data = json_decode(file_get_contents('php://input'), true);
$pathParts = explode('/', trim($_SERVER['PATH_INFO'] ?? '', '/'));
$serviceName = $pathParts[0] ?? null;
$action = $pathParts[1] ?? null;

// GET - Listar servicios o uno específico
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    
    if ($serviceName) {
        $service = getServiceByName($serviceName);
        
        if (!$service) {
            http_response_code(404);
            echo json_encode(['error' => 'Service not found']);
            exit;
        }
        
        // Decodificar JSON
        $service['check_config'] = json_decode($service['check_config'], true);
        $service['action_config'] = json_decode($service['action_config'], true);
        
        http_response_code(200);
        echo json_encode($service);
    } else {
        $services = getMonitoredServices();
        
        // Decodificar JSON para cada servicio
        foreach ($services as &$service) {
            $service['check_config'] = json_decode($service['check_config'], true);
            $service['action_config'] = json_decode($service['action_config'], true);
        }
        
        http_response_code(200);
        echo json_encode(['targets' => $services]);
    }
    
}

// POST - Crear nuevo servicio
else if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // Verificar permisos
    if (!checkPermission($userId, 'operator')) {
        http_response_code(403);
        echo json_encode(['error' => 'Insufficient permissions']);
        exit;
    }
    
    // Validar datos requeridos
    if (!isset($data['name']) || !isset($data['check_type']) || !isset($data['action_type'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Missing required fields: name, check_type, action_type']);
        exit;
    }
    
    // Verificar si ya existe
    if (getServiceByName($data['name'])) {
        http_response_code(409);
        echo json_encode(['error' => 'Service already exists']);
        exit;
    }
    
    try {
        $result = createService($data, $userId);
        
        if ($result) {
            http_response_code(201);
            echo json_encode([
                'message' => 'Service created successfully',
                'name' => $data['name']
            ]);
        } else {
            http_response_code(500);
            echo json_encode(['error' => 'Failed to create service']);
        }
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => $e->getMessage()]);
    }
    
}

// PUT - Actualizar servicio o toggle
else if ($_SERVER['REQUEST_METHOD'] === 'PUT') {
    
    if (!$serviceName) {
        http_response_code(400);
        echo json_encode(['error' => 'Service name is required']);
        exit;
    }
    
    // Verificar permisos
    if (!checkPermission($userId, 'operator')) {
        http_response_code(403);
        echo json_encode(['error' => 'Insufficient permissions']);
        exit;
    }
    
    // Toggle
    if ($action === 'toggle') {
        $result = toggleService($serviceName, $userId);
        
        if ($result['success']) {
            http_response_code(200);
            echo json_encode([
                'message' => 'Service toggled successfully',
                'name' => $serviceName,
                'enabled' => $result['enabled']
            ]);
        } else {
            http_response_code(404);
            echo json_encode(['error' => 'Service not found']);
        }
    }
    // Update
    else {
        try {
            $result = updateService($serviceName, $data, $userId);
            
            if ($result) {
                http_response_code(200);
                echo json_encode([
                    'message' => 'Service updated successfully',
                    'name' => $serviceName
                ]);
            } else {
                http_response_code(404);
                echo json_encode(['error' => 'Service not found']);
            }
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['error' => $e->getMessage()]);
        }
    }
    
}

// DELETE - Eliminar servicio
else if ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
    
    if (!$serviceName) {
        http_response_code(400);
        echo json_encode(['error' => 'Service name is required']);
        exit;
    }
    
    // Verificar permisos (solo admin)
    if (!checkPermission($userId, 'admin')) {
        http_response_code(403);
        echo json_encode(['error' => 'Insufficient permissions. Admin role required']);
        exit;
    }
    
    $result = deleteService($serviceName, $userId);
    
    if ($result) {
        http_response_code(200);
        echo json_encode([
            'message' => 'Service deleted successfully',
            'name' => $serviceName
        ]);
    } else {
        http_response_code(404);
        echo json_encode(['error' => 'Service not found']);
    }
    
}

else {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
}
