<?php
session_start();
include('../../db/db-connect.php');

// Check if the user is logged in and has the 'admin' role
if (!isset($_SESSION['username']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../index.php");
    exit();
}

// Fetch the details of the logged-in admin user
$username = $_SESSION['username'];
$query = "SELECT UserID, FirstName, LastName FROM users WHERE UserName = :username AND Role = 'admin'";
$stmt = $pdo->prepare($query);
$stmt->bindParam(':username', $username, PDO::PARAM_STR);
$stmt->execute();
$adminData = $stmt->fetch(PDO::FETCH_ASSOC);

// Handle POST requests for feedback actions (delete, update, reply)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'delete':
                // Delete feedback by IDs
                if (isset($_POST['feedback_ids'])) {
                    $feedback_ids = $_POST['feedback_ids'];
                    $deleteQuery = "DELETE FROM feedback WHERE FeedbackID IN (" . implode(',', array_map('intval', $feedback_ids)) . ")";
                    $pdo->exec($deleteQuery);
                }
                break;

            case 'update':
                // Update feedback status
                if (isset($_POST['feedback_id']) && isset($_POST['status'])) {
                    $feedbackId = $_POST['feedback_id'];
                    $status = $_POST['status'];
                    $updateQuery = "UPDATE feedback SET Status = :status WHERE FeedbackID = :id";
                    $stmt = $pdo->prepare($updateQuery);
                    $stmt->bindParam(':status', $status, PDO::PARAM_STR);
                    $stmt->bindParam(':id', $feedbackId, PDO::PARAM_INT);
                    $stmt->execute();
                }
                break;

            case 'reply':
                // Add an admin reply to feedback
                if (isset($_POST['feedback_id']) && isset($_POST['admin_reply'])) {
                    $feedbackId = $_POST['feedback_id'];
                    $adminReply = trim($_POST['admin_reply']);
                    $adminId = $adminData['UserID'];

                    $replyQuery = "UPDATE feedback SET admin_reply = :reply, admin_id = :admin_id, Status = 'Resolved' WHERE FeedbackID = :id";
                    $stmt = $pdo->prepare($replyQuery);
                    $stmt->bindParam(':reply', $adminReply, PDO::PARAM_STR);
                    $stmt->bindParam(':admin_id', $adminId, PDO::PARAM_INT);
                    $stmt->bindParam(':id', $feedbackId, PDO::PARAM_INT);
                    $stmt->execute();
                }
                break;
        }
        // Reload the page after action
        header("Location: " . $_SERVER['PHP_SELF']);
        exit();
    }
}

// Fetch all feedback records with resident and admin details
$feedbackQuery = "SELECT f.*, r.FullName, u.FirstName AS AdminFirstName, u.LastName AS AdminLastName
                  FROM feedback f
                  JOIN residents r ON f.ResidentID = r.ResidentID
                  LEFT JOIN users u ON f.admin_id = u.UserID
                  ORDER BY f.Date_Submitted DESC";
$stmt = $pdo->prepare($feedbackQuery);
$stmt->execute();
$feedbackList = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Feedback | Admin</title>
    <link rel="shortcut icon" href="../../img/logo.ico">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: Arial, sans-serif;
        }

        .container {
            display: flex;
            min-height: 100vh;
        }

        .sidebar {
            width: 280px;
            background-color: #8B0000;
            color: white;
            padding: 20px;
            display: flex;
            flex-direction: column;
        }

        .logo-section {
            text-align: center;
            margin-bottom: 40px;
        }

        .logo {
            width: 120px;
            height: 120px;
            background-color: #d9d2c9;
            border-radius: 50%;
            margin: 0 auto 15px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            color: black;
        }

        .admin-title {
            font-size: 18px;
            margin-top: 10px;
        }

        .menu {
            list-style: none;
            margin-top: 20px;
        }

        .menu-item {
            display: flex;
            align-items: center;
            padding: 12px 15px;
            margin-bottom: 10px;
            cursor: pointer;
            border-radius: 5px;
            transition: background-color 0.3s;
            text-decoration: none;
            color: white;
        }

        .menu-item:hover {
            background-color: #660000;
        }

        .menu-item.active {
            background-color: #660000;
        }

        .icon {
            width: 35px;
            height: 35px;
            background-color: #d9d2c9;
            border-radius: 50%;
            margin-right: 15px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #800000;
        }

        .icon svg {
            width: 20px;
            height: 20px;
        }

        .main-content {
            flex: 1;
            padding: 40px;
            background-color: #f4f4f4;
        }

        .feedback-card {
            background-color: white;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            margin-bottom: 20px;
            padding: 20px;
        }

        .feedback-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
            border-bottom: 1px solid #eee;
            padding-bottom: 10px;
        }

        .feedback-body {
            margin-bottom: 15px;
        }

        .feedback-actions {
            display: flex;
            gap: 10px;
        }

        .btn {
            padding: 8px 15px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            background-color: #8B0000;
            color: white;
        }

        .btn-reply {
            background-color: #28a745;
        }

        .admin-reply {
            background-color: #f8f9fa;
            border-left: 4px solid #28a745;
            padding: 10px;
            margin-top: 15px;
        }

        .reply-textarea {
            width: 100%;
            min-height: 100px;
            margin-bottom: 10px;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
        }

        .status-badge {
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 0.8em;
        }

        .status-pending {
            background-color: #ffc107;
            color: black;
        }

        .status-resolved {
            background-color: #28a745;
            color: white;
        }
    </style>
</head>

<body>
    <div class="container">
        <!-- Sidebar -->
        <div class="sidebar">
            <div class="logo-section">
                <img class="logo" src="../../img/logo.png" alt="">
                <div class="admin-title">SYSTEM ADMINISTRATOR</div>
            </div>
            <div class="menu">
                <a href="admin-dashboard.php" class="menu-item">
                    <span class="icon">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                            stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <rect x="3" y="3" width="7" height="7"></rect>
                            <rect x="14" y="3" width="7" height="7"></rect>
                            <rect x="14" y="14" width="7" height="7"></rect>
                            <rect x="3" y="14" width="7" height="7"></rect>
                        </svg>
                    </span>
                    Dashboard
                </a>
                <a href="admin-profile.php" class="menu-item">
                    <span class="icon">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                            stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                            <circle cx="12" cy="7" r="4"></circle>
                        </svg>
                    </span>
                    Profile
                </a>
                <a href="admin-residents.php" class="menu-item">
                    <span class="icon">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                            stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                            <circle cx="9" cy="7" r="4"></circle>
                            <path d="M23 21v-2a4 4 0 0 0-3-3.87"></path>
                            <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
                        </svg>
                    </span>
                    Residents
                </a>
                <a href="admin-doc-request.php" class="menu-item">
                    <span class="icon">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                            stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                            <polyline points="14 2 14 8 20 8"></polyline>
                            <line x1="16" y1="13" x2="8" y2="13"></line>
                            <line x1="16" y1="17" x2="8" y2="17"></line>
                            <polyline points="10 9 9 9 8 9"></polyline>
                        </svg>
                    </span>
                    Document Request
                </a>
                <a href="#" class="menu-item active">
                    <span class="icon">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                            stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path>
                        </svg>
                    </span>
                    Feedback
                </a>
                <a href="../../index.php" class="menu-item logout">
                    <span class="icon">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                            stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path>
                            <polyline points="16 17 21 12 16 7"></polyline>
                            <line x1="21" y1="12" x2="9" y2="12"></line>
                        </svg>
                    </span>
                    Logout
                </a>
            </div>
        </div>

        <div class="main-content">
            <h1 style="margin-bottom: 20px; color: #333;">Feedback Management</h1>

            <?php foreach ($feedbackList as $feedback): ?>
                <div class="feedback-card">
                    <div class="feedback-header">
                        <div>
                            <strong><?php echo htmlspecialchars($feedback['FullName']); ?></strong>
                            <span style="margin-left: 10px; color: #666;">
                                <?php echo date('Y-m-d H:i', strtotime($feedback['Date_Submitted'])); ?>
                            </span>
                        </div>
                        <span
                            class="status-badge <?php echo $feedback['Status'] == 'Resolved' ? 'status-resolved' : 'status-pending'; ?>">
                            <?php echo htmlspecialchars($feedback['Status']); ?>
                        </span>
                    </div>

                    <div class="feedback-body">
                        <?php echo htmlspecialchars($feedback['Feedback_Text']); ?>
                    </div>

                    <div class="feedback-actions">
                        <button class="btn" onclick="showReplyModal(<?php echo $feedback['FeedbackID']; ?>)">Reply</button>
                        <button class="btn" onclick="deleteFeedback(<?php echo $feedback['FeedbackID']; ?>)">Delete</button>
                    </div>

                    <?php if (!empty($feedback['admin_reply'])): ?>
                        <div class="admin-reply">
                            <strong>Admin Reply
                                (<?php echo htmlspecialchars($feedback['AdminFirstName'] . ' ' . $feedback['AdminLastName']); ?>):</strong>
                            <p><?php echo htmlspecialchars($feedback['admin_reply']); ?></p>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Reply Modal -->
    <div id="replyModal"
        style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.5); z-index:1000;">
        <div style="background:white; width:500px; margin:100px auto; padding:20px; border-radius:10px;">
            <h2>Admin Reply</h2>
            <form id="replyForm" method="POST">
                <input type="hidden" name="action" value="reply">
                <input type="hidden" name="feedback_id" id="replyFeedbackId">
                <textarea class="reply-textarea" name="admin_reply" placeholder="Type your reply here..."
                    required></textarea>
                <div style="display:flex; justify-content:flex-end; gap:10px;">
                    <button type="button" class="btn" onclick="closeReplyModal()">Cancel</button>
                    <button type="submit" class="btn btn-reply">Send Reply</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        // Display the reply modal for a specific feedback
        function showReplyModal(feedbackId) {
            document.getElementById('replyFeedbackId').value = feedbackId;
            document.getElementById('replyModal').style.display = 'block';
        }

        // Close the reply modal
        function closeReplyModal() {
            document.getElementById('replyModal').style.display = 'none';
        }

        // Handle feedback deletion with confirmation
        function deleteFeedback(feedbackId) {
            if (confirm('Are you sure you want to delete this feedback?')) {
                // Create a form dynamically to submit the delete action
                const form = document.createElement('form');
                form.method = 'POST';
                form.action = '';

                const actionInput = document.createElement('input');
                actionInput.type = 'hidden';
                actionInput.name = 'action';
                actionInput.value = 'delete';
                form.appendChild(actionInput);

                const feedbackInput = document.createElement('input');
                feedbackInput.type = 'hidden';
                feedbackInput.name = 'feedback_ids[]';
                feedbackInput.value = feedbackId;
                form.appendChild(feedbackInput);

                document.body.appendChild(form);
                form.submit();
            }
        }
    </script>

</body>

</html>