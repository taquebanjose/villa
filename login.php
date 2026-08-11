<?php
session_start();
include 'db/connection.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);
    $password = $_POST['password'];

    if (!empty($email) && !empty($password)) {
        /** @var mysqli $conn */
        // UPDATED: Added 'role' to the selected columns
        $stmt = $conn->prepare("SELECT id, name, password, role FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($row = $result->fetch_assoc()) {
            if (password_verify($password, $row['password'])) {
                // Set session variables (including the role!)
                $_SESSION['user_id'] = $row['id'];
                $_SESSION['user_name'] = $row['name'];
                $_SESSION['role'] = $row['role']; // <--- THIS WAS MISSING

                // Redirect straight to policy page upon successful login
                header("Location: policy.php");
                exit();
            } else {
                $error = "Invalid email or password.";
            }
        } else {
            $error = "Invalid email or password.";
        }
    } else {
        $error = "Please fill in all fields.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign In | Villa Marciana</title>
    <link rel="stylesheet" href="css/style.css">
    <style>
        body.light {
            background: #fcfbfa;
            font-family: 'Montserrat', sans-serif;
            margin: 0; padding: 0;
            color: #444;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
        }

        .auth-card {
            background: rgba(255, 255, 255, 0.85);
            backdrop-filter: blur(25px);
            -webkit-backdrop-filter: blur(25px);
            border: 1px solid rgba(255, 255, 255, 0.9);
            border-radius: 32px;
            padding: 40px;
            width: 100%;
            max-width: 400px;
            box-shadow: 0 30px 70px rgba(212, 175, 55, 0.05), 0 10px 30px rgba(0, 0, 0, 0.02);
            box-sizing: border-box;
        }

        .auth-header {
            text-align: center;
            margin-bottom: 30px;
        }

        .auth-header h1 {
            font-family: 'Cinzel', serif;
            color: #111;
            font-size: 1.5rem;
            margin-bottom: 5px;
        }

        .auth-header p {
            font-size: 0.8rem;
            color: #666;
            text-transform: uppercase;
            letter-spacing: 1px;
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

        .error-msg {
            background: rgba(255, 0, 0, 0.05);
            border: 1px solid rgba(255, 0, 0, 0.1);
            color: #d9534f;
            padding: 12px;
            border-radius: 12px;
            font-size: 0.85rem;
            text-align: center;
            margin-bottom: 20px;
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
            font-size: 0.85rem;
            cursor: pointer;
            transition: all 0.3s ease;
            letter-spacing: 1.5px;
            box-shadow: 0 8px 25px rgba(212, 175, 55, 0.2);
        }

        .auth-btn:hover {
            background: #b8931d;
            transform: translateY(-2px);
        }

        .auth-footer {
            text-align: center;
            margin-top: 25px;
            font-size: 0.85rem;
            color: #666;
        }

        .auth-footer a {
            color: #d4af37;
            text-decoration: none;
            font-weight: 600;
        }

        .auth-footer a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body class="light">

<div class="auth-card">
    <div class="auth-header">
        <h1>Villa Marciana</h1>
        <p>Sign In to Your Account</p>
    </div>

    <?php if (!empty($error)): ?>
        <div class="error-msg"><?php echo $error; ?></div>
    <?php endif; ?>

    <form action="login.php" method="POST">
        <div class="form-group">
            <label>Email Address</label>
            <input type="email" name="email" class="modern-input" placeholder="Enter your email" required>
        </div>

        <div class="form-group">
            <label>Password</label>
            <input type="password" name="password" class="modern-input" placeholder="Enter your password" required>
        </div>

        <button type="submit" class="auth-btn">Sign In</button>
    </form>

    <div class="auth-footer">
        Don't have an account? <a href="register.php">Register</a>
    </div>
</div>

</body>
</html>