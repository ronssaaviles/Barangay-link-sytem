<?php
// Start the session to maintain user login state
session_start();

// Include the database connection file
include('../../db/db-connect.php');

// Redirect to login page if the user is not logged in
if (!isset($_SESSION['username'])) {
    header("Location: ../index.php");
    exit();
}

// Get the logged-in username from the session
$username = $_SESSION['username'];

// Retrieve user data
$query = "SELECT * FROM users WHERE UserName = :username";
$stmt = $pdo->prepare($query);
$stmt->bindParam(':username', $username, PDO::PARAM_STR);
$stmt->execute();
$user = $stmt->fetch(PDO::FETCH_ASSOC);

// Retrieve resident information
$residentQuery = "
    SELECT r.ResidentID, r.FullName, r.Status
    FROM residents r
    JOIN users u ON r.UserID = u.UserID
    WHERE u.UserName = :username
";
$stmt = $pdo->prepare($residentQuery);
$stmt->bindParam(':username', $username, PDO::PARAM_STR);
$stmt->execute();
$resident = $stmt->fetch(PDO::FETCH_ASSOC);

// Ensure resident information exists
if (!$resident) {
    // If no resident record exists, create one
    try {
        $createResidentQuery = "
            INSERT INTO residents (
                FullName,
                BirthDate,
                Address,
                ContactNum,
                Status,
                UserID
            ) VALUES (
                :fullname,
                CURRENT_DATE(),
                'Not Provided',
                'Not Provided',
                'Active',
                :user_id
            )
        ";
        $stmt = $pdo->prepare($createResidentQuery);
        $fullName = $user['FirstName'] . ' ' . $user['LastName'];
        $stmt->bindParam(':fullname', $fullName, PDO::PARAM_STR);
        $stmt->bindParam(':user_id', $user['UserID'], PDO::PARAM_INT);
        $stmt->execute();

        // Retrieve the newly created resident information
        $residentId = $pdo->lastInsertId();
        $resident = [
            'ResidentID' => $residentId,
            'FullName' => $fullName,
            'Status' => 'Active'
        ];
    } catch (PDOException $e) {
        $errorMessage = "Error creating resident record: " . $e->getMessage();
        $resident = [
            'ResidentID' => null,
            'FullName' => $user['FirstName'] . ' ' . $user['LastName'],
            'Status' => 'Unknown'
        ];
    }
}

// Check if the request method is POST (for submitting feedback)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Retrieve feedback text from the submitted form
    $feedbackText = $_POST['feedback-text'] ?? '';

    // Validate feedback text is not empty and resident exists
    if (!empty($feedbackText) && $resident['ResidentID']) {
        try {
            // Insert the feedback into the database
            $insertQuery = "INSERT INTO feedback (Feedback_Text, Status, ResidentID) 
                          VALUES (:feedback_text, 'Pending', :resident_id)";
            $stmt = $pdo->prepare($insertQuery);
            $stmt->bindParam(':feedback_text', $feedbackText, PDO::PARAM_STR);
            $stmt->bindParam(':resident_id', $resident['ResidentID'], PDO::PARAM_INT);
            $stmt->execute();
            $successMessage = "Feedback submitted successfully!";
        } catch (PDOException $e) {
            // Handle database insertion error
            $errorMessage = "Error submitting feedback: " . $e->getMessage();
        }
    }
}

// Fetch feedback history for the logged-in user
if ($resident['ResidentID']) {
    $feedbackQuery = "SELECT * FROM feedback WHERE ResidentID = :resident_id ORDER BY Date_Submitted DESC";
    $stmt = $pdo->prepare($feedbackQuery);
    $stmt->bindParam(':resident_id', $resident['ResidentID'], PDO::PARAM_INT);
    $stmt->execute();
    $feedbackHistory = $stmt->fetchAll(PDO::FETCH_ASSOC);
} else {
    $feedbackHistory = [];
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Barangay Link | Feedback</title>
    <link rel="shortcut icon" href="../../img/logo.ico">
    <style>
        body {
            margin: 0;
            font-family: Arial, sans-serif;
            background-color: white;
        }

        .container {
            display: flex;
            height: 100vh;
        }

        .sidebar {
            width: 20%;
            background-color: #800000;
            color: white;
            display: flex;
            flex-direction: column;
            padding: 20px;
        }

        .user-section {
            margin-top: 50px;
            text-align: center;
            margin-bottom: 30px;
        }

        .logo {
            width: 150px;
            height: 150px;
            background-color: #d9d2c9;
            border-radius: 50%;
            margin: 0 auto;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            color: black;
        }

        .user-name {
            margin-top: 30px;
            font-size: 35px;
        }

        .menu {
            list-style: none;
            padding: 0;
            margin: 0;
        }

        .menu li {
            display: flex;
            align-items: center;
            padding: 15px;
            font-size: 30px;
            cursor: pointer;
            border-radius: 5px;
        }

        .menu a {
            text-decoration: none;
            color: white;
        }

        .menu li:not(:last-child) {
            margin-bottom: 10px;
        }

        .menu li:hover {
            background-color: #660000;
        }

        .menu-item.active {
            background-color: #660000;
        }

        .icon {
            width: 50px;
            height: 50px;
            background-color: #d9d2c9;
            border-radius: 50%;
            margin-right: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #800000;
        }

        .icon svg {
            width: 30px;
            height: 30px;
        }

        .content {
            flex-grow: 1;
            padding: 60px;
            overflow-y: auto;
        }

        .feedback-form {
            max-width: 800px;
            margin-left: 150px;
            margin-top: 40px;
        }

        .feedback-input {
            width: 100%;
            height: 200px;
            padding: 15px;
            margin-top: 20px;
            border: 2px solid black;
            border-radius: 4px;
            font-size: 16px;
            resize: none;
        }

        .button-group {
            display: flex;
            gap: 20px;
            justify-content: flex-end;
            margin-top: 30px;
            margin-right: -30px;
        }

        .btn {
            padding: 20px 60px;
            border: none;
            border-radius: 60px;
            font-size: 20px;
            cursor: pointer;
            color: white;
        }

        .btn-cancel {
            background-color: #800000;
        }

        .btn-submit {
            background-color: #800000;
        }

        .btn:hover {
            background-color: #660000;
        }

        .feedback-history {
            max-width: 800px;
            margin-left: 150px;
            margin-top: 60px;
            margin-bottom: 40px;
        }

        .history-title {
            font-size: 24px;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid #800000;
        }

        .feedback-card {
            background-color: #f5f5f5;
            border-left: 4px solid #800000;
            padding: 20px;
            margin-bottom: 20px;
            border-radius: 0 4px 4px 0;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        .feedback-card:hover {
            transform: translateX(5px);
            transition: transform 0.2s ease;
        }

        .feedback-meta {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
            color: #666;
            font-size: 14px;
        }

        .feedback-status {
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: bold;
        }

        .status-pending {
            background-color: #fff3cd;
            color: #856404;
        }

        .status-resolved {
            background-color: #d4edda;
            color: #155724;
        }

        .feedback-text {
            color: #333;
            line-height: 1.5;
        }

        .feedback-response {
            margin-top: 15px;
            padding-top: 15px;
            border-top: 1px solid #ddd;
            color: #555;
            font-style: italic;
        }
    </style>
</head>

<body>
    <div class="container">
        <!-- Sidebar -->

        <div class="sidebar">
            <div class="user-section">
                <img class="logo" src="../../img/logo.png" alt="">
                <p class="user-name"><?php echo htmlspecialchars($resident['FullName']); ?></p>
                </div>
            <ul class="menu">
                <a href="user-dashboard.php">
                    <li>
                        <span class="icon">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none"
                                stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <rect x="3" y="3" width="7" height="7"></rect>
                                <rect x="14" y="3" width="7" height="7"></rect>
                                <rect x="14" y="14" width="7" height="7"></rect>
                                <rect x="3" y="14" width="7" height="7"></rect>
                            </svg>
                        </span>
                        Dashboard
                    </li>
                </a>
                <a href="user-profile.php">
                    <li>
                        <span class="icon">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none"
                                stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                                <circle cx="12" cy="7" r="4"></circle>
                            </svg>
                        </span>
                        Profile
                    </li>
                </a>
                <a href="user-doc-request.php">
                    <li>
                        <span class="icon">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none"
                                stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                                <polyline points="14 2 14 8 20 8"></polyline>
                                <line x1="16" y1="13" x2="8" y2="13"></line>
                                <line x1="16" y1="17" x2="8" y2="17"></line>
                                <polyline points="10 9 9 9 8 9"></polyline>
                            </svg>
                        </span>
                        Document Request
                    </li>
                </a>
                <a href="#">
                    <li class="menu-item active">
                        <span class="icon">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none"
                                stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path>
                            </svg>
                        </span>
                        Feedback
                    </li>
                </a>
                <br><br><br><br><br><br><br><br>
                <a href="../../index.php">
                    <li>
                        <span class="icon">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none"
                                stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path>
                                <polyline points="16 17 21 12 16 7"></polyline>
                                <line x1="21" y1="12" x2="9" y2="12"></line>
                            </svg>
                        </span>
                        Logout
                    </li>
                </a>
            </ul>
        </div>

        <div class="content">
            <h1>FEEDBACK</h1>

            <?php if (isset($successMessage)): ?>
                <p style="color: green;"><?php echo $successMessage; ?></p>
            <?php elseif (isset($errorMessage)): ?>
                <p style="color: red;"><?php echo $errorMessage; ?></p>
            <?php endif; ?>

            <div class="feedback-form">
                <form method="POST">
                    <textarea name="feedback-text" class="feedback-input"
                        placeholder="Enter your feedback here..."></textarea>
                    <div class="button-group">
                        <button type="button" class="btn btn-cancel" onclick="cancelFeedback()">CANCEL</button>
                        <button type="submit" class="btn btn-submit">SUBMIT</button>
                    </div>
                </form>
            </div>

            <div class="feedback-history">
                <h2 class="history-title">FEEDBACK HISTORY</h2>
                <?php foreach ($feedbackHistory as $feedback): ?>
                    <div class="feedback-card">
                        <div class="feedback-meta">
                            <span><?php echo date('F d, Y', strtotime($feedback['Date_Submitted'])); ?></span>
                            <span
                                class="feedback-status <?php echo strtolower($feedback['Status']) === 'resolved' ? 'status-resolved' : 'status-pending'; ?>">
                                <?php echo htmlspecialchars($feedback['Status']); ?>
                            </span>
                        </div>
                        <div class="feedback-text">
                            <?php echo htmlspecialchars($feedback['Feedback_Text']); ?>
                        </div>
                        <?php if (!empty($feedback['admin_reply'])): ?>
                            <div class="feedback-response">
                                <strong>Admin Response:</strong>
                                <?php echo htmlspecialchars($feedback['admin_reply']); ?>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <script>
        /**
         * Clears the feedback text area when the cancel button is clicked.
         */
        function cancelFeedback() {
            document.querySelector('textarea[name="feedback-text"]').value = '';
        }
    </script>

</body>

</html>