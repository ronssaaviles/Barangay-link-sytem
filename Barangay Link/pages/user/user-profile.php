<?php
session_start();
include('../../db/db-connect.php');

// Check if the user is logged in; redirect to login page if not
if (!isset($_SESSION['username'])) {
    echo "Session not found. Redirecting...";
    header("Location: ../../index.php");
    exit();
}

// Retrieve the current user's data from the session
$currentUser = $_SESSION['username'];
$query = "SELECT * FROM users WHERE UserName = :username";
$stmt = $pdo->prepare($query);
$stmt->bindParam(':username', $currentUser, PDO::PARAM_STR);
$stmt->execute();
$user = $stmt->fetch(PDO::FETCH_ASSOC);

// If no user data is found, terminate with an error message
if (!$user) {
    die("User not found.");
}

// Handle form submission for profile updates
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Capture and sanitize input from the form
    $newEmail = $_POST['email'];
    $newPassword = !empty($_POST['password']) ? password_hash($_POST['password'], PASSWORD_DEFAULT) : $user['Password'];

    try {
        // Update the user's email and password in the database
        $updateQuery = "UPDATE users SET Email = :email, Password = :password WHERE UserID = :id";
        $stmt = $pdo->prepare($updateQuery);
        $stmt->bindParam(':email', $newEmail, PDO::PARAM_STR);
        $stmt->bindParam(':password', $newPassword, PDO::PARAM_STR);
        $stmt->bindParam(':id', $user['UserID'], PDO::PARAM_INT);
        $stmt->execute();

        // Check if a profile picture was uploaded and handle the upload
        if (isset($_FILES['profile_pic']) && $_FILES['profile_pic']['error'] === UPLOAD_ERR_OK) {
            $imageData = file_get_contents($_FILES['profile_pic']['tmp_name']);
            $updatePicQuery = "UPDATE users SET ProfilePic = :profilePic WHERE UserID = :id";
            $stmt = $pdo->prepare($updatePicQuery);
            $stmt->bindParam(':profilePic', $imageData, PDO::PARAM_LOB);
            $stmt->bindParam(':id', $user['UserID'], PDO::PARAM_INT);
            $stmt->execute();
        }

        // Redirect to avoid form resubmission
        header("Location: user-profile.php?success=1");
        exit();
    } catch (PDOException $e) {
        // Handle any database-related errors
        $errorMessage = "Error updating profile: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Barangay Link | Profile</title>
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
            margin-bottom: 100px;
            font-weight: normal;
        }

        .profile-content {
            flex-grow: 1;
            padding: 60px;
        }

        .profile-form {
            max-width: 600px;
            margin-left: 150px;
            margin-top: 40px;
        }

        .form-group {
            margin-bottom: 25px;
        }

        .form-group label {
            display: block;
            font-size: 20px;
            margin-bottom: 8px;
            color: #333;
        }

        .form-group input {
            width: 100%;
            padding: 10px;
            font-size: 16px;
            border: 2px solid black;
            border-radius: 4px;
            box-sizing: border-box;
        }

        .form-group input:focus {
            border-color: #8B0000;
            outline: none;
        }

        .form-group input:disabled {
            background-color: #f5f5f5;
            cursor: not-allowed;
        }

        .password-toggle {
            position: absolute;
            left: 74rem;
            top: 49%;
            transform: translateY(-50%);
            cursor: pointer;
            color: #666;
        }

        .button-group {
            display: flex;
            gap: 20px;
            justify-content: flex-end;
            margin-right: -500px;
            margin-top: 200px;
        }

        .btn {
            padding: 20px 60px;
            border: none;
            border-radius: 60px;
            font-size: 20px;
            cursor: pointer;
            color: white;
        }

        .btn-edit {
            background-color: #800000;
        }

        .btn-save {
            background-color: #800000;
        }

        .btn:hover {
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
                <p class="user-name"><?php echo htmlspecialchars($user['FirstName'] . ' ' . $user['LastName']); ?></p>
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
                <a href="#">
                    <li class="menu-item active">
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
            <h1><b>PROFILE</b></h1>
            <?php if (isset($errorMessage)): ?>
                <div class="message error"><?php echo htmlspecialchars($errorMessage); ?></div>
            <?php endif; ?>
            <?php if (isset($_GET['success'])): ?>
                <div class="message success"></div>
            <?php endif; ?>
            <form class="profile-form" method="POST" enctype="multipart/form-data">
                <div class="form-group">
                    <label for="username">Username:</label>
                    <input type="text" id="username" name="username"
                        value="<?php echo htmlspecialchars($user['UserName']); ?>" disabled>
                </div>
                <div class="form-group">
                    <label for="email">Email:</label>
                    <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($user['Email']); ?>"
                        required>
                </div>
                <div class="form-group">
                    <label for="password">Password:</label>
                    <input type="password" id="password" name="password" placeholder="Enter new password">
                    <span class="password-toggle" onclick="togglePassword()">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none"
                            stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                            <circle cx="12" cy="12" r="3"></circle>
                        </svg>
                    </span>
                </div>
                <div class="form-group">
                    <label for="profile_pic">Profile Picture:</label>
                    <input type="file" id="profile_pic" name="profile_pic" accept="image/*">
                    <div class="logo" style="margin-top: -15rem; margin-right: -25rem; float: right;">
                        <?php if (!empty($user['ProfilePic'])): ?>
                            <img src="data:image/jpeg;base64,<?php echo base64_encode($user['ProfilePic']); ?>"
                                alt="Profile Picture"
                                style="width: 300px; height: 300px; border-radius: 50%; object-fit: cover;">

                        <?php else: ?>
                            <p>Upload Image</p>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="button-group">
                    <button type="submit" class="btn btn-save">Save Changes</button>
                    <button type="reset" class="btn btn-edit">Cancel</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        /**
         * Toggles the visibility of the password input field between 'password' and 'text'.
         */
        function togglePassword() {
            const passwordInput = document.getElementById('password');
            const passwordToggle = document.querySelector('.password-toggle');

            // Check the current type and switch accordingly
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                passwordToggle.innerHTML = `
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"></path>
                    <line x1="1" y1="1" x2="23" y2="23"></line>
                </svg>`;
            } else {
                passwordInput.type = 'password';
                passwordToggle.innerHTML = `
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                    <circle cx="12" cy="12" r="3"></circle>
                </svg>`;
            }
        }

        /**
         * Displays a success message after profile update
         * and removes the 'success' parameter from the URL.
         */
        document.addEventListener("DOMContentLoaded", function () {
            const urlParams = new URLSearchParams(window.location.search);
            const success = urlParams.get('success');

            if (success) {
                alert("Profile updated successfully!");
                // Remove the `success` parameter from the URL
                urlParams.delete('success');
                window.history.replaceState({}, document.title, window.location.pathname);
            }
        });
    </script>

</body>

</html>