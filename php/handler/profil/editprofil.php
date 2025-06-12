<?php
include '../../db/connect.php';
session_start();

// Pastikan user login dan memiliki id_nasabah
$user = $_SESSION['user'];
$id_nasabah = $user['id_nasabah']?? null;

if (!$id_nasabah) {
    echo "Akses ditolak. Silakan login terlebih dahulu.";
    exit;
}

// Tangkap data dari form
$nama_lengkap   = $_POST['nama_lengkap'] ?? '';
$alamat         = $_POST['alamat'] ?? '';
$nomor_telepon  = $_POST['nomor_telepon'] ?? '';
$email          = $_POST['email'] ?? '';
$password       = $_POST['password'] ?? '';
$jenis_kelamin  = $_POST['jenis_kelamin'] ?? '';

// Validasi sederhana
if (empty($nama_lengkap) || empty($alamat) || empty($nomor_telepon) || empty($email) || empty($password) || empty($jenis_kelamin)) {
    echo "Semua field wajib diisi.";
    exit;
}

// Enkripsi password (gunakan bcrypt)
$hashed_password = password_hash($password, PASSWORD_BCRYPT);

// Update ke database
$query = "UPDATE nasabah SET 
            nama_lengkap = ?,
            alamat = ?,
            nomor_telepon = ?,
            email = ?,
            password = ?,
            jenis_kelamin = ?
            WHERE id_nasabah = ?";

$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, "ssssssi", $nama_lengkap, $alamat, $nomor_telepon, $email, $hashed_password, $jenis_kelamin, $id_nasabah);

if (mysqli_stmt_execute($stmt)) {
    header("Location: ../../../dashboard.php");
} else {
    echo "Gagal memperbarui profil: " . mysqli_error($conn);
}
?>
