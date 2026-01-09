<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();
require_once 'config/database.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$username = $_SESSION['username'];
$role = $_SESSION['role'];
$full_name = $_SESSION['full_name'];

$services = getMonitoredServices(false);

function generateYAML($services) {
    $yaml = "# Neon Watchdog Configuration\n";
    $yaml .= "# Generated: " . date('Y-m-d H:i:s') . "\n\n";
    
    $yaml .= "dashboard:\n";
    $yaml .= "  enabled: true\n";
    $yaml .= "  port: 8080\n";
    $yaml .= "  host: localhost\n\n";
    
    $yaml .= "services:\n";
    
    foreach ($services as $service) {
        $yaml .= "  - name: " . $service['name'] . "\n";
        $yaml .= "    display_name: \"" . $service['display_name'] . "\"\n";
        $yaml .= "    enabled: " . ($service['enabled'] ? 'true' : 'false') . "\n";
        $yaml .= "    check:\n";
        $yaml .= "      type: " . $service['check_type'] . "\n";
        $yaml .= "      interval: " . $service['check_interval'] . "s\n";
        $yaml .= "    action:\n";
        $yaml .= "      type: " . $service['action_type'] . "\n\n";
    }
    
    return $yaml;
}

if (isset($_GET['download'])) {
    header('Content-Type: application/x-yaml');
    header('Content-Disposition: attachment; filename="neon-watchdog-config.yaml"');
    echo generateYAML($services);
    exit;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Generador YAML - Neon Watchdog CMS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/prism/1.29.0/themes/prism-okaidia.min.css" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
</head>
<body>
    <?php include 'includes/navbar.php'; ?>
    
    <div class="container-fluid">
        <div class="row">
            <?php include 'includes/sidebar.php'; ?>
            
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2"><i class="fas fa-file-code me-2"></i>Generador de Configuración YAML</h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <a href="?download=1" class="btn btn-primary">
                            <i class="fas fa-download me-1"></i>Descargar YAML
                        </a>
                        <button onclick="copyYAML()" class="btn btn-secondary ms-2">
                            <i class="fas fa-copy me-1"></i>Copiar
                        </button>
                    </div>
                </div>

                <div class="alert alert-info">
                    <i class="fas fa-info-circle me-2"></i>
                    <strong>Información:</strong> Este archivo YAML es compatible con el sistema Neon Watchdog original en Go.
                    Guárdalo como <code>config.yaml</code> en el directorio del watchdog.
                </div>

                <div class="card shadow">
                    <div class="card-header">
                        <h5 class="mb-0">
                            <i class="fas fa-code me-2"></i>Vista Previa de Configuración
                            <span class="badge bg-primary ms-2"><?php echo count($services); ?> servicios</span>
                        </h5>
                    </div>
                    <div class="card-body p-0">
                        <pre class="m-0" style="max-height: 600px; overflow-y: auto;"><code id="yamlContent" class="language-yaml"><?php echo htmlspecialchars(generateYAML($services)); ?></code></pre>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/prism/1.29.0/prism.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/prism/1.29.0/components/prism-yaml.min.js"></script>
    <script src="assets/js/main.js"></script>
    <script>
        function copyYAML() {
            const yamlContent = document.getElementById('yamlContent').textContent;
            copyToClipboard(yamlContent);
        }
    </script>
</body>
</html>
