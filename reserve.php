<?php
// 1. Include the PDO database connection
require_once 'db/connection.php';

// 2. Fetch existing active reservations to filter calendar availability
$booked_slots = [];

try {
    // Select booked dates and slot types for confirmed or pending reservations
    $stmt = $pdo->prepare("
        SELECT reservation_date, slot_type 
        FROM reservations 
        WHERE status IN ('confirmed', 'pending')
    ");
    $stmt->execute();
    $reservations = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Group booked slots by date
    foreach ($reservations as $row) {
        $date = $row['reservation_date'];
        $slot = strtolower($row['slot_type']); // Expecting: 'day', 'night', or '22hr'
        
        if (!isset($booked_slots[$date])) {
            $booked_slots[$date] = [];
        }
        $booked_slots[$date][] = $slot;
    }
} catch (PDOException $e) {
    error_log("Database error in reserve.php: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Villa Reservation</title>
    
    <!-- Flatpickr CSS for Datepicker -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    
    <style>
        :root {
            --bg-color: #f8f9fa;
            --text-color: #212529;
            --card-bg: #ffffff;
            --accent-color: #0d6efd;
            --border-color: #dee2e6;
        }

        [data-theme="dark"] {
            --bg-color: #121212;
            --text-color: #e0e0e0;
            --card-bg: #1e1e1e;
            --accent-color: #3d8bfd;
            --border-color: #333333;
        }

        body {
            background-color: var(--bg-color);
            color: var(--text-color);
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            transition: background-color 0.3s, color 0.3s;
            margin: 0;
            padding: 40px 20px;
        }

        .container {
            max-width: 600px;
            margin: 0 auto;
            background: var(--card-bg);
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            border: 1px solid var(--border-color);
        }

        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
        }

        h2 {
            margin: 0;
        }

        .theme-toggle {
            background: none;
            border: 1px solid var(--border-color);
            color: var(--text-color);
            padding: 8px 14px;
            border-radius: 20px;
            cursor: pointer;
        }

        .form-group {
            margin-bottom: 20px;
        }

        label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
        }

        input[type="text"], input[type="email"], select {
            width: 100%;
            padding: 12px;
            border-radius: 6px;
            border: 1px solid var(--border-color);
            background-color: var(--bg-color);
            color: var(--text-color);
            box-sizing: border-box;
        }

        button[type="submit"] {
            width: 100%;
            padding: 14px;
            background-color: var(--accent-color);
            color: #ffffff;
            border: none;
            border-radius: 6px;
            font-size: 16px;
            font-weight: bold;
            cursor: pointer;
            transition: opacity 0.2s;
        }

        button[type="submit"]:hover {
            opacity: 0.9;
        }
    </style>
</head>
<body>

<div class="container">
    <div class="header">
        <h2>Book Your Stay</h2>
        <button type="button" class="theme-toggle" id="themeToggleBtn">Toggle Mode</button>
    </div>

    <form action="process_booking.php" method="POST">
        <div class="form-group">
            <label for="full_name">Full Name</label>
            <input type="text" id="full_name" name="full_name" required placeholder="John Doe">
        </div>

        <div class="form-group">
            <label for="email">Email Address</label>
            <input type="email" id="email" name="email" required placeholder="john@example.com">
        </div>

        <div class="form-group">
            <label for="reservation_date">Select Date</label>
            <input type="text" id="reservation_date" name="reservation_date" placeholder="Select Date" required readonly>
        </div>

        <div class="form-group">
            <label for="slot_type">Session Slot</label>
            <select id="slot_type" name="slot_type" required>
                <option value="" disabled selected>-- Select a Date First --</option>
                <option value="day" id="opt-day">Day Session (08:00 AM - 05:00 PM)</option>
                <option value="night" id="opt-night">Night Session (07:00 PM - 06:00 AM)</option>
                <option value="22hr" id="opt-22hr">22-Hour Stay (08:00 AM - 06:00 AM)</option>
            </select>
        </div>

        <button type="submit">Proceed to Reservation</button>
    </form>
</div>

<!-- Flatpickr JS -->
<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
<script>
    // Pass PHP-fetched booked slots to JavaScript
    const bookedSlotsData = <?php echo json_encode($booked_slots); ?>;

    // Light/Dark Theme Logic
    const themeBtn = document.getElementById('themeToggleBtn');
    const savedTheme = localStorage.getItem('theme') || 'light';
    document.documentElement.setAttribute('data-theme', savedTheme);

    themeBtn.addEventListener('click', () => {
        const currentTheme = document.documentElement.getAttribute('data-theme');
        const newTheme = currentTheme === 'dark' ? 'light' : 'dark';
        document.documentElement.setAttribute('data-theme', newTheme);
        localStorage.setItem('theme', newTheme);
    });

    // Flatpickr Calendar & Availability Logic
    const slotSelect = document.getElementById('slot_type');
    const optDay = document.getElementById('opt-day');
    const optNight = document.getElementById('opt-night');
    const opt22hr = document.getElementById('opt-22hr');

    flatpickr("#reservation_date", {
        dateFormat: "Y-m-d",
        minDate: "today",
        disable: [
            function(date) {
                // Format JavaScript date object to YYYY-MM-DD
                const formattedDate = date.toISOString().split('T')[0];
                const slots = bookedSlotsData[formattedDate];

                if (slots) {
                    // Disable date completely if 22-hour stay is taken OR both Day and Night are booked
                    if (slots.includes('22hr') || (slots.includes('day') && slots.includes('night'))) {
                        return true;
                    }
                }
                return false;
            }
        ],
        onChange: function(selectedDates, dateStr) {
            // Reset dropdown options visibility
            optDay.disabled = false;
            optDay.style.display = 'block';
            optNight.disabled = false;
            optNight.style.display = 'block';
            opt22hr.disabled = false;
            opt22hr.style.display = 'block';
            slotSelect.value = '';

            const slotsOnDate = bookedSlotsData[dateStr] || [];

            // If Day session is taken, hide Day option and 22-Hour option
            if (slotsOnDate.includes('day')) {
                optDay.disabled = true;
                optDay.style.display = 'none';
                opt22hr.disabled = true;
                opt22hr.style.display = 'none';
                slotSelect.value = 'night'; // Auto-select remaining option
            }

            // If Night session is taken, hide Night option and 22-Hour option
            if (slotsOnDate.includes('night')) {
                optNight.disabled = true;
                optNight.style.display = 'none';
                opt22hr.disabled = true;
                opt22hr.style.display = 'none';
                slotSelect.value = 'day'; // Auto-select remaining option
            }
        }
    });
</script>

</body>
</html>