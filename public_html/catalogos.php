<?php
require_once __DIR__ . '/../app/bootstrap.php';
require_once BASE_PATH . '/app/auth/protect.php';
require_once BASE_PATH . '/app/helpers/LayoutHelper.php';

$rol = (int)$_SESSION['rol'];
$menu_retorno  = match($rol) {
    1 => '/admin/menu_admin.php',
    2,3 => '/user/menu_user.php',
    default => '/index.php'
};
$menu_principal = match($rol) {
    1 => '/admin/menu_admin.php',
    2,3 => '/user/menu_user.php',
    default => '/index.php'
};
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Catálogos</title>
    <link rel="icon" type="image/png" href="/imagenes/loguito.png">
    <link rel="stylesheet" href="/css/acg.estilos.css">
</head>
<body>

<header class="header">
    <div class="header-title-group">
    <a href="<?= $menu_principal ?>"><img src="/imagenes/logo.png" alt="Logo" class="header-logo"></a>
        <h1>Catálogos</h1>
    </div>
    <div class="header-right">
        <a href="<?= $menu_retorno ?>" class="back-button">⬅️ Volver</a>
        <?= burgerBtn() ?>
    </div>
</header>

<main class="main-container">
    <div class="menu-grid">

        <a href="/registros.php" class="menu-card menu-card-captura">
            <div class="icon">📝</div>
            <h3>Capturar Información</h3>
        </a>

        <a href="/menu_info.php" class="menu-card menu-card-consulta">
            <div class="icon">🔍</div>
            <h3>Consultar Información</h3>
        </a>

        <?php if ($rol === 1): ?>
        <a href="/admin/manage_empresas.php" class="menu-card menu-card-empresa">
            <div class="icon">🏭</div>
            <h3>Empresas y Plantas</h3>
        </a>
        <?php endif; ?>

        <?php if ($rol === 1 || $rol === 2): ?>
        <a href="/admin/logo-empresa.php" class="menu-card menu-card-logo">
            <div class="icon">🖼️</div>
            <h3>Logo de Empresa</h3>
        </a>
        <?php endif; ?>

    </div>
</main>

<footer><p>Método ACG</p></footer>

<?php includeSidebar(); ?>
</body>
</html>
