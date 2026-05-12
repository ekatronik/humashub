<?php
// admin/login.php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/../includes/auth.php';

if (isLoggedIn()) {
    header("Location: index.php");
    exit();
}

$error = "";
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['username']) || !isset($_POST['password'])) {
        $error = "Data form tidak lengkap.";
    } else {
        $username = $_POST['username'];
        $password = $_POST['password'];
        $result = login($username, $password);
        if ($result === true) {
            header("Location: index.php");
            exit();
        } else {
            $error = $result;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login | Humas Hub UIN Ar-Raniry</title>
    <link rel="stylesheet" href="<?php echo (basename(dirname($_SERVER['PHP_SELF'])) == 'admin') ? '../' : '../../'; ?>assets/themes/<?php echo get_setting('app_theme', 'default'); ?>/style.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #006837 0%, #1e1e2d 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            height: 100vh;
            margin: 0;
        }
        .login-card {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.2);
            width: 100%;
            max-width: 400px;
            padding: 40px;
            border-radius: 30px;
            color: white;
            box-shadow: 0 15px 35px rgba(0,0,0,0.2);
        }
        .login-card h2 {
            text-align: center;
            margin-bottom: 30px;
            font-weight: 700;
        }
        .form-group {
            margin-bottom: 20px;
        }
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-size: 14px;
        }
        .form-control {
            width: 100%;
            padding: 12px 15px;
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(255, 255, 255, 0.2);
            border-radius: 12px;
            color: white;
            outline: none;
        }
        .form-control:focus {
            border-color: var(--secondary);
        }
        .btn-login {
            width: 100%;
            padding: 12px;
            background: var(--secondary);
            color: var(--dark);
            border: none;
            border-radius: 12px;
            font-weight: 700;
            cursor: pointer;
            margin-top: 10px;
            transition: 0.3s;
        }
        .btn-login:hover {
            background: #e59a1f;
            transform: scale(1.02);
        }
        .error-msg {
            background: rgba(255, 0, 0, 0.2);
            padding: 10px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-size: 14px;
            text-align: center;
        }
    </style>
</head>
<body data-theme="<?php echo get_setting('theme_mode', 'light'); ?>">
    <div class="login-card">
        <div style="text-align:center; margin-bottom: 20px;">
            <i class="fas fa-university fa-3x" style="color: var(--secondary);"></i>
        </div>
        <h2>Humas Hub</h2>
        <?php if ($error): ?>
            <div class="error-msg"><?php echo $error; ?></div>
        <?php endif; ?>
        <form method="POST">
            <div class="form-group">
                <label>Username</label>
                <input type="text" name="username" class="form-control" required autofocus>
            </div>
            <div class="form-group">
                <label>Password</label>
                <input type="password" name="password" class="form-control" required>
            </div>
            <button type="submit" class="btn-login">Masuk ke Dashboard</button>
        </form>
        <p style="text-align:center; font-size: 12px; margin-top: 30px; opacity: 0.6;">
            &copy; 2026 Humas UIN Ar-Raniry Banda Aceh
        </p>
    </div>
</body>
</html>
