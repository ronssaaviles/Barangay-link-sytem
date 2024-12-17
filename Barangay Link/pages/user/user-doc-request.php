<?php
// Start a session for user authentication and data sharing across pages
session_start();

// Include database connection file
include('../../db/db-connect.php');

// Redirect to login if the user is not logged in
if (!isset($_SESSION['username'])) {
    header("Location: ../index.php");
    exit();
}

// Retrieve user data from the session and fetch from the database
$username = $_SESSION['username'];
$query = "SELECT * FROM users WHERE UserName = :username";
$stmt = $pdo->prepare($query);
$stmt->bindParam(':username', $username, PDO::PARAM_STR);
$stmt->execute();
$user = $stmt->fetch(PDO::FETCH_ASSOC);

// Handle form submission for a document request
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Retrieve and validate form input data
    $documentType = $_POST['document-type'] ?? '';
    $firstName = $_POST['firstname'] ?? $user['FirstName'];
    $middleName = $_POST['middlename'] ?? '';
    $lastName = $_POST['lastname'] ?? $user['LastName'];
    $age = $_POST['age'] ?? 0;
    $nationality = $_POST['nationality'] ?? '';
    $address = $_POST['address'] ?? '';
    $contactNum = $_POST['contact_number'] ?? '';

    // Check if all required fields are filled and valid
    if (
        !empty($documentType) && !empty($firstName) && !empty($lastName) && $age > 0 &&
        !empty($nationality) && !empty($address) && !empty($contactNum)
    ) {
        try {
            // Start a database transaction
            $pdo->beginTransaction();

            // Check if a resident record already exists for the user
            $residentQuery = "
                SELECT r.ResidentID 
                FROM residents r 
                JOIN users u ON r.UserID = u.UserID 
                WHERE u.UserName = :username
            ";
            $stmt = $pdo->prepare($residentQuery);
            $stmt->bindParam(':username', $username, PDO::PARAM_STR);
            $stmt->execute();
            $resident = $stmt->fetch(PDO::FETCH_ASSOC);

            $residentId = null;

            if (!$resident) {
                // Insert a new resident record if one doesn't exist
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
                        :address,
                        :contact_number,
                        'Active',
                        (SELECT UserID FROM users WHERE UserName = :username)
                    )
                ";
                $stmt = $pdo->prepare($createResidentQuery);
                $fullName = $firstName . ' ' . ($middleName ? $middleName . ' ' : '') . $lastName;
                $stmt->bindParam(':fullname', $fullName, PDO::PARAM_STR);
                $stmt->bindParam(':address', $address, PDO::PARAM_STR);
                $stmt->bindParam(':contact_number', $contactNum, PDO::PARAM_STR);
                $stmt->bindParam(':username', $username, PDO::PARAM_STR);
                $stmt->execute();

                // Get the newly created ResidentID
                $residentId = $pdo->lastInsertId();
            } else {
                // Update the existing resident record
                $updateResidentQuery = "
                    UPDATE residents 
                    SET ContactNum = :contact_number,
                        Address = :address
                    WHERE ResidentID = :resident_id
                ";
                $stmt = $pdo->prepare($updateResidentQuery);
                $stmt->bindParam(':contact_number', $contactNum, PDO::PARAM_STR);
                $stmt->bindParam(':address', $address, PDO::PARAM_STR);
                $stmt->bindParam(':resident_id', $resident['ResidentID'], PDO::PARAM_INT);
                $stmt->execute();

                $residentId = $resident['ResidentID'];
            }

            // Insert the document request into the database
            $insertQuery = "
                INSERT INTO document_requests (
                    Document_Type, 
                    First_Name, 
                    Middle_Name, 
                    Last_Name, 
                    Age, 
                    Nationality, 
                    Address, 
                    ResidentID, 
                    Status,
                    Date_Requested,
                    Last_Updated
                ) VALUES (
                    :document_type, 
                    :first_name, 
                    :middle_name, 
                    :last_name, 
                    :age, 
                    :nationality, 
                    :address, 
                    :resident_id,
                    'Pending',
                    CURRENT_TIMESTAMP,
                    CURRENT_TIMESTAMP
                )
            ";
            $stmt = $pdo->prepare($insertQuery);
            $stmt->bindParam(':document_type', $documentType, PDO::PARAM_STR);
            $stmt->bindParam(':first_name', $firstName, PDO::PARAM_STR);
            $stmt->bindParam(':middle_name', $middleName, PDO::PARAM_STR);
            $stmt->bindParam(':last_name', $lastName, PDO::PARAM_STR);
            $stmt->bindParam(':age', $age, PDO::PARAM_INT);
            $stmt->bindParam(':nationality', $nationality, PDO::PARAM_STR);
            $stmt->bindParam(':address', $address, PDO::PARAM_STR);
            $stmt->bindParam(':resident_id', $residentId, PDO::PARAM_INT);
            $stmt->execute();

            // Commit the transaction
            $pdo->commit();
            $successMessage = "Document request submitted successfully!";
        } catch (PDOException $e) {
            // Rollback the transaction in case of an error
            $pdo->rollBack();
            $errorMessage = "Error submitting request: " . $e->getMessage();
        }
    } else {
        $errorMessage = "Please fill in all required fields.";
    }
}

// Fetch the request history for display
$requestQuery = "
    SELECT 
        dr.RequestID,
        dr.Document_Type,
        dr.Status,
        dr.Date_Requested,
        dr.Last_Updated,
        CONCAT(dr.First_Name, ' ', COALESCE(dr.Middle_Name, ''), ' ', dr.Last_Name) as FullName,
        r.Status as ResidentStatus
    FROM document_requests dr
    JOIN residents r ON dr.ResidentID = r.ResidentID
    JOIN users u ON r.UserID = u.UserID
    WHERE u.UserName = :username 
    ORDER BY dr.Date_Requested DESC
";
$stmt = $pdo->prepare($requestQuery);
$stmt->bindParam(':username', $_SESSION['username'], PDO::PARAM_STR);
$stmt->execute();
$requests = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Barangay Link | Document Request</title>
    <link rel="shortcut icon" href="../../img/logo.ico">
    <style>
        body {
            margin: 0;
            font-family: Arial, sans-serif;
            background-color: white;
            min-height: 100vh;
        }

        .container {
            display: flex;
            min-height: 100vh;
            position: relative;
        }

        .sidebar {
            width: 20%;
            min-width: 300px;
            background-color: #800000;
            color: white;
            display: flex;
            flex-direction: column;
            padding: 20px;
            height: 100vh;
            position: fixed;
            overflow-y: auto;
            box-sizing: border-box;
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
            font-size: 30px;
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

        .document-content {
            flex-grow: 1;
            padding: 60px;
            margin-left: 20%;
            min-height: 100vh;
            background-color: white;
            position: relative;
        }

        .document-form {
            max-width: 600px;
            margin-left: 150px;
            margin-top: 40px;
            position: relative;
            padding-bottom: 100px;
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

        .form-group input,
        .form-group select {
            width: 100%;
            padding: 10px;
            font-size: 16px;
            border: 2px solid black;
            border-radius: 4px;
            box-sizing: border-box;
        }

        .button-group {
            display: flex;
            gap: 20px;
            justify-content: flex-end;
            position: absolute;
            bottom: -30px;
            right: -600px;
            width: 100%;
            background-color: white;
            padding: 20px 0;
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

        /* Document description styles */
        .document-description {
            background-color: #f5f5f5;
            padding: 20px;
            border-radius: 8px;
            margin: 20px 0;
            border-left: 4px solid #800000;
            display: none;
            max-width: 600px;
        }

        .document-description h3 {
            margin-top: 0;
            color: #800000;
            font-size: 1.2em;
        }

        .document-description p {
            margin: 10px 0;
            line-height: 1.6;
            font-size: 0.95em;
        }

        .document-description ul {
            margin: 10px 0;
            padding-left: 20px;
        }

        .document-description li {
            margin: 5px 0;
            font-size: 0.95em;
        }

        /* Initially hide the form fields */
        #formFields {
            display: none;
        }

        .history-section {
            margin-top: 50px;
            margin-left: 150px;
            padding: 20px;
            background-color: #fff;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            max-width: 1000px;
        }

        .history-section h2 {
            color: #800000;
            margin-bottom: 20px;
        }

        .history-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
            background-color: white;
        }

        .history-table th,
        .history-table td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }

        .history-table th {
            background-color: #800000;
            color: white;
        }

        .history-table tr:hover {
            background-color: #f5f5f5;
        }

        .status-pending {
            color: #ff9900;
            font-weight: bold;
        }

        .status-approved {
            color: #28a745;
            font-weight: bold;
        }

        .status-rejected {
            color: #dc3545;
            font-weight: bold;
        }

        .btn-view {
            padding: 8px 16px;
            font-size: 14px;
            background-color: #800000;
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
                <a href="#">
                    <li class="menu-item active">
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


        <!-- Document Request Content -->
        <div class="document-content">
            <h1>DOCUMENT REQUEST</h1>
            <?php if (isset($successMessage)): ?>
                <p style="color: green;"><?= $successMessage ?></p>
            <?php endif; ?>
            <?php if (isset($errorMessage)): ?>
                <p style="color: red;"><?= $errorMessage ?></p>
            <?php endif; ?>

            <form method="POST" class="document-form">
                <div class="form-group">
                    <select name="document-type" id="documentType" onchange="showForm()">
                        <option value="">SELECT</option>
                        <option value="residency">Certificate of Residency</option>
                        <option value="clearance">Barangay Clearance</option>
                        <option value="indigency">Certificate of Indigency</option>
                    </select>
                </div>

                <!-- Descriptions & Form Fields -->
                <div id="residencyDescription" class="document-description">
                    <h3>Barangay Certificate of Residency</h3>
                    <p>Barangay Certificate of Residency is considered one of the primary proofs of residence used in
                        the Philippines, verifying that an
                        individual lives within a specific barangay or neighborhood.</p>
                    <ul>
                        <li>Requirements:</li>
                        <li>Latest Community Tax Certificate (Cedula)</li>
                        <li>Valid ID</li>
                        <li>Application Fee</li>
                    </ul>
                    <p><strong>Processing Time:</strong> Less than 1 hour - 1 day</p>
                </div>
                <div id="clearanceDescription" class="document-description">
                    <h3>Barangay Clearance</h3>
                    <p>Barangay Clearance is a document certifying that the applicant is of good moral character of a
                        given town or barangay. It also proves that the person has no bad records or immoral background.
                        The certificate confirms that the person stated has a good standing as a resident of the
                        barangay.
                    </p>
                    <ul>
                        <li>Requirements:</li>
                        <li>Latest Community Tax Certificate (Cedula)</li>
                        <li>Valid ID</li>
                        <li>Application Fee</li>
                    </ul>
                    <p><strong>Processing Time:</strong> Less than 1 hour - 1 day</p>
                </div>

                <div id="indigencyDescription" class="document-description">
                    <h3>Barangay Certificate of Indigency</h3>
                    <p>Barangay Certificate of Indigency is an official document issued by a barangay that certifies a
                        person or family as belonging to the economically disadvantaged or low-income sector of the
                        community.</p>
                    <ul>
                        <li>Requirements:</li>
                        <li>Latest Community Tax Certificate (Cedula)</li>
                        <li>Valid ID</li>
                        <li>Application Fee</li>
                    </ul>
                    <p><strong>Processing Time:</strong> Less than 1 hour - 1 day</p>
                </div>

                <div id="formFields">
                    <div class="form-group"><label>FIRST NAME</label><input type="text" name="firstname"
                            value="<?= htmlspecialchars($user['FirstName']) ?>" required></div>
                    <div class="form-group"><label>MIDDLE NAME</label><input type="text" name="middlename"></div>
                    <div class="form-group"><label>LAST NAME</label><input type="text" name="lastname"
                            value="<?= htmlspecialchars($user['LastName']) ?>" required></div>
                    <div class="form-group"><label>AGE</label><input type="number" name="age" required></div>
                    <div class="form-group"><label>NATIONALITY</label><input type="text" name="nationality" required>
                    </div>
                    <div class="form-group"><label>ADDRESS</label><input type="text" name="address" required></div>
                    <div class="form-group">
                        <label>CONTACT NUMBER</label>
                        <input type="tel" name="contact_number" pattern="[0-9]+" minlength="11" maxlength="11" required
                            placeholder="e.g., 09123456789"
                            title="Please enter a valid 11-digit phone number starting with 09">
                    </div>
                    <div class="button-group">
                        <button type="reset" class="btn btn-cancel" onclick="cancelForm()">CANCEL</button>
                        <button type="submit" class="btn btn-submit">SUBMIT</button>
                    </div>
                </div>
            </form>

            <!-- Request History -->
            <div class="history-section">
                <h2>REQUEST HISTORY</h2>
                <table class="history-table">
                    <thead>
                        <tr>
                            <th>Request ID</th>
                            <th>Document Type</th>
                            <th>Date Requested</th>
                            <th>Status</th>
                            <th>Last Update</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($requests): ?>
                            <?php foreach ($requests as $request): ?>
                                <tr>
                                    <td><?= htmlspecialchars($request['RequestID']) ?></td>
                                    <td><?= htmlspecialchars($request['Document_Type']) ?></td>
                                    <td><?= htmlspecialchars($request['Date_Requested']) ?></td>
                                    <td class="status-<?= strtolower($request['Status']) ?>">
                                        <?= htmlspecialchars($request['Status']) ?>
                                    </td>
                                    <td><?= htmlspecialchars($request['Last_Updated']) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="5">No requests found.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <script>
        // Show relevant form fields and descriptions based on document type selection
        function showForm() {
            const documentType = document.getElementById('documentType'); // Dropdown for document type
            const formFields = document.getElementById('formFields'); // Form input fields section
            const historySection = document.querySelector('.history-section'); // Request history section

            // Hide all document descriptions
            const descriptions = document.querySelectorAll('.document-description');
            descriptions.forEach(desc => desc.style.display = 'none');

            if (documentType.value !== '') {
                // Show the relevant document description
                const selectedDescription = document.getElementById(documentType.value + 'Description');
                if (selectedDescription) {
                    selectedDescription.style.display = 'block';
                }
                formFields.style.display = 'block'; // Show form fields
                historySection.style.display = 'none'; // Hide history section
                document.querySelector('.document-content').scrollTop = 0; // Scroll to top of content
            } else {
                formFields.style.display = 'none'; // Hide form fields
                historySection.style.display = 'block'; // Show history section
            }
        }

        // Reset the form and hide unnecessary sections
        function cancelForm() {
            document.getElementById('documentType').value = ''; // Reset dropdown
            document.getElementById('formFields').style.display = 'none'; // Hide form fields
            const descriptions = document.querySelectorAll('.document-description');
            descriptions.forEach(desc => desc.style.display = 'none'); // Hide all descriptions
            document.querySelector('.history-section').style.display = 'block'; // Show history section
            const inputs = document.querySelectorAll('input'); // Reset all inputs
            inputs.forEach(input => input.value = '');
        }
    </script>

</body>

</html>