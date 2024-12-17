<?php
// Start a new session or resume an existing one
session_start();

// Include database connection file
include('../../db/db-connect.php');

// Redirect to login page if the user is not logged in
if (!isset($_SESSION['username'])) {
    header("Location: ../index.php");
    exit();
}

// Fetch admin profile details from the database
$username = $_SESSION['username'];
$query = "SELECT UserID, UserName, Email, Password, FirstName, LastName FROM users WHERE UserName = :username AND Role = 'admin'";
$stmt = $pdo->prepare($query);
$stmt->bindParam(':username', $username, PDO::PARAM_STR);
$stmt->execute();
$admin = $stmt->fetch(PDO::FETCH_ASSOC);

// Terminate if admin details are not found
if (!$admin) {
    die("Error: Admin details not found.");
}

// Handle the profile update form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $newUsername = $_POST['username'];
    $newEmail = $_POST['email'];

    // Hash the new password if provided; otherwise, retain the old password
    $newPassword = !empty($_POST['password']) ? password_hash($_POST['password'], PASSWORD_DEFAULT) : $admin['Password'];

    // Update admin profile in the database
    $updateQuery = "UPDATE users SET UserName = :newUsername, Email = :newEmail, Password = :newPassword WHERE UserID = :adminId";
    $updateStmt = $pdo->prepare($updateQuery);
    $updateStmt->bindParam(':newUsername', $newUsername, PDO::PARAM_STR);
    $updateStmt->bindParam(':newEmail', $newEmail, PDO::PARAM_STR);
    $updateStmt->bindParam(':newPassword', $newPassword, PDO::PARAM_STR);
    $updateStmt->bindParam(':adminId', $admin['UserID'], PDO::PARAM_INT);

    // Check if the update is successful
    if ($updateStmt->execute()) {
        $_SESSION['username'] = $newUsername; // Update session username
        header("Location: admin-profile.php?success=1");
        exit();
    } else {
        $error = "Failed to update profile.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profile | Admin</title>
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

        .profile-title {
            font-size: 24px;
            margin-bottom: 40px;
            color: #333;
        }

        .profile-form {
            max-width: 600px;
        }

        .form-group {
            margin-bottom: 25px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: #333;
            font-weight: 500;
        }

        .form-group input {
            width: 100%;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 16px;
            transition: border-color 0.3s;
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
            left: 55rem;
            top: 37%;
            transform: translateY(-50%);
            cursor: pointer;
            color: #666;
        }

        .button-group {
            display: flex;
            gap: 15px;
            margin-top: 30px;
            justify-content: flex-end;
        }

        .btn {
            padding: 12px 30px;
            border: none;
            border-radius: 25px;
            font-size: 16px;
            cursor: pointer;
            transition: background-color 0.3s;
            color: white;
        }

        .btn-edit {
            background-color: #8B0000;
        }

        .btn-save {
            background-color: #8B0000;
        }

        .btn:hover {
            background-color: #660000;
        }

        .btn-cancel {
            background-color: #666;
        }

        .btn-cancel:hover {
            background-color: #444;
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
                <a href="#" class="menu-item active">
                    <span class="icon">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                            stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                            <circle cx="12" cy="7" r="4"></circle>
                        </svg> </span>
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
                        </svg> </span>
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
                        </svg> </span>
                    Document Request
                </a>
                <a href="admin-feedback.php" class="menu-item">
                    <span class="icon">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                            stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path>
                        </svg> </span>
                    Feedback
                </a>
                <a href="../../index.php" class="menu-item logout">
                    <span class="icon">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                            stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path>
                            <polyline points="16 17 21 12 16 7"></polyline>
                            <line x1="21" y1="12" x2="9" y2="12"></line>
                        </svg> </span>
                    Logout
                </a>
            </div>
        </div>
        <div class="main-content">
            <h1 class="profile-title">PROFILE</h1>

            <?php if (isset($_GET['success'])): ?>
                <p style="color: green;"></p>
            <?php elseif (isset($error)): ?>
                <p style="color: red;"><?= htmlspecialchars($error) ?></p>
            <?php endif; ?>

            <form class="profile-form" id="profileForm" method="POST">
                <div class="form-group">
                    <label for="adminId">ADMIN ID</label>
                    <input type="text" id="adminId" value="<?= htmlspecialchars($admin['UserID']) ?>" disabled>
                </div>
                <div class="form-group">
                    <label for="username">USERNAME</label>
                    <input type="text" id="username" name="username" value="<?= htmlspecialchars($admin['UserName']) ?>"
                        disabled>
                </div>
                <div class="form-group">
                    <label for="password">PASSWORD</label>
                    <input type="password" id="password" name="password" disabled>
                    <span class="password-toggle" onclick="togglePassword()">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none"
                            stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                            <circle cx="12" cy="12" r="3"></circle>
                        </svg>
                    </span>
                </div>

                <div class="form-group">
                    <label for="email">EMAIL ADDRESS</label>
                    <input type="email" id="email" name="email" value="<?= htmlspecialchars($admin['Email']) ?>"
                        disabled>
                </div>
                <div class="button-group">
                    <button type="button" class="btn btn-edit" onclick="enableEdit()">EDIT</button>
                    <button type="submit" class="btn btn-save" style="display: none;">SAVE</button>
                    <button type="button" class="btn btn-cancel" style="display: none;"
                        onclick="cancelEdit()">CANCEL</button>
                </div>

            </form>
        </div>
    </div>

    <script>
        // Enable editing of the profile fields
        function enableEdit() {
            document.getElementById('username').disabled = false;
            document.getElementById('email').disabled = false;
            document.getElementById('password').disabled = false;

            // Hide the Edit button and show Save and Cancel buttons
            document.querySelector('.btn-edit').style.display = 'none';
            document.querySelector('.btn-save').style.display = 'block';
            document.querySelector('.btn-cancel').style.display = 'block';
        }

        // Cancel the editing process and reset the form
        function cancelEdit() {
            const form = document.getElementById('profileForm');

            // Reset the form values to their initial state
            form.reset();

            // Disable all input fields
            document.getElementById('username').disabled = true;
            document.getElementById('email').disabled = true;
            document.getElementById('password').disabled = true;

            // Show only the Edit button
            document.querySelector('.btn-edit').style.display = 'block';
            document.querySelector('.btn-save').style.display = 'none';
            document.querySelector('.btn-cancel').style.display = 'none';
        }

        // Toggle the visibility of the password field
        function togglePassword() {
            const passwordInput = document.getElementById('password');
            const passwordToggle = document.querySelector('.password-toggle');

            // Switch between 'password' and 'text' types
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
    </script>
</body>

</html>