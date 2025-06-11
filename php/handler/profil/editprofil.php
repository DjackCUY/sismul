<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
</head>
<body>
    <form class="transfer-form" id="transferForm" action="insertprofil.php">
                <div class="form-group">
                    <label class="form-label">Nama lengkap</label>
                    <input type="text" class="form-input" placeholder="Nama lengkap" id="accountNumber" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Alamat</label>
                    <input type="text" class="form-input" placeholder="Alamat" id="recipientName" readonly>
                </div>
                <div class="form-group">
                    <label class="form-label">Nomor telepon</label>
                    <input type="number" class="form-input" placeholder="08..." id="transferAmount" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Email</label>
                    <input type="text" class="form-input" placeholder="Email" id="transferNote">
                </div>
                <div class="form-group">
                    <label class="form-label">Password</label>
                    <input type="number" class="form-input" placeholder="Password" id="transferAmount" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Jenis kelamin</label>
                    <input type="number" class="form-input" placeholder="P / L" id="transferAmount" required>
                </div>
                <button type="submit" class="submit-btn">Simpan</button>
            </form>
</body>
</html>