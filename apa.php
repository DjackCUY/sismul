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
    <style>
        /* Base styles */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
        }

        .phone-container {
            width: 375px;
            height: 812px;
            background: #1a1a1a;
            border-radius: 40px;
            padding: 20px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.3);
            position: relative;
            overflow: hidden;
        }

        .main-screen, .transfer-screen, .activity-screen, .success-screen, .report-screen, .settings-screen {
            width: 100%;
            height: 100%;
            background: #1a1a1a;
            border-radius: 20px;
            padding: 20px;
            position: absolute;
            top: 20px;
            left: 20px;
            right: 20px;
            bottom: 20px;
            display: none;
        }

        .main-screen {
            display: block;
        }

        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            color: white;
        }

        .greeting {
            font-size: 14px;
            opacity: 0.8;
        }

        .user-name {
            font-size: 18px;
            font-weight: 600;
            margin-top: 4px;
        }

        .profile-pic {
            width: 40px;
            height: 40px;
            background: #4CAF50;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 600;
            font-size: 14px;
        }

        .balance-card {
            background: linear-gradient(135deg, #4CAF50 0%, #45a049 100%);
            border-radius: 20px;
            padding: 24px;
            margin-bottom: 30px;
            color: white;
            position: relative;
        }

        .balance-label {
            font-size: 14px;
            opacity: 0.9;
            margin-bottom: 8px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .balance-amount {
            font-size: 32px;
            font-weight: 700;
            margin-bottom: 12px;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .balance-toggle {
            background: rgba(255, 255, 255, 0.2);
            border: none;
            color: white;
            width: 36px;
            height: 36px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .balance-toggle:hover {
            background: rgba(255, 255, 255, 0.3);
        }

        .balance-hidden {
            font-size: 32px;
            font-weight: 700;
            margin-bottom: 12px;
        }

        .section-title {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 16px;
            color: white;
            font-size: 16px;
            font-weight: 600;
        }

        .feature-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 16px;
            margin-bottom: 30px;
        }

        .feature-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 16px;
            padding: 20px;
            color: white;
            box-shadow: 0 8px 25px rgba(102, 126, 234, 0.3);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            cursor: pointer;
        }
        
        .feature-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 12px 35px rgba(102, 126, 234, 0.4);
        }
        
        .feature-card.investment {
            background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);
        }
        
        .feature-card.savings {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
        
        .feature-card.goal {
            background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
        }
        
        .feature-card.protection {
            background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
        }
        
        .feature-card.credit {
            background: linear-gradient(135deg, #fa709a 0%, #fee140 100%);
        }
        
        .feature-icon {
            font-size: 32px;
            margin-bottom: 12px;
            display: block;
        }

        .feature-title {
            font-size: 14px;
            font-weight: 600;
            margin-bottom: 4px;
        }

        .feature-amount {
            font-size: 18px;
            font-weight: 700;
            margin-bottom: 4px;
        }

        .feature-subtitle {
            font-size: 12px;
            opacity: 0.8;
        }

        .upcoming-activities {
            background: #2a2a2a;
            border-radius: 12px;
            padding: 16px;
            margin-top: 20px;
        }
        
        .activity-suggestion {
            display: flex;
            align-items: center;
            padding: 12px 0;
            border-bottom: 1px solid #333;
            color: white;
        }
        
        .activity-suggestion:last-child {
            border-bottom: none;
        }
        
        .activity-suggestion i {
            font-size: 20px;
            margin-right: 12px;
            color: #4CAF50;
        }

        /* Bottom Navigation - Made Solid */
        .bottom-nav {
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            background: #2a2a2a; /* Solid background instead of transparent */
            border-top: 1px solid #333;
            padding: 12px 0;
            border-bottom-left-radius: 20px;
            border-bottom-right-radius: 20px;
        }

        .nav-items {
            display: flex;
            justify-content: space-around;
            align-items: center;
        }

        .nav-item {
            display: flex;
            flex-direction: column;
            align-items: center;
            cursor: pointer;
            padding: 8px;
            border-radius: 12px;
            transition: all 0.3s ease;
            color: #999;
        }

        .nav-item.active {
            color: #4CAF50;
            background: rgba(76, 175, 80, 0.1);
        }

        .nav-item:hover {
            background: rgba(255, 255, 255, 0.05);
        }

        .nav-icon {
            font-size: 22px;
            margin-bottom: 4px;
        }

        .nav-label {
            font-size: 12px;
            font-weight: 500;
        }

        /* Screen Headers */
        .screen-header {
            display: flex;
            align-items: center;
            margin-bottom: 30px;
            color: white;
        }

        .back-btn {
            background: none;
            border: none;
            color: white;
            font-size: 24px;
            cursor: pointer;
            margin-right: 16px;
            padding: 8px;
            border-radius: 50%;
            transition: background 0.3s ease;
        }

        .back-btn:hover {
            background: rgba(255, 255, 255, 0.1);
        }

        .screen-title {
            font-size: 20px;
            font-weight: 600;
        }

        /* Transfer Screen */
        .transfer-form {
            color: white;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-label {
            display: block;
            margin-bottom: 8px;
            font-size: 14px;
            font-weight: 500;
        }

        .form-input {
            width: 100%;
            padding: 16px;
            background: #2a2a2a;
            border: 1px solid #333;
            border-radius: 12px;
            color: white;
            font-size: 16px;
        }

        .form-input:focus {
            outline: none;
            border-color: #4CAF50;
        }

        .submit-btn {
            width: 100%;
            padding: 16px;
            background: #4CAF50;
            color: white;
            border: none;
            border-radius: 12px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: background 0.3s ease;
        }

        .submit-btn:hover {
            background: #45a049;
        }

        /* Activity Screen */
        .activity-list {
            color: white;
        }

        .activity-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 16px 0;
            border-bottom: 1px solid #333;
        }

        .activity-item:last-child {
            border-bottom: none;
        }

        .activity-title {
            font-weight: 600;
            margin-bottom: 4px;
        }

        .activity-date {
            font-size: 12px;
            opacity: 0.7;
        }

        .activity-amount {
            font-weight: 600;
            font-size: 16px;
        }

        /* Success Screen */
        .success-screen {
            text-align: center;
            color: white;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }

        .success-icon {
            width: 80px;
            height: 80px;
            background: #4CAF50;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 40px;
            color: white;
            margin: 0 auto 20px;
        }

        .success-title {
            font-size: 24px;
            font-weight: 600;
            margin-bottom: 30px;
        }

        .transaction-details {
            background: #2a2a2a;
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 20px;
            text-align: left;
        }

        .detail-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 12px;
        }

        .detail-row:last-child {
            margin-bottom: 0;
        }

        .detail-label {
            opacity: 0.7;
            font-size: 14px;
        }

        .detail-value {
            font-weight: 500;
            font-size: 14px;
        }

        .amount-display {
            font-size: 32px;
            font-weight: 700;
            color: #4CAF50;
            margin-bottom: 30px;
        }

        .action-buttons {
            display: flex;
            gap: 12px;
        }

        .secondary-btn, .primary-btn {
            flex: 1;
            padding: 16px;
            border: none;
            border-radius: 12px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .secondary-btn {
            background: #2a2a2a;
            color: white;
        }

        .primary-btn {
            background: #4CAF50;
            color: white;
        }

        /* Report Screen */
        .report-filters {
            display: flex;
            gap: 8px;
            margin-bottom: 20px;
        }

        .filter-btn {
            padding: 8px 16px;
            background: #2a2a2a;
            border: 1px solid #333;
            border-radius: 20px;
            color: white;
            font-size: 14px;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .filter-btn.active {
            background: #4CAF50;
            border-color: #4CAF50;
        }

        .report-summary {
            background: #2a2a2a;
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 20px;
            color: white;
        }

        .summary-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 16px;
        }

        .summary-row:last-child {
            margin-bottom: 0;
        }

        .summary-label {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 14px;
        }

        .summary-value {
            font-weight: 600;
            font-size: 16px;
        }

        .summary-value.positive {
            color: #4CAF50;
        }

        .summary-value.negative {
            color: #f44336;
        }

        .chart-container {
            background: #2a2a2a;
            border-radius: 12px;
            padding: 40px;
            text-align: center;
            color: white;
            margin-bottom: 20px;
        }

        .chart-placeholder {
            opacity: 0.6;
        }

        /* Settings Screen */
        .profile-section {
            text-align: center;
            margin-bottom: 30px;
            color: white;
        }

        .profile-avatar {
            width: 80px;
            height: 80px;
            background: #4CAF50;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            font-weight: 600;
            margin: 0 auto 16px;
        }

        .profile-name {
            font-size: 20px;
            font-weight: 600;
            margin-bottom: 4px;
        }

        .profile-email {
            opacity: 0.7;
            margin-bottom: 16px;
        }

        .edit-profile-btn {
            background: #2a2a2a;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 20px;
            font-size: 14px;
            cursor: pointer;
        }

        .settings-section {
            margin-bottom: 30px;
        }

        .settings-title {
            color: white;
            font-weight: 600;
            margin-bottom: 16px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .setting-item {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 16px 0;
            border-bottom: 1px solid #333;
            color: white;
            cursor: pointer;
        }

        .setting-item:last-child {
            border-bottom: none;
        }

        .setting-left {
            display: flex;
            align-items: center;
        }

        .setting-icon {
            width: 40px;
            height: 40px;
            background: #2a2a2a;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 12px;
            font-size: 18px;
        }

        .setting-name {
            font-weight: 500;
            margin-bottom: 2px;
        }

        .setting-desc {
            font-size: 12px;
            opacity: 0.7;
        }

        .setting-right {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .setting-value {
            font-size: 14px;
            opacity: 0.7;
        }

        .toggle-switch {
            width: 50px;
            height: 28px;
            background: #333;
            border-radius: 14px;
            position: relative;
            cursor: pointer;
            transition: background 0.3s ease;
        }

        .toggle-switch.active {
            background: #4CAF50;
        }

        .toggle-switch::after {
            content: '';
            position: absolute;
            top: 2px;
            left: 2px;
            width: 24px;
            height: 24px;
            background: white;
            border-radius: 50%;
            transition: transform 0.3s ease;
        }

        .toggle-switch.active::after {
            transform: translateX(22px);
        }

        .logout-btn {
            width: 100%;
            padding: 16px;
            background: #f44336;
            color: white;
            border: none;
            border-radius: 12px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }

        /* Utility Classes */
        .text-success {
            color: #4CAF50;
        }

        .text-danger {
            color: #f44336;
        }
    </style>
</head>
<body>
    <div class="phone-container">
        <!-- Report Screen -->
        <div class="report-screen" id="reportScreen">
            <div class="screen-header">
                <button class="back-btn" onclick="showMain()"><i class="bi bi-arrow-left"></i></button>
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
                    <span class="summary-value positive" id="totalIncome">+Rp 2.500.000</span>
                </div>
                <div class="summary-row">
                    <span class="summary-label"><i class="bi bi-arrow-up-circle text-danger"></i> Total Pengeluaran</span>
                    <span class="summary-value negative" id="totalExpense">-Rp 1.750.000</span>
                </div>
                <div class="summary-row">
                    <span class="summary-label"><i class="bi bi-calculator"></i> Saldo Bersih</span>
                    <span class="summary-value positive" id="netBalance">+Rp 750.000</span>
                </div>
                <div class="summary-row">
                    <span class="summary-label"><i class="bi bi-graph-up"></i> Rata-rata Harian</span>
                    <span class="summary-value" id="dailyAverage">Rp 107.143</span>
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
                <div class="activity-item">
                    <div class="activity-info">
                        <div class="activity-title">
                            <i class="bi bi-receipt"></i> Makanan & Minuman
                        </div>
                        <div class="activity-date">12 transaksi</div>
                    </div>
                    <div class="activity-amount">-Rp 450.000</div>
                </div>
                <div class="activity-item">
                    <div class="activity-info">
                        <div class="activity-title">
                            <i class="bi bi-receipt"></i> Transportasi
                        </div>
                        <div class="activity-date">8 transaksi</div>
                    </div>
                    <div class="activity-amount">-Rp 320.000</div>
                </div>
                <div class="activity-item">
                    <div class="activity-info">
                        <div class="activity-title">
                            <i class="bi bi-receipt"></i> Belanja
                        </div>
                        <div class="activity-date">6 transaksi</div>
                    </div>
                    <div class="activity-amount">-Rp 280.000</div>
                </div>
            </div>
        </div>

        <!-- Settings Screen -->
        <div class="settings-screen" id="settingsScreen">
            <div class="screen-header">
                <button class="back-btn" onclick="showMain()"><i class="bi bi-arrow-left"></i></button>
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
                <div class="settings-title"><i class="bi bi-bell"></i> Notifikasi</div>
                <div class="setting-item" onclick="toggleSetting('push')">
                    <div class="setting-left">
                        <div class="setting-icon"><i class="bi bi-phone-vibrate"></i></div>
                        <div class="setting-info">
                            <div class="setting-name">Push Notification</div>
                            <div class="setting-desc">Notifikasi transaksi & promo</div>
                        </div>
                    </div>
                    <div class="toggle-switch active" id="push-toggle"></div>
                </div>
                <div class="setting-item" onclick="toggleSetting('email')">
                    <div class="setting-left">
                        <div class="setting-icon"><i class="bi bi-envelope"></i></div>
                        <div class="setting-info">
                            <div class="setting-name">Email Notification</div>
                            <div class="setting-desc">Laporan bulanan via email</div>
                        </div>
                    </div>
                    <div class="toggle-switch" id="email-toggle"></div>
                </div>
                <div class="setting-item" onclick="toggleSetting('sms')">
                    <div class="setting-left">
                        <div class="setting-icon"><i class="bi bi-chat-text"></i></div>
                        <div class="setting-info">
                            <div class="setting-name">SMS Notification</div>
                            <div class="setting-desc">Konfirmasi transaksi via SMS</div>
                        </div>
                    </div>
                    <div class="toggle-switch active" id="sms-toggle"></div>
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
                <div class="balance-amount" id="balanceAmount"><?= formatRupiah($rekening['saldo']) ?></div>
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
                    <span><i class="bi bi-calendar-check"></i> Aktivitas Mendatang</span>
                    <span><i class="bi bi-plus-circle"></i></span>
                </div>
                <div class="upcoming-activities">
                    <div class="activity-suggestion">
                        <i class="bi bi-alarm"></i>
                        <div>
                            <div style="font-weight: 600;">Pembayaran Tagihan Listrik</div>
                            <div style="font-size: 12px; color: #666;">Jatuh tempo: 15 Juni 2025</div>
                        </div>
                    </div>
                    <div class="activity-suggestion">
                        <i class="bi bi-piggy-bank"></i>
                        <div>
                            <div style="font-weight: 600;">Target Menabung Bulanan</div>
                            <div style="font-size: 12px; color: #666;">Progress: 65% dari target</div>
                        </div>
                    </div>
                    <div class="activity-suggestion">
                        <i class="bi bi-credit-card"></i>
                        <div>
                            <div style="font-weight: 600;">Cashback Promo</div>
                            <div style="font-size: 12px; color: #666;">Berlaku hingga akhir bulan</div>
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

            <form class="transfer-form" id="transferForm">
                <div class="form-group">
                    <label class="form-label">Nomor Rekening Tujuan</label>
                    <input type="text" class="form-input" placeholder="Masukkan nomor rekening" id="accountNumber" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Nama Penerima</label>
                    <input type="text" class="form-input" placeholder="Nama akan muncul otomatis" id="recipientName" readonly>
                </div>
                <div class="form-group">
                    <label class="form-label">Jumlah Transfer</label>
                    <input type="number" class="form-input" placeholder="Rp 0" id="transferAmount" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Catatan (Opsional)</label>
                    <input type="text" class="form-input" placeholder="Tambahkan catatan" id="transferNote">
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
                    <span class="detail-value" id="successRecipient">TOKO HYPERSHOP.CO</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Nomor Transaksi:</span>
                    <span class="detail-value">#8128572394</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Tanggal & Waktu:</span>
                    <span class="detail-value" id="transactionDate">Dec 15, 2022 | 8:58:45 PM</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Nomor Referensi:</span>
                    <span class="detail-value">442774356886</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Sumber Dana:</span>
                    <span class="detail-value">Fatimah Azzahrah</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Nomor Tujuan:</span>
                    <span class="detail-value">3434634634643</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Alias Penerima:</span>
                    <span class="detail-value">Kevin Hypershop</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Catatan:</span>
                    <span class="detail-value">-</span>
                </div>
            </div>
            <div class="amount-display" id="successAmount">Rp 10.525</div>
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
    <script src="js/main.js"></script>
</body>
</html>