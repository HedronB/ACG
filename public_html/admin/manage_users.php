<?php
require_once __DIR__ . '/../../app/bootstrap.php';

require_once BASE_PATH . '/app/auth/protect.php';
require_once BASE_PATH . '/app/config/db.php';

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["usuarios"]) && is_array($_POST["usuarios"])) {

    $sql = "UPDATE usuarios SET us_rol = ?, us_empresa = ? WHERE us_id = ?";
    $stmt = $conn->prepare($sql);

    foreach ($_POST["usuarios"] as $us_id => $datos) {
        $rol     = isset($datos["rol"]) ? (int)$datos["rol"] : 0;
        $empresa = isset($datos["empresa"]) && $datos["empresa"] !== "" ? (int)$datos["empresa"] : null;

        $stmt->execute([$rol, $empresa, (int)$us_id]);
    }

    header("Location: manage_users.php?success=1");
    exit();
}

$sqlUsuarios = "
    SELECT u.us_id,
           u.us_nombre,
           u.us_correo,
           u.us_rol,
           u.us_empresa,
           r.ro_nombre,
           e.em_nombre
    FROM usuarios u
    LEFT JOIN roles r    ON u.us_rol = r.ro_id
    LEFT JOIN empresas e ON u.us_empresa = e.em_id
    ORDER BY u.us_nombre ASC
";
$usuarios = $conn->query($sqlUsuarios)->fetchAll(PDO::FETCH_ASSOC);

$sqlRoles = "SELECT ro_id, ro_nombre FROM roles ORDER BY ro_id ASC";
$roles = $conn->query($sqlRoles)->fetchAll(PDO::FETCH_ASSOC);

$sqlEmpresas = "SELECT em_id, em_nombre FROM empresas ORDER BY em_nombre ASC";
$empresas = $conn->query($sqlEmpresas)->fetchAll(PDO::FETCH_ASSOC);

?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Administrar usuarios</title>
    <link rel="icon" type="image/png" href="/imagenes/loguito.png">
    <link rel="stylesheet" href="/css/acg.estilos.css">
    <style>
        .header {
            justify-content: space-between;
        }
        .tabla-registros td,
        .tabla-registros th {
            vertical-align: middle;
        }
        .roles-radios {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
        }
        .roles-radios label {
            display: flex;
            align-items: center;
            gap: 4px;
            font-size: 0.85em;
        }
        .empresa-select {
            width: 100%;
            padding: 8px;
            border-radius: 4px;
            border: 1px solid #d1d5db;
            font-size: 0.9em;
        }
        .mensaje-exito {
            padding: 10px 15px;
            margin-bottom: 15px;
            border-radius: 4px;
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
    </style>
</head>

<body>

    <header class="header">
        <div class="header-title-group">
            <a href="/admin/menu_admin.php">
                <img src="/imagenes/logo.png" alt="Logo ACG" class="header-logo">
            </a>
            <a href="/admin/menu_admin.php">
                <h1>Administrar usuarios</h1>
            </a>
        </div>

        <a href="/admin/menu_admin.php" class="back-button">⬅️ Volver</a>
    </header>

    <main class="main-container">

        <div class="form-section">

            <?php if (isset($_GET["success"])): ?>
                <div class="mensaje-exito">
                    Cambios guardados correctamente.
                </div>
            <?php endif; ?>

            <form method="POST" class="input-form">

                <div class="registros-section">
                    <table class="tabla-registros">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Nombre</th>
                                <th>Correo</th>
                                <th>Rol</th>
                                <th>Empresa</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($usuarios as $u): ?>
                                <tr>
                                    <td><?= htmlspecialchars($u["us_id"]) ?></td>
                                    <td><?= htmlspecialchars($u["us_nombre"]) ?></td>
                                    <td><?= htmlspecialchars($u["us_correo"]) ?></td>
                                    <td>
                                        <div class="roles-radios">
                                            <?php foreach ($roles as $r): ?>
                                                <label>
                                                    <input
                                                        type="radio"
                                                        name="usuarios[<?= $u["us_id"] ?>][rol]"
                                                        value="<?= $r["ro_id"] ?>"
                                                        <?= ((int)$u["us_rol"] === (int)$r["ro_id"]) ? "checked" : "" ?>
                                                    >
                                                    <?= htmlspecialchars($r["ro_nombre"]) ?>
                                                </label>
                                            <?php endforeach; ?>
                                        </div>
                                    </td>
                                    <td>
                                        <select
                                            class="empresa-select"
                                            name="usuarios[<?= $u["us_id"] ?>][empresa]"
                                        >
                                            <option value="">-- Sin empresa --</option>
                                            <?php foreach ($empresas as $e): ?>
                                                <option
                                                    value="<?= $e["em_id"] ?>"
                                                    <?= ((int)$u["us_empresa"] === (int)$e["em_id"]) ? "selected" : "" ?>
                                                >
                                                    <?= htmlspecialchars($e["em_nombre"]) ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </td>
                                </tr>
                            <?php endforeach; ?>

                            <?php if (empty($usuarios)): ?>
                                <tr>
                                    <td colspan="5">No hay usuarios registrados.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <div class="actions-container">
                    <button type="submit" class="btn btn-primary">
                        Guardar cambios
                    </button>
                </div>

            </form>

        </div>

    </main>

    <footer>
        <p>Método ACG</p>
    </footer>

</body>
</html>
