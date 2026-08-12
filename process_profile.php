<?php
session_start();
include 'db/connection.php';

// 1. Check if the user is authenticated
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// 2. Check if the request is a POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name']);
    $cropped_image = isset($_POST['cropped_image']) ? $_POST['cropped_image'] : '';

    // Basic validation
    if (empty($name)) {
        header("Location: profile.php?error=name_required");
        exit();
    }

    $image_filename = null;

    // 3. Process the Base64 Croppie Image
    if (!empty($cropped_image)) {
        if (preg_match('/^data:image\/(\w+);base64,/', $cropped_image, $type)) {
            $data = substr($cropped_image, strpos($cropped_image, ',') + 1);
            $type = strtolower($type[1]);

            if (in_array($type, ['png', 'jpg', 'jpeg', 'gif'])) {
                $data = base64_decode($data);

                if ($data !== false) {
                    if (!is_dir('uploads')) {
                        mkdir('uploads', 0755, true);
                    }

                    $image_filename = 'user_' . $user_id . '_' . time() . '.' . $type;
                    $upload_path = 'uploads/' . $image_filename;

                    if (!file_put_contents($upload_path, $data)) {
                        header("Location: profile.php?error=upload_failed");
                        exit();
                    }
                } else {
                    header("Location: profile.php?error=invalid_image_data");
                    exit();
                }
            } else {
                header("Location: profile.php?error=invalid_file_type");
                exit();
            }
        }
    }

    // 4. Update the Database using PDO
    if ($image_filename) {
        // Fetch old image to delete it
        $stmt_old = $pdo->prepare("SELECT image FROM users WHERE id = ?");
        $stmt_old->execute([$user_id]);
        $old_user = $stmt_old->fetch(PDO::FETCH_ASSOC);
        
        if (!empty($old_user['image'])) {
            $old_image_path = 'uploads/' . $old_user['image'];
            if (file_exists($old_image_path)) {
                unlink($old_image_path);
            }
        }

        // Update name and new image
        $stmt = $pdo->prepare("UPDATE users SET name = ?, image = ? WHERE id = ?");
        $success = $stmt->execute([$name, $image_filename, $user_id]);
    } else {
        // Only update name
        $stmt = $pdo->prepare("UPDATE users SET name = ? WHERE id = ?");
        $success = $stmt->execute([$name, $user_id]);
    }

    if ($success) {
        header("Location: profile.php?msg=updated");
    } else {
        header("Location: profile.php?error=db_error");
    }
    
    exit();
} else {
    header("Location: profile.php");
    exit();
}