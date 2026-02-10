<?php
require_once __DIR__ . '/../app/bootstrap.php';

require_once BASE_PATH . '/app/auth/protect.php';
require_once BASE_PATH . '/app/config/db.php';

$menu_retorno = "/";

switch ($_SESSION['rol']) {
    case 1:
        $menu_retorno = "/admin/menu_admin.php";
        break;

    case 2:
    case 3:
        $menu_retorno = "/user/menu_user.php";
        break;

    default:
        $menu_retorno = "/index.php";
}

if (!isset($_SESSION["id"])) {
    header("Location: index.php?error=Debe iniciar sesión");
    exit();
}

$nombreSesion = $_SESSION["nombre"]  ?? "";
$correoSesion = $_SESSION["correo"]  ?? "";

$pswrdPlaceholder = "********";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $nuevoNombre = trim($_POST["nombre"]);
    $nuevoCorreo = trim($_POST["correo"]);
    $nuevaPass   = trim($_POST["pswrd"]);

    $set    = "us_nombre = ?, us_correo = ?";
    $params = [$nuevoNombre, $nuevoCorreo];

    if ($nuevaPass !== "" && $nuevaPass !== $pswrdPlaceholder) {
        $hash = password_hash($nuevaPass, PASSWORD_DEFAULT);
        $set .= ", us_password = ?";
        $params[] = $hash;
    }

    $sql = "UPDATE usuarios SET $set WHERE us_id = ?";
    $params[] = $_SESSION["id"];

    $stmt = $conn->prepare($sql);

    try {
        if ($stmt->execute($params)) {

            $_SESSION["nombre"] = $nuevoNombre;
            $_SESSION["correo"] = $nuevoCorreo;

            echo "<script>alert('Perfil actualizado correctamente'); window.location='/perfil.php';</script>";
            exit();
        } else {
            echo "<script>alert('Error al actualizar el perfil');</script>";
        }
    } catch (PDOException $e) {
        echo "<script>alert('Error al actualizar el perfil: " . htmlspecialchars($e->getMessage()) . "');</script>";
    }
}
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mi Perfil</title>
    <link rel="icon" type="image/png" href="/imagenes/loguito.png">
    <link rel="stylesheet" href="/css/acg.estilos.css">
    <style>
        .header {
            justify-content: space-between;
        }
        .edit-profile {
            margin-right: 15px;
            color: #1e3a8a;
            font-weight: 600;
        }
    </style>
</head>

<body>

    <header class="header">
        <div class="header-title-group">
            <a href="<?= $menu_retorno ?>">
                <img src="/imagenes/logo.png" alt="Logo ACG" class="header-logo">
            </a>
            <a href="<?= $menu_retorno ?>">
                <h1>Mi Perfil</h1>
            </a>
        </div>

        <a href="<?= $menu_retorno ?>" class="back-button">⬅️ Volver</a>
    </header>

    <main class="main-container">

        <div class="form-section">

            <form method="POST" class="input-form">

                <label>Nombre Completo</label>
                <input
                    type="text"
                    name="nombre"
                    placeholder="Nombre Completo"
                    value="<?= htmlspecialchars($nombreSesion) ?>"
                    readonly
                    required
                >

                <label>Correo Electrónico</label>
                <input
                    type="email"
                    name="correo"
                    placeholder="Correo electrónico"
                    value="<?= htmlspecialchars($correoSesion) ?>"
                    readonly
                    required
                >

                <label>Contraseña</label>
                <input
                    type="password"
                    name="pswrd"
                    placeholder="Dejar en blanco para no cambiarla"
                    value="<?= htmlspecialchars($pswrdPlaceholder) ?>"
                    readonly
                >

                <button type="submit" id="guardar" class="btn btn-primary" style="display:none;">
                    Guardar Cambios
                </button>
            </form>

            <div style="margin-top: 20px;">
                <a href="#" class="edit-profile" id="editarPerfil">Editar Perfil</a>
                <a href="logout.php" class="edit-profile">Cerrar Sesión</a>
            </div>

        </div>

    </main>

    <footer>
        <p>Método ACG</p>
    </footer>

    <script>
        document.addEventListener("DOMContentLoaded", function () {
            const editLink   = document.getElementById("editarPerfil");
            const form       = document.querySelector(".input-form");
            const inputsRO   = form.querySelectorAll("input[readonly]");
            const submitBtn  = document.getElementById("guardar");
            const passInput  = form.querySelector("input[name='pswrd']");

            editLink.addEventListener("click", function (e) {
                e.preventDefault();

                inputsRO.forEach(input => input.removeAttribute("readonly"));

                passInput.type = "text";

                if (passInput.value === "********") {
                    passInput.value = "";
                }

                submitBtn.style.display = "inline-block";

                editLink.textContent = "Editando...";
                editLink.style.pointerEvents = "none";
            });
        });
    </script>
</body>
</html>
