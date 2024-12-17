<?php
session_start();
include('../../db/db-connect.php');

// Set the PDO error mode to exception for debugging database connection errors
try {
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    echo "Connection failed: " . $e->getMessage();
}

// Redirect to login page if the user is not logged in
if (!isset($_SESSION['username'])) {
    header("Location: ../index.php");
    exit();
}

// Fetch user data from the database using the session's username
$username = $_SESSION['username'];
$userQuery = "SELECT * FROM users WHERE UserName = :username";
$userStmt = $pdo->prepare($userQuery);
$userStmt->bindParam(':username', $username, PDO::PARAM_STR);
$userStmt->execute();
$user = $userStmt->fetch(PDO::FETCH_ASSOC);

// Retrieve all residents' data if the user exists in the database
if ($user) {
    $residentsQuery = "SELECT * FROM residents";
    $residentsStmt = $pdo->prepare($residentsQuery);
    $residentsStmt->execute();
    $residents = $residentsStmt->fetchAll(PDO::FETCH_ASSOC);
} else {
    $residents = [];
}

// Count the total number of residents retrieved
$totalResidents = count($residents);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Residents | Admin</title>
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

        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
        }

        .residents-title {
            font-size: 24px;
            color: #333;
        }

        .search-box {
            display: flex;
            gap: 10px;
            align-items: center;
        }

        .search-input {
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
            width: 300px;
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
        }

        .btn:hover {
            background-color: #660000;
        }

        .residents-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }

        .residents-table th,
        .residents-table td {
            padding: 15px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }

        .residents-table th {
            background-color: #f5f5f5;
            font-weight: bold;
            color: #333;
        }

        .residents-table tr:hover {
            background-color: #f9f9f9;
        }

        .status {
            padding: 5px 10px;
            border-radius: 15px;
            font-size: 14px;
        }

        .status-active {
            background-color: #e6ffe6;
            color: #006600;
        }

        .status-inactive {
            background-color: #ffe6e6;
            color: #660000;
        }

        .action-buttons {
            display: flex;
            gap: 10px;
        }

        .btn-edit,
        .btn-delete {
            padding: 8px 15px;
            border-radius: 5px;
            cursor: pointer;
            font-size: 14px;
        }

        .btn-edit {
            background-color: #8B0000;
            color: white;
            border: none;
        }

        .btn-delete {
            background-color: white;
            color: #8B0000;
            border: 1px solid #8B0000;
        }

        .logout {
            margin-top: auto;
        }

        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            overflow: auto;
            background-color: rgba(0, 0, 0, 0.4);
        }

        .modal-content {
            background-color: #fefefe;
            margin: 10% auto;
            padding: 20px;
            border: 1px solid #888;
            width: 500px;
            border-radius: 10px;
        }

        .modal-content input,
        .modal-content select {
            width: 100%;
            padding: 10px;
            margin: 10px 0;
            border: 1px solid #ddd;
            border-radius: 5px;
        }

        .modal-buttons {
            display: flex;
            justify-content: space-between;
            margin-top: 20px;
        }

        .close-modal {
            color: #aaa;
            float: right;
            font-size: 28px;
            font-weight: bold;
            cursor: pointer;
        }

        .close-modal:hover {
            color: black;
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
                <a href="#" class="menu-item active">
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

        <div class="main-content">
            <div class="header">
                <h1 class="residents-title">RESIDENTS (Total: <?= $totalResidents ?>)</h1>
                <div class="search-box">
                    <input type="text" class="search-input" placeholder="Search residents...">
                    <button class="btn" onclick="addNewResident()">ADD NEW</button>
                </div>
            </div>

            <table class="residents-table">
                <thead>
                    <tr>
                        <th>RESIDENT ID</th>
                        <th>FULL NAME</th>
                        <th>BIRTH DATE</th>
                        <th>ADDRESS</th>
                        <th>CONTACT</th>
                        <th>STATUS</th>
                        <th>ACTIONS</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($residents)): ?>
                        <?php foreach ($residents as $resident): ?>
                            <tr>
                                <td>RES<?= htmlspecialchars($resident['ResidentID']) ?></td>
                                <td><?= htmlspecialchars($resident['FullName']) ?></td>
                                <td><?= htmlspecialchars($resident['BirthDate']) ?></td>
                                <td><?= htmlspecialchars($resident['Address']) ?></td>
                                <td><?= htmlspecialchars($resident['ContactNum']) ?></td>
                                <td>
                                    <span
                                        class="status <?= $resident['Status'] === 'Active' ? 'status-active' : 'status-inactive' ?>">
                                        <?= htmlspecialchars($resident['Status']) ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="action-buttons">
                                        <button class="btn-edit"
                                            onclick="editResident(<?= htmlspecialchars($resident['ResidentID']) ?>)">Edit</button>
                                        <button class="btn-delete"
                                            onclick="deleteResident(<?= htmlspecialchars($resident['ResidentID']) ?>)">Delete</button>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="7" style="text-align: center;">No residents found</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>

            <!-- Modals for Add, Edit, Delete -->
            <div id="addResidentModal" class="modal">
                <div class="modal-content">
                    <span class="close-modal" onclick="closeModal('addResidentModal')">&times;</span>
                    <h2>Add New Resident</h2>
                    <form id="addResidentForm">
                        <input type="text" name="fullName" placeholder="Full Name" required>
                        <input type="date" name="birthDate" required>
                        <input type="text" name="address" placeholder="Address" required>
                        <input type="text" name="contactNum" placeholder="Contact Number" required>
                        <select name="status">
                            <option value="Active">Active</option>
                            <option value="Inactive">Inactive</option>
                        </select>
                        <div class="modal-buttons">
                            <button type="button" class="btn" onclick="submitAddResident()">Add Resident</button>
                            <button type="button" class="btn" style="background-color: #666;"
                                onclick="closeModal('addResidentModal')">Cancel</button>
                        </div>
                    </form>
                </div>
            </div>

            <div id="editResidentModal" class="modal">
                <div class="modal-content">
                    <span class="close-modal" onclick="closeModal('editResidentModal')">&times;</span>
                    <h2>Edit Resident</h2>
                    <form id="editResidentForm">
                        <input type="hidden" name="residentId" id="editResidentId">
                        <input type="text" name="fullName" id="editFullName" placeholder="Full Name" required>
                        <input type="date" name="birthDate" id="editBirthDate" required>
                        <input type="text" name="address" id="editAddress" placeholder="Address" required>
                        <input type="text" name="contactNum" id="editContactNum" placeholder="Contact Number" required>
                        <select name="status" id="editStatus">
                            <option value="Active">Active</option>
                            <option value="Inactive">Inactive</option>
                        </select>
                        <div class="modal-buttons">
                            <button type="button" class="btn" onclick="submitEditResident()">Update Resident</button>
                            <button type="button" class="btn" style="background-color: #666;"
                                onclick="closeModal('editResidentModal')">Cancel</button>
                        </div>
                    </form>
                </div>
            </div>

        </div>
    </div>

    <script>
        // Open the "Add Resident" modal dialog
        function addNewResident() {
            document.getElementById('addResidentModal').style.display = 'block';
        }

        // Submit the "Add Resident" form data to the server via a POST request
        function submitAddResident() {
            const form = document.getElementById('addResidentForm');
            const formData = new FormData(form);
            formData.append('action', 'add_resident');

            fetch('residents-functions.php', {
                method: 'POST',
                body: formData
            })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert(data.message);
                        location.reload(); // Reload the page to update the residents list
                    } else {
                        alert(data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('An error occurred while adding the resident');
                });
        }

        // Open the "Edit Resident" modal with pre-filled data
        function editResident(residentId) {
            // Convert residentId to string with "RES" prefix
            residentId = 'RES' + String(residentId);

            // Find the matching resident's row in the table
            const rows = document.querySelectorAll('.residents-table tbody tr');
            let targetRow = null;

            for (let row of rows) {
                const idCell = row.querySelector('td:first-child');
                if (idCell && idCell.textContent.trim() === residentId) {
                    targetRow = row;
                    break;
                }
            }

            if (!targetRow) {
                console.error('Resident row not found');
                alert('Could not find resident details');
                return;
            }

            // Extract data from the row's cells to pre-fill the modal
            const fullName = targetRow.cells[1].textContent.trim();
            const birthDate = targetRow.cells[2].textContent.trim();
            const address = targetRow.cells[3].textContent.trim();
            const contactNum = targetRow.cells[4].textContent.trim();
            const status = targetRow.cells[5].querySelector('.status').textContent.trim();

            // Populate the "Edit Resident" modal with the extracted data
            document.getElementById('editResidentId').value = residentId.replace('RES', '');
            document.getElementById('editFullName').value = fullName;
            document.getElementById('editBirthDate').value = birthDate;
            document.getElementById('editAddress').value = address;
            document.getElementById('editContactNum').value = contactNum;
            document.getElementById('editStatus').value = status;

            // Display the modal
            document.getElementById('editResidentModal').style.display = 'block';
        }

        // Submit the "Edit Resident" form data to the server via a POST request
        function submitEditResident() {
            const form = document.getElementById('editResidentForm');
            const formData = new FormData(form);
            formData.append('action', 'edit_resident');

            fetch('residents-functions.php', {
                method: 'POST',
                body: formData
            })
                .then(response => {
                    if (!response.ok) {
                        throw new Error(`HTTP error! status: ${response.status}`);
                    }
                    return response.json();
                })
                .then(data => {
                    if (data.success) {
                        alert(data.message);
                        location.reload(); // Reload the page to reflect changes
                    } else {
                        alert(data.message);
                    }
                })
                .catch(error => {
                    console.error('Detailed Error:', error);
                    alert('An error occurred while updating the resident: ' + error.message);
                });
        }

        // Delete a resident after confirmation
        function deleteResident(residentId) {
            if (confirm('Are you sure you want to delete this resident? This action cannot be undone.')) {
                const formData = new FormData();
                formData.append('action', 'delete_resident');
                formData.append('residentId', residentId);

                fetch('residents-functions.php', {
                    method: 'POST',
                    body: formData
                })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            alert(data.message);
                            location.reload(); // Refresh the list of residents
                        } else {
                            alert(data.message);
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        alert('An error occurred while deleting the resident');
                    });
            }
        }

        // Close a modal dialog
        function closeModal(modalId) {
            document.getElementById(modalId).style.display = 'none';
        }

        // Filter residents in the table based on search input
        document.querySelector('.search-input').addEventListener('input', function () {
            const searchTerm = this.value.toLowerCase();
            const rows = document.querySelectorAll('.residents-table tbody tr');

            rows.forEach(row => {
                const fullName = row.children[1].textContent.toLowerCase();
                const address = row.children[3].textContent.toLowerCase();
                const contactNum = row.children[4].textContent.toLowerCase();

                if (fullName.includes(searchTerm) ||
                    address.includes(searchTerm) ||
                    contactNum.includes(searchTerm)) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        });
    </script>

    <script>
        if (!Element.prototype.contains) {
            Element.prototype.contains = function (text) {
                return this.textContent.trim() === text.trim();
            };
        }
    </script>
</body>

</html>