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

$audit_logs = getAuditLogs();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Audit Logs - Neon Watchdog CMS</title>
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
                    <h1 class="h2"><i class="fas fa-history me-2"></i>Audit Logs</h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <button onclick="exportLogs()" class="btn btn-sm btn-outline-secondary">
                            <i class="fas fa-download me-1"></i>Exportar CSV
                        </button>
                    </div>
                </div>

                <!-- Filtros -->
                <div class="card shadow mb-4">
                    <div class="card-body">
                        <form class="row g-3">
                            <div class="col-md-3">
                                <label class="form-label">Usuario</label>
                                <select class="form-select" id="filter_user">
                                    <option value="">Todos</option>
                                    <option value="admin">Admin</option>
                                    <option value="operator">Operator</option>
                                    <option value="viewer">Viewer</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Acción</label>
                                <select class="form-select" id="filter_action">
                                    <option value="">Todas</option>
                                    <option value="login">Login</option>
                                    <option value="logout">Logout</option>
                                    <option value="create">Create</option>
                                    <option value="update">Update</option>
                                    <option value="delete">Delete</option>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">Desde</label>
                                <input type="date" class="form-control" id="filter_date_from">
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">Hasta</label>
                                <input type="date" class="form-control" id="filter_date_to">
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">&nbsp;</label>
                                <button type="button" onclick="applyFilters()" class="btn btn-primary w-100">
                                    <i class="fas fa-filter me-1"></i>Filtrar
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Tabla de logs -->
                <div class="card shadow">
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-sm table-hover">
                                <thead>
                                    <tr>
                                        <th style="width: 50px;">ID</th>
                                        <th>Fecha/Hora</th>
                                        <th>Usuario</th>
                                        <th>Acción</th>
                                        <th>Recurso</th>
                                        <th>Detalles</th>
                                        <th>IP</th>
                                        <th>User Agent</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($audit_logs as $log): 
                                        $action_class = [
                                            'login' => 'success',
                                            'logout' => 'secondary',
                                            'create' => 'primary',
                                            'update' => 'info',
                                            'delete' => 'danger'
                                        ][$log['action']] ?? 'secondary';
                                    ?>
                                        <tr>
                                            <td><?php echo $log['id']; ?></td>
                                            <td>
                                                <small><?php echo date('d/m/Y H:i:s', strtotime($log['created_at'])); ?></small>
                                            </td>
                                            <td>
                                                <strong><?php echo htmlspecialchars($log['username']); ?></strong>
                                            </td>
                                            <td>
                                                <span class="badge bg-<?php echo $action_class; ?>">
                                                    <?php echo strtoupper($log['action']); ?>
                                                </span>
                                            </td>
                                            <td><?php echo htmlspecialchars($log['resource_type']); ?></td>
                                            <td>
                                                <small class="text-muted">
                                                    <?php echo htmlspecialchars(substr($log['details'] ?? '', 0, 50)); ?>
                                                    <?php if (strlen($log['details'] ?? '') > 50): ?>...<?php endif; ?>
                                                </small>
                                            </td>
                                            <td><code><?php echo htmlspecialchars($log['ip_address']); ?></code></td>
                                            <td>
                                                <small class="text-muted">
                                                    <?php 
                                                    $ua = $log['user_agent'] ?? '';
                                                    echo htmlspecialchars(substr($ua, 0, 30));
                                                    if (strlen($ua) > 30) echo '...';
                                                    ?>
                                                </small>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        
                        <?php if (empty($audit_logs)): ?>
                            <div class="text-center py-5">
                                <i class="fas fa-history fa-4x text-muted mb-3"></i>
                                <p class="text-muted">No hay registros de auditoría</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="assets/js/main.js"></script>
    <script>
        function applyFilters() {
            alert('Aplicar filtros de búsqueda');
            // TODO: Implementar filtrado de logs
        }
        
        function exportLogs() {
            alert('Exportar logs a CSV');
            // TODO: Implementar exportación de logs
        }
    </script>
</body>
</html>
