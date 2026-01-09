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

$notifications = getAllNotifications();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Notificaciones - Neon Watchdog CMS</title>
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
                    <h1 class="h2"><i class="fas fa-bell me-2"></i>Centro de Notificaciones</h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <button onclick="markAllAsRead()" class="btn btn-sm btn-outline-secondary">
                            <i class="fas fa-check-double me-1"></i>Marcar todas como leídas
                        </button>
                    </div>
                </div>

                <?php if (empty($notifications)): ?>
                    <div class="text-center py-5">
                        <i class="fas fa-bell-slash fa-4x text-muted mb-3"></i>
                        <p class="text-muted">No hay notificaciones</p>
                    </div>
                <?php else: ?>
                    <div class="list-group">
                        <?php foreach ($notifications as $notif): 
                            $severity_class = [
                                'critical' => 'danger',
                                'high' => 'warning',
                                'medium' => 'info',
                                'low' => 'secondary'
                            ][$notif['severity']] ?? 'secondary';
                            
                            $icon = [
                                'critical' => 'fa-exclamation-circle',
                                'high' => 'fa-exclamation-triangle',
                                'medium' => 'fa-info-circle',
                                'low' => 'fa-bell'
                            ][$notif['severity']] ?? 'fa-bell';
                            
                            $bg_class = $notif['is_read'] ? '' : 'bg-light';
                        ?>
                            <div class="list-group-item list-group-item-action <?php echo $bg_class; ?>">
                                <div class="d-flex w-100 justify-content-between">
                                    <h5 class="mb-1">
                                        <i class="fas <?php echo $icon; ?> text-<?php echo $severity_class; ?> me-2"></i>
                                        <?php echo htmlspecialchars($notif['title']); ?>
                                        <?php if (!$notif['is_read']): ?>
                                            <span class="badge bg-primary">Nuevo</span>
                                        <?php endif; ?>
                                    </h5>
                                    <small class="text-muted"><?php echo date('d/m/Y H:i', strtotime($notif['created_at'])); ?></small>
                                </div>
                                <p class="mb-1"><?php echo htmlspecialchars($notif['message']); ?></p>
                                <small class="text-muted">
                                    <span class="badge bg-<?php echo $severity_class; ?>"><?php echo strtoupper($notif['severity']); ?></span>
                                    <?php if ($notif['service_name']): ?>
                                        <span class="ms-2">Servicio: <strong><?php echo htmlspecialchars($notif['service_name']); ?></strong></span>
                                    <?php endif; ?>
                                </small>
                                <?php if (!$notif['is_read']): ?>
                                    <button onclick="markAsRead(<?php echo $notif['id']; ?>)" class="btn btn-sm btn-outline-primary ms-2">
                                        <i class="fas fa-check"></i> Marcar como leída
                                    </button>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="assets/js/main.js"></script>
    <script>
        function markAsRead(notificationId) {
            alert('Funcionalidad de marcar como leída: ' + notificationId);
            // TODO: Implementar llamada a API para marcar como leída
        }
        
        function markAllAsRead() {
            alert('Marcar todas las notificaciones como leídas');
            // TODO: Implementar llamada a API para marcar todas
        }
    </script>
</body>
</html>
