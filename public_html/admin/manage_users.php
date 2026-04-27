<?php
require_once __DIR__ . '/../../app/bootstrap.php';
require_once BASE_PATH . '/app/auth/protect.php';
require_once BASE_PATH . '/app/config/db.php';
require_once BASE_PATH . '/app/helpers/LayoutHelper.php';

$rol = (int)$_SESSION['rol'];

// ── Crear usuario nuevo ────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'crear_usuario') {
    $rolActual     = (int)$_SESSION['rol'];
    $empresaActual = isset($_SESSION['empresa']) ? (int)$_SESSION['empresa'] : null;

    $nombre  = trim($_POST['nuevo_nombre'] ?? '');
    $correo  = trim($_POST['nuevo_correo'] ?? '');
    $pswrd   = $_POST['nuevo_password'] ?? '';
    $rol_new = (int)($_POST['nuevo_rol'] ?? 0);
    $emp_new = isset($_POST['nuevo_empresa']) && $_POST['nuevo_empresa'] !== '' ? (int)$_POST['nuevo_empresa'] : null;
    $pla_new = isset($_POST['nuevo_planta'])  && $_POST['nuevo_planta']  !== '' ? (int)$_POST['nuevo_planta']  : null;

    // Gerente no puede crear admins ni asignar otras empresas
    if ($rolActual === 2) {
        if ($rol_new === 1) $rol_new = 3;
        $emp_new = $empresaActual;
    }

    if ($nombre && $correo && $pswrd) {
        $pass = password_hash($pswrd, PASSWORD_DEFAULT);
        try {
            $conn->prepare('INSERT INTO usuarios (us_nombre, us_correo, us_password, us_rol, us_empresa, us_planta)
                            VALUES (?, ?, ?, ?, ?, ?)')->execute([$nombre, $correo, $pass, $rol_new, $emp_new, $pla_new]);
            header('Location: manage_users.php?success=2');
            exit();
        } catch (PDOException $e) {
            $create_error = $e->getCode() === '23000' ? 'Ese correo ya está registrado.' : 'Error al crear el usuario.';
        }
    } else {
        $create_error = 'Nombre, correo y contraseña son obligatorios.';
    }
}

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["usuarios"]) && is_array($_POST["usuarios"])) {
    $rolActual     = (int)$_SESSION['rol'];
    $empresaActual = isset($_SESSION['empresa']) ? (int)$_SESSION['empresa'] : null;
    $plantaActual  = isset($_SESSION['planta']) && $_SESSION['planta'] !== '' ? (int)$_SESSION['planta'] : null;

    $sql = "UPDATE usuarios SET us_rol = ?, us_empresa = ?, us_planta = ? WHERE us_id = ?";
    $stmt = $conn->prepare($sql);

    foreach ($_POST["usuarios"] as $us_id => $datos) {
        $nuevoRol = isset($datos["rol"]) ? (int)$datos["rol"] : 0;

        // Gerente no puede asignar rol admin (1) ni inactivo como upgrade
        if ($rolActual === 2) {
            if ($nuevoRol === 1) $nuevoRol = 2; // Máximo gerente
            $empresa = $empresaActual; // No puede cambiar empresa
            $planta  = isset($datos["planta"]) && $datos["planta"] !== "" ? (int)$datos["planta"] : null;
            // Gerente de planta no puede asignar planta diferente a la suya
            if ($plantaActual && $planta !== $plantaActual) $planta = $plantaActual;
        } else {
            // Admin: control total
            $empresa = isset($datos["empresa"]) && $datos["empresa"] !== "" ? (int)$datos["empresa"] : null;
            $planta  = isset($datos["planta"])  && $datos["planta"]  !== "" ? (int)$datos["planta"]  : null;
        }

        try {
            $stmt->execute([$nuevoRol, $empresa, $planta, (int)$us_id]);
        } catch (PDOException $e) {
            // FK constraint: empresa o planta inválida — ignorar ese registro
        }
    }

    header("Location: manage_users.php?success=1");
    exit();
}

// Filtrado según rol del usuario actual
$rolActual     = (int)$_SESSION['rol'];
$empresaActual = isset($_SESSION['empresa']) ? (int)$_SESSION['empresa'] : null;
$plantaActual  = isset($_SESSION['planta']) && $_SESSION['planta'] !== '' ? (int)$_SESSION['planta'] : null;

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
    WHERE 1=1
";
$paramsU = [];

if ($rolActual === 2) {
    // Gerente: filtra por empresa siempre
    $sqlUsuarios .= " AND u.us_empresa = :empresa";
    $paramsU[':empresa'] = $empresaActual;
    // Si tiene planta asignada, solo ve su planta (no puede ver toda la empresa)
    if ($plantaActual) {
        $sqlUsuarios .= " AND u.us_planta = :planta";
        $paramsU[':planta'] = $plantaActual;
    }
}
// Admin (rol 1) ve todo — sin filtros

$sqlUsuarios .= " ORDER BY u.us_nombre ASC";
$stmtU = $conn->prepare($sqlUsuarios);
$stmtU->execute($paramsU);
$usuarios = $stmtU->fetchAll(PDO::FETCH_ASSOC);

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

$menu_retorno  = match($rol) {
    1 => '/admin/menu_admin.php',
    2,3 => '/user/menu_user.php',
    default => '/index.php'
};
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
            <a href="<?= $menu_principal ?>"><img src="/imagenes/logo.png" alt="Logo" class="header-logo"></a>
            <h1>Administrar usuarios</h1>
        </div>
        <div class="header-right">
            <a href="<?= $menu_retorno ?>" class="back-button">⬅️ Volver</a>
            <?= burgerBtn() ?>
        </div>
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
                        
<?php if (!empty($create_error)): ?>
<div class="mensaje error" style="display:block;margin-bottom:12px;"><?= htmlspecialchars($create_error) ?></div>
<?php endif; ?>
<?php if (isset($_GET['success']) && $_GET['success'] == '1'): ?>
<div class="mensaje exito" style="display:block;margin-bottom:12px;">✅ Cambios guardados correctamente.</div>
<?php endif; ?>
<?php if (isset($_GET['success']) && $_GET['success'] == '2'): ?>
<div class="mensaje exito" style="display:block;margin-bottom:12px;">✅ Usuario creado correctamente.</div>
<?php endif; ?>
<?php if (isset($_GET['error']) && $_GET['error'] == 'fk'): ?>
<div class="mensaje error" style="display:block;margin-bottom:12px;">⚠️ Algunos cambios no se pudieron guardar. Verifica que la empresa o planta asignada exista.</div>
<?php endif; ?>

<!-- Botón crear usuario -->
<div style="margin-bottom:16px;">
    <button type="button" onclick="document.getElementById('modal-crear-usuario').style.display='flex'"
            class="btn btn-guardar">➕ Crear nuevo usuario</button>
</div>

<!-- Modal crear usuario -->

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
                    <button type="submit" class="btn btn-guardar">💾 Guardar cambios</button>
                </div>
            </form>

<div id="modal-crear-usuario" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.5);
     z-index:1000;align-items:center;justify-content:center;" onclick="if(event.target===this)this.style.display='none'">
    <div style="background:#fff;border-radius:10px;padding:24px 28px;width:100%;max-width:420px;
                box-shadow:0 8px 32px rgba(0,0,0,.25);">
        <h3 style="margin:0 0 16px;color:#1e3a8a;">Crear nuevo usuario</h3>
        <form method="POST">
            <input type="hidden" name="action" value="crear_usuario">
            <div style="display:flex;flex-direction:column;gap:10px;font-size:.88em;">
                <div>
                    <label style="font-weight:600;color:#555;display:block;margin-bottom:3px;">Nombre completo *</label>
                    <input type="text" name="nuevo_nombre" required style="width:100%;padding:7px 10px;border:1px solid #d1d5db;border-radius:4px;">
                </div>
                <div>
                    <label style="font-weight:600;color:#555;display:block;margin-bottom:3px;">Correo electrónico *</label>
                    <input type="email" name="nuevo_correo" required style="width:100%;padding:7px 10px;border:1px solid #d1d5db;border-radius:4px;">
                </div>
                <div>
                    <label style="font-weight:600;color:#555;display:block;margin-bottom:3px;">Contraseña *</label>
                    <input type="password" name="nuevo_password" required style="width:100%;padding:7px 10px;border:1px solid #d1d5db;border-radius:4px;">
                </div>
                <div>
                    <label style="font-weight:600;color:#555;display:block;margin-bottom:3px;">Rol</label>
                    <select name="nuevo_rol" style="width:100%;padding:7px;border:1px solid #d1d5db;border-radius:4px;">
                        <option value="0">Inactivo</option>
                        <?php foreach ($roles as $r): ?>
                        <?php if ($r['ro_id'] == 1 && $rolActual !== 1) continue; ?>
                        <option value="<?= $r['ro_id'] ?>"><?= htmlspecialchars($r['ro_nombre']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <?php if ($rolActual === 1): ?>
                <div>
                    <label style="font-weight:600;color:#555;display:block;margin-bottom:3px;">Empresa</label>
                    <select name="nuevo_empresa" id="modal_empresa"
                            onchange="actualizarPlantasModal(this.value)"
                            style="width:100%;padding:7px;border:1px solid #d1d5db;border-radius:4px;">
                        <option value="">— Sin empresa —</option>
                        <?php
                        $emps = $conn->query("SELECT em_id, em_nombre FROM empresas ORDER BY em_nombre")->fetchAll(PDO::FETCH_ASSOC);
                        foreach ($emps as $e): ?>
                        <option value="<?= $e['em_id'] ?>"><?= htmlspecialchars($e['em_nombre']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label style="font-weight:600;color:#555;display:block;margin-bottom:3px;">Planta</label>
                    <select name="nuevo_planta" id="modal_planta"
                            style="width:100%;padding:7px;border:1px solid #d1d5db;border-radius:4px;">
                        <option value="">— Sin planta asignada —</option>
                    </select>
                </div>
                <?php endif; ?>
                <div style="display:flex;gap:10px;margin-top:6px;">
                    <button type="submit" class="btn btn-guardar" style="flex:1;">💾 Crear usuario</button>
                    <button type="button" class="btn btn-limpiar" style="flex:1;"
                            onclick="document.getElementById('modal-crear-usuario').style.display='none'">Cancelar</button>
                </div>
            </div>
        </form>
    </div>
</div>

        </div>
    </main>

    <footer>
        <p>Método ACG</p>
    </footer>

<script>
// Mapa de plantas por empresa, generado desde PHP
const PLANTAS_POR_EMPRESA = <?= json_encode($plantasPorEmpresa, JSON_UNESCAPED_UNICODE) ?>;

function actualizarPlantasModal(empresaId) {
    const sel = document.getElementById('modal_planta');
    if (!sel) return;
    sel.innerHTML = '<option value="">— Sin planta asignada —</option>';
    if (!empresaId || !PLANTAS_POR_EMPRESA[empresaId]) return;
    PLANTAS_POR_EMPRESA[empresaId].forEach(pl => {
        const opt = document.createElement('option');
        opt.value = pl.id;
        opt.textContent = pl.nombre;
        sel.appendChild(opt);
    });
}

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

<?php includeSidebar(); ?>
</body>
</html>
