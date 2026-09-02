<?php
// Halaman Login Administrator
require_once 'config/database.php';
require_once 'includes/auth.php';

// Jika sudah login, langsung alihkan ke dashboard
if (is_logged_in()) {
    header("Location: index.php");
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($username) || empty($password)) {
        $error = 'Username dan Password wajib diisi.';
    } else {
        try {
            $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
            $stmt->execute([$username]);
            $user = $stmt->fetch();

            if ($user && password_verify($password, $user['password'])) {
                // Set variabel sesi login
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_name'] = $user['name'];
                $_SESSION['last_activity'] = time();
                
                header("Location: index.php");
                exit;
            } else {
                $error = 'Username atau Password salah.';
            }
        } catch (\PDOException $e) {
            $error = 'Kesalahan database: ' . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - EDMS RSGM Universitas Airlangga</title>
    <!-- Google Fonts: Inter -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <!-- Google Material Symbols Outlined -->
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200&display=block" rel="stylesheet">
    <style>
        :root {
            --primary: #031636;
            --primary-light: #1a2b4c;
            --secondary: #804a89;
            --background: #f8f9ff;
            --surface: #ffffff;
            --surface-variant: #e5eeff;
            --on-surface: #0b1c30;
            --on-surface-variant: #44474e;
            --error: #ba1a1a;
            --error-container: #ffdad6;
            --on-error-container: #93000a;
            --outline: #75777f;
            --radius-xl: 1rem;
            --radius-lg: 0.5rem;
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, #eff4ff 0%, #dce9ff 100%);
            color: var(--on-surface);
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            padding: 20px;
        }

        .login-container {
            width: 100%;
            max-width: 420px;
            background: rgba(255, 255, 255, 0.7);
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
            border: 1px solid rgba(255, 255, 255, 0.5);
            border-radius: var(--radius-xl);
            padding: 40px 30px;
            box-shadow: 0 10px 30px rgba(3, 22, 54, 0.08);
            text-align: center;
        }

        .logo-section {
            margin-bottom: 30px;
        }

        .logo-icon {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 60px;
            height: 60px;
            background: linear-gradient(135deg, var(--secondary) 0%, var(--primary) 100%);
            color: white;
            border-radius: 50%;
            margin-bottom: 15px;
            box-shadow: 0 4px 15px rgba(128, 74, 137, 0.3);
        }

        .logo-icon span {
            font-size: 32px;
        }

        h2 {
            font-size: 24px;
            font-weight: 700;
            color: var(--primary);
            margin-bottom: 6px;
        }

        .subtitle {
            font-size: 13px;
            color: var(--on-surface-variant);
        }

        .error-message {
            background-color: var(--error-container);
            color: var(--on-error-container);
            padding: 12px;
            border-radius: var(--radius-lg);
            font-size: 13px;
            margin-bottom: 20px;
            text-align: left;
            display: flex;
            align-items: center;
            gap: 8px;
            border: 1px solid rgba(186, 26, 26, 0.1);
        }

        .error-message span {
            font-size: 18px;
        }

        .form-group {
            margin-bottom: 20px;
            text-align: left;
        }

        label {
            display: block;
            font-size: 12px;
            font-weight: 600;
            color: var(--on-surface-variant);
            text-transform: uppercase;
            letter-spacing: 0.05em;
            margin-bottom: 6px;
        }

        .input-wrapper {
            position: relative;
            display: flex;
            align-items: center;
        }

        .input-wrapper span {
            position: absolute;
            left: 12px;
            color: var(--on-surface-variant);
            font-size: 20px;
        }

        input {
            width: 100%;
            padding: 12px 12px 12px 42px;
            border: 1px solid var(--outline);
            border-radius: var(--radius-lg);
            font-size: 14px;
            background: var(--surface);
            color: var(--on-surface);
            outline: none;
            transition: all 0.2s ease;
        }

        input:focus {
            border-color: var(--secondary);
            box-shadow: 0 0 0 3px rgba(128, 74, 137, 0.15);
        }

        .btn-submit {
            width: 100%;
            padding: 14px;
            background: linear-gradient(135deg, var(--secondary) 0%, var(--primary) 100%);
            color: white;
            border: none;
            border-radius: var(--radius-lg);
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s ease;
            box-shadow: 0 4px 12px rgba(3, 22, 54, 0.15);
            margin-top: 10px;
        }

        .btn-submit:hover {
            opacity: 0.95;
            transform: translateY(-1px);
            box-shadow: 0 6px 15px rgba(3, 22, 54, 0.2);
        }

        .btn-submit:active {
            transform: translateY(0);
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="logo-section">
            <div class="logo-icon">
                <span class="material-symbols-outlined">folder_managed</span>
            </div>
            <h2>EDMS RSGM</h2>
            <p class="subtitle">Universitas Airlangga</p>
        </div>

        <?php if (!empty($error)): ?>
            <div class="error-message">
                <span class="material-symbols-outlined">error</span>
                <div><?php echo htmlspecialchars($error); ?></div>
            </div>
        <?php endif; ?>

        <form method="POST" action="">
            <div class="form-group">
                <label for="username">Username</label>
                <div class="input-wrapper">
                    <span class="material-symbols-outlined">person</span>
                    <input type="text" id="username" name="username" placeholder="Masukkan username admin" required autocomplete="username">
                </div>
            </div>
            
            <div class="form-group">
                <label for="password">Kata Sandi</label>
                <div class="input-wrapper">
                    <span class="material-symbols-outlined">lock</span>
                    <input type="password" id="password" name="password" placeholder="Masukkan kata sandi" required autocomplete="current-password">
                </div>
            </div>

            <button type="submit" class="btn-submit">Masuk Ke Sistem</button>
        </form>
    </div>
</body>
</html>
