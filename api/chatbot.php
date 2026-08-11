<?php
// api/chatbot.php
header('Content-Type: application/json');
session_start();

// Disable HTML error display so PHP warnings don't break JSON output
ini_set('display_errors', 0);
error_reporting(E_ALL);

// 1. Database Connection (Path-robust using __DIR__)
$dbPath = __DIR__ . '/../db/connection.php';
if (file_exists($dbPath)) {
    require_once $dbPath;
} else {
    echo json_encode(['reply' => 'Database connection configuration missing.']);
    exit;
}

// 2. Parse User Input
$input = json_decode(file_get_contents('php://input'), true);
$userMessage = trim($input['message'] ?? '');

if (empty($userMessage)) {
    echo json_encode(['reply' => 'Please ask a valid question!']);
    exit;
}

// 3. Query Database
$roomInformation = "";
if (isset($conn) && !$conn->connect_error) {
    $query = "SELECT room_type, capacity, day_rate, night_rate FROM rooms WHERE status = 'available'";
    $result = $conn->query($query);

    if ($result && $result->num_rows > 0) {
        $roomInformation .= "Current Accommodation & Live Rates:\n";
        while ($row = $result->fetch_assoc()) {
            $roomInformation .= "- " . $row['room_type'] . ": Max Capacity " . $row['capacity'] . " persons. Day Rate: ₱" . number_format($row['day_rate'], 2) . " | Night Rate: ₱" . number_format($row['night_rate'], 2) . "\n";
        }
    }
}

if (empty($roomInformation)) {
    $roomInformation = "Note: Live room rate information is temporarily unavailable in the database.";
}

// 4. API Credentials & Prompt
$apiKey = '';

$systemContext = "You are 'Marcie', the hospitable AI receptionist for Villa Marciana Resort.\n" .
    "Answer guest inquiries politely using this LIVE DATABASE INFORMATION:\n\n" . $roomInformation . "\n\n" .
    "Resort Guidelines:\n" .
    "- Day Swim Shift: 8:00 AM – 5:00 PM\n" .
    "- Night Swim Shift: 6:00 PM – 6:00 AM\n" .
    "- Room Check-in: 2:00 PM | Check-out: 12:00 PM\n" .
    "- Direct guests to click 'Book Now' to make online reservations.\n" .
    "Keep replies warm, accurate, and concise.";

// 5. Send Request to Gemini API (Using v1 endpoint for gemini-1.5-flash)
$url = "https://generativelanguage.googleapis.com/v1/models/gemini-1.5-flash:generateContent?key=" . $apiKey;

$payload = [
    "system_instruction" => [
        "parts" => [
            ["text" => $systemContext]
        ]
    ],
    "contents" => [
        [
            "role" => "user", 
            "parts" => [
                ["text" => $userMessage]
            ]
        ]
    ]
];

$ch = curl_init();
curl_setopt_array($ch, [
    CURLOPT_URL => $url,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST => true,
    CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
    CURLOPT_POSTFIELDS => json_encode($payload),
    CURLOPT_SSL_VERIFYPEER => false,
    CURLOPT_SSL_VERIFYHOST => false,
    CURLOPT_TIMEOUT => 15
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curlError = curl_error($ch);
curl_close($ch);

// 6. Return Clean JSON
if ($httpCode === 200 && $response) {
    $responseData = json_decode($response, true);
    $aiReply = $responseData['candidates'][0]['content']['parts'][0]['text'] 
               ?? "I'm having a little trouble thinking right now. Please try again!";
    echo json_encode(['reply' => $aiReply]);
} else {
    $responseData = json_decode($response, true);
    $apiErrorMessage = $responseData['error']['message'] ?? null;
    
    $errorMsg = !empty($curlError) ? $curlError : ($apiErrorMessage ? "API Error: " . $apiErrorMessage : "HTTP Code " . $httpCode);
    echo json_encode(['reply' => "Connection Error (" . $errorMsg . "). Please check your API configuration or network."]);
}