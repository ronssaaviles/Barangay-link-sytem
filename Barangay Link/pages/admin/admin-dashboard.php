<?php
// Start a session to track user information
session_start();

// Include the database connection file
include('../../db/db-connect.php');

// Check if the user is logged in by verifying session data
if (!isset($_SESSION['username'])) {
    // If not logged in, redirect to the login page
    header("Location: ../index.php");
    exit(); // Terminate further script execution
}

// Fetch the logged-in user's full name
$username = $_SESSION['username']; // Retrieve the username from session data
$query = "SELECT FirstName, LastName FROM users WHERE UserName = :username";
$stmt = $pdo->prepare($query); // Prepare the SQL statement to prevent SQL injection
$stmt->bindParam(':username', $username, PDO::PARAM_STR); // Bind the username parameter
$stmt->execute(); // Execute the query
$user = $stmt->fetch(PDO::FETCH_ASSOC); // Fetch the user's details as an associative array

// Combine first and last names or set a default name
$fullName = $user ? htmlspecialchars($user['FirstName'] . ' ' . $user['LastName']) : "Admin";

// Fetch dashboard statistics
// 1. Fetch total number of active residents
$residentQuery = "SELECT COUNT(*) AS total FROM residents WHERE Status = 'Active'";
$residentStmt = $pdo->query($residentQuery); // Execute the query directly
$totalResidents = $residentStmt->fetch(PDO::FETCH_ASSOC)['total'];

// 2. Fetch total number of pending document requests
$requestQuery = "SELECT COUNT(*) AS pending FROM document_requests WHERE Status = 'Pending'";
$requestStmt = $pdo->query($requestQuery);
$pendingRequests = $requestStmt->fetch(PDO::FETCH_ASSOC)['pending'];

// 3. Fetch total number of unresolved feedback
$feedbackQuery = "SELECT COUNT(*) AS unresolved FROM feedback WHERE Status = 'Pending'";
$feedbackStmt = $pdo->query($feedbackQuery);
$unresolvedFeedback = $feedbackStmt->fetch(PDO::FETCH_ASSOC)['unresolved'];

// 4. Fetch total number of admin users
$adminQuery = "SELECT COUNT(*) AS total FROM users WHERE Role = 'admin'";
$adminStmt = $pdo->query($adminQuery);
$totalAdmins = $adminStmt->fetch(PDO::FETCH_ASSOC)['total'];
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard | Admin</title>
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

        .main-content {
            flex: 1;
            padding: 30px;
            background-color: #f5f5f5;
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

        .menu a {
            text-decoration: none;
        }

        .menu-item {
            display: flex;
            align-items: center;
            padding: 12px 15px;
            margin-bottom: 10px;
            cursor: pointer;
            border-radius: 5px;
            text-decoration: none;
            color: white;
            transition: background-color 0.3s;
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

        .dashboard-title {
            font-size: 24px;
            margin-bottom: 30px;
            color: #333;
        }

        .grid-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
            padding: 20px;
        }

        .grid-item {
            background-color: #8B0000;
            color: white;
            padding: 25px;
            border-radius: 8px;
            cursor: pointer;
            transition: transform 0.3s, background-color 0.3s;
            min-height: 150px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
            text-decoration: none;
        }

        .grid-item:hover {
            background-color: #660000;
            transform: translateY(-5px);
        }

        .stats-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background-color: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        .stat-number {
            font-size: 24px;
            font-weight: bold;
            color: #8B0000;
        }

        .stat-label {
            color: #666;
            margin-top: 5px;
        }

        .logout {
            margin-top: auto;
        }
    </style>
</head>

<body>
    <div class="container">
        <div class="sidebar">
            <div class="logo-section">
                <img class="logo" src="../../img/logo.png" alt="">
                <div class="admin-title">SYSTEM ADMINISTRATOR</div>
            </div>
            <ul class="menu">
                <li class="menu-item active">
                    <span class="icon">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                            stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <rect x="3" y="3" width="7" height="7"></rect>
                            <rect x="14" y="3" width="7" height="7"></rect>
                            <rect x="14" y="14" width="7" height="7"></rect>
                            <rect x="3" y="14" width="7" height="7"></rect>
                        </svg> </span>
                    Dashboard
                </li>
                <a href="admin-profile.php">
                    <li class="menu-item">
                        <span class="icon">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none"
                                stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                                <circle cx="12" cy="7" r="4"></circle>
                            </svg> </span>
                        Profile
                    </li>
                </a>
                <a href="admin-residents.php">
                    <li class="menu-item">
                        <span class="icon">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none"
                                stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                                <circle cx="9" cy="7" r="4"></circle>
                                <path d="M23 21v-2a4 4 0 0 0-3-3.87"></path>
                                <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
                            </svg> </span>
                        Residents
                    </li>
                </a>
                <a href="admin-doc-request.php">
                    <li class="menu-item">
                        <span class="icon">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none"
                                stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                                <polyline points="14 2 14 8 20 8"></polyline>
                                <line x1="16" y1="13" x2="8" y2="13"></line>
                                <line x1="16" y1="17" x2="8" y2="17"></line>
                                <polyline points="10 9 9 9 8 9"></polyline>
                            </svg> </span>
                        Document Request
                    </li>
                </a>
                <a href="admin-feedback.php">
                    <li class="menu-item">
                        <span class="icon">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none"
                                stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path>
                            </svg> </span>
                        Feedback
                    </li>
                </a>
                <a href="./../../index.php">
                    <li class="menu-item logout">
                        <span class="icon">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none"
                                stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path>
                                <polyline points="16 17 21 12 16 7"></polyline>
                                <line x1="21" y1="12" x2="9" y2="12"></line>
                            </svg> </span>
                        Logout
                    </li>
                </a>
            </ul>
        </div>
        <div class="main-content">
            <h1 class="dashboard-title">Welcome, <?= $fullName ?>!</h1>

            <div class="stats-container">
                <div class="stat-card">
                    <div class="stat-number"><?= $totalResidents ?></div>
                    <div class="stat-label">Total Residents</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number"><?= $pendingRequests ?></div>
                    <div class="stat-label">Pending Requests</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number"><?= $unresolvedFeedback ?></div>
                    <div class="stat-label">New Feedback</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number"><?= $totalAdmins ?></div>
                    <div class="stat-label">Active Admins</div>
                </div>
            </div>

            <div class="grid-container">
                <a href="admin-dashboard.php" class="grid-item">DASHBOARD</a>
                <a href="admin-profile.php" class="grid-item">PROFILE</a>
                <a href="admin-residents.php" class="grid-item">RESIDENTS</a>
                <a href="admin-doc-request.php" class="grid-item">DOCUMENT REQUEST</a>
                <a href="admin-feedback.php" class="grid-item">FEEDBACK</a>
                <a href="../../index.php" class="grid-item">LOGOUT</a>
            </div>
        </div>
    </div>
</body>

</html>