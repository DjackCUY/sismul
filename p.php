<?php
session_start();
include "php/db/connect.php";

// Cek apakah user sudah login
if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit();
}

$user = $_SESSION['user'];
$id_nasabah = $user['id_nasabah'];

// Ambil data rekening nasabah
$sql_rekening = "SELECT r.*, jr.nama_jenis 
                 FROM rekening r 
                 JOIN jenis_rekening jr ON r.id_jenis = jr.id_jenis 
                 WHERE r.id_nasabah = ? AND r.status_rekening = 'AKTIF'";
$stmt_rekening = $conn->prepare($sql_rekening);
$stmt_rekening->bind_param("i", $id_nasabah);
$stmt_rekening->execute();
$result_rekening = $stmt_rekening->get_result();
$rekening = $result_rekening->fetch_assoc();

// Jika tidak ada rekening aktif, redirect atau tampilkan pesan
if (!$rekening) {
    $rekening = ['nomor_rekening' => 'Tidak ada rekening', 'saldo' => 0];
}

$nomor_rekening = $rekening['nomor_rekening'];

// Ambil transaksi terbaru (30 hari terakhir)
$sql_transaksi = "SELECT t.*, jt.nama_transaksi, jt.kode_transaksi,
                         CASE 
                             WHEN t.id_jenis_transaksi IN (1, 6) THEN 'KREDIT'
                             WHEN t.nomor_rekening = ? AND t.nomor_rekening_tujuan IS NOT NULL THEN 'DEBIT'
                             WHEN t.nomor_rekening != ? AND t.nomor_rekening_tujuan = ? THEN 'KREDIT'
                             ELSE 'DEBIT'
                         END as tipe_transaksi
                  FROM transaksi t
                  JOIN jenis_transaksi jt ON t.id_jenis_transaksi = jt.id_jenis_transaksi
                  WHERE (t.nomor_rekening = ? OR t.nomor_rekening_tujuan = ?)
                  AND t.tanggal_transaksi >= DATE_SUB(NOW(), INTERVAL 30 DAY)
                  ORDER BY t.tanggal_transaksi DESC
                  LIMIT 10";
$stmt_transaksi = $conn->prepare($sql_transaksi);
$stmt_transaksi->bind_param("sssss", $nomor_rekening, $nomor_rekening, $nomor_rekening, $nomor_rekening, $nomor_rekening);
$stmt_transaksi->execute();
$result_transaksi = $stmt_transaksi->get_result();

// Hitung ringkasan keuangan (7 hari terakhir)
$sql_summary = "SELECT 
                    SUM(CASE WHEN (t.id_jenis_transaksi IN (1, 6) OR (t.nomor_rekening != ? AND t.nomor_rekening_tujuan = ?)) THEN t.jumlah ELSE 0 END) as total_pemasukan,
                    SUM(CASE WHEN (t.id_jenis_transaksi NOT IN (1, 6) AND t.nomor_rekening = ?) THEN t.jumlah ELSE 0 END) as total_pengeluaran,
                    COUNT(*) as total_transaksi
                FROM transaksi t
                WHERE (t.nomor_rekening = ? OR t.nomor_rekening_tujuan = ?)
                AND t.tanggal_transaksi >= DATE_SUB(NOW(), INTERVAL 7 DAY)";
$stmt_summary = $conn->prepare($sql_summary);
$stmt_summary->bind_param("sssss", $nomor_rekening, $nomor_rekening, $nomor_rekening, $nomor_rekening, $nomor_rekening);
$stmt_summary->execute();
$result_summary = $stmt_summary->get_result();
$summary = $result_summary->fetch_assoc();

$total_pemasukan = $summary['total_pemasukan'] ?? 0;
$total_pengeluaran = $summary['total_pengeluaran'] ?? 0;
$saldo_bersih = $total_pemasukan - $total_pengeluaran;
$rata_rata_harian = $summary['total_transaksi'] > 0 ? $saldo_bersih / 7 : 0;

// Format mata uang
function formatRupiah($angka) {
    return "Rp " . number_format($angka, 0, ',', '.');
}

// Ambil data untuk kategori pengeluaran tertinggi
$sql_kategori = "SELECT jt.nama_transaksi, COUNT(*) as jumlah_transaksi, SUM(t.jumlah) as total_jumlah
                 FROM transaksi t
                 JOIN jenis_transaksi jt ON t.id_jenis_transaksi = jt.id_jenis_transaksi
                 WHERE t.nomor_rekening = ? 
                 AND t.id_jenis_transaksi NOT IN (1, 6)
                 AND t.tanggal_transaksi >= DATE_SUB(NOW(), INTERVAL 7 DAY)
                 GROUP BY t.id_jenis_transaksi, jt.nama_transaksi
                 ORDER BY total_jumlah DESC
                 LIMIT 3";
$stmt_kategori = $conn->prepare($sql_kategori);
$stmt_kategori->bind_param("s", $nomor_rekening);
$stmt_kategori->execute();
$result_kategori = $stmt_kategori->get_result();
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AlimBank - Mobile Banking</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <div class="phone-container">
        <!-- Report Screen -->
        <div class="report-screen" id="reportScreen">
            <div class="screen-header">
                <button class="back-btn" onclick="showMain()">←</button>
                <div class="screen-title">Laporan Keuangan</div>
            </div>

            <div class="report-filters">
                <button class="filter-btn active" onclick="setReportFilter('week', this)">7 Hari</button>
                <button class="filter-btn" onclick="setReportFilter('month', this)">1 Bulan</button>
                <button class="filter-btn" onclick="setReportFilter('year', this)">1 Tahun</button>
            </div>

            <div class="report-summary">
                <div class="summary-row">
                    <span class="summary-label">Total Pemasukan</span>
                    <span class="summary-value positive" id="totalIncome">+<?= formatRupiah($total_pemasukan) ?></span>
                </div>
                <div class="summary-row">
                    <span class="summary-label">Total Pengeluaran</span>
                    <span class="summary-value negative" id="totalExpense">-<?= formatRupiah($total_pengeluaran) ?></span>
                </div>
                <div class="summary-row">
                    <span class="summary-label">Saldo Bersih</span>
                    <span class="summary-value <?= $saldo_bersih >= 0 ? 'positive' : 'negative' ?>" id="netBalance"><?= $saldo_bersih >= 0 ? '+' : '' ?><?= formatRupiah($saldo_bersih) ?></span>
                </div>
                <div class="summary-row">
                    <span class="summary-label">Rata-rata Harian</span>
                    <span class="summary-value" id="dailyAverage"><?= formatRupiah(abs($rata_rata_harian)) ?></span>
                </div>
            </div>

            <div class="chart-container">
                <div class="chart-placeholder">
                    <div style="font-size: 48px; margin-bottom: 10px;">📊</div>
                    <div>Grafik Transaksi</div>
                    <div style="font-size: 12px; opacity: 0.6; margin-top: 5px;">Fitur visualisasi akan ditambahkan</div>
                </div>
            </div>

            <div class="features-section">
                <div class="section-title">
                    <span>📈 Kategori Pengeluaran Tertinggi</span>
                </div>
                <?php if ($result_kategori->num_rows > 0): ?>
                    <?php while ($kategori = $result_kategori->fetch_assoc()): ?>
                        <div class="activity-item">
                            <div class="activity-info">
                                <div class="activity-title"><?= htmlspecialchars($kategori['nama_transaksi']) ?></div>
                                <div class="activity-date"><?= $kategori['jumlah_transaksi'] ?> transaksi</div>
                            </div>
                            <div class="activity-amount">-<?= formatRupiah($kategori['total_jumlah']) ?></div>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <div class="activity-item">
                        <div class="activity-info">
                            <div class="activity-title">Tidak ada transaksi</div>
                            <div class="activity-date">7 hari terakhir</div>
                        </div>
                        <div class="activity-amount">Rp 0</div>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Settings Screen -->
        <div class="settings-screen" id="settingsScreen">
            <div class="screen-header">
                <button class="back-btn" onclick="showMain()">←</button>
                <div class="screen-title">Pengaturan</div>
            </div>

            <div class="profile-section">
                <div class="profile-avatar"><?= strtoupper(substr($user['nama_lengkap'], 0, 2)) ?></div>
                <div class="profile-name"><?= htmlspecialchars($user['nama_lengkap']) ?></div>
                <div class="profile-email"><?= htmlspecialchars($user['email']) ?></div>
                <button class="edit-profile-btn">Edit Profil</button>
            </div>

            <div class="settings-section">
                <div class="settings-title">🔐 Keamanan</div>
                <div class="setting-item" onclick="toggleSetting('biometric')">
                    <div class="setting-left">
                        <div class="setting-icon">👆</div>
                        <div class="setting-info">
                            <div class="setting-name">Login Biometrik</div>
                            <div class="setting-desc">Gunakan sidik jari atau Face ID</div>
                        </div>
                    </div>
                    <div class="toggle-switch active" id="biometric-toggle"></div>
                </div>
                <div class="setting-item">
                    <div class="setting-left">
                        <div class="setting-icon">🔑</div>
                        <div class="setting-info">
                            <div class="setting-name">Ubah PIN</div>
                            <div class="setting-desc">Ganti PIN transaksi Anda</div>
                        </div>
                    </div>
                    <div class="setting-right">
                        <span>→</span>
                    </div>
                </div>
                <div class="setting-item">
                    <div class="setting-left">
                        <div class="setting-icon">🔒</div>
                        <div class="setting-info">
                            <div class="setting-name">Ubah Password</div>
                            <div class="setting-desc">Ganti password login Anda</div>
                        </div>
                    </div>
                    <div class="setting-right">
                        <span>→</span>
                    </div>
                </div>
            </div>

            <div class="settings-section">
                <div class="settings-title">🔔 Notifikasi</div>
                <div class="setting-item" onclick="toggleSetting('push')">
                    <div class="setting-left">
                        <div class="setting-icon">📱</div>
                        <div class="setting-info">
                            <div class="setting-name">Push Notification</div>
                            <div class="setting-desc">Notifikasi transaksi & promo</div>
                        </div>
                    </div>
                    <div class="toggle-switch active" id="push-toggle"></div>
                </div>
                <div class="setting-item" onclick="toggleSetting('email')">
                    <div class="setting-left">
                        <div class="setting-icon">📧</div>
                        <div class="setting-info">
                            <div class="setting-name">Email Notification</div>
                            <div class="setting-desc">Laporan bulanan via email</div>
                        </div>
                    </div>
                    <div class="toggle-switch" id="email-toggle"></div>
                </div>
                <div class="setting-item" onclick="toggleSetting('sms')">
                    <div class="setting-left">
                        <div class="setting-icon">💬</div>
                        <div class="setting-info">
                            <div class="setting-name">SMS Notification</div>
                            <div class="setting-desc">Konfirmasi transaksi via SMS</div>
                        </div>
                    </div>
                    <div class="toggle-switch active" id="sms-toggle"></div>
                </div>
            </div>

            <div class="settings-section">
                <div class="settings-title">⚙️ Umum</div>
                <div class="setting-item">
                    <div class="setting-left">
                        <div class="setting-icon">🌐</div>
                        <div class="setting-info">
                            <div class="setting-name">Bahasa</div>
                            <div class="setting-desc">Pilih bahasa aplikasi</div>
                        </div>
                    </div>
                    <div class="setting-right">
                        <span class="setting-value">Indonesia</span>
                        <span>→</span>
                    </div>
                </div>
                <div class="setting-item">
                    <div class="setting-left">
                        <div class="setting-icon">🌙</div>
                        <div class="setting-info">
                            <div class="setting-name">Mode Gelap</div>
                            <div class="setting-desc">Aktifkan tema gelap</div>
                        </div>
                    </div>
                    <div class="toggle-switch active" id="dark-toggle"></div>
                </div>
                <div class="setting-item">
                    <div class="setting-left">
                        <div class="setting-icon">💰</div>
                        <div class="setting-info">
                            <div class="setting-name">Mata Uang</div>
                            <div class="setting-desc">Format tampilan uang</div>
                        </div>
                    </div>
                    <div class="setting-right">
                        <span class="setting-value">IDR (Rp)</span>
                        <span>→</span>
                    </div>
                </div>
            </div>

            <div class="settings-section">
                <div class="settings-title">📞 Bantuan</div>
                <div class="setting-item">
                    <div class="setting-left">
                        <div class="setting-icon">❓</div>
                        <div class="setting-info">
                            <div class="setting-name">FAQ</div>
                            <div class="setting-desc">Pertanyaan yang sering ditanyakan</div>
                        </div>
                    </div>
                    <div class="setting-right">
                        <span>→</span>
                    </div>
                </div>
                <div class="setting-item">
                    <div class="setting-left">
                        <div class="setting-icon">💬</div>
                        <div class="setting-info">
                            <div class="setting-name">Live Chat</div>
                            <div class="setting-desc">Hubungi customer service</div>
                        </div>
                    </div>
                    <div class="setting-right">
                        <span>→</span>
                    </div>
                </div>
                <div class="setting-item">
                    <div class="setting-left">
                        <div class="setting-icon">📋</div>
                        <div class="setting-info">
                            <div class="setting-name">Syarat & Ketentuan</div>
                            <div class="setting-desc">Kebijakan penggunaan aplikasi</div>
                        </div>
                    </div>
                    <div class="setting-right">
                        <span>→</span>
                    </div>
                </div>
                <div class="setting-item">
                    <div class="setting-left">
                        <div class="setting-icon">🔒</div>
                        <div class="setting-info">
                            <div class="setting-name">Kebijakan Privasi</div>
                            <div class="setting-desc">Perlindungan data pribadi</div>
                        </div>
                    </div>
                    <div class="setting-right">
                        <span>→</span>
                    </div>
                </div>
            </div>
            <a href="php/auth/logout.php"><button class="logout-btn">Keluar dari Akun</button></a>
        </div>

        <!-- Main Screen -->
        <div class="main-screen" id="mainScreen">
            <div class="header">
                <div>
                    <div class="greeting">
                        <?php
                        $hour = date('H');
                        if ($hour < 12) {
                            echo "Selamat Pagi";
                        } elseif ($hour < 17) {
                            echo "Selamat Siang";
                        } elseif ($hour < 19) {
                            echo "Selamat Sore";
                        } else {
                            echo "Selamat Malam";
                        }
                        ?>
                    </div>
                    <div class="user-name"><?= htmlspecialchars($user['nama_lengkap']) ?></div>
                </div>
                <div class="profile-pic"><?= strtoupper(substr($user['nama_lengkap'], 0, 2)) ?></div>
            </div>

            <div class="balance-card">
                <div class="balance-label">Saldo Rekening</div>
                <div class="balance-amount" id="balanceAmount"><?= formatRupiah($rekening['saldo']) ?></div>
                <h5><?= htmlspecialchars($rekening['nomor_rekening']) ?></h5>
            </div>

            <div class="features-section">
                <div class="section-title">
                    <span>🎁 Promo & Pengingat</span>
                    <span>+</span>
                </div>
                <div class="feature-grid">
                    <div class="feature-card">
                        <div class="feature-icon">💳</div>
                        <div class="feature-title">Diskon hingga</div>
                        <div class="feature-amount">Rp 15.250</div>
                        <div class="feature-subtitle">setiap transaksi non-tunai</div>
                    </div>
                    <div class="feature-card">
                        <div class="feature-icon">💰</div>
                        <div class="feature-title">Bulanan</div>
                        <div class="feature-amount">Rp 180</div>
                        <div class="feature-subtitle">saving goal</div>
                    </div>
                </div>
            </div>

            <div class="features-section">
                <div class="section-title">
                    <span>📈 Aktivitas Mendatang</span>
                    <span>+</span>
                </div>
            </div>

            <div class="bottom-nav">
                <div class="nav-items">
                    <div class="nav-item active" onclick="showMain()">
                        <div class="nav-icon">🏠</div>
                        <div class="nav-label">Home</div>
                    </div>
                    <div class="nav-item" onclick="showActivity()">
                        <div class="nav-icon">📊</div>
                        <div class="nav-label">Transaksi</div>
                    </div>
                    <div class="nav-item" onclick="showTransfer()">
                        <div class="nav-icon">💸</div>
                        <div class="nav-label">Transfer</div>
                    </div>
                    <div class="nav-item" onclick="showReport()">
                        <div class="nav-icon">📋</div>
                        <div class="nav-label">Laporan</div>
                    </div>
                    <div class="nav-item" onclick="showSettings()">
                        <div class="nav-icon">⚙️</div>
                        <div class="nav-label">Setting</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Transfer Screen -->
        <div class="transfer-screen" id="transferScreen">
            <div class="screen-header">
                <button class="back-btn" onclick="showMain()">←</button>
                <div class="screen-title">Transfer Dana</div>
            </div>

            <form class="transfer-form" id="transferForm" method="POST" action="php/transfer/process.php">
                <div class="form-group">
                    <label class="form-label">Nomor Rekening Tujuan</label>
                    <input type="text" class="form-input" placeholder="Masukkan nomor rekening" id="accountNumber" name="nomor_rekening_tujuan" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Nama Penerima</label>
                    <input type="text" class="form-input" placeholder="Nama akan muncul otomatis" id="recipientName" name="nama_penerima" readonly>
                </div>
                <div class="form-group">
                    <label class="form-label">Jumlah Transfer</label>
                    <input type="number" class="form-input" placeholder="0" id="transferAmount" name="jumlah" min="10000" max="<?= $rekening['saldo'] ?>" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Catatan (Opsional)</label>
                    <input type="text" class="form-input" placeholder="Tambahkan catatan" id="transferNote" name="keterangan">
                </div>
                <button type="submit" class="submit-btn">Transfer Sekarang</button>
            </form>
        </div>

        <!-- Success Screen -->
        <div class="success-screen" id="successScreen">
            <div class="success-icon">✓</div>
            <div class="success-title">Transaksi Berhasil</div>
            <div class="transaction-details">
                <div class="detail-row">
                    <span class="detail-label">Tujuan:</span>
                    <span class="detail-value" id="successRecipient">-</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Nomor Transaksi:</span>
                    <span class="detail-value" id="transactionId">#-</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Tanggal & Waktu:</span>
                    <span class="detail-value" id="transactionDate"><?= date('M d, Y | g:i:s A') ?></span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Sumber Dana:</span>
                    <span class="detail-value"><?= htmlspecialchars($user['nama_lengkap']) ?></span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Nomor Rekening Asal:</span>
                    <span class="detail-value"><?= htmlspecialchars($rekening['nomor_rekening']) ?></span>
                </div>
            </div>
            <div class="amount-display" id="successAmount">Rp 0</div>
            <div class="action-buttons">
                <button class="secondary-btn">Bagikan</button>
                <button class="primary-btn" onclick="showMain()">Selesai</button>
            </div>
        </div>

        <!-- Activity Screen -->
        <div class="activity-screen" id="activityScreen">
            <div class="screen-header">
                <button class="back-btn" onclick="showMain()">←</button>
                <div class="screen-title">Aktivitas Transaksi</div>
            </div>

            <div class="activity-list">
                <?php if ($result_transaksi->num_rows > 0): ?>
                    <?php 
                    // Reset pointer untuk membaca ulang
                    $result_transaksi->data_seek(0);
                    while ($transaksi = $result_transaksi->fetch_assoc()): 
                        $is_kredit = ($transaksi['tipe_transaksi'] == 'KREDIT');
                        $amount_display = ($is_kredit ? '+' : '-') . formatRupiah($transaksi['jumlah']);
                        $date_formatted = date('d M Y, H:i', strtotime($transaksi['tanggal_transaksi']));
                    ?>
                        <div class="activity-item">
                            <div class="activity-info">
                                <div class="activity-title">
                                    <?php if ($transaksi['kode_transaksi'] == 'TR' && !$is_kredit): ?>
                                        Transfer ke Rekening <?= htmlspecialchars($transaksi['nomor_rekening_tujuan']) ?>
                                    <?php elseif ($transaksi['kode_transaksi'] == 'TR' && $is_kredit): ?>
                                        Transfer dari Rekening <?= htmlspecialchars($transaksi['nomor_rekening']) ?>
                                    <?php else: ?>
                                        <?= htmlspecialchars($transaksi['nama_transaksi']) ?>
                                    <?php endif; ?>
                                </div>
                                <div class="activity-date"><?= $date_formatted ?></div>
                            </div>
                            <div class="activity-amount" style="color: <?= $is_kredit ? '#4CAF50' : '#f44336' ?>">
                                <?= $amount_display ?>
                            </div>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <div class="activity-item">
                        <div class="activity-info">
                            <div class="activity-title">Belum ada transaksi</div>
                            <div class="activity-date">30 hari terakhir</div>
                        </div>
                        <div class="activity-amount">Rp 0</div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <script src="js/main.js"></script>
</body>
</html>