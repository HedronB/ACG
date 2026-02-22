<?php
require_once __DIR__ . '/../../app/bootstrap.php';

require_once BASE_PATH . '/app/auth/protect.php';
require_once BASE_PATH . '/app/config/db.php';
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Consultar Información</title>
    <link rel="icon" type="image.png" href="/imagenes/loguito.png">
    <link rel="stylesheet" href="/css/acg.estilos.css">
</head>

<body>

    <header class="header">
        <div class="header-title-group">
            <a href="menu_user.php">
                <img src="/imagenes/logo.png" alt="Logo ACG" class="header-logo">
            </a>
            <a href="menu_user.php">
                <h1>Consultar Información</h1>
            </a>
        </div>

        <a href="menu_user.php" class="back-button">⬅️ Volver</a>
    </header>

    <main class="main-container">
        <div class="menu-grid">

            <a href="/lists/list-maquina.php" class="menu-card">
                <div class="icon">🏭</div>
                <h3>Máquinas</h3>
            </a>

            <a href="/lists/list-molde.php" class="menu-card">
                <div class="icon">📦</div>
                <h3>Moldes</h3>
            </a>

            <a href="/lists/list-resina.php" class="menu-card">
                <div class="icon">💧</div>
                <h3>Resinas</h3>
            </a>

            <a href="/lists/list-pieza.php" class="menu-card">
                <div class="icon">🧩</div>
                <h3>Piezas</h3>
            </a>

            <!-- <a href="../form-hojaResultado.php" class="menu-card">
                <div class="icon">📊</div>
                <h3>Hoja de Resultado</h3>
            </a>

            <a href="../form-hojaProceso.php" class="menu-card">
                <div class="icon">📋</div>
                <h3>Hoja de Proceso</h3>
            </a>

            <a href="proceso.php" class="menu-card disabled">
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