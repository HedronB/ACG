<?php
require_once __DIR__ . '/../../app/bootstrap.php';
require_once BASE_PATH . '/app/auth/protect.php';
require_once BASE_PATH . '/app/config/db.php';
require_once BASE_PATH . '/app/helpers/LayoutHelper.php';

$rol       = (int)$_SESSION['rol'];
$empresaId = (int)($_SESSION['empresa'] ?? 0);

// Solo admin y gerente
if ($rol !== 1 && $rol !== 2) {
    header('Location: /index.php'); exit();
}

$mensaje = '';
$tipoMsg = '';

// Procesar subida
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['logo'])) {
    $file    = $_FILES['logo'];
    $empId   = ($rol === 1) ? (int)($_POST['empresa_id'] ?? $empresaId) : $empresaId;

    $allowed = ['image/png','image/jpeg','image/jpg','image/gif','image/webp','image/svg+xml'];
    $maxSize = 2 * 1024 * 1024; // 2MB

    if ($file['error'] !== UPLOAD_ERR_OK) {
        $mensaje = 'Error al subir el archivo.'; $tipoMsg = 'error';
    } elseif (!in_array($file['type'], $allowed)) {
        $mensaje = 'Solo se permiten imágenes (PNG, JPG, GIF, WEBP, SVG).'; $tipoMsg = 'error';
    } elseif ($file['size'] > $maxSize) {
        $mensaje = 'El archivo no puede superar 2MB.'; $tipoMsg = 'error';
    } else {
        $ext     = pathinfo($file['name'], PATHINFO_EXTENSION);
        $nombre  = 'empresa_' . $empId . '.' . strtolower($ext);
        $destDir = BASE_PATH . '/public_html/imagenes/logos/';
        $dest    = $destDir . $nombre;

        if (!is_dir($destDir)) mkdir($destDir, 0755, true);

        // Eliminar logo anterior de cualquier extensión
        foreach (glob($destDir . 'empresa_' . $empId . '.*') as $old) unlink($old);

        if (move_uploaded_file($file['tmp_name'], $dest)) {
            $ruta = '/imagenes/logos/' . $nombre;
            $stmt = $conn->prepare("UPDATE empresas SET em_logo = ? WHERE em_id = ?");
            $stmt->execute([$ruta, $empId]);
            $mensaje = '✅ Logo actualizado correctamente.'; $tipoMsg = 'exito';
        } else {
            $mensaje = 'No se pudo guardar el archivo.'; $tipoMsg = 'error';
        }
    }
}

// Eliminar logo
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['eliminar_logo'])) {
    $empId = ($rol === 1) ? (int)($_POST['empresa_id'] ?? $empresaId) : $empresaId;
    $stmt  = $conn->prepare("SELECT em_logo FROM empresas WHERE em_id = ?");
    $stmt->execute([$empId]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($row && $row['em_logo']) {
        $file = BASE_PATH . '/public_html' . $row['em_logo'];
        if (file_exists($file)) unlink($file);
        $conn->prepare("UPDATE empresas SET em_logo = NULL WHERE em_id = ?")->execute([$empId]);
    }
    $mensaje = 'Logo eliminado.'; $tipoMsg = 'exito';
}

// Cargar empresas (admin ve todas, gerente solo la suya)
if ($rol === 1) {
    $empresas = $conn->query("SELECT em_id, em_nombre, em_logo FROM empresas ORDER BY em_nombre")->fetchAll(PDO::FETCH_ASSOC);
} else {
    $stmt = $conn->prepare("SELECT em_id, em_nombre, em_logo FROM empresas WHERE em_id = ?");
    $stmt->execute([$empresaId]);
    $empresas = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

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
    <title>Logo de Empresa</title>
    <link rel="icon" type="image/png" href="/imagenes/loguito.png">
    <link rel="stylesheet" href="/css/acg.estilos.css">
    <style>
        .logo-card { background:#fff; border-radius:8px; padding:24px; box-shadow:0 1px 4px rgba(0,0,0,0.09); margin-bottom:20px; }
        .logo-preview { width:180px; height:80px; object-fit:contain; border:1px solid #e5e7eb; border-radius:6px; padding:8px; background:#f8faff; }
        .logo-placeholder { width:180px; height:80px; border:2px dashed #d1d5db; border-radius:6px; display:flex; align-items:center; justify-content:center; color:#aaa; font-size:0.85em; }
        .logo-actions { display:flex; gap:10px; align-items:center; flex-wrap:wrap; margin-top:12px; }
        .empresa-nombre { font-size:1.05em; font-weight:700; color:#1e3a8a; margin-bottom:12px; }
        input[type="file"] { padding:6px; border:1px solid #d1d5db; border-radius:4px; font-size:0.9em; }
    </style>
</head>
<body>

<header class="header">
    <div class="header-title-group">
        <a href="<?= $menu_principal ?>"><img src="/imagenes/logo.png" alt="Logo" class="header-logo"></a>
        <h1>Logo de Empresa</h1>
    </div>
    <div class="header-right">
        <a href="<?= $menu_retorno ?>" class="back-button">⬅️ Volver</a>
        <?= burgerBtn() ?>
    </div>
</header>

<main class="main-container">

    <?php if ($mensaje): ?>
        <div class="mensaje-<?= $tipoMsg ?>" style="margin-bottom:16px;"><?= htmlspecialchars($mensaje) ?></div>
    <?php endif; ?>

    <?php foreach ($empresas as $emp): ?>
    <div class="logo-card">
        <div class="empresa-nombre">🏭 <?= htmlspecialchars($emp['em_nombre']) ?></div>

        <?php if ($emp['em_logo']): ?>
            <img src="<?= htmlspecialchars($emp['em_logo']).'?t='.time() ?>" alt="Logo actual" class="logo-preview">
            <p style="font-size:0.8em; color:#666; margin:6px 0 0;">Logo actual</p>
        <?php else: ?>
            <div class="logo-placeholder">Sin logo</div>
        <?php endif; ?>

        <form method="POST" enctype="multipart/form-data">
            <input type="hidden" name="empresa_id" value="<?= $emp['em_id'] ?>">
            <div class="logo-actions">
                <input type="file" name="logo" accept="image/*" required>
                <button type="submit" class="btn btn-guardar">💾 Subir logo</button>
                <?php if ($emp['em_logo']): ?>
                <button type="submit" name="eliminar_logo" value="1"
                    class="btn btn-danger"
                    onclick="return confirm('¿Eliminar el logo de esta empresa?')">
                    ✖️ Eliminar logo
                </button>
                <?php endif; ?>
            </div>
            <p style="font-size:0.78em; color:#999; margin-top:8px;">PNG, JPG, SVG o WEBP · Máx. 2MB · Recomendado: fondo transparente</p>
        </form>
    </div>
    <?php endforeach; ?>

</main>

<footer><p>Método ACG</p></footer>

<?php includeSidebar(); ?>
</body>
</html>
