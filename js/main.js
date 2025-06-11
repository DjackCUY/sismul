        // Data simulasi
        let currentBalance = 2235114.50;
        let transactions = [];

        // Transfer functionality
        document.getElementById('transferForm').addEventListener('submit', function(e) {
            e.preventDefault();
        
            const accountNumber = document.getElementById('accountNumber').value;
            const amount = parseFloat(document.getElementById('transferAmount').value);
            const note = document.getElementById('transferNote').value;
            const recipientName = document.getElementById('recipientName').value;
        
            if (!accountNumber || amount <= 0 || recipientName === '' || recipientName === 'Rekening tidak ditemukan') {
                alert('Mohon lengkapi data transfer dengan benar.');
                return;
            }
        
            const formData = new FormData();
            formData.append('rekening_tujuan', accountNumber);
            formData.append('jumlah', amount);
            formData.append('catatan', note);
        
            fetch('php/handler/transaksi/proses_transaksi.php', {
                method: 'POST',
                body: formData
            })
            .then(res => res.text())
            .then(response => {
                if (response.includes('BERHASIL')) {
                    // Update tampilan sukses
                    document.getElementById('successAmount').textContent = `Rp ${amount.toLocaleString('id-ID')}`;
                    document.getElementById('transactionDate').textContent = new Date().toLocaleString('id-ID');
                    document.getElementById('successRecipient').textContent = recipientName;
                    document.getElementById('successAlias').textContent = recipientName;
                    document.getElementById('successAccount').textContent = accountNumber;
                    document.getElementById('successSender').textContent = currentUserName;
                    document.getElementById('successNote').textContent = note || "-";
                    document.getElementById('successTransaksi').textContent = "#" + Math.floor(Math.random() * 10000000000);
                    document.getElementById('successRef').textContent = Math.floor(100000000000 + Math.random() * 900000000000);

                    transactions.unshift({
                        type: 'transfer',
                        amount: -amount,
                        recipient: recipientName,
                        date: new Date().toLocaleString('id-ID'),
                        note: note
                    });
                
                    showSuccess();
                } else {
                    alert('Gagal transfer: ' + response);
                }
            })
            .catch(err => {
                console.error(err);
                alert('Terjadi kesalahan saat mengirim data.');
            });
        });

        // Auto-fill recipient name when account number is entered
        document.getElementById('accountNumber').addEventListener('input', function(e) {
            const accountNumber = e.target.value;
            const recipientName = document.getElementById('recipientName');
            
            if (accountNumber.length >= 10) {
                // Simulasi auto-fill nama
                recipientName.value = 'Kevin Hypershop';
            } else {
                recipientName.value = '';
            }
        });

        // Navigation functions

        function showMain() {
            hideAllScreens();
            document.getElementById('mainScreen').style.display = 'block';
            updateNavigation('main');
        }

        function showTransfer() {
            hideAllScreens();
            document.getElementById('transferScreen').style.display = 'block';
            updateNavigation('transfer');
        }

        function showSuccess() {
            hideAllScreens();
            document.getElementById('successScreen').style.display = 'block';
        }

        function showActivity() {
            hideAllScreens();
            document.getElementById('activityScreen').style.display = 'block';
            updateNavigation('activity');
            updateActivityList();
        }

        function showReport() {
            hideAllScreens();
            document.getElementById('reportScreen').style.display = 'block';
            updateNavigation('report');
            updateReportData();
        }

        function showSettings() {
            hideAllScreens();
            document.getElementById('settingsScreen').style.display = 'block';
            updateNavigation('settings');
        }

        function hideAllScreens() {
            const screens = ['mainScreen', 'transferScreen', 'successScreen', 'activityScreen', 'reportScreen', 'settingsScreen'];
            screens.forEach(screen => {
                document.getElementById(screen).style.display = 'none';
            });
        }

        function updateNavigation(active) {
            const navItems = document.querySelectorAll('.nav-item');
            navItems.forEach(item => item.classList.remove('active'));
            
            // Add active class based on current screen
            if (active === 'main') {
                navItems[0].classList.add('active');
            } else if (active === 'activity') {
                navItems[1].classList.add('active');
            } else if (active === 'transfer') {
                navItems[2].classList.add('active');
            } else if (active === 'report') {
                navItems[3].classList.add('active');
            } else if (active === 'settings') {
                navItems[4].classList.add('active');
            }
        }

        function updateBalanceDisplay() {
            document.getElementById('balanceAmount').textContent = `Rp ${currentBalance.toLocaleString('id-ID')}`;
        }

        function updateActivityList() {
            const activityList = document.querySelector('.activity-list');
            if (transactions.length === 0) {
                // Default transactions
                activityList.innerHTML = `
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
                `;
            } else {
                // Generate from transaction data
                activityList.innerHTML = transactions.map(transaction => `
                    <div class="activity-item">
                        <div class="activity-info">
                            <div class="activity-title">Transfer ke ${transaction.recipient}</div>
                            <div class="activity-date">${transaction.date}</div>
                        </div>
                        <div class="activity-amount">${transaction.amount < 0 ? '-' : '+'}Rp ${Math.abs(transaction.amount).toLocaleString('id-ID')}</div>
                    </div>
                `).join('');
            }
        }

        // Add click handlers for navigation
        document.querySelectorAll('.nav-item').forEach((item, index) => {
            item.addEventListener('click', function() {
                switch(index) {
                    case 0: showMain(); break;
                    case 1: showActivity(); break;
                    case 2: showTransfer(); break;
                    case 3: showReport(); break;
                    case 4: showSettings(); break;
                    default: break;
                }
            });
        });

        /* Report functions */
        function updateReportData() {
            const totalIncome = 125000;
            const totalExpense = 85525;
            const netBalance = totalIncome - totalExpense;
            const dailyAverage = netBalance / 7;

            document.getElementById('totalIncome').textContent = `+Rp ${totalIncome.toLocaleString('id-ID')}`;
            document.getElementById('totalExpense').textContent = `-Rp ${totalExpense.toLocaleString('id-ID')}`;
            document.getElementById('netBalance').textContent = `+Rp ${netBalance.toLocaleString('id-ID')}`;
            document.getElementById('dailyAverage').textContent = `Rp ${Math.round(dailyAverage).toLocaleString('id-ID')}`;
        }

        function setReportFilter(period, button) {
            // Remove active class from all filter buttons
                document.querySelectorAll('.filter-btn').forEach(btn => {
                btn.classList.remove('active');
            });
            
            // Add active class to clicked button
            button.classList.add('active');
            
            // Update report data based on selected period
            let totalIncome, totalExpense;
            
            switch(period) {
                case 'week':
                    totalIncome = 125000;
                    totalExpense = 85525;
                    break;
                case 'month':
                    totalIncome = 540000;
                    totalExpense = 320000;
                    break;
                case 'year':
                    totalIncome = 6480000;
                    totalExpense = 4200000;
                    break;
            }
            
            const netBalance = totalIncome - totalExpense;
            const days = period === 'week' ? 7 : (period === 'month' ? 30 : 365);
            const dailyAverage = netBalance / days;

            document.getElementById('totalIncome').textContent = `+Rp ${totalIncome.toLocaleString('id-ID')}`;
            document.getElementById('totalExpense').textContent = `-Rp ${totalExpense.toLocaleString('id-ID')}`;
            document.getElementById('netBalance').textContent = `+Rp ${netBalance.toLocaleString('id-ID')}`;
            document.getElementById('dailyAverage').textContent = `Rp ${Math.round(dailyAverage).toLocaleString('id-ID')}`;
        }

        // Settings functions
        function toggleSetting(settingName) {
            const toggle = document.getElementById(settingName + '-toggle');
            if (toggle) {
                toggle.classList.toggle('active');
                
                // Show feedback
                const settingItem = toggle.closest('.setting-item');
                const settingNameEl = settingItem.querySelector('.setting-name');
                const originalText = settingNameEl.textContent;
                
                if (toggle.classList.contains('active')) {
                    settingNameEl.textContent = originalText + ' ✓';
                    setTimeout(() => {
                        settingNameEl.textContent = originalText;
                    }, 1500);
                }
            }
        }

    // Mata Saldo
    function toggleBalance() {
    const balanceValue = document.getElementById('balanceValue');
    const balanceToggle = document.getElementById('balanceToggle');
    
    if (balanceVisible) {
        // Sembunyikan saldo
        balanceValue.textContent = '••••••••';
        balanceValue.classList.add('balance-hidden');
        balanceToggle.classList.remove('bi-eye');
        balanceToggle.classList.add('bi-eye-slash');
        balanceVisible = false;
    } else {
        // Tampilkan saldo
        balanceValue.textContent = originalBalance;
        balanceValue.classList.remove('balance-hidden');
        balanceToggle.classList.remove('bi-eye-slash');
        balanceToggle.classList.add('bi-eye');
        balanceVisible = true;
    }
}