<?php
require_once __DIR__ . '/../../app/bootstrap.php';
require_once BASE_PATH . '/app/auth/protect.php';
require_once BASE_PATH . '/app/config/db.php';
require_once BASE_PATH . '/app/helpers/ResponseHelper.php';
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') jsonError('Método no permitido', 405);
$d = json_decode(file_get_contents('php://input'), true);
if (!$d || !isset($d['proceso_id'])) jsonError('Datos inválidos', 400);

$pid = (int)$d['proceso_id'];
$now = date('Y-m-d H:i:s');
$uid = (int)$_SESSION['id'];

// Obtener columnas reales de la tabla
$cols_res = $conn->query("SHOW COLUMNS FROM procesos_calificador")->fetchAll(PDO::FETCH_COLUMN);
$cols_validos = array_filter($cols_res, fn($c) => !in_array($c, ['cal_id','cal_proceso_id','cal_fecha_actualizado']));

// Mapear: key del JSON → nombre columna
$data_cols = [];
$data_vals = [];
foreach ($cols_validos as $col) {
    $key = substr($col, 4); // quitar prefijo cal_
    if (array_key_exists($key, $d)) {
        $data_cols[] = $col;
        $data_vals[] = ($d[$key] !== null && $d[$key] !== '') ? $d[$key] : null;
    }
}

if (empty($data_cols)) {
    jsonError('Sin datos para guardar', 400);
}

$ins_cols = 'cal_proceso_id,' . implode(',', $data_cols) . ',cal_fecha_actualizado';
$ins_ph   = '?,' . implode(',', array_fill(0, count($data_cols), '?')) . ',?';
$upd      = implode(',', array_map(fn($c) => "$c=VALUES($c)", array_merge($data_cols, ['cal_fecha_actualizado'])));

$params = array_merge([$pid], $data_vals, [$now]);

try {
    $conn->prepare("INSERT INTO procesos_calificador ($ins_cols) VALUES ($ins_ph)
                    ON DUPLICATE KEY UPDATE $upd")->execute($params);
    $conn->prepare("UPDATE procesos SET pr_fecha_modificacion=?, pr_ultimo_usuario_id=? WHERE pr_id=?")
         ->execute([$now, $uid, $pid]);
    jsonSuccess('Calificador guardado correctamente');
} catch (PDOException $e) {
    jsonError('Error al guardar: ' . $e->getMessage(), 500);
}
