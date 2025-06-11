<?php
include("../db/connect.php");

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $nama = $_POST['nama'];
    $alamat = $_POST['alamat'];
    $telepon = $_POST['telepon'];
    $jenis = $_POST['jenis'];
    $email = $_POST['email'];
    $password = $_POST['password'];

    // Hash password sebelum disimpan
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);

    // Siapkan SQL statement
    $stmt = $conn->prepare("INSERT INTO nasabah (nama_lengkap, alamat, nomor_telepon, email, password, jenis_kelamin) VALUES (?, ?, ?, ?, ?, ?)");

    // Bind parameter ke query: s = string
    $stmt->bind_param("ssssss", $nama, $alamat, $telepon, $email, $hashed_password, $jenis);

    // Eksekusi dan redirect
    if ($stmt->execute()) {
        header("Location: ../../index.php");
        exit();
    } else {
        echo "Gagal menambahkan data: " . $stmt->error;
    }

    // Tutup statement dan koneksi
    $stmt->close();
    $conn->close();
}
?>