<?php
include("../../db/connect.php");
session_start();

header('Content-Type: application/json');

$user = $_SESSION['user'];
$rekening = $user['nomor_rekening'] ?? null;

if (!$rekening) {
    echo json_encode(["error" => "Nomor rekening tidak disediakan"]);
    exit;
}

// Query saldo
$stmtSaldo = $conn->prepare("SELECT r.saldo, n.nama_lengkap, n.email, n.nomor_telepon 
                             FROM rekening r 
                             JOIN nasabah n ON r.id_nasabah = n.id_nasabah 
                             WHERE r.nomor_rekening = ?");
$stmtSaldo->bind_param("s", $rekening);
$stmtSaldo->execute();
$resultSaldo = $stmtSaldo->get_result();

if ($row = $resultSaldo->fetch_assoc()) {
    echo json_encode([
        "saldo" => (float)$row['saldo'],
        "user" => [
            "nama" => $row['nama_lengkap'],
            "email" => $row['email'],
            "telepon" => $row['nomor_telepon']
        ]
    ]);
} else {
    echo json_encode(["error" => "Rekening tidak ditemukan"]);
}

$stmtSaldo->close();
$conn->close();
