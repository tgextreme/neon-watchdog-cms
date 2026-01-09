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
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Servicios - Neon Watchdog CMS</title>
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
                    <h1 class="h2"><i class="fas fa-server me-2"></i>Servicios Monitoreados</h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addServiceModal">
                            <i class="fas fa-plus me-1"></i>Nuevo Servicio
                        </button>
                    </div>
                </div>

                <div class="card shadow">
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Servicio</th>
                                        <th>Tipo</th>
                                        <th>Intervalo</th>
                                        <th>Estado</th>
                                        <th>Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($services)): ?>
                                    <tr>
                                        <td colspan="5" class="text-center text-muted py-4">
                                            <i class="fas fa-inbox fa-3x mb-3 d-block"></i>
                                            No hay servicios configurados. Haz clic en "Nuevo Servicio" para agregar uno.
                                        </td>
                                    </tr>
                                    <?php else: ?>
                                    <?php foreach ($services as $service): ?>
                                    <tr>
                                        <td>
                                            <strong><?php echo htmlspecialchars($service['display_name']); ?></strong><br>
                                            <small class="text-muted"><?php echo htmlspecialchars($service['name']); ?></small>
                                        </td>
                                        <td><span class="badge bg-info"><?php echo htmlspecialchars($service['check_type']); ?></span></td>
                                        <td><?php echo $service['check_interval']; ?>s</td>
                                        <td>
                                            <?php if ($service['enabled']): ?>
                                                <span class="badge bg-success"><i class="fas fa-check-circle me-1"></i>Activo</span>
                                            <?php else: ?>
                                                <span class="badge bg-secondary"><i class="fas fa-pause-circle me-1"></i>Pausado</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <button class="btn btn-sm btn-outline-primary" onclick="editService(<?php echo $service['id']; ?>)">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <button class="btn btn-sm btn-outline-danger" onclick="deleteService(<?php echo $service['id']; ?>)">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <!-- Modal para agregar servicio -->
    <div class="modal fade" id="addServiceModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-plus-circle me-2"></i>Nuevo Servicio</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p class="text-muted">Para agregar servicios, utiliza el API REST o edita directamente la base de datos.</p>
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle me-2"></i>
                        <strong>Documentación:</strong> Consulta <a href="API-REST.md" target="_blank">API-REST.md</a> para más información.
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="assets/js/main.js"></script>
    <script>
        function editService(id) {
            alert('Editar servicio ID: ' + id + '\nEsta funcionalidad se implementará próximamente.');
        }
        function deleteService(id) {
            if (confirm('¿Estás seguro de eliminar este servicio?')) {
                alert('Eliminar servicio ID: ' + id + '\nEsta funcionalidad se implementará próximamente.');
            }
        }
    </script>
</body>
</html>
