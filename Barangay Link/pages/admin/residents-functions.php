<?php
session_start();
include('../../db/db-connect.php');

// Function to validate input
function validateInput($data)
{
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

// Add New Resident
function addNewResident($pdo, $data)
{
    try {
        // Validate inputs
        $fullName = validateInput($data['fullName']);
        $birthDate = validateInput($data['birthDate']);
        $address = validateInput($data['address']);
        $contactNum = validateInput($data['contactNum']);
        $status = validateInput($data['status']);

        // Get the current user's ID (assuming the logged-in user is creating the resident)
        $username = $_SESSION['username'];
        $userQuery = "SELECT UserID FROM users WHERE UserName = :username";
        $userStmt = $pdo->prepare($userQuery);
        $userStmt->bindParam(':username', $username, PDO::PARAM_STR);
        $userStmt->execute();
        $user = $userStmt->fetch(PDO::FETCH_ASSOC);

        if (!$user) {
            return ['success' => false, 'message' => 'User not found'];
        }

        // Prepare SQL to insert new resident
        $sql = "INSERT INTO residents (FullName, BirthDate, Address, ContactNum, Status, UserID) 
                VALUES (:fullName, :birthDate, :address, :contactNum, :status, :userID)";

        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(':fullName', $fullName, PDO::PARAM_STR);
        $stmt->bindParam(':birthDate', $birthDate, PDO::PARAM_STR);
        $stmt->bindParam(':address', $address, PDO::PARAM_STR);
        $stmt->bindParam(':contactNum', $contactNum, PDO::PARAM_STR);
        $stmt->bindParam(':status', $status, PDO::PARAM_STR);
        $stmt->bindParam(':userID', $user['UserID'], PDO::PARAM_INT);

        $stmt->execute();

        return ['success' => true, 'message' => 'Resident added successfully', 'residentId' => $pdo->lastInsertId()];
    } catch (PDOException $e) {
        return ['success' => false, 'message' => 'Error adding resident: ' . $e->getMessage()];
    }
}

// Edit Existing Resident
function editResident($pdo, $data)
{
    try {
        // Validate inputs
        if (empty($data['residentId']) || empty($data['fullName'])) {
            return ['success' => false, 'message' => 'Resident ID and Full Name are required'];
        }

        $residentId = validateInput($data['residentId']);
        $fullName = validateInput($data['fullName']);
        $birthDate = validateInput($data['birthDate']);
        $address = validateInput($data['address']);
        $contactNum = validateInput($data['contactNum']);
        $status = validateInput($data['status']);

        // Check if resident exists before updating
        $checkQuery = "SELECT COUNT(*) FROM residents WHERE ResidentID = :residentId";
        $checkStmt = $pdo->prepare($checkQuery);
        $checkStmt->bindParam(':residentId', $residentId, PDO::PARAM_INT);
        $checkStmt->execute();

        if ($checkStmt->fetchColumn() == 0) {
            return ['success' => false, 'message' => 'Resident not found'];
        }

        // Update resident information
        $updateQuery = "UPDATE residents 
                        SET FullName = :fullName, 
                            BirthDate = :birthDate, 
                            Address = :address, 
                            ContactNum = :contactNum, 
                            Status = :status 
                        WHERE ResidentID = :residentId";

        $updateStmt = $pdo->prepare($updateQuery);
        $updateStmt->bindParam(':fullName', $fullName, PDO::PARAM_STR);
        $updateStmt->bindParam(':birthDate', $birthDate, PDO::PARAM_STR);
        $updateStmt->bindParam(':address', $address, PDO::PARAM_STR);
        $updateStmt->bindParam(':contactNum', $contactNum, PDO::PARAM_STR);
        $updateStmt->bindParam(':status', $status, PDO::PARAM_STR);
        $updateStmt->bindParam(':residentId', $residentId, PDO::PARAM_INT);

        $updateStmt->execute();

        return ['success' => true, 'message' => 'Resident updated successfully'];
    } catch (PDOException $e) {
        error_log('Edit Resident Error: ' . $e->getMessage()); // Log the actual error
        return ['success' => false, 'message' => 'An unexpected error occurred. Please try again.'];
    }
}

// Delete Resident
function deleteResident($pdo, $residentId)
{
    try {
        // First, check if there are any document requests or feedback associated with this resident
        $checkDocRequests = $pdo->prepare("SELECT COUNT(*) FROM document_requests WHERE ResidentID = :residentId");
        $checkDocRequests->bindParam(':residentId', $residentId, PDO::PARAM_INT);
        $checkDocRequests->execute();
        $docRequestCount = $checkDocRequests->fetchColumn();

        $checkFeedback = $pdo->prepare("SELECT COUNT(*) FROM feedback WHERE ResidentID = :residentId");
        $checkFeedback->bindParam(':residentId', $residentId, PDO::PARAM_INT);
        $checkFeedback->execute();
        $feedbackCount = $checkFeedback->fetchColumn();

        // If there are associated records, prevent deletion
        if ($docRequestCount > 0 || $feedbackCount > 0) {
            return ['success' => false, 'message' => 'Cannot delete resident with existing document requests or feedback'];
        }

        // Prepare SQL to delete resident
        $sql = "DELETE FROM residents WHERE ResidentID = :residentId";

        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(':residentId', $residentId, PDO::PARAM_INT);

        $stmt->execute();

        return ['success' => true, 'message' => 'Resident deleted successfully'];
    } catch (PDOException $e) {
        return ['success' => false, 'message' => 'Error deleting resident: ' . $e->getMessage()];
    }
}

// Handle POST Requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_SESSION['username'])) {
        echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
        exit();
    }

    $action = $_POST['action'] ?? '';

    switch ($action) {
        case 'add_resident':
            $result = addNewResident($pdo, $_POST);
            echo json_encode($result);
            break;

        case 'edit_resident':
            $result = editResident($pdo, $_POST);
            echo json_encode($result);
            break;

        case 'delete_resident':
            $result = deleteResident($pdo, $_POST['residentId']);
            echo json_encode($result);
            break;

        default:
            echo json_encode(['success' => false, 'message' => 'Invalid action']);
    }
    exit();
}
?>