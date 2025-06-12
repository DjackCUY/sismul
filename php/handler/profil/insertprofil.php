<?php
include '../../db/connect.php'; // koneksi ke database

session_start();
$id_nasabah = $_SESSION['id_nasabah'] ?? null;

if (!$id_nasabah) {
    echo "Anda belum login.";
    exit;
}

// Ambil data nasabah dari database
$query = mysqli_query($koneksi, "SELECT * FROM nasabah WHERE id_nasabah = '$id_nasabah'");
$data = mysqli_fetch_assoc($query);

// Simpan perubahan
if (isset($_POST['update'])) {
    $nama_lengkap = $_POST['nama_lengkap'];
    $alamat = $_POST['alamat'];
    $nomor_telepon = $_POST['nomor_telepon'];
    $email = $_POST['email'];
    $jenis_kelamin = $_POST['jenis_kelamin'];

    $update = mysqli_query($koneksi, "UPDATE nasabah SET 
        nama_lengkap = '$nama_lengkap',
        alamat = '$alamat',
        nomor_telepon = '$nomor_telepon',
        email = '$email',
        jenis_kelamin = '$jenis_kelamin'
        WHERE id_nasabah = '$id_nasabah'");

    if ($update) {
        echo "<script>alert('Profil berhasil diperbarui!'); window.location='edit_profil.php';</script>";
    } else {
        echo "Gagal memperbarui profil: " . mysqli_error($koneksi);
    }
}
?>

<!-- <!DOCTYPE html>
<html>
<head>
    <title>Edit Profil Nasabah</title>
</head>
<body>
    <h2>Edit Profil</h2>
    <form method="POST">
        <label>Nama Lengkap:</label><br>
        <input type="text" name="nama_lengkap" value="<?= $data['nama_lengkap'] ?>" required><br><br>

        <label>Alamat:</label><br>
        <textarea name="alamat"><?= $data['alamat'] ?></textarea><br><br>

        <label>Nomor Telepon:</label><br>
        <input type="text" name="nomor_telepon" value="<?= $data['nomor_telepon'] ?>"><br><br>

        <label>Email:</label><br>
        <input type="email" name="email" value="<?= $data['email'] ?>"><br><br>

        <label>Jenis Kelamin:</label><br>
        <select name="jenis_kelamin">
            <option value="L" <?= $data['jenis_kelamin'] == 'L' ? 'selected' : '' ?>>Laki-laki</option>
            <option value="P" <?= $data['jenis_kelamin'] == 'P' ? 'selected' : '' ?>>Perempuan</option>
        </select><br><br>

        <button type="submit" name="update">Simpan Perubahan</button>
    </form>
</body>
</html> -->
