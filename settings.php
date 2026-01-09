<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();
require_once 'config/database.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: index.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$username = $_SESSION['username'];
$role = $_SESSION['role'];
$full_name = $_SESSION['full_name'];

$settings = getSystemConfig();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Configuración del Sistema - Neon Watchdog CMS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
</head>
<body>
    <?php include 'includes/navbar.php'; ?>
    
    <div class="container-fluid">
        <div class="row">
            <?php include 'includes/sidebar.php'; ?>
            
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2"><i class="fas fa-cog me-2"></i>Configuración del Sistema</h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <button onclick="saveSettings()" class="btn btn-success">
                            <i class="fas fa-save me-1"></i>Guardar Cambios
                        </button>
                    </div>
                </div>

                <div class="row">
                    <!-- Dashboard Settings -->
                    <div class="col-md-6 mb-4">
                        <div class="card shadow">
                            <div class="card-header bg-primary text-white">
                                <h5 class="mb-0"><i class="fas fa-tachometer-alt me-2"></i>Dashboard</h5>
                            </div>
                            <div class="card-body">
                                <div class="mb-3">
                                    <label class="form-label">Puerto del Dashboard</label>
                                    <input type="number" class="form-control" id="dashboard_port" 
                                           value="<?php echo htmlspecialchars($settings['dashboard_port'] ?? '8080'); ?>">
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Intervalo de Actualización (segundos)</label>
                                    <input type="number" class="form-control" id="refresh_interval" 
                                           value="<?php echo htmlspecialchars($settings['refresh_interval'] ?? '30'); ?>">
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Email Settings -->
                    <div class="col-md-6 mb-4">
                        <div class="card shadow">
                            <div class="card-header bg-info text-white">
                                <h5 class="mb-0"><i class="fas fa-envelope me-2"></i>Configuración de Email</h5>
                            </div>
                            <div class="card-body">
                                <div class="mb-3">
                                    <label class="form-label">Servidor SMTP</label>
                                    <input type="text" class="form-control" id="smtp_host" 
                                           value="<?php echo htmlspecialchars($settings['smtp_host'] ?? ''); ?>" 
                                           placeholder="smtp.ejemplo.com">
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Puerto SMTP</label>
                                    <input type="number" class="form-control" id="smtp_port" 
                                           value="<?php echo htmlspecialchars($settings['smtp_port'] ?? '587'); ?>">
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Usuario SMTP</label>
                                    <input type="text" class="form-control" id="smtp_user" 
                                           value="<?php echo htmlspecialchars($settings['smtp_user'] ?? ''); ?>">
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Contraseña SMTP</label>
                                    <input type="password" class="form-control" id="smtp_password" 
                                           placeholder="••••••••">
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Email Desde</label>
                                    <input type="email" class="form-control" id="email_from" 
                                           value="<?php echo htmlspecialchars($settings['email_from'] ?? ''); ?>" 
                                           placeholder="noreply@ejemplo.com">
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Notifications Settings -->
                    <div class="col-md-6 mb-4">
                        <div class="card shadow">
                            <div class="card-header bg-warning text-dark">
                                <h5 class="mb-0"><i class="fas fa-bell me-2"></i>Notificaciones</h5>
                            </div>
                            <div class="card-body">
                                <div class="mb-3">
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" id="notifications_enabled" 
                                               <?php echo ($settings['notifications_enabled'] ?? true) ? 'checked' : ''; ?>>
                                        <label class="form-check-label" for="notifications_enabled">
                                            Activar notificaciones
                                        </label>
                                    </div>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Webhook URL (Slack/Discord)</label>
                                    <input type="url" class="form-control" id="webhook_url" 
                                           value="<?php echo htmlspecialchars($settings['webhook_url'] ?? ''); ?>" 
                                           placeholder="https://hooks.slack.com/services/...">
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Monitoring Settings -->
                    <div class="col-md-6 mb-4">
                        <div class="card shadow">
                            <div class="card-header bg-success text-white">
                                <h5 class="mb-0"><i class="fas fa-heartbeat me-2"></i>Monitoreo</h5>
                            </div>
                            <div class="card-body">
                                <div class="mb-3">
                                    <label class="form-label">Timeout de Chequeo (segundos)</label>
                                    <input type="number" class="form-control" id="check_timeout" 
                                           value="<?php echo htmlspecialchars($settings['check_timeout'] ?? '10'); ?>">
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Reintentos antes de alertar</label>
                                    <input type="number" class="form-control" id="max_retries" 
                                           value="<?php echo htmlspecialchars($settings['max_retries'] ?? '3'); ?>">
                                </div>
                                <div class="mb-3">
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" id="auto_restart" 
                                               <?php echo ($settings['auto_restart'] ?? false) ? 'checked' : ''; ?>>
                                        <label class="form-check-label" for="auto_restart">
                                            Reinicio automático de servicios
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="assets/js/main.js"></script>
    <script>
        function saveSettings() {
            alert('Guardar configuración del sistema');
            // TODO: Implementar guardado de configuración
        }
    </script>
</body>
</html>
