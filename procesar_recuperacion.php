<?php
require "config/db.php";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $correo = trim($_POST["correo"]);

    // 1. Verificar si existe el usuario
    $sql = "SELECT us_id FROM usuarios WHERE us_correo = ?";
    $stmt = $conn->prepare($sql);
    $stmt->execute([$correo]);
    $usuario = $stmt->fetch(PDO::FETCH_ASSOC);

    // ⚠️ Mensaje genérico (buena práctica)
    if (!$usuario) {
        echo "Si el correo está registrado, recibirás una contraseña temporal.";
        exit;
    }

    // 2. Generar contraseña temporal
    $passwordTemporal = generarPassword(8);

    // 3. Hashear la contraseña (compatible con tu login)
    $passwordHash = password_hash($passwordTemporal, PASSWORD_DEFAULT);

    // 4. Guardar nueva contraseña en BD
    $sql = "UPDATE usuarios SET us_password = ? WHERE us_correo = ?";
    $stmt = $conn->prepare($sql);
    $stmt->execute([$passwordHash, $correo]);

    // 5. Enviar correo
    $asunto = "Contraseña temporal - Método ACG";
    $mensaje =
        "Hola,\n\n".
        "Se ha generado una contraseña temporal para tu cuenta:\n\n".
        "Contraseña temporal: $passwordTemporal\n\n".
        "Inicia sesión y cámbiala desde tu perfil.\n\n".
        "Saludos,\nMétodo ACG";

    $headers = "From: no-reply@metodoacg.com";

    mail($correo, $asunto, $mensaje, $headers);

    echo "Si el correo está registrado, recibirás una contraseña temporal.";
}

// 🔐 Generador de contraseña
function generarPassword($longitud = 8) {
    $caracteres = "ABCDEFGHJKLMNPQRSTUVWXYZabcdefghijkmnpqrstuvwxyz23456789";
    return substr(str_shuffle($caracteres), 0, $longitud);
}
