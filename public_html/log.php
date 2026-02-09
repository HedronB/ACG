<?php
require_once __DIR__ . '/../app/bootstrap.php';

require_once BASE_PATH . '/app/config/db.php';


if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $correo = trim($_POST["correo"]);
    $pass   = trim($_POST["pswrd"]);

    $sql = "SELECT us_id, us_nombre, us_password, us_rol, us_empresa
            FROM usuarios
            WHERE us_correo = ?";

    $stmt = $conn->prepare($sql);
    $stmt->execute([$correo]);
    $u = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($u) {

        $passwordIsValid =
            password_verify($pass, $u["us_password"]) ||
            ($u["us_password"] === $pass);

        if ($passwordIsValid) {

            $_SESSION["id"]      = $u["us_id"];
            $_SESSION["nombre"]  = $u["us_nombre"];
            $_SESSION["rol"]     = $u["us_rol"];
            $_SESSION["empresa"] = $u["us_empresa"];

            switch ($u["us_rol"]) {
                case 1:
                    header("Location: admin/menu_admin.php");
                    exit();

                case 2:
                case 3:
                    header("Location: user/menu_user.php");
                    exit();

                case 0:
                default:
                    header("Location: index.php?error=Usuario inactivo");
                    exit();
            }
        }
    }

    $error = "Usuario o contraseña incorrectos";
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Iniciar Sesión</title>
    <link rel="icon" type="image/png" href="/imagenes/loguito.png">
    <link rel="stylesheet" href="/css/acg.estilos.css">
</head>
<body>

    <header class="header">
        <img src="/imagenes/logo.png" alt="Logo ACG" class="header-logo">
        <h1>Iniciar Sesión</h1>
    </header>

    <main class="main-container">

        <div class="form-section">

            <form action="log.php" method="POST" class="input-form">

                <label>Correo Electrónico</label>
                <input type="email" name="correo" placeholder="Correo electrónico" required>

                <label>Contraseña</label>
                <input type="password" name="pswrd" placeholder="Contraseña" required>

                <button class="btn btn-success" type="submit">Entrar</button>
            </form>

        </div>

    </main>

    <footer>
        <p>Método ACG</p>
    </footer>

</body>
</html>