<?php

error_reporting(E_ALL);
ini_set('display_errors', '0');

session_start();
header('Content-Type: application/json');


register_shutdown_function(function () {
    $error = error_get_last();
    if ($error && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
        http_response_code(500);
        echo json_encode([
            'ok' => false,
            'error' => 'Server error: ' . $error['message'] . ' (baris ' . $error['line'] . ')'
        ]);
    }
});

try {
    require_once __DIR__ . '/../config/database.php';

    if (!isset($_SESSION['pelanggan_id']) || !isset($_SESSION['id_meja'])) {
        http_response_code(400);
        echo json_encode(['ok' => false, 'error' => 'Sesi tidak ditemukan, silakan scan ulang QR.']);
        exit;
    }

    $rawInput = file_get_contents('php://input');
    $input = json_decode($rawInput, true);

    if (json_last_error() !== JSON_ERROR_NONE) {
        http_response_code(400);
        echo json_encode(['ok' => false, 'error' => 'Data yang dikirim tidak valid.']);
        exit;
    }

    $qtyList = $input['qty'] ?? [];

    $itemDipesan = [];
    foreach ($qtyList as $idMenu => $qty) {
        $qty = (int)$qty;
        if ($qty > 0) {
            $itemDipesan[$idMenu] = $qty;
        }
    }

    if (empty($itemDipesan)) {
        http_response_code(400);
        echo json_encode(['ok' => false, 'error' => 'Keranjang masih kosong.']);
        exit;
    }

    $db = new Database();
    $conn = $db->getConnection();

    $conn->beginTransaction();

    $placeholders = implode(',', array_fill(0, count($itemDipesan), '?'));
    $stmtMenu = $conn->prepare("SELECT id, harga FROM menu WHERE id IN ($placeholders)");
    $stmtMenu->execute(array_keys($itemDipesan));
    $hargaMenu = [];
    foreach ($stmtMenu->fetchAll() as $m) {
        $hargaMenu[$m['id']] = $m['harga'];
    }

    if (count($hargaMenu) === 0) {
        throw new Exception('Menu yang dipilih tidak ditemukan di database.');
    }

    $grandTotal = 0;
    foreach ($itemDipesan as $idMenu => $qty) {
        if (!isset($hargaMenu[$idMenu])) {
            continue;
        }
        $grandTotal += $hargaMenu[$idMenu] * $qty;
    }

    $idPesan = 'PS' . substr(str_pad((string)random_int(0, 99999999), 8, '0', STR_PAD_LEFT), 0, 8);
    $stmtPesan = $conn->prepare("
        INSERT INTO pesan (id, id_meja, id_pelanggan, status_pesan, tgl_pesan, grand_total)
        VALUES (:id, :id_meja, :id_pelanggan, 'DIPROSES', NOW(), :grand_total)
    ");
    $stmtPesan->execute([
        'id' => $idPesan,
        'id_meja' => $_SESSION['id_meja'],
        'id_pelanggan' => $_SESSION['pelanggan_id'],
        'grand_total' => $grandTotal,
    ]);

    $stmtDetail = $conn->prepare("
        INSERT INTO detail_pesan (id, id_pesan, id_menu, quantity, subtotal)
        VALUES (:id, :id_pesan, :id_menu, :quantity, :subtotal)
    ");
    foreach ($itemDipesan as $idMenu => $qty) {
        if (!isset($hargaMenu[$idMenu])) {
            continue;
        }
        $idDetail = 'DP' . substr(str_pad((string)random_int(0, 99999999), 8, '0', STR_PAD_LEFT), 0, 8);
        $stmtDetail->execute([
            'id' => $idDetail,
            'id_pesan' => $idPesan,
            'id_menu' => $idMenu,
            'quantity' => $qty,
            'subtotal' => $hargaMenu[$idMenu] * $qty,
        ]);
    }

    $conn->commit();
    echo json_encode(['ok' => true, 'id_pesan' => $idPesan, 'grand_total' => $grandTotal]);

} catch (Throwable $e) {
    if (isset($conn) && $conn->inTransaction()) {
        $conn->rollBack();
    }
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => 'Gagal membuat pesanan: ' . $e->getMessage()]);
}