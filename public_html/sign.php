<?php
require_once __DIR__ . '/../app/bootstrap.php';

require_once BASE_PATH . '/app/config/db.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $nombre  = trim($_POST["nombre"]);
    $correo  = trim($_POST["correo"]);
    $pass    = password_hash($_POST["pswrd"], PASSWORD_DEFAULT);
    $empresa = isset($_POST["empresa"]) ? intval($_POST["empresa"]) : null;

    $sql = "INSERT INTO usuarios (us_nombre, us_correo, us_password, us_rol, us_empresa)
            VALUES (?, ?, ?, ?, ?)";

    $stmt = $conn->prepare($sql);

    if ($stmt->execute([$nombre, $correo, $pass, 0, $empresa])) {
        header("Location: log.php?success=ok");
        exit();
    }

    $error = "No se pudo registrar. Es posible que el correo ya esté registrado.";
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registrarse</title>
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

            <form method="POST" class="input-form">

                <label>Nombre Completo</label>
                <input type="text" name="nombre" placeholder="Nombre Completo" required>

                <label>Correo Electrónico</label>
                <input type="email" name="correo" placeholder="Correo electrónico" required>

                <label>Contraseña</label>
                <input type="password" name="pswrd" placeholder="Contraseña" required>

                <button class="btn btn-success" type="submit">Registrarse</button>
            </form>

        </div>

    </main>

    <footer>
        <p>Método ACG</p>
    </footer>

</body>
</html>