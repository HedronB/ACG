<?php
require_once __DIR__ . '/../app/bootstrap.php';
require_once BASE_PATH . '/app/config/db.php';

$mensaje = '';
$tipo    = '';
$paso    = 'solicitar'; // solicitar | enviado

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['correo'])) {

    $correo = trim(strtolower($_POST['correo']));

    // Buscar usuario activo con ese correo
    $stmt = $conn->prepare(
        "SELECT us_id, us_nombre FROM usuarios WHERE us_correo = ? AND us_rol > 0"
    );
    $stmt->execute([$correo]);
    $usuario = $stmt->fetch(PDO::FETCH_ASSOC);

    // Siempre mostrar el mismo mensaje por seguridad (no confirmar si existe el correo)
    $paso = 'enviado';

    if ($usuario) {
        // Generar contraseña temporal: 10 caracteres alfanuméricos
        $chars    = 'ABCDEFGHJKLMNPQRSTUVWXYZabcdefghjkmnpqrstuvwxyz23456789';
        $temporal = '';
        for ($i = 0; $i < 10; $i++) {
            $temporal .= $chars[random_int(0, strlen($chars) - 1)];
        }

        $hash = password_hash($temporal, PASSWORD_DEFAULT);

        // Guardar en BD
        $upd = $conn->prepare(
            "UPDATE usuarios SET us_password = ? WHERE us_id = ?"
        );
        $upd->execute([$hash, $usuario['us_id']]);

        // Enviar correo
        $nombre  = $usuario['us_nombre'];
        $asunto  = "=?UTF-8?B?" . base64_encode("Recuperación de contraseña – Método ACG") . "?=";
        $cuerpo  = "Hola {$nombre},\r\n\r\n";
        $cuerpo .= "Recibimos una solicitud para restablecer tu contraseña en el sistema Método ACG.\r\n\r\n";
        $cuerpo .= "Tu contraseña temporal es:\r\n\r\n";
        $cuerpo .= "    {$temporal}\r\n\r\n";
        $cuerpo .= "Ingresa con esta contraseña y cámbiala desde tu perfil.\r\n\r\n";
        $cuerpo .= "Si no solicitaste este cambio, puedes ignorar este mensaje.\r\n\r\n";
        $cuerpo .= "— Método ACG";

        $headers  = "From: noreply@metodoacg.com\r\n";
        $headers .= "Reply-To: noreply@metodoacg.com\r\n";
        $headers .= "Content-Type: text/plain; charset=UTF-8\r\n";
        $headers .= "Content-Transfer-Encoding: base64\r\n";

        mail($correo, $asunto, base64_encode($cuerpo), $headers);
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recuperar Contraseña</title>
    <link rel="icon" type="image/png" href="/imagenes/loguito.png">
    <link rel="stylesheet" href="/css/acg.estilos.css">
</head>
<body>

    <header class="header">
        <img src="/imagenes/logo.png" alt="Logo ACG" class="header-logo">
        <h1>Recuperar Contraseña</h1>
    </header>

    <main class="main-container">
        <div class="form-section">

            <?php if ($paso === 'solicitar'): ?>

                <p style="margin-bottom:18px; color:#555; font-size:0.95em;">
                    Ingresa tu correo electrónico y te enviaremos una contraseña temporal.
                </p>

                <form method="POST" class="input-form">
                    <label>Correo Electrónico</label>
                    <input
                        type="email"
                        name="correo"
                        placeholder="tu@correo.com"
                        required
                        autofocus
                    >
                    <button class="btn btn-primary" type="submit">
                        Enviar contraseña temporal
                    </button>
                </form>

            <?php else: ?>

                <div class="mensaje-exito" style="display:block; margin-bottom:20px;">
                    Si ese correo está registrado en el sistema, recibirás un mensaje
                    con tu contraseña temporal en unos minutos.
                    <br><br>
                    Revisa también tu carpeta de spam.
                </div>

            <?php endif; ?>

            <div style="margin-top:16px; text-align:center;">
                <a href="/log.php" style="font-size:0.9em; color:#0056b3;">
                    ← Volver al inicio de sesión
                </a>
            </div>

        </div>
    </main>

    <footer>
        <p>Método ACG</p>
    </footer>

</body>
</html>
