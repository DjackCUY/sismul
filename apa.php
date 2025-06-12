<?php
session_start();
include "php/db/connect.php";

// Cek apakah user sudah login
if (!isset($_SESSION['user'])) {
    header("Location: index.php");
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

// Fungsi untuk generate rekomendasi berdasarkan saldo dan aktivitas
function generateRecommendations($saldo, $total_pengeluaran, $total_pemasukan) {
    $recommendations = [];
    
    // Rekomendasi berdasarkan saldo
    if ($saldo > 10000000) {
        $recommendations[] = [
            'icon' => 'bi-graph-up-arrow',
            'title' => 'Investasi Deposito',
            'subtitle' => 'Bunga hingga 6.5% p.a',
            'amount' => 'Mulai 10 Juta',
            'type' => 'investment'
        ];
    } elseif ($saldo > 5000000) {
        $recommendations[] = [
            'icon' => 'bi-piggy-bank',
            'title' => 'Tabungan Premium',
            'subtitle' => 'Bunga lebih tinggi',
            'amount' => 'Upgrade Gratis',
            'type' => 'savings'
        ];
    } else {
        $recommendations[] = [
            'icon' => 'bi-wallet2',
            'title' => 'Target Menabung',
            'subtitle' => 'Capai impian Anda',
            'amount' => 'Mulai 50rb/hari',
            'type' => 'goal'
        ];
    }
    
    // Rekomendasi berdasarkan pengeluaran
    if ($total_pengeluaran > $total_pemasukan) {
        $recommendations[] = [
            'icon' => 'bi-shield-check',
            'title' => 'Proteksi Finansial',
            'subtitle' => 'Asuransi & Dana Darurat',
            'amount' => 'Mulai 100rb/bulan',
            'type' => 'protection'
        ];
    } else {
        $recommendations[] = [
            'icon' => 'bi-credit-card',
            'title' => 'Kartu Kredit',
            'subtitle' => 'Cashback hingga 5%',
            'amount' => 'Tanpa iuran tahunan',
            'type' => 'credit'
        ];
    }
    
    return array_slice($recommendations, 0, 2);
}

$recommendations = generateRecommendations($rekening['saldo'], $total_pengeluaran, $total_pemasukan);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AlimBank - Mobile Banking</title>
    <link rel="stylesheet" href="css/style.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">
    <!-- Tambahkan di dalam tag <style> yang sudah ada di dashboard.php -->
<style>

/* Perbaikan hover navbar - area yang lebih kecil dan rapi */
.bottom-nav .nav-item {
    padding: 6px 4px;
    margin: 0 2px;
    border-radius: 6px;
    min-width: 55px;
    transition: all 0.2s ease;
}

.bottom-nav .nav-item:hover {
    background-color: rgba(255, 255, 255, 0.03);
    transform: translateY(-0.5px);
}

.bottom-nav .nav-item.active {
    background-color: rgba(76, 175, 80, 0.15);
}

/* Jika ingin lebih kecil lagi */
.bottom-nav .nav-items {
    gap: 2px;
    padding: 0 4px;
}

.audio-settings {
    padding: 15px;
    margin: 10px 0;
    background: rgba(255, 255, 255, 0.05);
    border-radius: 10px;
}

.volume-control {
    display: flex;
    align-items: center;
    gap: 10px;
    margin-top: 10px;
}

.volume-slider {
    flex: 1;
    height: 4px;
    background: rgba(255, 255, 255, 0.2);
    border-radius: 2px;
    outline: none;
    -webkit-appearance: none;
}

.volume-slider::-webkit-slider-thumb {
    -webkit-appearance: none;
    width: 16px;
    height: 16px;
    background: #4CAF50;
    border-radius: 50%;
    cursor: pointer;
}

.sound-test-btn {
    background: rgba(76, 175, 80, 0.2);
    color: #4CAF50;
    border: 1px solid #4CAF50;
    padding: 8px 12px;
    border-radius: 6px;
    font-size: 12px;
    cursor: pointer;
    transition: all 0.2s;
}

.sound-test-btn:hover {
    background: rgba(76, 175, 80, 0.3);
}
</style>
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
                    <span class="summary-label"><i class="bi bi-arrow-down-circle text-success"></i> Total Pemasukan</span>
                    <span class="summary-value positive" id="totalIncome">+<?= formatRupiah($total_pemasukan) ?></span>
                </div>
                <div class="summary-row">
                    <span class="summary-label"><i class="bi bi-arrow-up-circle text-danger"></i> Total Pengeluaran</span>
                    <span class="summary-value negative" id="totalExpense">-<?= formatRupiah($total_pengeluaran) ?></span>
                </div>
                <div class="summary-row">
                    <span class="summary-label"><i class="bi bi-calculator"></i> Saldo Bersih</span>
                    <span class="summary-value <?= $saldo_bersih >= 0 ? 'positive' : 'negative' ?>" id="netBalance"><?= $saldo_bersih >= 0 ? '+' : '' ?><?= formatRupiah($saldo_bersih) ?></span>
                </div>
                <div class="summary-row">
                    <span class="summary-label"><i class="bi bi-graph-up"></i> Rata-rata Harian</span>
                    <span class="summary-value" id="dailyAverage"><?= formatRupiah(abs($rata_rata_harian)) ?></span>
                </div>
            </div>

            <div class="chart-container">
                <div class="chart-placeholder">
                    <div style="font-size: 48px; margin-bottom: 10px;"><i class="bi bi-bar-chart"></i></div>
                    <div>Grafik Transaksi</div>
                    <div style="font-size: 12px; opacity: 0.6; margin-top: 5px;">Fitur visualisasi akan ditambahkan</div>
                </div>
            </div>

            <div class="features-section">
                <div class="section-title">
                    <span><i class="bi bi-pie-chart"></i> Kategori Pengeluaran Tertinggi</span>
                </div>
                <?php if ($result_kategori->num_rows > 0): ?>
                    <?php while ($kategori = $result_kategori->fetch_assoc()): ?>
                        <div class="activity-item">
                            <div class="activity-info">
                                <div class="activity-title">
                                    <i class="bi bi-receipt"></i> <?= htmlspecialchars($kategori['nama_transaksi']) ?>
                                </div>
                                <div class="activity-date"><?= $kategori['jumlah_transaksi'] ?> transaksi</div>
                            </div>
                            <div class="activity-amount">-<?= formatRupiah($kategori['total_jumlah']) ?></div>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <div class="activity-item">
                        <div class="activity-info">
                            <div class="activity-title"><i class="bi bi-inbox"></i> Tidak ada transaksi</div>
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
                <button class="edit-profile-btn"><i class="bi bi-pencil"></i> Edit Profil</button>
            </div>

            <div class="settings-section">
                <div class="settings-title"><i class="bi bi-shield-lock"></i> Keamanan</div>
                <div class="setting-item" onclick="toggleSetting('biometric')">
                    <div class="setting-left">
                        <div class="setting-icon"><i class="bi bi-fingerprint"></i></div>
                        <div class="setting-info">
                            <div class="setting-name">Login Biometrik</div>
                            <div class="setting-desc">Gunakan sidik jari atau Face ID</div>
                        </div>
                    </div>
                    <div class="toggle-switch active" id="biometric-toggle"></div>
                </div>
                <div class="setting-item">
                    <div class="setting-left">
                        <div class="setting-icon"><i class="bi bi-key"></i></div>
                        <div class="setting-info">
                            <div class="setting-name">Ubah PIN</div>
                            <div class="setting-desc">Ganti PIN transaksi Anda</div>
                        </div>
                    </div>
                    <div class="setting-right">
                        <span><i class="bi bi-chevron-right"></i></span>
                    </div>
                </div>
                <div class="setting-item">
                    <div class="setting-left">
                        <div class="setting-icon"><i class="bi bi-lock"></i></div>
                        <div class="setting-info">
                            <div class="setting-name">Ubah Password</div>
                            <div class="setting-desc">Ganti password login Anda</div>
                        </div>
                    </div>
                    <div class="setting-right">
                        <span><i class="bi bi-chevron-right"></i></span>
                    </div>
                </div>
            </div>

<div class="settings-section">
    <div class="settings-title"><i class="bi bi-volume-up"></i> Audio & Suara</div>
    
    <div class="setting-item" onclick="toggleSetting('sound')">
        <div class="setting-left">
            <div class="setting-icon"><i class="bi bi-volume-up-fill"></i></div>
            <div class="setting-info">
                <div class="setting-name">Suara Notifikasi</div>
                <div class="setting-desc">Aktifkan suara untuk transaksi</div>
            </div>
        </div>
        <div class="toggle-switch active" id="sound-toggle"></div>
    </div>
    
    <div class="audio-settings">
        <div class="setting-name" style="font-size: 14px; margin-bottom: 10px;">Volume Notifikasi</div>
        <div class="volume-control">
            <i class="bi bi-volume-down"></i>
            <input type="range" class="volume-slider" id="volumeSlider" min="0" max="100" value="70">
            <i class="bi bi-volume-up"></i>
        </div>
        <div style="display: flex; gap: 8px; margin-top: 10px;">
            <button class="sound-test-btn" onclick="testSound('success')">
                <i class="bi bi-check-circle"></i> Test Berhasil
            </button>
            <button class="sound-test-btn" onclick="testSound('incoming')">
                <i class="bi bi-arrow-down-circle"></i> Test Masuk
            </button>
            <button class="sound-test-btn" onclick="testSound('failed')">
                <i class="bi bi-x-circle"></i> Test Gagal
            </button>
        </div>
    </div>
    
    <div class="setting-item" onclick="toggleSetting('vibration')">
        <div class="setting-left">
            <div class="setting-icon"><i class="bi bi-phone-vibrate"></i></div>
            <div class="setting-info">
                <div class="setting-name">Getaran</div>
                <div class="setting-desc">Bergetar saat ada notifikasi</div>
            </div>
        </div>
        <div class="toggle-switch active" id="vibration-toggle"></div>
    </div>
</div>

            <div class="settings-section">
                <div class="settings-title"><i class="bi bi-gear"></i> Umum</div>
                <div class="setting-item">
                    <div class="setting-left">
                        <div class="setting-icon"><i class="bi bi-globe"></i></div>
                        <div class="setting-info">
                            <div class="setting-name">Bahasa</div>
                            <div class="setting-desc">Pilih bahasa aplikasi</div>
                        </div>
                    </div>
                    <div class="setting-right">
                        <span class="setting-value">Indonesia</span>
                        <span><i class="bi bi-chevron-right"></i></span>
                    </div>
                </div>
                <div class="setting-item">
                    <div class="setting-left">
                        <div class="setting-icon"><i class="bi bi-moon"></i></div>
                        <div class="setting-info">
                            <div class="setting-name">Mode Gelap</div>
                            <div class="setting-desc">Aktifkan tema gelap</div>
                        </div>
                    </div>
                    <div class="toggle-switch active" id="dark-toggle"></div>
                </div>
                <div class="setting-item">
                    <div class="setting-left">
                        <div class="setting-icon"><i class="bi bi-currency-dollar"></i></div>
                        <div class="setting-info">
                            <div class="setting-name">Mata Uang</div>
                            <div class="setting-desc">Format tampilan uang</div>
                        </div>
                    </div>
                    <div class="setting-right">
                        <span class="setting-value">IDR (Rp)</span>
                        <span><i class="bi bi-chevron-right"></i></span>
                    </div>
                </div>
            </div>

            <div class="settings-section">
                <div class="settings-title"><i class="bi bi-headset"></i> Bantuan</div>
                <div class="setting-item">
                    <div class="setting-left">
                        <div class="setting-icon"><i class="bi bi-question-circle"></i></div>
                        <div class="setting-info">
                            <div class="setting-name">FAQ</div>
                            <div class="setting-desc">Pertanyaan yang sering ditanyakan</div>
                        </div>
                    </div>
                    <div class="setting-right">
                        <span><i class="bi bi-chevron-right"></i></span>
                    </div>
                </div>
                <div class="setting-item">
                    <div class="setting-left">
                        <div class="setting-icon"><i class="bi bi-chat-dots"></i></div>
                        <div class="setting-info">
                            <div class="setting-name">Live Chat</div>
                            <div class="setting-desc">Hubungi customer service</div>
                        </div>
                    </div>
                    <div class="setting-right">
                        <span><i class="bi bi-chevron-right"></i></span>
                    </div>
                </div>
                <div class="setting-item">
                    <div class="setting-left">
                        <div class="setting-icon"><i class="bi bi-file-text"></i></div>
                        <div class="setting-info">
                            <div class="setting-name">Syarat & Ketentuan</div>
                            <div class="setting-desc">Kebijakan penggunaan aplikasi</div>
                        </div>
                    </div>
                    <div class="setting-right">
                        <span><i class="bi bi-chevron-right"></i></span>
                    </div>
                </div>
                <div class="setting-item">
                    <div class="setting-left">
                        <div class="setting-icon"><i class="bi bi-shield-check"></i></div>
                        <div class="setting-info">
                            <div class="setting-name">Kebijakan Privasi</div>
                            <div class="setting-desc">Perlindungan data pribadi</div>
                        </div>
                    </div>
                    <div class="setting-right">
                        <span><i class="bi bi-chevron-right"></i></span>
                    </div>
                </div>
            </div>
            <a href="php/auth/logout.php"><button class="logout-btn"><i class="bi bi-box-arrow-left"></i> Keluar dari Akun</button></a>
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
            <div class="balance-label"><i class="bi bi-wallet2"></i> Saldo Rekening</div>
            <div class="balance-amount" id="balanceAmount">
                <span id="balanceValue"></span>
                <i class="bi bi-eye-slash balance-toggle" id="balanceToggle" onclick="toggleBalance()"></i>
            </div>
            <h5><i class="bi bi-credit-card"></i> <?= htmlspecialchars($rekening['nomor_rekening']) ?></h5>
        </div>

            <div class="features-section">
                <div class="section-title">
                    <span><i class="bi bi-lightbulb"></i> Rekomendasi Untuk Anda</span>
                    <span><i class="bi bi-plus-circle"></i></span>
                </div>
                <div class="feature-grid">
                    <?php foreach ($recommendations as $rec): ?>
                        <div class="feature-card <?= $rec['type'] ?>">
                            <div class="feature-icon"><i class="bi <?= $rec['icon'] ?>"></i></div>
                            <div class="feature-title"><?= $rec['title'] ?></div>
                            <div class="feature-amount"><?= $rec['amount'] ?></div>
                            <div class="feature-subtitle"><?= $rec['subtitle'] ?></div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="features-section">
                <div class="section-title">
                    <span style="color: white;"><i class="bi bi-calendar-check"></i> Aktivitas Mendatang</span>
                    <span><i class="bi bi-plus-circle"></i></span>
                </div>
                <div class="upcoming-activities">
                    <div class="activity-suggestion">
                        <i class="bi bi-alarm"></i>
                        <div>
                            <div>Pembayaran Tagihan Listrik</div>
                            <div>Jatuh tempo: 15 Juni 2025</div>
                        </div>
                    </div>
                    <div class="activity-suggestion">
                        <i class="bi bi-piggy-bank"></i>
                        <div>
                            <div>Target Menabung Bulanan</div>
                            <div>Progress: 65% dari target</div>
                        </div>
                    </div>
                    <div class="activity-suggestion">
                        <i class="bi bi-credit-card"></i>
                        <div>
                            <div>Cashback Promo</div>
                            <div>Berlaku hingga akhir bulan</div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="bottom-nav">
                <div class="nav-items">
                    <div class="nav-item active" onclick="showMain()">
                        <div class="nav-icon"><i class="bi bi-house-fill"></i></div>
                        <div class="nav-label">Home</div>
                    </div>
                    <div class="nav-item" onclick="showActivity()">
                        <div class="nav-icon"><i class="bi bi-list-ul"></i></div>
                        <div class="nav-label">Transaksi</div>
                    </div>
                    <div class="nav-item" onclick="showTransfer()">
                        <div class="nav-icon"><i class="bi bi-arrow-left-right"></i></div>
                        <div class="nav-label">Transfer</div>
                    </div>
                    <div class="nav-item" onclick="showReport()">
                        <div class="nav-icon"><i class="bi bi-graph-up"></i></div>
                        <div class="nav-label">Laporan</div>
                    </div>
                    <div class="nav-item" onclick="showSettings()">
                        <div class="nav-icon"><i class="bi bi-gear-fill"></i></div>
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

            <form class="transfer-form" id="transferForm" action="php/handler/transaksi/proses_transaksi.php" method="post">
                <div class="form-group">
                    <label class="form-label">Nomor Rekening Tujuan</label>
                    <input type="text" class="form-input" placeholder="Masukkan nomor rekening" id="accountNumber" name="rekening_tujuan" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Nama Penerima</label>
                    <input type="text" class="form-input" placeholder="Nama akan muncul otomatis" id="recipientName" readonly>
                </div>
                <div class="form-group">
                    <label class="form-label">Jumlah Transfer</label>
                    <input type="number" class="form-input" placeholder="Rp 0" id="transferAmount" name="jumlah" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Catatan (Opsional)</label>
                    <input type="text" class="form-input" placeholder="Tambahkan catatan" id="transferNote" name="catatan">
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
                    <span class="detail-value" id="successTransaksi">#-</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Tanggal & Waktu:</span>
                    <span class="detail-value" id="transactionDate">-</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Nomor Referensi:</span>
                    <span class="detail-value" id="successRef">-</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Sumber Dana:</span>
                    <span class="detail-value" id="successSender">-</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Nomor Tujuan:</span>
                    <span class="detail-value" id="successAccount">-</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Alias Penerima:</span>
                    <span class="detail-value" id="successAlias">-</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Catatan:</span>
                    <span class="detail-value" id="successNote">-</span>
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
                <div class="activity-item">
                    <div class="activity-info">
                        <div class="activity-title">Transfer ke TOKO HYPERSHOP.CO</div>
                        <div class="activity-date">15 Dec 2022, 20:58</div>
                    </div>
                    <div class="activity-amount">-Rp 10.525</div>
                </div>
                <div class="activity-item">
                    <div class="activity-info">
                        <div class="activity-title">Top Up Saldo</div>
                        <div class="activity-date">14 Dec 2022, 15:30</div>
                    </div>
                    <div class="activity-amount">+Rp 50.000</div>
                </div>
                <div class="activity-item">
                    <div class="activity-info">
                        <div class="activity-title">Transfer dari Ahmad Rizki</div>
                        <div class="activity-date">13 Dec 2022, 12:15</div>
                    </div>
                    <div class="activity-amount">+Rp 25.000</div>
                </div>
                <div class="activity-item">
                    <div class="activity-info">
                        <div class="activity-title">Pembayaran PLN</div>
                        <div class="activity-date">12 Dec 2022, 09:20</div>
                    </div>
                    <div class="activity-amount">-Rp 150.000</div>
                </div>
            </div>
        </div>
    </div>
<script>
    let balanceVisible = false;
    balanceValue.textContent = '••••••••';
    balanceValue.classList.add('balance-hidden');
    const originalBalance = '<?= formatRupiah($rekening['saldo']) ?>';
</script>
    <script src="js/main.js"></script>
    <script>
    const currentUserName = "<?php echo $_SESSION['user']['nama_lengkap']; ?>";

    document.getElementById("accountNumber").addEventListener("input", function () {
        const nomorRekening = this.value;
        const namaField = document.getElementById("recipientName");
    
        if (nomorRekening.length >= 5) { // Bisa disesuaikan
            fetch(`php/handler/get/get_nama_penerima.php?rekening=${nomorRekening}`)
                .then(response => response.text())
                .then(data => {
                    namaField.value = data || "Rekening tidak ditemukan";
                })
                .catch(() => {
                    namaField.value = "Gagal mengambil nama";
                });
        } else {
            namaField.value = "";
        }
    });

// Audio Notification System
class AudioNotification {
    constructor() {
        this.sounds = {
            success: document.getElementById('successSound'),
            incoming: document.getElementById('incomingSound'),
            failed: document.getElementById('failedSound'),
            notification: document.getElementById('notificationSound')
        };
        
        this.volume = localStorage.getItem('notificationVolume') || 0.7;
        this.soundEnabled = localStorage.getItem('soundEnabled') !== 'false';
        this.vibrationEnabled = localStorage.getItem('vibrationEnabled') !== 'false';
        
        this.initAudio();
        this.setupEventListeners();
    }
    
    initAudio() {
        // Set volume untuk semua audio
        Object.values(this.sounds).forEach(audio => {
            if (audio) {
                audio.volume = this.volume;
            }
        });
        
        // Update UI
        const volumeSlider = document.getElementById('volumeSlider');
        if (volumeSlider) {
            volumeSlider.value = this.volume * 100;
        }
        
        // Update toggle states
        this.updateToggleStates();
    }
    
    setupEventListeners() {
        // Volume slider
        const volumeSlider = document.getElementById('volumeSlider');
        if (volumeSlider) {
            volumeSlider.addEventListener('input', (e) => {
                this.volume = e.target.value / 100;
                localStorage.setItem('notificationVolume', this.volume);
                
                Object.values(this.sounds).forEach(audio => {
                    if (audio) audio.volume = this.volume;
                });
            });
        }
    }
    
    play(type) {
        if (!this.soundEnabled) return;
        
        const audio = this.sounds[type];
        if (audio) {
            // Reset audio to beginning
            audio.currentTime = 0;
            
            // Play audio with error handling
            const playPromise = audio.play();
            if (playPromise !== undefined) {
                playPromise.catch(error => {
                    console.log('Audio play failed:', error);
                });
            }
        }
        
        // Trigger vibration if enabled
        if (this.vibrationEnabled && navigator.vibrate) {
            this.vibrate(type);
        }
    }
    
    vibrate(type) {
        const patterns = {
            success: [100, 50, 100],
            incoming: [200],
            failed: [300, 100, 300],
            notification: [150]
        };
        
        navigator.vibrate(patterns[type] || [150]);
    }
    
    updateToggleStates() {
        const soundToggle = document.getElementById('sound-toggle');
        const vibrationToggle = document.getElementById('vibration-toggle');
        
        if (soundToggle) {
            soundToggle.classList.toggle('active', this.soundEnabled);
        }
        
        if (vibrationToggle) {
            vibrationToggle.classList.toggle('active', this.vibrationEnabled);
        }
    }
    
    toggleSound() {
        this.soundEnabled = !this.soundEnabled;
        localStorage.setItem('soundEnabled', this.soundEnabled);
        this.updateToggleStates();
    }
    
    toggleVibration() {
        this.vibrationEnabled = !this.vibrationEnabled;
        localStorage.setItem('vibrationEnabled', this.vibrationEnabled);
        this.updateToggleStates();
    }
}

// Initialize audio system
const audioNotification = new AudioNotification();

// Test sound function
function testSound(type) {
    audioNotification.play(type);
}

// Update toggle setting function
function toggleSetting(setting) {
    switch(setting) {
        case 'sound':
            audioNotification.toggleSound();
            break;
        case 'vibration':
            audioNotification.toggleVibration();
            break;
        // ... other existing toggles
    }
}

// 6. MODIFIKASI TRANSFER FORM SUBMISSION
document.getElementById('transferForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    
    fetch('php/handler/transaksi/proses_transaksi.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Play success sound
            audioNotification.play('success');
            
            // Update success screen dengan data
            updateSuccessScreen(data);
            showSuccess();
        } else {
            // Play failed sound
            audioNotification.play('failed');
            
            // Show error message
            alert(data.message || 'Transaksi gagal');
        }
    })
    .catch(error => {
        audioNotification.play('failed');
        console.error('Error:', error);
        alert('Terjadi kesalahan sistem');
    });
});

// 7. REAL-TIME NOTIFICATION CHECKER (Opsional)
// Untuk notifikasi transaksi masuk dari user lain
function checkNewTransactions() {
    if (!document.hidden) { // Only check when page is visible
        fetch('php/handler/get/check_new_transactions.php')
        .then(response => response.json())
        .then(data => {
            if (data.hasNewTransaction) {
                audioNotification.play('incoming');
                
                // Show notification
                showNotification('Transaksi Masuk', 
                    `Anda menerima ${data.amount} dari ${data.sender}`);
            }
        })
        .catch(error => console.log('Check transaction error:', error));
    }
}

// Check every 30 seconds
setInterval(checkNewTransactions, 30000);

// 8. WEB NOTIFICATION API (Bonus)
function showNotification(title, body) {
    if ('Notification' in window && Notification.permission === 'granted') {
        new Notification(title, {
            body: body,
            icon: 'images/logo.png',
            badge: 'images/badge.png'
        });
    }
}

// Request notification permission
if ('Notification' in window && Notification.permission === 'default') {
    Notification.requestPermission();
}

// 9. PAGE VISIBILITY API - Play sound only when page is visible
document.addEventListener('visibilitychange', function() {
    if (!document.hidden) {
        // Page became visible, check for any missed notifications
        checkNewTransactions();
    }
});
</script>
<div class="audio-container" style="display: none;">
    <audio id="successSound" preload="auto">
        <source src="sounds/transaction-success.mp3" type="audio/mpeg">
        <source src="sounds/transaction-success.wav" type="audio/wav">
    </audio>
    
    <audio id="incomingSound" preload="auto">
        <source src="sounds/transaction-incoming.mp3" type="audio/mpeg">
        <source src="sounds/transaction-incoming.wav" type="audio/wav">
    </audio>
    
    <audio id="failedSound" preload="auto">
        <source src="sounds/transaction-failed.mp3" type="audio/mpeg">
        <source src="sounds/transaction-failed.wav" type="audio/wav">
    </audio>
    
    <audio id="notificationSound" preload="auto">
        <source src="sounds/notification.mp3" type="audio/mpeg">
        <source src="sounds/notification.wav" type="audio/wav">
    </audio>
</div>
</body>
</html>