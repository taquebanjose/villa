<?php
// admin/functions.php

/**
 * Logs admin actions for security auditing
 */
function logActivity($conn, $action, $details) {
    if (session_status() === PHP_SESSION_NONE) { 
        session_start(); 
    }
    
    $admin_id = $_SESSION['user_id'] ?? 0;
    $admin_name = $_SESSION['name'] ?? 'System';
    
    $stmt = $conn->prepare("INSERT INTO admin_logs (admin_id, admin_name, action_type, details) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("isss", $admin_id, $admin_name, $action, $details);
    $stmt->execute();
    $stmt->close();
}

/**
 * Returns a consistent HTML pill for reservation statuses
 * Colors are handled via CSS classes in style.css
 */
function getStatusPill($status) {
    $status_clean = strtolower(trim($status));
    
    // Map database status to a readable label
    // Added 'pending' to ensure it displays with the circle icon consistently
    $labels = [
        'confirmed'      => '✓ Confirmed',
        'pending'        => '○ Pending',
        'pending_cancel' => '⚠️ Cancellation Request',
        'cancelled'      => '✕ Cancelled',
        'archived'       => '📁 Archived'
    ];

    $display_text = isset($labels[$status_clean]) ? $labels[$status_clean] : strtoupper($status_clean);
    
    // The class 'status-pill' provides the shape, '$status_clean' provides the color
    return "<span class='status-pill status-$status_clean'>$display_text</span>";
}

/**
 * Formats database date to a more readable "resort" style
 * Example: "2026-02-12" -> "Thu, Feb 12, 2026"
 */
function formatResortDate($date) {
    if (!$date) return "N/A";
    return date("D, M d, Y", strtotime($date));
}
?>