<?php
include 'db/connection.php';

header('Content-Type: application/json');

$date = isset($_GET['date']) ? $_GET['date'] : '';

// Default: everything is open
$availability = [
    'day' => true,
    'night' => true,
    '22hr' => true
];

if (!empty($date)) {
    // Fetch all active/approved bookings for the specified date using PDO
    $stmt = $pdo->prepare("SELECT session_type FROM bookings WHERE booking_date = ? AND status != 'cancelled'");
    $stmt->execute([$date]);
    
    $booked_sessions = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $booked_sessions[] = $row['session_type'];
    }

    // --- SMART SESSION-LENGTH CONSTRAINTS ---
    // Rule A: If a 22-hour session is booked, nothing else can be booked that day.
    if (in_array('22hr', $booked_sessions)) {
        $availability['day'] = false;
        $availability['night'] = false;
        $availability['22hr'] = false;
    }
    
    // Rule B: If a Day Session is already booked, "Day" and "22-hour" are blocked.
    if (in_array('day', $booked_sessions)) {
        $availability['day'] = false;
        $availability['22hr'] = false;
    }

    // Rule C: If a Night Session is already booked, "Night" and "22-hour" are blocked.
    if (in_array('night', $booked_sessions)) {
        $availability['night'] = false;
        $availability['22hr'] = false;
    }
}

echo json_encode($availability);