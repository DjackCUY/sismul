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
            <form class="login-form" id="loginForm" action="php/auth/auth.php" method="post">
                <div class="input-group">
                    <input type="text" placeholder="Username" id="username" name="email" required>
                </div>
                <div class="input-group">
                    <input type="password" placeholder="Password" id="password" name="password" required>
                </div>
                <button type="submit" class="login-btn">Masuk</button>
            </form>
            <br>
            <p>Belum Punya Akun..? <a href="reg.php">Klik Disini</a> </p>
        </div>
    </div>
    <!-- <script src="js/main.js"></script> -->
</body>
</html>