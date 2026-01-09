<nav id="sidebarMenu" class="col-md-3 col-lg-2 d-md-block bg-light sidebar collapse">
    <div class="position-sticky pt-3">
        <ul class="nav flex-column">
            <li class="nav-item">
                <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'index.php' ? 'active' : ''; ?>" href="index.php">
                    <i class="fas fa-home me-2"></i>Dashboard
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'services.php' ? 'active' : ''; ?>" href="services.php">
                    <i class="fas fa-server me-2"></i>Servicios
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'yaml-generator.php' ? 'active' : ''; ?>" href="yaml-generator.php">
                    <i class="fas fa-file-code me-2"></i>Generador YAML
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="notifications.php">
                    <i class="fas fa-bell me-2"></i>Notificaciones
                </a>
            </li>
            <?php if (isset($role) && $role == 'admin'): ?>
            <li class="nav-item">
                <a class="nav-link" href="users.php">
                    <i class="fas fa-users me-2"></i>Usuarios
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="settings.php">
                    <i class="fas fa-cog me-2"></i>Configuración
                </a>
            </li>
            <?php endif; ?>
        </ul>

        <h6 class="sidebar-heading d-flex justify-content-between align-items-center px-3 mt-4 mb-1 text-muted">
            <span>Herramientas</span>
        </h6>
        <ul class="nav flex-column mb-2">
            <li class="nav-item">
                <a class="nav-link" href="audit-logs.php">
                    <i class="fas fa-history me-2"></i>Logs de Auditoría
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="api-keys.php">
                    <i class="fas fa-key me-2"></i>API Keys
                </a>
            </li>
        </ul>
    </div>
</nav>
