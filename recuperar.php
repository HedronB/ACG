<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Recuperar contraseña</title>
    <link rel="stylesheet" href="css/acg.estilos.css">
</head>
<body>

<header class="header">
    <h1>Recuperar contraseña</h1>
</header>

<main class="main-container">
    <div class="form-section">

        <form method="POST" action="procesar_recuperacion.php" class="input-form">
            <label>Correo electrónico</label>
            <input type="email" name="correo" required>

            <button class="btn btn-success" type="submit">
                Enviar contraseña temporal
            </button>
        </form>

        <br>
        <a href="index.php">Volver al inicio de sesión</a>

    </div>
</main>

</body>
</html>
