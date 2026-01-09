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

$api_keys = getAPIKeys();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>API Keys - Neon Watchdog CMS</title>
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
                    <h1 class="h2"><i class="fas fa-key me-2"></i>Gestión de API Keys</h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#generateKeyModal">
                            <i class="fas fa-plus me-1"></i>Generar Nueva Key
                        </button>
                    </div>
                </div>

                <div class="alert alert-info">
                    <i class="fas fa-info-circle me-2"></i>
                    <strong>Información:</strong> Las API Keys permiten acceso programático al sistema. Guárdalas de forma segura ya que no se podrán recuperar después de generarlas.
                </div>

                <div class="card shadow">
                    <div class="card-body">
                        <?php if (empty($api_keys)): ?>
                            <div class="text-center py-5">
                                <i class="fas fa-key fa-4x text-muted mb-3"></i>
                                <p class="text-muted">No hay API Keys generadas</p>
                                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#generateKeyModal">
                                    <i class="fas fa-plus me-1"></i>Generar Primera Key
                                </button>
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>ID</th>
                                            <th>Nombre</th>
                                            <th>Key</th>
                                            <th>Permisos</th>
                                            <th>Estado</th>
                                            <th>Último Uso</th>
                                            <th>Expira</th>
                                            <th>Acciones</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($api_keys as $key): ?>
                                            <tr>
                                                <td><?php echo $key['id']; ?></td>
                                                <td>
                                                    <strong><?php echo htmlspecialchars($key['key_name']); ?></strong>
                                                </td>
                                                <td>
                                                    <code class="api-key-display">
                                                        <?php echo substr($key['api_key'], 0, 12); ?>••••••••<?php echo substr($key['api_key'], -4); ?>
                                                    </code>
                                                    <button onclick="copyToClipboard('<?php echo $key['api_key']; ?>')" class="btn btn-sm btn-outline-secondary ms-1" title="Copiar">
                                                        <i class="fas fa-copy"></i>
                                                    </button>
                                                </td>
                                                <td>
                                                    <?php
                                                    $permissions = explode(',', $key['permissions']);
                                                    foreach ($permissions as $perm):
                                                    ?>
                                                        <span class="badge bg-info me-1"><?php echo trim($perm); ?></span>
                                                    <?php endforeach; ?>
                                                </td>
                                                <td>
                                                    <?php if ($key['is_active']): ?>
                                                        <span class="badge bg-success">Activa</span>
                                                    <?php else: ?>
                                                        <span class="badge bg-danger">Revocada</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <?php 
                                                    if ($key['last_used_at']) {
                                                        echo '<small>' . date('d/m/Y H:i', strtotime($key['last_used_at'])) . '</small>';
                                                    } else {
                                                        echo '<span class="text-muted">Nunca</span>';
                                                    }
                                                    ?>
                                                </td>
                                                <td>
                                                    <?php 
                                                    if ($key['expires_at']) {
                                                        $expires = strtotime($key['expires_at']);
                                                        $now = time();
                                                        if ($expires < $now) {
                                                            echo '<span class="badge bg-danger">Expirada</span>';
                                                        } else {
                                                            echo '<small>' . date('d/m/Y', $expires) . '</small>';
                                                        }
                                                    } else {
                                                        echo '<span class="badge bg-secondary">Sin expiración</span>';
                                                    }
                                                    ?>
                                                </td>
                                                <td>
                                                    <?php if ($key['is_active']): ?>
                                                        <button onclick="revokeKey(<?php echo $key['id']; ?>)" class="btn btn-sm btn-outline-danger" title="Revocar">
                                                            <i class="fas fa-ban"></i>
                                                        </button>
                                                    <?php else: ?>
                                                        <button onclick="regenerateKey(<?php echo $key['id']; ?>)" class="btn btn-sm btn-outline-primary" title="Regenerar">
                                                            <i class="fas fa-sync"></i>
                                                        </button>
                                                    <?php endif; ?>
                                                    <button onclick="deleteKey(<?php echo $key['id']; ?>)" class="btn btn-sm btn-outline-danger ms-1" title="Eliminar">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <!-- Modal para generar nueva key -->
    <div class="modal fade" id="generateKeyModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-key me-2"></i>Generar Nueva API Key</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="generateKeyForm">
                        <div class="mb-3">
                            <label class="form-label">Nombre de la Key</label>
                            <input type="text" class="form-control" name="key_name" placeholder="Mi aplicación" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Permisos</label>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="permissions[]" value="read" id="perm_read" checked>
                                <label class="form-check-label" for="perm_read">Read (Lectura)</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="permissions[]" value="write" id="perm_write">
                                <label class="form-check-label" for="perm_write">Write (Escritura)</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="permissions[]" value="delete" id="perm_delete">
                                <label class="form-check-label" for="perm_delete">Delete (Eliminación)</label>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Fecha de Expiración (Opcional)</label>
                            <input type="date" class="form-control" name="expires_at">
                            <small class="text-muted">Dejar vacío para sin expiración</small>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="button" onclick="generateNewKey()" class="btn btn-primary">Generar Key</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="assets/js/main.js"></script>
    <script>
        function generateNewKey() {
            alert('Generar nueva API Key');
            // TODO: Implementar generación de API Key
        }
        
        function revokeKey(keyId) {
            if (confirm('¿Revocar esta API Key? Esta acción desactivará permanentemente el acceso.')) {
                alert('Revocar key ID: ' + keyId);
                // TODO: Implementar revocación de key
            }
        }
        
        function regenerateKey(keyId) {
            if (confirm('¿Regenerar esta API Key? Se generará una nueva clave.')) {
                alert('Regenerar key ID: ' + keyId);
                // TODO: Implementar regeneración de key
            }
        }
        
        function deleteKey(keyId) {
            if (confirm('¿Eliminar esta API Key permanentemente?')) {
                alert('Eliminar key ID: ' + keyId);
                // TODO: Implementar eliminación de key
            }
        }
    </script>
</body>
</html>
