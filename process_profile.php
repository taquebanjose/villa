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
        // Strip the metadata prefix (e.g., "data:image/png;base64,") to get the raw data
        if (preg_match('/^data:image\/(\w+);base64,/', $cropped_image, $type)) {
            $data = substr($cropped_image, strpos($cropped_image, ',') + 1);
            $type = strtolower($type[1]); // e.g., 'png', 'jpg'

            // Ensure the file extension is safe
            if (in_array($type, ['png', 'jpg', 'jpeg', 'gif'])) {
                $data = base64_decode($data);

                if ($data !== false) {
                    // Ensure the uploads directory exists
                    if (!is_dir('uploads')) {
                        mkdir('uploads', 0755, true);
                    }

                    // Generate a unique file name to avoid overwriting files
                    $image_filename = 'user_' . $user_id . '_' . time() . '.' . $type;
                    $upload_path = 'uploads/' . $image_filename;

                    // Save the raw file data
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

    // 4. Update the Database
    if ($image_filename) {
        // First, fetch the old image so we can delete it and save space on the server
        $stmt_old = $conn->prepare("SELECT image FROM users WHERE id = ?");
        $stmt_old->bind_param("i", $user_id);
        $stmt_old->execute();
        $old_user = $stmt_old->get_result()->fetch_assoc();
        
        if (!empty($old_user['image'])) {
            $old_image_path = 'uploads/' . $old_user['image'];
            if (file_exists($old_image_path)) {
                unlink($old_image_path); // Delete old avatar
            }
        }

        // Update name and new image
        $stmt = $conn->prepare("UPDATE users SET name = ?, image = ? WHERE id = ?");
        $stmt->bind_param("ssi", $name, $image_filename, $user_id);
    } else {
        // Only update name if no new image was cropped
        $stmt = $conn->prepare("UPDATE users SET name = ? WHERE id = ?");
        $stmt->bind_param("si", $name, $user_id);
    }

    if ($stmt->execute()) {
        header("Location: profile.php?msg=updated");
    } else {
        header("Location: profile.php?error=db_error");
    }
    
    $stmt->close();
    $conn->close();
    exit();
} else {
    // Redirect if they try to access the process file directly
    header("Location: profile.php");
    exit();
}