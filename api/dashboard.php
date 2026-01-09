<?php
/**
 * API de Dashboard y Estadísticas
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

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    
    $stats = getDashboardStats();
    
    http_response_code(200);
    echo json_encode([
        'success' => true,
        'stats' => $stats,
        'timestamp' => date('c')
    ]);
    
} else {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
}
