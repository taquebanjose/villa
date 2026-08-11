<?php
session_start();
include 'db/connection.php';

$error = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];

    if (empty($name) || empty($email) || empty($password)) {
        $error = "Please fill in all fields.";
    } else {
        // Check if email already exists
        $check = $conn->prepare("SELECT id FROM users WHERE email = ?");
        $check->bind_param("s", $email);
        $check->execute();
        $check->store_result();

        if ($check->num_rows > 0) {
            $error = "An account with this email already exists.";
        } else {
            $check->close();

            // Hash password securely
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);

            $stmt = $conn->prepare("INSERT INTO users (name, email, password) VALUES (?, ?, ?)");
            $stmt->bind_param("sss", $name, $email, $hashed_password);

            if ($stmt->execute()) {
                $_SESSION['user_id'] = $conn->insert_id;
                $_SESSION['user_name'] = $name;
                // Redirect straight to policy page upon successful login
                header("Location: policy.php");
                exit();
            } else {
                $error = "Registration failed. Please try again.";
            }
            $stmt->close();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Account | Villa Marciana</title>
    <link rel="stylesheet" href="css/style.css">
    <style>
        body.light {
            background: #fcfbfa;
            font-family: 'Montserrat', sans-serif;
            margin: 0; padding: 0;
            display: flex; justify-content: center; align-items: center;
            min-height: 100vh;
            background: radial-gradient(circle at top right, rgba(212, 175, 55, 0.05), transparent 50%), #fcfbfa;
        }

        .auth-card {
            background: rgba(255, 255, 255, 0.8);
            backdrop-filter: blur(25px) saturate(180%);
            -webkit-backdrop-filter: blur(25px) saturate(180%);
            border: 1px solid rgba(255, 255, 255, 0.9);
            border-radius: 32px;
            padding: 50px 40px;
            max-width: 420px;
            width: 90%;
            box-shadow: 0 30px 70px rgba(212, 175, 55, 0.08), 0 10px 30px rgba(0, 0, 0, 0.02);
            animation: fadeIn 0.8s cubic-bezier(0.16, 1, 0.3, 1);
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .auth-header {
            text-align: center;
            margin-bottom: 35px;
        }

        .auth-header h1 {
            font-family: 'Cinzel', serif;
            font-weight: 400;
            color: #d4af37;
            margin: 0 0 10px;
            font-size: 1.8rem;
        }

        .auth-header p {
            color: #777;
            font-size: 0.85rem;
            margin: 0;
            letter-spacing: 0.5px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            font-size: 0.78rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: #444;
            margin-bottom: 8px;
        }

        .modern-input {
            width: 100%;
            padding: 15px 20px;
            background: rgba(255, 255, 255, 0.9);
            border: 1px solid rgba(0, 0, 0, 0.08);
            border-radius: 16px;
            font-size: 0.95rem;
            color: #111;
            box-sizing: border-box;
            transition: all 0.3s ease;
        }

        .modern-input:focus {
            outline: none;
            border-color: #d4af37;
            box-shadow: 0 0 0 4px rgba(212, 175, 55, 0.1);
            background: #fff;
        }

        .auth-btn {
            background: #d4af37;
            color: #fff;
            width: 100%;
            padding: 16px;
            border-radius: 50px;
            border: none;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 1.5px;
            font-size: 0.85rem;
            cursor: pointer;
            transition: all 0.3s ease;
            margin-top: 10px;
            box-shadow: 0 8px 25px rgba(212, 175, 55, 0.2);
        }

        .auth-btn:hover {
            background: #b8931d;
            transform: translateY(-2px);
        }

        .error-msg {
            background: rgba(198, 40, 40, 0.08);
            border: 1px solid rgba(198, 40, 40, 0.2);
            color: #c62828;
            padding: 12px;
            border-radius: 12px;
            font-size: 0.85rem;
            text-align: center;
            margin-bottom: 20px;
        }

        .auth-footer {
            text-align: center;
            margin-top: 30px;
            font-size: 0.85rem;
            color: #666;
        }

        .auth-footer a {
            color: #d4af37;
            text-decoration: none;
            font-weight: 700;
        }

        .auth-footer a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body class="light">

    <div class="auth-card">
        <div class="auth-header">
            <h1 onclick="location.href='index.php'" style="cursor:pointer;">Villa Marciana</h1>
            <p>Begin your luxury reservation journey.</p>
        </div>

        <?php if (!empty($error)): ?>
            <div class="error-msg"><?= htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <form action="register.php" method="POST">
            <div class="form-group">
                <label>Full Name</label>
                <input type="text" name="name" class="modern-input" required placeholder="John Doe">
            </div>

            <div class="form-group">
                <label>Email Address</label>
                <input type="email" name="email" class="modern-input" required placeholder="guest@example.com">
            </div>

            <div class="form-group">
                <label>Password</label>
                <input type="password" name="password" class="modern-input" required placeholder="••••••••">
            </div>

            <button type="submit" class="auth-btn">Create Account</button>
        </form>

        <div class="auth-footer">
            Already have an account? <a href="login.php">Sign In</a>
        </div>
    </div>

</body>
</html>