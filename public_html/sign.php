<?php
require_once __DIR__ . '/../app/bootstrap.php';
require_once BASE_PATH . '/app/config/db.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre  = trim($_POST['nombre']  ?? '');
    $correo  = trim($_POST['correo']  ?? '');
    $pswrd   =      $_POST['pswrd']   ?? '';

    if (!$nombre || !$correo || !$pswrd) {
        $error = 'Todos los campos son obligatorios.';
    } elseif (!filter_var($correo, FILTER_VALIDATE_EMAIL)) {
        $error = 'El correo no tiene un formato válido.';
    } else {
        $pass = password_hash($pswrd, PASSWORD_DEFAULT);
        try {
            $stmt = $conn->prepare(
                'INSERT INTO usuarios (us_nombre, us_correo, us_password, us_rol, us_empresa, us_planta)
                 VALUES (?, ?, ?, 0, NULL, NULL)'
            );
            $stmt->execute([$nombre, $correo, $pass]);
            header('Location: log.php?success=ok');
            exit();
        } catch (PDOException $e) {
            if ($e->getCode() === '23000') {
                $error = 'Ese correo ya está registrado. ¿Quieres <a href="log.php">iniciar sesión</a>?';
            } else {
                $error = 'Error al registrar. Por favor intenta más tarde.';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registrarse — ACG</title>
    <link rel="icon" type="image/png" href="/imagenes/loguito.png">
    <link rel="stylesheet" href="/css/acg.estilos.css">
</head>
<body>
<header class="header">
    <img src="/imagenes/logo.png" alt="Logo ACG" class="header-logo">
    <h1>Registrarse</h1>
</header>
<main class="main-container">
    <div class="form-section">
        <?php if ($error): ?>
        <div class="mensaje error" style="display:block;margin-bottom:14px;"><?= $error ?></div>
        <?php endif; ?>
        <form method="POST" class="input-form">
            <label>Nombre Completo</label>
            <input type="text" name="nombre" value="<?= htmlspecialchars($_POST['nombre'] ?? '') ?>" required>

            <label>Correo Electrónico</label>
            <input type="email" name="correo" value="<?= htmlspecialchars($_POST['correo'] ?? '') ?>" required>

            <label>Contraseña</label>
            <input type="password" name="pswrd" required>

            <p style="font-size:.82em;color:#888;margin:4px 0 14px;">
                Tu cuenta quedará inactiva hasta que un administrador te asigne un rol y empresa.
            </p>

            <button class="btn btn-guardar" type="submit">Registrarse</button>
        </form>
        <p style="text-align:center;margin-top:16px;font-size:.88em;">
            ¿Ya tienes cuenta? <a href="log.php">Iniciar sesión</a>
        </p>
    </div>
</main>
<footer><p>Método ACG</p></footer>
</body>
</html>
