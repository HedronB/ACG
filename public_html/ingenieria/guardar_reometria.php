<?php
require_once __DIR__ . '/../../app/bootstrap.php';
require_once BASE_PATH . '/app/auth/protect.php';
require_once BASE_PATH . '/app/config/db.php';
require_once BASE_PATH . '/app/helpers/ResponseHelper.php';
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') jsonError('Método no permitido', 405);
$d = json_decode(file_get_contents('php://input'), true);
if (!$d || !isset($d['proceso_id'], $d['filas'])) jsonError('Datos inválidos', 400);

$pid = (int)$d['proceso_id'];
try {
    $conn->beginTransaction();
    // Limpiar filas anteriores
    $conn->prepare("DELETE FROM procesos_reometria WHERE reo_proceso_id = ?")->execute([$pid]);
    $stmt = $conn->prepare("INSERT INTO procesos_reometria
        (reo_proceso_id, reo_orden, reo_presion_1, reo_presion_2,
         reo_presion_prom, reo_tiempo, reo_diferencia)
        VALUES (?,?,?,?,?,?,?)");
    foreach ($d['filas'] as $f) {
        $stmt->execute([
            $pid, (int)$f['orden'],
            $f['p1'] ?? null, $f['p2'] ?? null,
            $f['prom'] ?? null, $f['tiempo'] ?? null, $f['dif'] ?? null
        ]);
    }
    $conn->prepare("UPDATE procesos SET pr_fecha_modificacion=?, pr_ultimo_usuario_id=? WHERE pr_id=?")
         ->execute([date('Y-m-d H:i:s'), (int)$_SESSION['id'], $pid]);
    $conn->commit();
    jsonSuccess('Reometría guardada correctamente');
} catch (PDOException $e) {
    if ($conn->inTransaction()) $conn->rollBack();
    jsonError('Error al guardar reometría', 500);
}
