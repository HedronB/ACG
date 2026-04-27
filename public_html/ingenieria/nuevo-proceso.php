<?php
require_once __DIR__ . '/../../app/bootstrap.php';
require_once BASE_PATH . '/app/auth/protect.php';
require_once BASE_PATH . '/app/config/db.php';
require_once BASE_PATH . '/app/helpers/LayoutHelper.php';

$rol       = (int)$_SESSION['rol'];
$empresaId = (int)($_SESSION['empresa'] ?? 0);
$plantaId  = isset($_SESSION['planta']) && $_SESSION['planta'] !== '' ? (int)$_SESSION['planta'] : null;

$menu_retorno = match($rol) {
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
    <title>Ingeniería de Proceso</title>
    <link rel="icon" type="image/png" href="/imagenes/loguito.png">
    <link rel="stylesheet" href="/css/acg.estilos.css">
    <style>
        .proceso-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));
            gap: 24px;
            margin-top: 20px;
        }
        .proceso-card {
            background: #fff;
            border-radius: 12px;
            padding: 32px 24px;
            text-align: center;
            text-decoration: none;
            color: #333;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
            border-top: 4px solid #0056b3;
            transition: transform 0.2s, box-shadow 0.2s;
        }
        .proceso-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 6px 20px rgba(0,0,0,0.13);
        }
        .proceso-card.corregir { border-top-color: #f39c12; }
        .proceso-card.ver      { border-top-color: #7c3aed; }
        .proceso-card .icon    { font-size: 2.8em; margin-bottom: 16px; }
        .proceso-card h3       { font-size: 1.15em; margin: 0 0 8px 0; color: #1e3a8a; }
        .proceso-card p        { font-size: 0.85em; color: #666; margin: 0; }
    </style>
</head>
<body>

<header class="header">
    <div class="header-title-group">
        <a href="<?= $menu_retorno ?>"><img src="/imagenes/logo.png" alt="Logo ACG" class="header-logo"></a>
        <h1>Ingeniería de Proceso</h1>
    </div>
    <div class="header-right">
        <a href="<?= $menu_retorno ?>" class="back-button">⬅️ Volver</a>
        <?= burgerBtn() ?>
    </div>
</header>

<main class="main-container">
    <div class="proceso-grid">

        <a href="/ingenieria/seleccionar-maquina.php?modo=nuevo" class="proceso-card">
            <div class="icon">🆕</div>
            <h3>Nuevo Proceso</h3>
            <p>Iniciar una nueva hoja de proceso para una máquina</p>
        </a>

        <a href="/ingenieria/seleccionar-maquina.php?modo=corregir" class="proceso-card corregir">
            <div class="icon">✏️</div>
            <h3>Corregir Proceso</h3>
            <p>Modificar una hoja de proceso existente</p>
        </a>

        <a href="/ingenieria/seleccionar-maquina.php?modo=ver" class="proceso-card ver">
            <div class="icon">👁️</div>
            <h3>Ver Proceso Anterior</h3>
            <p>Consultar una hoja de proceso en modo lectura</p>
        </a>

        <a href="/ingenieria/calificador.php" class="proceso-card" style="border-top-color:#059669;">
            <div class="icon">📊</div>
            <h3>Calificador de Proceso</h3>
            <p>Cálculos de ingeniería: disparo, husillo, tiempos y caudales</p>
        </a>

    </div>
</main>

<footer><p>Método ACG</p></footer>

<?php includeSidebar(); ?>
</body>
</html>
