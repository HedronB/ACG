<?php
require_once __DIR__ . '/../../app/bootstrap.php';
require_once BASE_PATH . '/app/auth/protect.php';
require_once BASE_PATH . '/app/config/db.php';
header('Content-Type: application/json');

$empresaId = (int)($_GET['empresa'] ?? $_SESSION['empresa'] ?? 0);
$plantaId  = isset($_GET['planta']) && $_GET['planta'] !== '' ? (int)$_GET['planta'] : null;
$rol = (int)$_SESSION['rol'];

$sql = "SELECT ma_id, ma_no, ma_marca, ma_modelo, ma_diam_husillo,
               ma_dist_barras, ma_apert_max, ma_tonelaje,
               ma_vol_inyec, ma_max_pres_inyec, ma_max_vel_inyec
        FROM maquinas WHERE ma_activo = 1";
$p = [];
// Solo filtrar por empresa si el rol no es admin Y la empresa es válida (>0)
if ($rol !== 1 && $empresaId > 0) { $sql .= " AND ma_empresa = :empresa"; $p[':empresa'] = $empresaId; }
if ($plantaId)  { $sql .= " AND ma_planta = :planta"; $p[':planta'] = $plantaId; }
$sql .= " ORDER BY ma_marca, ma_modelo";
$stmt = $conn->prepare($sql); $stmt->execute($p);
echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
