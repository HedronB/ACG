<?php
require_once __DIR__ . '/../../app/bootstrap.php';
require_once BASE_PATH . '/app/auth/protect.php';
require_once BASE_PATH . '/app/config/db.php';

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["usuarios"]) && is_array($_POST["usuarios"])) {

    $sql = "UPDATE usuarios SET us_rol = ?, us_empresa = ?, us_planta = ? WHERE us_id = ?";
    $stmt = $conn->prepare($sql);

    foreach ($_POST["usuarios"] as $us_id => $datos) {
        $rol     = isset($datos["rol"])     ? (int)$datos["rol"]     : 0;
        $empresa = isset($datos["empresa"]) && $datos["empresa"] !== "" ? (int)$datos["empresa"] : null;
        // Planta: solo válida si pertenece a la empresa seleccionada
        $planta  = isset($datos["planta"])  && $datos["planta"]  !== "" ? (int)$datos["planta"]  : null;

        $stmt->execute([$rol, $empresa, $planta, (int)$us_id]);
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
           u.us_planta,
           r.ro_nombre,
           e.em_nombre,
           p.pl_nombre
    FROM usuarios u
    LEFT JOIN roles    r ON u.us_rol     = r.ro_id
    LEFT JOIN empresas e ON u.us_empresa = e.em_id
    LEFT JOIN plantas  p ON u.us_planta  = p.pl_id
    ORDER BY u.us_nombre ASC
";
$usuarios = $conn->query($sqlUsuarios)->fetchAll(PDO::FETCH_ASSOC);

$sqlRoles    = "SELECT ro_id, ro_nombre FROM roles ORDER BY ro_id ASC";
$roles       = $conn->query($sqlRoles)->fetchAll(PDO::FETCH_ASSOC);

$sqlEmpresas = "SELECT em_id, em_nombre FROM empresas ORDER BY em_nombre ASC";
$empresas    = $conn->query($sqlEmpresas)->fetchAll(PDO::FETCH_ASSOC);

// Cargar plantas activas agrupadas por empresa para el JS
$sqlPlantas  = "SELECT pl_id, pl_nombre, pl_empresa FROM plantas WHERE pl_activo = 1 ORDER BY pl_empresa, pl_nombre ASC";
$plantasRaw  = $conn->query($sqlPlantas)->fetchAll(PDO::FETCH_ASSOC);

// Agrupar por empresa para JSON
$plantasPorEmpresa = [];
foreach ($plantasRaw as $p) {
    $plantasPorEmpresa[$p['pl_empresa']][] = ['id' => $p['pl_id'], 'nombre' => $p['pl_nombre']];
}
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
        .planta-select { width: 100%; padding: 8px; border-radius: 4px; border: 1px solid #d1d5db; font-size: 0.9em; }
        .badge-superusuario { background-color: #e0e7ff; color: #3730a3; font-size: 0.72em; font-weight: 700; padding: 2px 7px; border-radius: 10px; vertical-align: middle; margin-left: 5px; }
        .tabla-registros th, .tabla-registros td { min-width: 80px; }
        .tabla-registros .col-rol  { min-width: 260px; }
        .tabla-registros .col-emp  { min-width: 160px; }
        .tabla-registros .col-plt  { min-width: 160px; }
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
        <div class="form-section wide">

            <?php if (isset($_GET["success"])): ?>
                <div class="mensaje-exito">Cambios guardados correctamente.</div>
            <?php endif; ?>

            <p style="font-size:0.85em; color:#555; margin-bottom:15px;">
                Un usuario con empresa asignada pero <strong>sin planta</strong> es considerado 
                <span class="badge-superusuario">Super Usuario</span> — tiene acceso a todas las 
                plantas de su empresa.
            </p>

            <form method="POST" class="input-form">
                <div class="registros-section">
                    <div class="tabla-container-scroll">
                        <table class="tabla-registros">
                            <thead>
                                <tr>
                                    <th>Nombre</th>
                                    <th>Correo</th>
                                    <th class="col-rol">Rol</th>
                                    <th class="col-emp">Empresa</th>
                                    <th class="col-plt">Planta</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($usuarios as $u): ?>
                                    <tr>
                                        <td>
                                            <?= htmlspecialchars($u["us_nombre"]) ?>
                                            <?php if ($u["us_empresa"] && !$u["us_planta"]): ?>
                                                <span class="badge-superusuario">Super</span>
                                            <?php endif; ?>
                                        </td>
                                        <td><?= htmlspecialchars($u["us_correo"]) ?></td>
                                        <td class="col-rol">
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
                                        <td class="col-emp">
                                            <select
                                                class="empresa-select"
                                                name="usuarios[<?= $u["us_id"] ?>][empresa]"
                                                data-uid="<?= (int)$u["us_id"] ?>"
                                                onchange="actualizarPlantas(this)"
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
                                        <td class="col-plt">
                                            <select
                                                class="planta-select"
                                                name="usuarios[<?= $u["us_id"] ?>][planta]"
                                                id="planta_<?= (int)$u["us_id"] ?>"
                                            >
                                                <option value="">-- Super Usuario (todas) --</option>
                                                <?php
                                                $empId = (int)$u["us_empresa"];
                                                if ($empId && isset($plantasPorEmpresa[$empId])):
                                                    foreach ($plantasPorEmpresa[$empId] as $pl):
                                                ?>
                                                    <option
                                                        value="<?= $pl["id"] ?>"
                                                        <?= ((int)$u["us_planta"] === (int)$pl["id"]) ? "selected" : "" ?>
                                                    >
                                                        <?= htmlspecialchars($pl["nombre"]) ?>
                                                    </option>
                                                <?php endforeach; endif; ?>
                                            </select>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                                <?php if (empty($usuarios)): ?>
                                    <tr><td colspan="5">No hay usuarios registrados.</td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="actions-container">
                    <button type="submit" class="btn btn-primary">Guardar cambios</button>
                </div>
            </form>
        </div>
    </main>

    <footer>
        <p>Método ACG</p>
    </footer>

<script>
// Mapa de plantas por empresa, generado desde PHP
const PLANTAS_POR_EMPRESA = <?= json_encode($plantasPorEmpresa, JSON_UNESCAPED_UNICODE) ?>;

function actualizarPlantas(selectEmpresa) {
    const uid      = selectEmpresa.getAttribute('data-uid');
    const empresaId = selectEmpresa.value;
    const selectPlanta = document.getElementById('planta_' + uid);
    if (!selectPlanta) return;

    // Guardar selección actual antes de limpiar
    const valorActual = selectPlanta.value;

    // Limpiar opciones
    selectPlanta.innerHTML = '<option value="">-- Super Usuario (todas) --</option>';

    if (!empresaId || !PLANTAS_POR_EMPRESA[empresaId]) return;

    PLANTAS_POR_EMPRESA[empresaId].forEach(pl => {
        const opt = document.createElement('option');
        opt.value = pl.id;
        opt.textContent = pl.nombre;
        if (String(pl.id) === String(valorActual)) opt.selected = true;
        selectPlanta.appendChild(opt);
    });
}
</script>

</body>
</html>
