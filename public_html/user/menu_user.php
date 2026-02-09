<?php
require_once __DIR__ . '/../../app/bootstrap.php';

require_once BASE_PATH . '/app/auth/protect.php';
require_once BASE_PATH . '/app/config/db.php';

$nombre = $_SESSION['nombre'];
$rol = $_SESSION['rol'];
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
        <a href="menu_user.php">
            <img src="/imagenes/logo.png" alt="Logo ACG" class="header-logo">
        </a>
        <a href="menu_user.php">
            <h1>Menú Principal</h1>
        </a>
    </header>

    <main class="main-container">
        <div class="menu-grid">

            <a href="/registros.php" class="menu-card">
                <div class="icon">📝</div>
                <h3>Capturar Información</h3>
            </a>

            <a href="menu_info_user.php" class="menu-card">
                <div class="icon">🔍</div>
                <h3>Consultar Información</h3>
            </a>

            <?php if ($rol != 3): ?>
            <a href="menu_reportes_user.php" class="menu-card">
                <div class="icon">📊</div>
                <h3>Reportes</h3>
            </a>
            <?php endif; ?>
            

            <a href="/perfil.php" class="menu-card">
                <div class="icon">👤</div>
                <h3>Mi Perfil</h3>
            </a>

        </div>
    </main>

    <footer>
        <p>Método ACG</p>
    </footer>

</body>

</html>