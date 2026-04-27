<?php
require_once __DIR__ . '/../app/bootstrap.php';
require_once BASE_PATH . '/app/auth/protect.php';
require_once BASE_PATH . '/app/config/db.php';
require_once BASE_PATH . '/app/helpers/LayoutHelper.php';

$rol = (int)$_SESSION['rol'];
$menu_retorno  = '/catalogos.php';
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
    <title>Consultar Información</title>
    <link rel="icon" type="image/png" href="/imagenes/loguito.png">
    <link rel="stylesheet" href="/css/acg.estilos.css">
</head>
<body>
    <header class="header">
        <div class="header-title-group">
            <a href="<?= $menu_principal ?>"><img src="/imagenes/logo.png" alt="Logo ACG" class="header-logo"></a>
            <h1>Consultar Información</h1>
        </div>
        <div class="header-right">
        <a href="<?= $menu_retorno ?>" class="back-button">⬅️ Volver</a>
        <?= burgerBtn() ?>
    </div>
    </header>    

    <main class="main-container">
        <div class="menu-grid">

            <a href="/lists/list-maquina.php" class="menu-card menu-card-maquina">
                <div class="icon">🏭</div>
                <h3>Máquinas</h3>
            </a>

            <a href="/lists/list-molde.php" class="menu-card menu-card-molde">
                <div class="icon">📦</div>
                <h3>Moldes</h3>
            </a>

            <a href="/lists/list-resina.php" class="menu-card menu-card-resina">
                <div class="icon">💧</div>
                <h3>Resinas</h3>
            </a>

            <a href="/lists/list-pieza.php" class="menu-card menu-card-pieza">
                <div class="icon">🧩</div>
                <h3>Piezas</h3>
            </a>

        </div>
    </main>

    <footer>
        <p>Método ACG</p>
    </footer>
<?php includeSidebar(); ?>
</body>
</html>
