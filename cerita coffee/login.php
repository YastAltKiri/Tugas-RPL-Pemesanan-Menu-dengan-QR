<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Masuk - Cerita Coffee</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <div class="page" style="max-width: 400px; padding-top: 80px;">
        <div style="text-align:center; margin-bottom: 24px;">
            <h1>Cerita Coffee</h1>
            <p style="color: var(--espresso-light); margin: 0;">Masuk ke akun staf</p>
        </div>

        <div class="card">
            <?php if (isset($_GET['error'])): ?>
                <p class="msg-error">
                    <?php
                        if ($_GET['error'] === 'invalid') {
                            echo "Username atau password salah.";
                        } elseif ($_GET['error'] === 'empty') {
                            echo "Username dan password wajib diisi.";
                        }
                    ?>
                </p>
            <?php endif; ?>

            <form action="proses_login.php" method="POST">
                <label>Username</label>
                <input type="text" name="username" required autofocus>

                <label>Password</label>
                <input type="password" name="password" required>

                <button type="submit" class="btn btn-primary" style="width:100%;">Masuk</button>
            </form>
        </div>
    </div>
</body>
</html>