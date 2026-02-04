<?php
require_once __DIR__ . '/../app/bootstrap.php';

require_once BASE_PATH . '/app/auth/protect.php';
require_once BASE_PATH . '/app/config/db.php';
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Menú Principal</title>
    <link rel="icon" type="image/png" href="../imagenes/loguito.png">
    <link rel="stylesheet" href="../css/acg.estilos.css">
    <style>
        .header {
            justify-content: space-between;
        }
    </style>
</head>

<body>

    <header class="header">
        <div class="header-title-group">
            <a href="menu_user.php">
                <img src="../imagenes/logo.png" alt="Logo ACG" class="header-logo">
            </a>
            <a href="menu_user.php">
                <h1>Menú Reportes</h1>
            </a>
        </div>    
        <a href="menu_user.php" class="back-button">⬅️ Volver</a>
    </header>

    <main class="main-container">
        <div class="menu-grid">

            <a href="../registros-cambios.php" class="menu-card">
                <div class="icon">📝</div>
                <h3>Registro de Cambios</h3>
            </a>
            
            <a href="#" class="menu-card">
                <div class="icon">📈</div>
                <h3>Indicadores</h3>
            </a>

        </div>
    </main>

    <footer>
        <p>Método ACG</p>
    </footer>

</body>

</html>