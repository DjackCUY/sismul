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
        <!-- Login Screen -->
        <div class="login-screen" id="loginScreen">
            <div class="logo">
                Alim<span>Bank</span>
            </div>
            <form class="login-form" id="loginForm" action="php/auth/regist.php" method="post">
                <div class="input-group">
                    <input type="text" placeholder="Nama Lengkap" id="" name="nama" required>
                </div>
                <div class="input-group">
                    <input type="text" placeholder="Alamat" id="" name="alamat" required>
                </div>
                <div class="input-group">
                    <input type="text" placeholder="No. Telepon" id="" name="telepon" required>
                </div>
                <div class="input-group">
                    <select name="jenis" id="">
                        <option value="L">Laki-Laki</option>
                        <option value="P">Perempuan</option>
                    </select>
                </div>
                <div class="input-group">
                    <input type="text" placeholder="E-mail" id="username" name="email" required>
                </div>
                <div class="input-group">
                    <input type="password" placeholder="Password" id="password" name="password" required>
                </div>
                <button type="submit" class="login-btn">Daftar</button>
            </form>
        </div>
    </div>
    <!-- <script src="js/main.js"></script> -->
</body>
</html>