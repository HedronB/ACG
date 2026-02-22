<?php
require_once __DIR__ . '/../../app/bootstrap.php';
require_once BASE_PATH . '/app/auth/protect.php';
require_once BASE_PATH . '/app/config/db.php';
require_once BASE_PATH . '/app/helpers/ResponseHelper.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') jsonError("Método no permitido", 405);

$input = json_decode(file_get_contents('php://input'), true);
if (!$input || !isset($input['mo_id'])) jsonError("Datos inválidos", 400);

$usuarioSesion = (int)$_SESSION['id'];
$empresaSesion = (int)$_SESSION['empresa'];
$rol           = (int)$_SESSION['rol'];
$moId          = (int)$input['mo_id'];

$check = $conn->prepare("SELECT mo_empresa, mo_usuario FROM moldes WHERE mo_id = ?");
$check->execute([$moId]);
$reg = $check->fetch(PDO::FETCH_ASSOC);

if (!$reg) jsonError("Registro no encontrado", 404);
if ($rol !== 1) {
    if ($rol === 2 && (int)$reg['mo_empresa'] !== $empresaSesion) jsonError("Sin permiso", 403);
    if ($rol === 3 && (int)$reg['mo_usuario'] !== $usuarioSesion) jsonError("Sin permiso", 403);
}

$campos = [
    'mo_no_pieza','mo_numero','mo_ancho','mo_alto','mo_largo',
    'mo_placas_voladas','mo_anillo_centrador','mo_no_circ_agua','mo_peso',
    'mo_apert_min','mo_abierto','mo_tipo_colada','mo_no_zonas','mo_no_cavidades',
    'mo_peso_pieza','mo_puert_cavidad','mo_no_coladas','mo_peso_colada',
    'mo_peso_disparo','mo_noyos','mo_entr_aire','mo_thermoreguladores',
    'mo_valve_gates','mo_tiempo_ciclo','mo_cavidades_activas'
];

$sets = implode(', ', array_map(fn($c) => "$c = :$c", $campos));
$sets .= ', mo_actualizado_en = NOW(), mo_actualizado_por = :mo_actualizado_por';

$stmt = $conn->prepare("UPDATE moldes SET $sets WHERE mo_id = :mo_id");
$params = [':mo_id' => $moId, ':mo_actualizado_por' => $usuarioSesion];
foreach ($campos as $c) {
    $params[":$c"] = isset($input[$c]) && $input[$c] !== '' ? $input[$c] : null;
}

try {
    $stmt->execute($params);
    jsonSuccess("Registro actualizado correctamente");
} catch (PDOException $e) {
    jsonError("Error al actualizar el registro", 500);
}
