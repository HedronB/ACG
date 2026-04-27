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

try {
    $sql = "INSERT INTO procesos_eyc
        (eyc_proceso_id, eyc_descripcion, eyc_cojin, eyc_vel_inyeccion,
         eyc_tpo_sostenimiento, eyc_tpo_enfriamiento, eyc_diam_bebedero,
         eyc_pos_puerto, eyc_porc_molido, eyc_densidad_caliente, eyc_peso_disparo,
         eyc_vol_disparo, eyc_diam_husillo_min, eyc_diam_husillo_sug,
         eyc_diam_husillo_max, eyc_tonelaje_sug, eyc_fecha_actualizado)
    VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)
    ON DUPLICATE KEY UPDATE
        eyc_descripcion=VALUES(eyc_descripcion), eyc_cojin=VALUES(eyc_cojin),
        eyc_vel_inyeccion=VALUES(eyc_vel_inyeccion),
        eyc_tpo_sostenimiento=VALUES(eyc_tpo_sostenimiento),
        eyc_tpo_enfriamiento=VALUES(eyc_tpo_enfriamiento),
        eyc_diam_bebedero=VALUES(eyc_diam_bebedero),
        eyc_pos_puerto=VALUES(eyc_pos_puerto),
        eyc_porc_molido=VALUES(eyc_porc_molido),
        eyc_densidad_caliente=VALUES(eyc_densidad_caliente),
        eyc_peso_disparo=VALUES(eyc_peso_disparo),
        eyc_vol_disparo=VALUES(eyc_vol_disparo),
        eyc_diam_husillo_min=VALUES(eyc_diam_husillo_min),
        eyc_diam_husillo_sug=VALUES(eyc_diam_husillo_sug),
        eyc_diam_husillo_max=VALUES(eyc_diam_husillo_max),
        eyc_tonelaje_sug=VALUES(eyc_tonelaje_sug),
        eyc_fecha_actualizado=VALUES(eyc_fecha_actualizado)";

    $conn->prepare($sql)->execute([
        $pid, $d['descripcion']??'Base', $d['cojin'], $d['vel_inyeccion'],
        $d['tpo_sostenimiento'], $d['tpo_enfriamiento'], $d['diam_bebedero'],
        $d['pos_puerto'] ?? 2, $d['porc_molido'], $d['densidad_caliente'], $d['peso_disparo'],
        $d['vol_disparo'], $d['diam_husillo_min'], $d['diam_husillo_sug'],
        $d['diam_husillo_max'], $d['tonelaje_sug'], $now
    ]);

    // Actualizar fecha modificación del proceso
    $conn->prepare("UPDATE procesos SET pr_fecha_modificacion=?, pr_ultimo_usuario_id=? WHERE pr_id=?")
         ->execute([$now, (int)$_SESSION['id'], $pid]);

    jsonSuccess('E y C guardado correctamente');
} catch (PDOException $e) {
    jsonError('Error al guardar', 500);
}
