<?php
include("../../db/connect.php");

if (isset($_GET['rekening'])) {
    $rekening = $_GET['rekening'];

    $stmt = $conn->prepare("SELECT n.nama_lengkap 
                            FROM rekening r 
                            JOIN nasabah n ON r.id_nasabah = n.id_nasabah 
                            WHERE r.nomor_rekening = ? AND r.status_rekening = 'AKTIF'");
    $stmt->bind_param("s", $rekening);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($data = $result->fetch_assoc()) {
        echo $data['nama_lengkap'];
    } else {
        echo '';
    }

    $stmt->close();
    $conn->close();
}
?>
