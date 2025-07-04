<?php
session_start();
include("../db/connect.php");

$error = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);

    $stmt = $conn->prepare("SELECT n.*, r.nomor_rekening 
    FROM nasabah n 
    JOIN rekening r ON n.id_nasabah = r.id_nasabah 
    WHERE n.email = ? AND r.status_rekening = 'AKTIF' LIMIT 1");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($user = $result->fetch_assoc()) {
        if (password_verify($password, $user['password'])) {
            $_SESSION['user'] = $user;

            header("Location: ../../dashboard.php");
            exit();
        } else {
            $_SESSION['error'] = "Password salah!";
            header("Location: ../../index.php");
            exit();
        }
    } else {
        $_SESSION['error'] = "Username tidak ditemukan!";
        header("Location: ../../index.php");
        exit();
    }
}
?>
