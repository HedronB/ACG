<?php
// Datos de sesión para el sidebar
$_sb_nombre = $_SESSION['nombre'] ?? 'Usuario';
$_sb_rol    = (int)($_SESSION['rol'] ?? 0);
$_sb_roles  = [0=>'Inactivo',1=>'Administrador',2=>'Gerente',3=>'Empleado'];
$_sb_rol_nombre = $_sb_roles[$_sb_rol] ?? 'Desconocido';
$_sb_menu   = match($_sb_rol) {
    1 => '/admin/menu_admin.php',
    2,3 => '/user/menu_user.php',
    default => '/index.php'
};
?>
<!-- Overlay -->
<div class="sidebar-overlay" id="sidebarOverlay" onclick="cerrarSidebar()"></div>

<!-- Sidebar -->
<aside class="sidebar" id="sidebar">
    <div class="sidebar-header">
        <h2>Menú</h2>
        <button class="sidebar-close" onclick="cerrarSidebar()" aria-label="Cerrar">✕</button>
    </div>
    <div class="sidebar-user">
        <div class="sidebar-user-name">👤 <?= htmlspecialchars($_sb_nombre) ?></div>
        <div class="sidebar-user-role"><?= htmlspecialchars($_sb_rol_nombre) ?></div>
    </div>
    <nav class="sidebar-nav">
        <a href="<?= $_sb_menu ?>"><span class="nav-icon">🏠</span> Menú principal</a>
        <a href="/perfil.php"><span class="nav-icon">⚙️</span> Mi perfil</a>
        <a href="/reportes/registros-cambios.php"><span class="nav-icon">📋</span> Registros de cambios</a>
        <div class="nav-divider"></div>
        <a href="/logout.php" class="nav-logout"><span class="nav-icon">🚪</span> Cerrar sesión</a>
    </nav>
</aside>

<script>
function abrirSidebar() {
    document.getElementById('sidebar').classList.add('open');
    document.getElementById('sidebarOverlay').classList.add('open');
    document.getElementById('burgerBtn').classList.add('open');
}
function cerrarSidebar() {
    document.getElementById('sidebar').classList.remove('open');
    document.getElementById('sidebarOverlay').classList.remove('open');
    document.getElementById('burgerBtn').classList.remove('open');
}
document.addEventListener('keydown', e => { if (e.key === 'Escape') cerrarSidebar(); });
</script>
