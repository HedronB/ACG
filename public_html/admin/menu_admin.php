<?php
require_once __DIR__ . '/../../app/bootstrap.php';
require_once BASE_PATH . '/app/auth/protect.php';
require_once BASE_PATH . '/app/config/db.php';
require_once BASE_PATH . '/app/helpers/LayoutHelper.php';

$rol    = (int)$_SESSION['rol'];
$nombre = $_SESSION['nombre'] ?? '';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Menú Principal</title>
    <link rel="icon" type="image/png" href="/imagenes/loguito.png">
    <link rel="stylesheet" href="/css/acg.estilos.css">
</head>
<body>

<header class="header">
    <a href="/admin/menu_admin.php"><img src="/imagenes/logo.png" alt="Logo ACG" class="header-logo"></a>
    <h1>Menú Principal</h1>
    <div class="header-right">
        <?= burgerBtn() ?>
    </div>
</header>

<main class="main-container">
    <div class="menu-grid">

        <a href="/ingenieria/procesos.php" class="menu-card menu-card-proceso">
            <div class="icon">⚙️</div>
            <h3>Ingeniería de Proceso</h3>
        </a>

        <a href="/ingenieria/calificador-proceso.php" class="menu-card menu-card-calificador">
            <div class="icon">🏆</div>
            <h3>Calificador de Proceso</h3>
        </a>

        <a href="/forms/form-hojaResultado.php" class="menu-card menu-card-resultado">
            <div class="icon">📊</div>
            <h3>Hoja de Resultado</h3>
        </a>

        <a href="/reportes/registros-cambios.php" class="menu-card menu-card-cambios">
            <div class="icon">🕓</div>
            <h3>Registros de Cambios</h3>
        </a>

        <a href="/catalogos.php" class="menu-card menu-card-catalogos">
            <div class="icon">📁</div>
            <h3>Maestros</h3>
        </a>

        <a href="/perfil.php" class="menu-card menu-card-perfil">
            <div class="icon">👤</div>
            <h3>Mi Perfil</h3>
        </a>

        <?php if ($rol === 1 || $rol === 2): ?>
        <a href="/admin/manage_users.php" class="menu-card menu-card-usuarios">
            <div class="icon">👥</div>
            <h3>Administrar Usuarios</h3>
        </a>
        <?php endif; ?>

        <?php if ($rol === 1): ?>
        <a href="/admin/manage_empresas.php" class="menu-card menu-card-catalogos">
            <div class="icon">🏭</div>
            <h3>Empresas y Plantas</h3>
        </a>
        <?php endif; ?>

    </div>
</main>

<footer><p>Método ACG</p></footer>

<?php includeSidebar(); ?>
</body>
</html>
