<?php
session_start();
include "php/db/connect.php";
$user = $_SESSION['user'];
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
                    <span class="summary-value positive" id="totalIncome">+Rp 125.000</span>
                </div>
                <div class="summary-row">
                    <span class="summary-label">Total Pengeluaran</span>
                    <span class="summary-value negative" id="totalExpense">-Rp 85.525</span>
                </div>
                <div class="summary-row">
                    <span class="summary-label">Saldo Bersih</span>
                    <span class="summary-value positive" id="netBalance">+Rp 39.475</span>
                </div>
                <div class="summary-row">
                    <span class="summary-label">Rata-rata Harian</span>
                    <span class="summary-value" id="dailyAverage">Rp 5.639</span>
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
                <div class="activity-item">
                    <div class="activity-info">
                        <div class="activity-title">Belanja Online</div>
                        <div class="activity-date">5 transaksi</div>
                    </div>
                    <div class="activity-amount">-Rp 45.525</div>
                </div>
                <div class="activity-item">
                    <div class="activity-info">
                        <div class="activity-title">Tagihan & Utilitas</div>
                        <div class="activity-date">2 transaksi</div>
                    </div>
                    <div class="activity-amount">-Rp 25.000</div>
                </div>
                <div class="activity-item">
                    <div class="activity-info">
                        <div class="activity-title">Makanan & Minuman</div>
                        <div class="activity-date">3 transaksi</div>
                    </div>
                    <div class="activity-amount">-Rp 15.000</div>
                </div>
            </div>
        </div>

        <!-- Settings Screen -->
        <div class="settings-screen" id="settingsScreen">
            <div class="screen-header">
                <button class="back-btn" onclick="showMain()">←</button>
                <div class="screen-title">Pengaturan</div>
            </div>

            <div class="profile-section">
                <div class="profile-avatar">FA</div>
                <div class="profile-name">Fatimah Azzahrah</div>
                <div class="profile-email">fatimah.azzahrah@email.com</div>
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
                    <div class="greeting">Selamat Malam</div>
                    <div class="user-name"><?= htmlspecialchars($user['nama_lengkap']) ?></div>
                </div>
                <div class="profile-pic">FA</div>
            </div>

            <div class="balance-card">
                <div class="balance-label">Saldo Rekening</div>
                <div class="balance-amount" id="balanceAmount">Rp 2.235.114,50</div>
                <h5>7 2004 33 291</h5>
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