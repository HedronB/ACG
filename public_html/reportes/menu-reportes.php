<?php
require_once __DIR__ . '/../../app/bootstrap.php';
require_once BASE_PATH . '/app/auth/protect.php';
require_once BASE_PATH . '/app/helpers/LayoutHelper.php';

$rol = (int)$_SESSION['rol'];
$menu_retorno = $rol === 1 ? '/admin/menu_admin.php' : '/user/menu_user.php';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reportes</title>
    <link rel="icon" type="image/png" href="/imagenes/loguito.png">
    <link rel="stylesheet" href="/css/acg.estilos.css">
</head>
<body>
    <header class="header">
        <div class="header-title-group">
            <a href="<?= $menu_retorno ?>"><img src="/imagenes/logo.png" alt="Logo ACG" class="header-logo"></a>
            <h1>Reportes</h1>
        </div>
        <div class="header-right">
        <a href="<?= $menu_retorno ?>" class="back-button">⬅️ Volver</a>
        <?= burgerBtn() ?>
    </div>
    </header>

    <main class="main-container">
        <div class="menu-grid">

            <a href="/forms/form-hojaProceso.php" class="menu-card">
                <div class="icon">⚙️</div>
                <h3>Hoja de Proceso</h3>
            </a>

            <a href="/forms/form-hojaResultado.php" class="menu-card">
                <div class="icon">📋</div>
                <h3>Hoja de Resultado</h3>
            </a>

            <a href="/reportes/registros-cambios.php" class="menu-card">
                <div class="icon">🕓</div>
                <h3>Registro de Cambios</h3>
            </a>

        </div>
    </main>

    <footer><p>Método ACG</p></footer>
<?php includeSidebar(); ?>
</body>
</html>
