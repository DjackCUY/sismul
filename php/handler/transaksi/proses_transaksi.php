<?php
session_start();
include("../../db/connect.php");

// Ambil data user dari session
if (!isset($_SESSION['user'])) {
    die("Anda harus login.");
}


$pengirim = $_SESSION['user']['nomor_rekening'];
$tujuan = $_POST['rekening_tujuan'];
$jumlah = floatval($_POST['jumlah']);
$catatan = $_POST['catatan'] ?? '';

if ($pengirim === $tujuan) {
    die("Tidak bisa transfer ke rekening sendiri.");
}

$conn->begin_transaction();

try {
    // Ambil data rekening pengirim
    $stmt1 = $conn->prepare("SELECT saldo FROM rekening WHERE nomor_rekening = ?");
    $stmt1->bind_param("s", $pengirim);
    $stmt1->execute();
    $result1 = $stmt1->get_result();
    if (!$result1 || $result1->num_rows === 0) {
        throw new Exception("Rekening pengirim tidak ditemukan.");
    }
    $data_pengirim = $result1->fetch_assoc();
    $saldo_pengirim = $data_pengirim['saldo'];

    // Cek saldo cukup
    if ($jumlah > $saldo_pengirim) {
        throw new Exception("Saldo tidak cukup.");
    }

    // Ambil data rekening penerima
    $stmt2 = $conn->prepare("SELECT saldo FROM rekening WHERE nomor_rekening = ?");
    $stmt2->bind_param("s", $tujuan);
    $stmt2->execute();
    $result2 = $stmt2->get_result();
    if (!$result2 || $result2->num_rows === 0) {
        throw new Exception("Rekening tujuan tidak ditemukan.");
    }
    $data_penerima = $result2->fetch_assoc();
    $saldo_penerima = $data_penerima['saldo'];

    // Hitung saldo baru
    $saldo_pengirim_baru = $saldo_pengirim - $jumlah;
    $saldo_penerima_baru = $saldo_penerima + $jumlah;

    $id_jenis_transfer = 3; // Transfer Antar Rekening

    // Simpan transaksi pengirim
    $stmt3 = $conn->prepare("INSERT INTO transaksi 
        (nomor_rekening, id_jenis_transaksi, nomor_rekening_tujuan, jumlah, saldo_sebelum, saldo_sesudah, keterangan, id_teller) 
        VALUES (?, ?, ?, ?, ?, ?, ?, NULL)");
    $keterangan_pengirim = "Transfer ke $tujuan. $catatan";
    $stmt3->bind_param("sisddds", $pengirim, $id_jenis_transfer, $tujuan, $jumlah, $saldo_pengirim, $saldo_pengirim_baru, $keterangan_pengirim);
    $stmt3->execute();

    // Simpan transaksi penerima
    $stmt4 = $conn->prepare("INSERT INTO transaksi 
        (nomor_rekening, id_jenis_transaksi, nomor_rekening_tujuan, jumlah, saldo_sebelum, saldo_sesudah, keterangan, id_teller) 
        VALUES (?, ?, ?, ?, ?, ?, ?, NULL)");
    $keterangan_penerima = "Penerimaan transfer dari $pengirim. $catatan";
    $stmt4->bind_param("sisddds", $tujuan, $id_jenis_transfer, $pengirim, $jumlah, $saldo_penerima, $saldo_penerima_baru, $keterangan_penerima);
    $stmt4->execute();

    // Update saldo pengirim
    $stmt5 = $conn->prepare("UPDATE rekening SET saldo = ? WHERE nomor_rekening = ?");
    $stmt5->bind_param("ds", $saldo_pengirim_baru, $pengirim);
    $stmt5->execute();

    // Update saldo penerima
    $stmt6 = $conn->prepare("UPDATE rekening SET saldo = ? WHERE nomor_rekening = ?");
    $stmt6->bind_param("ds", $saldo_penerima_baru, $tujuan);
    $stmt6->execute();

    $conn->commit();

    echo "BERHASIL";
    exit();

} catch (Exception $e) {
    $conn->rollback();
    echo "GAGAL: " . $e->getMessage();
}

// Tutup statement
@$stmt1->close();
@$stmt2->close();
@$stmt3->close();
@$stmt4->close();
@$stmt5->close();
@$stmt6->close();
$conn->close();
?>
