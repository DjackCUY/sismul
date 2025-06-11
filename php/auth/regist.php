<?php
include("../db/connect.php");

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $nama = $_POST['nama'];
    $alamat = $_POST['alamat'];
    $telepon = $_POST['telepon'];
    $jenis = $_POST['jenis'];
    $email = $_POST['email'];
    $password = $_POST['password'];

    $hashed_password = password_hash($password, PASSWORD_DEFAULT);

    $stmt = $conn->prepare("INSERT INTO nasabah (nama_lengkap, alamat, nomor_telepon, email, password, jenis_kelamin, tanggal_daftar) VALUES (?, ?, ?, ?, ?, ?, NOW())");
    $stmt->bind_param("ssssss", $nama, $alamat, $telepon, $email, $hashed_password, $jenis);

    if ($stmt->execute()) {
        $id_nasabah_baru = $conn->insert_id;

        $nomor_rekening = date("ymdHis") . rand(10, 99);

        $id_jenis = 1;

        $saldo_awal = 100000;

        $pin = "123456";

        $stmt2 = $conn->prepare("INSERT INTO rekening (nomor_rekening, id_nasabah, id_jenis, saldo, tanggal_buka, status_rekening, pin) VALUES (?, ?, ?, ?, CURDATE(), 'AKTIF', ?)");
        $stmt2->bind_param("siids", $nomor_rekening, $id_nasabah_baru, $id_jenis, $saldo_awal, $pin);

        if ($stmt2->execute()) {
            header("Location: ../../index.php");
            exit();
        } else {
            echo "Gagal membuat rekening: " . $stmt2->error;
        }

        $stmt2->close();
    } else {
        echo "Gagal mendaftar: " . $stmt->error;
    }

    $stmt->close();
    $conn->close();
}
?>
