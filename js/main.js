        // Data simulasi
        let currentBalance = 2235114.50;
        let transactions = [];

        // Transfer functionality
        document.getElementById('transferForm').addEventListener('submit', function(e) {
            e.preventDefault();
            const accountNumber = document.getElementById('accountNumber').value;
            const amount = parseFloat(document.getElementById('transferAmount').value);
            const note = document.getElementById('transferNote').value;
            
            if (amount > currentBalance) {
                alert('Saldo tidak mencukupi');
                return;
            }
            
            if (accountNumber && amount > 0) {
                // Update balance
                currentBalance -= amount;
                updateBalanceDisplay();
                
                // Add to transaction history
                transactions.unshift({
                    type: 'transfer',
                    amount: -amount,
                    recipient: 'TOKO HYPERSHOP.CO',
                    date: new Date().toLocaleString('id-ID'),
                    note: note
                });
                
                // Update success screen
                document.getElementById('successAmount').textContent = `Rp ${amount.toLocaleString('id-ID')}`;
                document.getElementById('transactionDate').textContent = new Date().toLocaleString('id-ID');
                
                showSuccess();
            } else {
                alert('Mohon lengkapi data transfer');
            }
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

        // Initialize app
        document.addEventListener('DOMContentLoaded', function() {
            showLogin();
        });

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