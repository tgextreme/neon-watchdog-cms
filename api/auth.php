<?php
/**
 * Ejemplo de Login y Autenticación
 */

require_once '../config/database.php';

header('Content-Type: application/json');

// Obtener datos del POST
$data = json_decode(file_get_contents('php://input'), true);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // Login
    if (!isset($data['username']) || !isset($data['password'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Username and password are required']);
        exit;
    }
    
    $user = authenticateUser($data['username'], $data['password']);
    
    if (!$user) {
        http_response_code(401);
        echo json_encode(['error' => 'Invalid credentials']);
        exit;
    }
    
    $token = createSession($user['id']);
    
    unset($user['password_hash']); // No enviar el hash de contraseña
    
    http_response_code(200);
    echo json_encode([
        'success' => true,
        'message' => 'Login successful',
        'token' => $token,
        'user' => $user
    ]);
    
} else if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    
    // Verificar sesión
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
    
    unset($session['token']);
    
    http_response_code(200);
    echo json_encode([
        'success' => true,
        'session' => $session
    ]);
    
} else if ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
    
    // Logout
    $authHeader = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
    if (preg_match('/Bearer\s+(.*)$/i', $authHeader, $matches)) {
        $token = $matches[1];
    } else {
        http_response_code(401);
        echo json_encode(['error' => 'No authorization token provided']);
        exit;
    }
    
    destroySession($token);
    
    http_response_code(200);
    echo json_encode([
        'success' => true,
        'message' => 'Logout successful'
    ]);
    
} else {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
}
