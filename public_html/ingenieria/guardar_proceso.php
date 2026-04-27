<?php
require_once __DIR__ . '/../../app/bootstrap.php';
require_once BASE_PATH . '/app/auth/protect.php';
require_once BASE_PATH . '/app/config/db.php';
require_once BASE_PATH . '/app/helpers/ResponseHelper.php';
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') jsonError('Método no permitido', 405);
$input = json_decode(file_get_contents('php://input'), true);
if (!$input || !isset($input['pieza_id'], $input['maquina_id'])) jsonError('Datos inválidos', 400);

$piezaId   = (int)$input['pieza_id'];
$maquinaId = (int)$input['maquina_id'];
$empresaId = (int)$_SESSION['empresa'];
$usuarioId = (int)$_SESSION['id'];

// Verificar que no existe ya este proceso
$check = $conn->prepare("SELECT pr_id FROM procesos WHERE pr_pieza_id = ? AND pr_maquina_id = ?");
$check->execute([$piezaId, $maquinaId]);
if ($existing = $check->fetch(PDO::FETCH_ASSOC)) {
    jsonError('Ya existe un proceso para esta combinación pieza/máquina. ID: ' . $existing['pr_id'], 409);
}

// Buscar IDs de molde y resina por código interno
$moldeId = null; $resinaId = null;
if (!empty($input['molde_num'])) {
    $stmtMo = $conn->prepare("SELECT mo_id FROM moldes WHERE mo_numero = ? AND mo_activo = 1 LIMIT 1");
    $stmtMo->execute([$input['molde_num']]);
    $rowMo = $stmtMo->fetch(PDO::FETCH_ASSOC);
    $moldeId = $rowMo ? (int)$rowMo['mo_id'] : null;
}
if (!empty($input['resina_cod'])) {
    $stmtRe = $conn->prepare("SELECT re_id FROM resinas WHERE re_cod_int = ? AND re_activo = 1 LIMIT 1");
    $stmtRe->execute([$input['resina_cod']]);
    $rowRe = $stmtRe->fetch(PDO::FETCH_ASSOC);
    $resinaId = $rowRe ? (int)$rowRe['re_id'] : null;
}

try {
    $conn->beginTransaction();
    $stmt = $conn->prepare("INSERT INTO procesos
        (pr_pieza_id, pr_maquina_id, pr_molde_id, pr_resina_id, pr_empresa_id, pr_usuario_id)
        VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->execute([$piezaId, $maquinaId, $moldeId, $resinaId, $empresaId, $usuarioId]);
    $procesoId = $conn->lastInsertId();

    // Crear registro vacío de E y C
    $conn->prepare("INSERT INTO procesos_eyc (eyc_proceso_id) VALUES (?)")->execute([$procesoId]);
    $conn->commit();
    echo json_encode(['ok' => true, 'proceso_id' => $procesoId]);
} catch (PDOException $e) {
    if ($conn->inTransaction()) $conn->rollBack();
    jsonError('Error al crear el proceso', 500);
}
