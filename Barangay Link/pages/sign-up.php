<?php
// Start session for user management
session_start();

// Database connection parameters
$host = 'localhost';
$dbname = 'barangay_db';
$username = 'root';
$password = '';

// Establish database connection
try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}

// Initialize error and success messages
$error = '';
$success = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Retrieve form data
    $firstname = $_POST['firstname'];
    $lastname = $_POST['lastname'];
    $email = $_POST['email'];
    $username = $_POST['username'];
    $password = $_POST['password'];
    $confirmPassword = $_POST['confirm-password'];

    // Validate form data
    if (empty($firstname) || empty($lastname) || empty($email) || empty($username) || empty($password) || empty($confirmPassword)) {
        $error = 'All fields are required.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Invalid email format.';
    } elseif ($password !== $confirmPassword) {
        $error = 'Passwords do not match.';
    } else {
        try {
            // Check if the username or email already exists in the database
            $query = "SELECT * FROM users WHERE username = :username OR email = :email";
            $stmt = $pdo->prepare($query);
            $stmt->bindParam(':username', $username);
            $stmt->bindParam(':email', $email);
            $stmt->execute();

            if ($stmt->rowCount() > 0) {
                $error = 'Username or email already exists.';
            } else {
                // Hash the password for security
                $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

                // Insert user data into the database
                $query = "INSERT INTO users (FirstName, LastName, Email, UserName, Password) 
                          VALUES (:firstname, :lastname, :email, :username, :password)";
                $stmt = $pdo->prepare($query);
                $stmt->bindParam(':firstname', $firstname);
                $stmt->bindParam(':lastname', $lastname);
                $stmt->bindParam(':email', $email);
                $stmt->bindParam(':username', $username);
                $stmt->bindParam(':password', $hashedPassword);
                $stmt->execute();

                // Display success message and redirect
                $success = 'Account created successfully! Redirecting to login...';
                header("refresh:2;url=../index.php");
            }
        } catch (Exception $e) {
            $error = 'Error processing signup: ' . $e->getMessage();
        }
    }
}
?>


<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Barangay Link | Register</title>
    <link rel="shortcut icon" href="../img/logo.ico">
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
            background-image: url(../img/3.jpg);
            background-repeat: no-repeat;
            background-size: cover;
        }

        .register-container {
            display: flex;
            background-color: white;
            border-radius: 10px;
            background: transparent;
            backdrop-filter: blur(20px);
            border-radius: 10px;
            box-shadow: 0 0 30px rgba(0, 0, 0, 0.5);
            overflow: hidden;
            width: 900px;
            height: 650px;
        }

        .register-image {
            background-color: #800000;
            width: 40%;
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

        .welcome-text {
            text-align: center;
            font-size: 24px;
            margin-top: 20px;
        }

        .register-form {
            width: 60%;
            padding: 40px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            overflow-y: auto;
        }

        h1 {
            font-size: 32px;
            margin-top: 100px;
            margin-bottom: 30px;
            color: #800000;
        }

        .input-group {
            margin-bottom: 20px;
            position: relative;
        }

        label {
            display: block;
            margin-bottom: 8px;
            color: white;
            font-weight: bold;
        }

        input,
        select {
            width: 100%;
            padding: 12px;
            border: 2px solid #ddd;
            border-radius: 5px;
            font-size: 16px;
            transition: border-color 0.3s;
        }

        input:focus,
        select:focus {
            border-color: #800000;
            outline: none;
        }

        .password-toggle {
            position: absolute;
            right: 12px;
            top: 39px;
            cursor: pointer;
            color: #666;
        }

        .role-selection {
            display: flex;
            gap: 20px;
            margin-bottom: 20px;
        }

        .role-option {
            flex: 1;
            padding: 15px;
            border: 2px solid #ddd;
            border-radius: 5px;
            cursor: pointer;
            text-align: center;
            transition: all 0.3s;
        }

        .role-option:hover {
            border-color: #800000;
        }

        .role-option.selected {
            background-color: #800000;
            color: white;
            border-color: #800000;
        }

        button {
            background-color: #800000;
            color: white;
            padding: 12px;
            border: none;
            border-radius: 5px;
            font-size: 16px;
            cursor: pointer;
            transition: background-color 0.3s;
            margin-top: 20px;
            width: 100%;
        }

        button:hover {
            background-color: #660000;
        }

        .login-link {
            text-align: center;
            margin-top: 20px;
            color: white;
        }

        .login-link a {
            color: #800000;
            text-decoration: none;
            font-weight: bold;
        }

        .login-link a:hover {
            text-decoration: underline;
        }

        .name-group {
            display: flex;
            gap: 20px;
        }

        .name-group .input-group {
            flex: 1;
        }

        .message {
            text-align: center;
            margin-top: 20px;
            font-size: 16px;
            font-weight: bold;
        }

        .error {
            color: red;
        }

        .success {
            color: green;
        }
    </style>
</head>

<body>
    <div class="register-container">
        <div class="register-image">
            <div class="logo">
                <img src="../img/logo.png" alt="Barangay Link Logo" style="width: 80%; height: 80%;">
            </div>
            <div class="welcome-text">
                <h2>Create Account</h2>
                <p>Join us to get started</p>
            </div>
        </div>
        <div class="register-form">
            <h1>Register</h1>
            <form action="sign-up.php" method="POST">
                <div class="name-group">
                    <div class="input-group">
                        <label for="firstname">First Name</label>
                        <input type="text" id="firstname" name="firstname" required>
                    </div>
                    <div class="input-group">
                        <label for="lastname">Last Name</label>
                        <input type="text" id="lastname" name="lastname" required>
                    </div>
                </div>
                <div class="input-group">
                    <label for="email">Email Address</label>
                    <input type="email" id="email" name="email" required>
                </div>
                <div class="input-group">
                    <label for="username">Username</label>
                    <input type="text" id="username" name="username" required>
                </div>
                <div class="input-group">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" required>
                    <span class="password-toggle" onclick="togglePassword('password', this)">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none"
                            stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                            <circle cx="12" cy="12" r="3"></circle>
                        </svg>
                    </span>
                </div>
                <div class="input-group">
                    <label for="confirm-password">Confirm Password</label>
                    <input type="password" id="confirm-password" name="confirm-password" required>
                    <span class="password-toggle" onclick="togglePassword('confirm-password', this)">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none"
                            stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                            <circle cx="12" cy="12" r="3"></circle>
                        </svg>
                    </span>
                </div>

                <button type="submit">Register</button>

                <?php if ($error): ?>
                    <div class="message error"><?= $error ?></div>
                <?php elseif ($success): ?>
                    <div class="message success"><?= $success ?></div>
                <?php endif; ?>

                <div class="login-link">
                    <p>Already have an account? <a href="../index.php">Login here</a></p>
                </div>
            </form>
        </div>
    </div>
    <script>
        /**
         * Toggles the visibility of a password input field
         */
        function togglePassword(inputId, toggleElement) {
            const passwordInput = document.getElementById(inputId);

            if (passwordInput.type === 'password') {
                // Switch to text visibility
                passwordInput.type = 'text';
                toggleElement.innerHTML = `
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"></path>
                    <line x1="1" y1="1" x2="23" y2="23"></line>
                </svg>`;
            } else {
                // Switch back to password visibility
                passwordInput.type = 'password';
                toggleElement.innerHTML = `
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                    <circle cx="12" cy="12" r="3"></circle>
                </svg>`;
            }
        }
    </script>

</body>

</html>