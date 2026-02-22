<?php
require_once __DIR__ . '/../app/bootstrap.php';

require_once BASE_PATH . '/app/auth/protect.php';
require_once BASE_PATH . '/app/config/db.php';

$menu_retorno = "/";

switch ($_SESSION['rol']) {
    case 1:
        $menu_retorno = "/admin/menu_admin.php";
        break;

    case 2:
        $menu_retorno = "/user/menu_user.php";
        break;

    case 3:
        $menu_retorno = "/user/menu_user.php";
        break;

    default:
        $menu_retorno = "/index.php";
}
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registrar Datos</title>
    <link rel="icon" type="image/png" href="/imagenes/loguito.png">
    <link rel="stylesheet" href="/css/acg.estilos.css">
</head>

<body>

    <header class="header">
        <div class="header-title-group">
            <a href="<?= $menu_retorno ?>">
                <img src="/imagenes/logo.png" alt="Logo ACG" class="header-logo">
            </a>
            <a href="<?= $menu_retorno ?>">
                <h1>Registrar Datos</h1>
            </a>
        </div>

        <a href="<?= $menu_retorno ?>" class="back-button">⬅️ Volver</a>
    </header>

    <main class="main-container">
        <div class="title-section">
            <h2>Registro de Información</h2>
            <p>Seleccione el formulario que desea gestionar.</p>
        </div>

        <div class="menu-grid">
            <a href="/forms/form-maquina.php" class="menu-card">
                <div class="icon">🏭</div>
                <h3>Máquinas</h3>
            </a>
            <a href="/forms/form-molde.php" class="menu-card">
                <div class="icon">📦</div>
                <h3>Moldes</h3>
            </a>
            <a href="/forms/form-resina.php" class="menu-card">
                <div class="icon">💧</div>
                <h3>Resinas</h3>
            </a>
            <a href="/forms/form-pieza.php" class="menu-card">
                <div class="icon">🧩</div>
                <h3>Piezas</h3>
            </a>
            <!-- <a href="/forms/form-proceso.php" class="menu-card disabled">
                <div class="icon">⚙️</div>
                <h3>Ingeniería de Proceso</h3>
            </a> -->
        </div>
    </main>

    <footer>
        <p>Método ACG</p>
    </footer>

</body>

</html>