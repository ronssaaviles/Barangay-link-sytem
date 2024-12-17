<?php
// Start session to manage user login state
session_start();

// Include the database connection file
include('../../db/db-connect.php');

// Check if the user is logged in
if (!isset($_SESSION['username'])) {
    // Redirect to the login page if the user is not logged in
    header("Location: ../index.php");
    exit();
}

// Fetch the user's details from the database
$username = $_SESSION['username'];
$query = "SELECT firstname, lastname, role FROM users WHERE username = :username";
$stmt = $pdo->prepare($query);
$stmt->bindParam(':username', $username, PDO::PARAM_STR); // Bind the username parameter to prevent SQL injection
$stmt->execute();
$user = $stmt->fetch(PDO::FETCH_ASSOC); // Retrieve the user's data as an associative array

if ($user) {
    // Store the full name and role of the user
    $fullName = htmlspecialchars($user['firstname'] . ' ' . $user['lastname']); // Use htmlspecialchars to prevent XSS
    $role = htmlspecialchars($user['role']);
} else {
    // Fallback values in case user data is not found
    $fullName = "User";
    $role = "User";
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Barangay Link | Dashboard</title>
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

        /* Main Content */
        .main-content {
            flex-grow: 1;
            padding: 60px;
        }

        h1 {
            font-size: 30px;
            margin-bottom: 50px;
            font-weight: normal;
        }

        .grid {
            display: grid;
            margin-left: 100px;
            grid-template-columns: repeat(2, 1fr);
            gap: 20px;
        }

        .grid a {
            text-decoration: none;
            color: white;
        }

        .card {
            background-color: #800000;
            color: white;
            text-align: left;
            padding: 30px;
            font-size: 30px;
            border-radius: 5px;
            display: flex;
            width: 30rem;
            height: 150px;
            cursor: pointer;
        }

        .card:hover {
            background-color: #660000;
        }
    </style>
</head>

<body>
    <div class="container">
        <!-- Sidebar -->
        <div class="sidebar">
            <div class="user-section">
                <img class="logo" src="../../img/logo.png" alt="">
                <p class="user-name"><?php echo $fullName; ?></p>
            </div>
            <ul class="menu">
                <a href="#">
                    <li class="menu-item active">
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
                <a href="user-feedback.php">
                    <li>
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

        <!-- Main Content -->
        <div class="main-content">
            <h1><b>DASHBOARD</b></h1>
            <div class="grid">
                <a href="#">
                    <div class="card">DASHBOARD</div>
                </a>
                <a href="user-profile.php">
                    <div class="card">PROFILE</div>
                </a>
                <a href="user-doc-request.php">
                    <div class="card">DOCUMENT <br>REQUEST</div>
                </a>
                <a href="user-feedback.php">
                    <div class="card">FEEDBACK</div>
                </a>
                <a href="../../index.php">
                    <div class="card">LOGOUT</div>
                </a>
            </div>
        </div>
    </div>
</body>

</html>