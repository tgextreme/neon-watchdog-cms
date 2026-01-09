<?php
/**
 * Función helper para obtener el token de autorización
 */
function getAuthToken() {
    // Intentar desde diferentes fuentes
    $authHeader = null;
    
    // PHP-FPM / Apache con mod_php
    if (isset($_SERVER["HTTP_AUTHORIZATION"])) {
        $authHeader = $_SERVER["HTTP_AUTHORIZATION"];
    }
    
    // Apache con .htaccess rewrite
    if (!$authHeader && isset($_SERVER["REDIRECT_HTTP_AUTHORIZATION"])) {
        $authHeader = $_SERVER["REDIRECT_HTTP_AUTHORIZATION"];
    }
    
    // Usar apache_request_headers si está disponible
    if (!$authHeader && function_exists("apache_request_headers")) {
        $headers = apache_request_headers();
        if (isset($headers["Authorization"])) {
            $authHeader = $headers["Authorization"];
        } elseif (isset($headers["authorization"])) {
            $authHeader = $headers["authorization"];
        }
    }
    
    // Extraer token
    if ($authHeader && preg_match("/Bearer\s+(.*)$/i", $authHeader, $matches)) {
        return $matches[1];
    }
    
    return null;
}
