<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 0);
error_log('Received request: ' . json_encode($_POST));

// Ensure the user is logged in and is an admin
if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../index.php");
    exit();
}

include('../../db/db-connect.php');

// Standardize session variables for user identification
$_SESSION['user_id'] = $_SESSION['UserID'] ?? $_SESSION['user_id'];
unset($_SESSION['UserID']); // Remove redundant session variables

// Handle incoming POST requests for document actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && isset($_POST['request_id'])) {
    ob_clean(); // Clear the output buffer
    header('Content-Type: application/json');
    header('X-Content-Type-Options: nosniff'); // Security header to prevent MIME sniffing

    try {
        $requestId = filter_var($_POST['request_id'], FILTER_VALIDATE_INT); // Validate request ID
        $action = $_POST['action'];
        $adminId = $_SESSION['user_id']; // Fetch admin ID from session

        if ($requestId === false) {
            throw new Exception('Invalid request ID');
        }

        $pdo->beginTransaction(); // Begin a database transaction

        // Determine the action to perform
        switch ($action) {
            case 'approve':
                $status = 'Approved';
                break;
            case 'reject':
                $status = 'Rejected';
                break;
            case 'delete':
                // Handle deletion of document requests
                $deleteQuery = "DELETE FROM document_requests WHERE RequestID = :request_id";
                $stmt = $pdo->prepare($deleteQuery);
                $stmt->bindParam(':request_id', $requestId, PDO::PARAM_INT);
                $stmt->execute();
                $pdo->commit();
                echo json_encode(['success' => true, 'message' => 'Request deleted successfully']);
                exit;
            default:
                throw new Exception('Invalid action');
        }

        if (isset($status)) {
            // Fetch admin details for logging updates
            $adminQuery = "SELECT CONCAT(FirstName, ' ', LastName) as AdminName 
                           FROM users 
                           WHERE UserID = :admin_id";
            $stmt = $pdo->prepare($adminQuery);
            $stmt->bindParam(':admin_id', $adminId, PDO::PARAM_INT);
            $stmt->execute();
            $adminData = $stmt->fetch(PDO::FETCH_ASSOC);

            // Update the request's status in the database
            $updateQuery = "
                UPDATE document_requests 
                SET Status = :status,
                    Last_Updated = CURRENT_TIMESTAMP,
                    Updated_By = :admin_id
                WHERE RequestID = :request_id
            ";
            $stmt = $pdo->prepare($updateQuery);
            $stmt->bindParam(':status', $status, PDO::PARAM_STR);
            $stmt->bindParam(':admin_id', $adminId, PDO::PARAM_INT);
            $stmt->bindParam(':request_id', $requestId, PDO::PARAM_INT);
            $stmt->execute();

            $pdo->commit(); // Commit the transaction

            echo json_encode([
                'success' => true,
                'message' => "Request successfully {$action}d",
                'updatedData' => [
                    'status' => $status,
                    'lastUpdated' => date('M d, Y h:i A'),
                    'updatedBy' => $adminData['AdminName']
                ]
            ]);
            exit;
        }

    } catch (Exception $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack(); // Roll back the transaction in case of an error
        }
        echo json_encode([
            'success' => false,
            'message' => $e->getMessage()
        ]);
        exit;
    }
}

// Fetch the logged-in admin's details
$username = $_SESSION['username'];
$query = "SELECT UserID, FirstName, LastName FROM users WHERE UserName = :username AND Role = 'admin'";
$stmt = $pdo->prepare($query);
$stmt->bindParam(':username', $username, PDO::PARAM_STR);
$stmt->execute();
$admin = $stmt->fetch(PDO::FETCH_ASSOC);

// Fetch all document requests and associated resident/admin information
$requestQuery = "
    SELECT 
        dr.*,
        r.FullName as ResidentName,
        r.ContactNum,
        r.Status as ResidentStatus,
        a.FirstName as AdminFirstName,
        a.LastName as AdminLastName
    FROM document_requests dr
    JOIN residents r ON dr.ResidentID = r.ResidentID
    LEFT JOIN users a ON dr.Updated_By = a.UserID AND a.Role = 'admin'
    ORDER BY 
        CASE 
            WHEN dr.Status = 'Pending' THEN 1
            WHEN dr.Status = 'Approved' THEN 2
            WHEN dr.Status = 'Rejected' THEN 3
        END,
        dr.Date_Requested DESC
";
$stmt = $pdo->prepare($requestQuery);
$stmt->execute();
$requests = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document Request | Admin</title>
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
            background-color: white;
        }

        .document-title {
            font-size: 24px;
            color: #333;
            margin-bottom: 30px;
        }

        .document-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }

        .document-table th,
        .document-table td {
            border: 1px solid #ddd;
            padding: 12px;
            text-align: left;
        }

        .document-table th {
            background-color: #f5f5f5;
            font-weight: bold;
            color: #333;
        }

        .document-table tr:hover {
            background-color: #f9f9f9;
        }

        .button-container {
            display: flex;
            justify-content: flex-end;
            gap: 15px;
            margin-top: 20px;
        }

        .btn {
            padding: 12px 30px;
            border: none;
            border-radius: 25px;
            font-size: 16px;
            cursor: pointer;
            transition: background-color 0.3s;
            color: white;
            background-color: #8B0000;
            text-transform: uppercase;
        }

        .btn:hover {
            background-color: #660000;
        }

        .table-container {
            max-height: 400px;
            overflow-y: auto;
        }

        .logout {
            margin-top: auto;
        }

        .status-pending {
            color: #ff9900;
        }

        .status-approved {
            color: #28a745;
        }

        .status-rejected {
            color: #dc3545;
        }

        .action-buttons {
            display: flex;
            gap: 10px;
        }

        .btn-action {
            padding: 5px 10px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            color: white;
        }

        .btn-approve {
            background-color: #28a745;
        }

        .btn-reject {
            background-color: #dc3545;
        }

        .btn-delete {
            background-color: #6c757d;
        }

        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
        }

        .modal-content {
            position: relative;
            background-color: #fefefe;
            margin: 15% auto;
            padding: 20px;
            border: 1px solid #888;
            width: 80%;
            max-width: 500px;
        }

        .notification {
            position: fixed;
            top: 20px;
            right: 20px;
            padding: 15px 20px;
            border-radius: 5px;
            background: white;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            z-index: 1000;
            max-width: 350px;
            animation: slideIn 0.5s ease-out;
        }

        .notification h3 {
            margin: 0 0 5px 0;
            font-size: 16px;
            font-weight: bold;
        }

        .notification p {
            margin: 0;
            font-size: 14px;
        }

        .notification-success {
            border-left: 4px solid #28a745;
        }

        .notification-error {
            border-left: 4px solid #dc3545;
        }

        @keyframes slideIn {
            from {
                transform: translateX(100%);
                opacity: 0;
            }

            to {
                transform: translateX(0);
                opacity: 1;
            }
        }

        .loading {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            display: none;
            justify-content: center;
            align-items: center;
            z-index: 1001;
        }

        .loading-content {
            background: white;
            padding: 20px;
            border-radius: 5px;
            text-align: center;
        }

        .spinner {
            border: 4px solid #f3f3f3;
            border-top: 4px solid #8B0000;
            border-radius: 50%;
            width: 40px;
            height: 40px;
            animation: spin 1s linear infinite;
            margin: 0 auto 10px;
        }

        @keyframes spin {
            0% {
                transform: rotate(0deg);
            }

            100% {
                transform: rotate(360deg);
            }
        }

        @keyframes slideOut {
            from {
                transform: translateX(0);
                opacity: 1;
            }

            to {
                transform: translateX(100%);
                opacity: 0;
            }
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
                <a href="#" class="menu-item active">
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
                <a href="admin-feedback.php" class="menu-item">
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

        <!-- Main Content -->
        <div class="main-content">
            <h1 class="document-title">DOCUMENT REQUESTS</h1>

            <div class="table-container">
                <table class="document-table">
                    <thead>
                        <tr>
                            <th>Request ID</th>
                            <th>Document Type</th>
                            <th>Resident Name</th>
                            <th>Contact</th>
                            <th>Date Requested</th>
                            <th>Status</th>
                            <th>Last Updated</th>
                            <th>Updated By</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($requests as $request): ?>
                            <tr data-request-id="<?= htmlspecialchars($request['RequestID']) ?>">
                                <td><?= htmlspecialchars($request['RequestID']) ?></td>
                                <td><?= htmlspecialchars(ucfirst($request['Document_Type'])) ?></td>
                                <td><?= htmlspecialchars($request['ResidentName']) ?></td>
                                <td><?= htmlspecialchars($request['ContactNum']) ?></td>
                                <td><?= htmlspecialchars(date('M d, Y g:i A', strtotime($request['Date_Requested']))) ?>
                                </td>
                                <td class="status-<?= strtolower($request['Status']) ?>">
                                    <?= htmlspecialchars($request['Status']) ?>
                                </td>
                                <td><?= htmlspecialchars(date('M d, Y g:i A', strtotime($request['Last_Updated']))) ?></td>
                                <td>
                                    <?= $request['AdminFirstName'] ?
                                        htmlspecialchars($request['AdminFirstName'] . ' ' . $request['AdminLastName']) :
                                        'N/A' ?>
                                </td>
                                <td class="action-buttons">
                                    <?php if ($request['Status'] === 'Pending'): ?>
                                        <button class="btn-action btn-approve"
                                            onclick="handleRequest(<?= $request['RequestID'] ?>, 'approve')">
                                            Approve
                                        </button>
                                        <button class="btn-action btn-reject"
                                            onclick="handleRequest(<?= $request['RequestID'] ?>, 'reject')">
                                            Reject
                                        </button>
                                    <?php endif; ?>
                                    <button class="btn-action btn-delete"
                                        onclick="handleRequest(<?= $request['RequestID'] ?>, 'delete')">
                                        Delete
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Confirmation Modal -->
    <div id="confirmModal" class="modal">
        <div class="modal-content">
            <h2>Confirm Action</h2>
            <p id="modalMessage"></p>
            <div class="button-container">
                <button onclick="confirmAction()" class="btn-confirm">Confirm</button>
                <button onclick="closeModal()" class="btn-cancel">Cancel</button>
            </div>
        </div>
    </div>

    <!-- Loading Overlay -->
    <div class="loading" id="loadingOverlay">
        <div class="loading-content">
            <div class="spinner"></div>
            <p>Processing request...</p>
        </div>
    </div>


    <script>
        // Global variables for request handling
        let currentAction = ''; // Stores the action (approve/reject/delete) to be confirmed
        let currentRequestId = null; // Stores the current request ID being processed

        // Modal handling functions

        /**
         * Opens the confirmation modal for a specific request action.
         */
        function openModal(requestId, action) {
            currentRequestId = requestId; // Set the current request ID
            currentAction = action; // Set the current action
            const modal = document.getElementById('confirmModal');
            const message = document.getElementById('modalMessage');

            // Define confirmation messages for each action
            const messages = {
                'approve': 'Are you sure you want to approve this request?',
                'reject': 'Are you sure you want to reject this request?',
                'delete': 'Are you sure you want to delete this request? This action cannot be undone.'
            };

            // Set the modal message and display the modal
            message.textContent = messages[action] || 'Are you sure you want to proceed?';
            modal.style.display = 'block';
        }

        /**
         * Closes the confirmation modal and resets action variables.
         */
        function closeModal() {
            const modal = document.getElementById('confirmModal');
            if (modal) {
                modal.style.display = 'none'; // Hide the modal
                currentAction = ''; // Reset the current action
                currentRequestId = null; // Reset the current request ID
            }
        }

        // Main request handler function

        /**
         * Handles the initial action (approve, reject, or delete) for a request.
         * Opens the confirmation modal.
         */
        function handleRequest(requestId, action) {
            openModal(requestId, action); // Open the confirmation modal
        }

        // Table row update function

        /**
         * Updates the table row with the latest data after an action is performed.
         */
        function updateTableRow(requestId, updatedData) {
            const row = document.querySelector(`tr[data-request-id="${requestId}"]`);
            if (row) {
                // Get table cells for status, last updated, and updated by
                const statusCell = row.querySelector('td:nth-child(6)');
                const lastUpdatedCell = row.querySelector('td:nth-child(7)');
                const updatedByCell = row.querySelector('td:nth-child(8)');
                const actionsCell = row.querySelector('td:nth-child(9)');

                // Update the status cell with new data
                if (statusCell) {
                    statusCell.textContent = updatedData.status;
                    statusCell.className = `status-${updatedData.status.toLowerCase()}`;
                }

                // Update the last updated timestamp
                if (lastUpdatedCell) {
                    lastUpdatedCell.textContent = updatedData.lastUpdated;
                }

                // Update the "Updated By" field
                if (updatedByCell) {
                    updatedByCell.textContent = updatedData.updatedBy || 'N/A';
                }

                // Remove approve/reject buttons if the status is not pending
                if (actionsCell && updatedData.status !== 'Pending') {
                    const buttons = actionsCell.querySelectorAll('.btn-approve, .btn-reject');
                    buttons.forEach(button => button.remove());
                }
            }
        }

        // Notification handling functions

        /**
         * Displays a notification to the user.
         */
        function showNotification(title, message, type) {
            // Remove existing notifications before showing a new one
            const existingNotifications = document.querySelectorAll('.notification');
            existingNotifications.forEach(notification => {
                notification.style.animation = 'none';
                notification.style.opacity = '0';
                setTimeout(() => notification.remove(), 100);
            });

            // Create a new notification element
            const notification = document.createElement('div');
            notification.className = `notification notification-${type}`;
            notification.innerHTML = `
        <h3>${title}</h3>
        <p>${message}</p>
    `;

            document.body.appendChild(notification); // Add the notification to the DOM

            // Add animations for the notification
            notification.offsetHeight; // Force reflow
            notification.style.animation = 'slideIn 0.5s ease-out';

            // Automatically remove the notification after 4.5 seconds
            setTimeout(() => {
                notification.style.animation = 'slideOut 0.5s ease-in forwards';
                setTimeout(() => notification.remove(), 500);
            }, 4500);
        }

        // Confirmation action handler

        /**
         * Processes the confirmed action (approve, reject, or delete) for a request.
         * Sends the request to the server and updates the UI based on the response.
         */
        async function confirmAction() {
            // Check for internet connectivity
            if (!navigator.onLine) {
                showNotification('Error', 'No internet connection. Please check your network.', 'error');
                return;
            }

            const loadingOverlay = document.getElementById('loadingOverlay');
            const confirmButton = document.querySelector('.btn-confirm');

            try {
                if (confirmButton) {
                    confirmButton.disabled = true; // Disable the confirm button to prevent multiple submissions
                }
                loadingOverlay.style.display = 'flex'; // Show the loading overlay

                // Prepare data for the server request
                const formData = new FormData();
                formData.append('request_id', currentRequestId);
                formData.append('action', currentAction);

                // Send the request to the server
                const response = await fetch(window.location.href, {
                    method: 'POST',
                    body: formData,
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    credentials: 'same-origin'
                });

                const responseText = await response.text();

                // Parse JSON response
                let data = JSON.parse(responseText);

                if (data.success) {
                    // Show success notification and update the table row if necessary
                    showNotification('Success', data.message || 'Request processed successfully.', 'success');
                    if (currentAction === 'delete') {
                        setTimeout(() => window.location.reload(), 1500); // Reload page after deletion
                    } else if (data.updatedData) {
                        updateTableRow(currentRequestId, data.updatedData);
                    }
                } else {
                    throw new Error(data.message || 'An error occurred while processing the request.');
                }
            } catch (error) {
                // Show error notification
                showNotification('Error', 'Could not complete the request. Please try again.', 'error');
            } finally {
                // Hide the loading overlay and re-enable the confirm button
                loadingOverlay.style.display = 'none';
                if (confirmButton) {
                    confirmButton.disabled = false;
                }
                closeModal(); // Close the modal
            }
        }

        // Event listeners for online/offline status

        // Notify the user when the connection is restored
        window.addEventListener('online', () => {
            showNotification('Connection Restored', 'You are now back online.', 'success');
        });

        // Notify the user when the connection is lost
        window.addEventListener('offline', () => {
            showNotification('Connection Lost', 'You are currently offline. Please check your internet connection.', 'error');
        });
    </script>
</body>

</html>