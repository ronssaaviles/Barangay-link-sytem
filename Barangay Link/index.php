<?php
// Start the PHP session for managing user sessions
session_start();

// Database connection parameters
$host = 'localhost'; // Database host
$dbname = 'barangay_db'; // Database name
$username = 'root'; // Database username
$password = ''; // Database password

// Establish a connection to the database using PDO
try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION); // Enable exceptions for errors
} catch (PDOException $e) {
    // Handle connection errors
    die("Database connection failed: " . $e->getMessage());
}

// Initialize error message variable
$error = '';

// Check if the form has been submitted via POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get user input from the login form
    $inputUsername = $_POST['username'];
    $inputPassword = $_POST['password'];
    $inputRole = $_POST['role'];

    try {
        // Prepare a query to check user credentials in the database
        $query = "SELECT * FROM users WHERE UserName = :username AND Role = :role";
        $stmt = $pdo->prepare($query);
        $stmt->bindParam(':username', $inputUsername);
        $stmt->bindParam(':role', $inputRole);
        $stmt->execute();

        // Fetch user details if they exist
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        // Verify the entered password against the hashed password in the database
        if ($user && password_verify($inputPassword, $user['Password'])) {
            // Set session variables upon successful login
            $_SESSION['UserID'] = $user['UserID'];
            $_SESSION['username'] = $user['UserName'];
            $_SESSION['role'] = $user['Role'];
            $_SESSION['ProfilePic'] = base64_encode($user['ProfilePic']); // Encode profile picture

            // Redirect user to appropriate dashboard based on role
            if ($inputRole === 'admin') {
                header('Location: ./pages/admin/admin-dashboard.php');
            } else {
                header('Location: ./pages/user/user-dashboard.php');
            }
            exit;
        } else {
            // Set error message for invalid login details
            $error = "Invalid username, password, or role.";
        }
    } catch (Exception $e) {
        // Handle unexpected errors during login process
        $error = "Error processing login: " . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Barangay Link | Login</title>
    <link rel="shortcut icon" href="img/logo.ico">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: Arial, sans-serif;
        }

        body {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background-color: #f5f5f5;
            background-image: url(img/3.jpg);
            background-repeat: no-repeat;
            background-size: cover;
        }

        .login-container {
            display: flex;
            background: transparent;
            backdrop-filter: blur(20px);
            border-radius: 10px;
            box-shadow: 0 0 30px rgba(0, 0, 0, 0.5);
            overflow: hidden;
            width: 900px;
            height: 550px;
        }

        .login-image {
            background-color: #8B0000;
            width: 50%;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            color: white;
            padding: 40px;
        }

        .logo {
            width: 150px;
            height: 150px;
            background-color: #d9d2c9;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            color: black;
            margin-bottom: 20px;
        }

        .login-form {
            width: 50%;
            padding: 40px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            color: white;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .password-group {
            position: relative;
        }

        label {
            display: block;
            margin-bottom: 8px;
            color: white;
        }

        input {
            width: 100%;
            padding: 12px;
            border: 2px solid #ddd;
            border-radius: 5px;
            font-size: 16px;
        }

        input:focus {
            border-color: #8B0000;
            outline: none;
        }

        .password-toggle {
            position: absolute;
            right: 12px;
            top: 73%;
            transform: translateY(-50%);
            cursor: pointer;
            color: #666;
        }

        button {
            background-color: #8B0000;
            color: white;
            padding: 12px;
            border: none;
            border-radius: 5px;
            font-size: 16px;
            cursor: pointer;
            width: 100%;
            margin-top: 20px;
        }

        button:hover {
            background-color: #660000;
        }

        .role-toggle {
            display: flex;
            margin-bottom: 20px;
            border: 2px solid #ddd;
            border-radius: 5px;
            overflow: hidden;
            color: white;
        }

        .role-option {
            flex: 1;
            padding: 10px;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s;
        }

        .role-option.active {
            background-color: #8B0000;
            color: white;
        }
    </style>
</head>

<body>
    <div class="login-container">
        <div class="login-image">
            <div class="logo">
                <img src="img/logo.png" alt="Barangay Link Logo" style="width: 80%; height: 80%;">
            </div>
            <h2>Welcome Back!</h2>
            <p>Please login to access your account</p>
        </div>
        <div class="login-form">
            <h1>Login</h1>
            <form method="POST" action="">
                <div class="role-toggle">
                    <div class="role-option active" onclick="toggleRole('user', this)">User</div>
                    <div class="role-option" onclick="toggleRole('admin', this)">Admin</div>
                </div>
                <div class="form-group">
                    <label for="username">Username</label>
                    <input type="text" name="username" id="username" required>
                </div>
                <div class="form-group password-group">
                    <label for="password">Password</label>
                    <input type="password" name="password" id="password" required>
                    <span class="password-toggle" onclick="togglePassword()">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none"
                            stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                            <circle cx="12" cy="12" r="3"></circle>
                        </svg>
                    </span>
                </div>
                <input type="hidden" id="role" name="role" value="user">
                <button type="submit">Login</button>
                <?php if (!empty($error))
                    echo "<p style='color: red;'>$error</p>"; ?>
                <p style="margin-top: 20px; text-align: center;">
                    Don't have an account? <a href="./pages/sign-up.php"
                        style="color: #8B0000; font-weight: bold;">Register here</a>
                </p>
            </form>
        </div>
    </div>

    <script>
        /**
         * Toggle the selected role in the role toggle group.
         */
        function toggleRole(role, element) {
            // Remove active class from all role options
            document.querySelectorAll('.role-option').forEach(opt => opt.classList.remove('active'));
            // Add active class to the clicked role option
            element.classList.add('active');
            // Update hidden role input field
            document.getElementById('role').value = role;
        }

        /**
         * Toggle the visibility of the password field.
         */
        function togglePassword() {
            const passwordInput = document.getElementById('password');
            const passwordToggle = document.querySelector('.password-toggle');

            // Toggle between password and text input type
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