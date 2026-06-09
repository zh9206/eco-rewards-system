<?php
session_start();

// To prevent error
if (!isset($_SESSION['UserID'])) {
    header("Location: index.html");
    exit();
}

include ("conn.php");

$userID = $_SESSION['UserID'];

// Get User's profile
$sqlUser = "SELECT User.*, Role.Role_Name
            FROM User
            JOIN Role ON User.RoleID = Role.RoleID
            WHERE User.UserID = ?";

$stmtUser = $dbConn->prepare($sqlUser);
$stmtUser->bind_param("s", $userID);
$stmtUser->execute();
$resultUser = $stmtUser->get_result();
$user = $resultUser->fetch_assoc();

include("profile.php");

// Get All Users' Details
$sql = "SELECT * FROM User";
$result = $dbConn->query($sql);

$users = [];
while ($row = $result->fetch_assoc()) {
    $users[] = $row;
}

$roleMap = [
    "R01" => "Student",
    "R02" => "Moderator",
    "R03" => "Administrator"
];
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Manage Users - <?php echo $user['Username']?></title>
<link rel="stylesheet" href="styles.css">
<link rel="stylesheet" href="responsive.css">
<style>
body {
    margin: 0;
    padding: 0;
    display: flex;
    justify-content: flex-start;
    align-items: stretch;
    min-height: 100vh;
}

.main{
    display: flex;
    align-items: flex-start;
    gap: 24px;
}

.stats{
    display:flex;
    justify-content:flex-end;
    align-items:center;
    font-size:16px;
    font-weight:600;
    color:#2e7d32;
    gap:6px;
    margin-bottom:5px;
}

/* Action Panel */
.action-panel{
    width: 180px;
    position: sticky;
    top: 20px;
    display: flex;
    flex-direction: column;
    gap: 14px;
    align-self: flex-start;
}

.action-btn{
    width: 100%;
    padding: 12px 16px;
    border: none;
    border-radius: 14px;
    font-size: 14px;
    font-weight: 600;
    cursor: pointer;
    box-shadow: 0 4px 12px rgba(0,0,0,0.08);
    transition: 0.25s ease;
}

.action-btn:hover{
    transform: translateY(-2px);
}

.delete-btn{
    background: #dc3545;
    color: #fff;
}

#editBtn{
    background: #84B179;
    color: #fff;
}

.action-panel .btn:first-child{
    background: #A2CB8B;
    color: #fff;
}

.modal-content h2 {
    margin-bottom: 18px;
    color: #2e7d32;
}

.modal-body {
    max-height: 60vh;
    overflow-y: auto;
}

.modal-footer {
    position: sticky;
    bottom: 0;
    background: white;
    padding-top: 10px;
}

.password-wrapper {
    position: relative;
}

.password-wrapper input {
    padding-right: 40px;
}

.toggle-password {
    position: absolute;
    right: 12px;
    top: 50%;
    transform: translateY(-50%);
    cursor: pointer;
}

</style>
</head>
<body>

    <!-- Hamburger Button - Small Screen Only!! -->
    <?php include("hamburger.php")?>

    <!-- Sidebar -->
    <div class="sidebar" id="sidebar">
        <div class="sidebar-top">
            <div class="logo">
                <img src="logo.png" alt="Logo">
            </div>

            <div class="nav-links">
                <a href="admin.php" class="active">Manage Users</a>
                <a href="SustainabilityChallenges.php">Sustainability Challenges</a>
                <a href="viewReport.php">View Report & Statistics</a>
                <a href="rewardsSystem.php">Rewards System</a>
                <a href="announcement.php">Announcement</a>
            </div>
        </div>

        <div class="sidebar-bottom">
            <div class="profile" onclick="openProfileModal()">
                <img src="profile-pic.png">
                <span><?php echo $user['Username']; ?></span>
            </div>

            <button class="btn" onclick="logout()">Logout</button>
        </div>
    </div>

    <div class="main">
        <div class="section">

            <h1>Users List</h1>

            <div class="top-bar">
                <!-- Search Bar -->
                <div class="search-box">
                    <span class="search-icon">🔍</span>
                    <input type="text" id="searchInput" placeholder="Search user...">
                </div>

                <!-- Role Filter -->
                <div class="filter-container">
                    <select id="roleFilter" class="filter-dropdown">
                        <option value="">All</option>
                        <option value="Student">Student</option>
                        <option value="Moderator">Moderator</option>
                        <option value="Administrator">Administrator</option>
                    </select>
                </div>

                <div class="stats">
                    Total: <b id="totalusers"><?php echo count($users); ?></b>
                </div>
            </div>

            <table class="table">
                <thead>
                    <tr>
                        <th>User ID</th>
                        <th>Username</th>
                        <th>Gender</th>
                        <th>Password</th>
                        <th>D.O.B</th>
                        <th>Contact</th>
                        <th>Role</th>
                        <th>Last Active</th>
                    </tr>
                </thead>
                <tbody id="userTable">
                    <?php foreach ($users as $row): ?>
                        <tr onclick='selectUser(<?php echo json_encode($row, JSON_HEX_APOS | JSON_HEX_QUOT); ?>, this)'>
                            <td data-label="User ID"><?php echo htmlspecialchars($row['UserID']); ?></td>
                            <td data-label="Username"><?php echo htmlspecialchars($row['Username']); ?></td>
                            <td data-label="Gender"><?php echo htmlspecialchars($row['Gender']); ?></td>
                            <td>******</td>
                            <td data-label="DOB"><?php echo htmlspecialchars($row['DOB']); ?></td>
                            <td data-label="Contact"><?php echo htmlspecialchars($row['Contact']); ?></td>
                            <td data-label="Role ID"><?php echo htmlspecialchars($roleMap[$row['RoleID']] ?? $row['RoleID']); ?></td>
                            <td data-label="Last Active"><?php echo !empty($row['LastActive']) ? htmlspecialchars($row['LastActive']) : '-'; ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <div class="section">
            <div class="action-panel">
                <button class="btn action-btn" id="addBtn">Add</button>
                <button class="btn action-btn" id="editBtn">Edit</button>
                <button class="btn action-btn delete-btn" id="deleteBtn">Delete</button>
            </div>
        </div>
    </div>

    <!-- Edit Modal -->
    <div id="userModal" class="modal">
        <div class="modal-content">
            <span class="close-btn" onclick="closeModal('userModal')">✖</span>

            <h2>Edit User</h2>

            <div class="modal-body">
                <div class="form-group">
                    <label>User ID</label>
                    <input type="text" id="userId" readonly>
                </div>

                <div class="form-group">
                    <label>Username</label>
                    <input type="text" id="name" required>
                </div>

                <div class="form-group">
                    <label>Gender</label>
                    <select id="gender">
                        <option value="Male">Male</option>
                        <option value="Female">Female</option>
                    </select>
                </div>

                <div class="form-group">
                    <label>D.O.B</label>
                    <input type="date" id="dob" required>
                </div>

                <div class="form-group">
                    <label>Contact</label>
                    <input type="text" id="contact" required>
                </div>

                <div class="form-group">
                    <label>Role</label>
                    <select id="role" disabled>
                        <option value="R01">Student</option>
                        <option value="R02">Moderator</option>
                        <option value="R03">Administrator</option>
                    </select>
                    <input type="hidden" id="roleHidden">
                </div>

                <div class="form-group">
                    <label>Last Active</label>
                    <input type="text" id="lastActive" readonly>
                </div>

                <div class="form-group">
                    <label>Password</label>
                    <div class="password-wrapper">
                        <input type="password" id="password">
                        <span class="toggle-password">👁️</span>
                    </div>
                </div>

                <div class="form-group">
                    <label>Confirm Password</label>
                    <div class="password-wrapper">
                        <input type="password" id="confirmPassword">
                        <span class="toggle-password">👁️</span>
                    </div>
                </div>
            </div>

            <div class="modal-footer">
                <button class="btn" id="saveBtn">SAVE</button>
            </div>
        </div>
    </div>

    <!-- Add Modal -->
    <div id="addUserModal" class="modal">
        <div class="modal-content">
            <span class="close-btn" onclick="closeModal('addUserModal')">✖</span>

            <h2>Add User</h2>

            <div class="modal-body">
                <div class="form-group">
                    <label>User ID</label>
                    <input type="text" id="addUserId" name="userid" readonly>
                </div>

                <div class="form-group">
                    <label>Username</label>
                    <input type="text" id="addUsername" name="username" required>
                </div>

                <div class="form-group">
                    <label>Gender</label>
                    <select id="addGender" name="gender">
                        <option value="Male">Male</option>
                        <option value="Female">Female</option>
                    </select>
                </div>

                <div class="form-group">
                    <label>Date of Birth</label>
                    <input type="date" id="addDob" name="dob" required>
                </div>

                <div class="form-group">
                    <label>Contact Number</label>
                    <input type="text" id="addContact" name="contact" required>
                </div>

                <div class="form-group">
                    <label>Role</label>
                    <select id="addRoleId" name="roleid">
                        <option value="R01">Student</option>
                        <option value="R02">Moderator</option>
                        <option value="R03">Administrator</option>
                    </select>
                </div>

                <div class="form-group">
                    <label>Password</label>
                    <div class="password-wrapper">
                        <input type="password" id="addPassword" name="password" required>
                        <span class="toggle-password">👁️</span>
                    </div>
                </div>

                <div class="form-group">
                    <label>Confirm Password</label>
                    <div class="password-wrapper">
                        <input type="password" id="addConfirmPassword" name="confirmPassword" required>
                        <span class="toggle-password">👁️</span>
                    </div>
                </div>
            </div>

            <div class="modal-footer">
                <button class="btn" id="addSaveBtn">SAVE</button>
            </div>
        </div>
    </div>

<!-- Toast -->
    <div id="toast" class="toast"></div>

<script>
// Select User Row
let selectedUser = null;

function selectUser(user, row) {
    document.querySelectorAll("#userTable tr").forEach(r => r.classList.remove("selected"));
    row.classList.add("selected");
    selectedUser = user;
}

// Filter
document.getElementById("roleFilter").addEventListener("change", function(){
    const roleText = this.value;
    document.querySelectorAll("#userTable tr").forEach(r => {
        const roleDisplay = r.cells[6].innerText;
        r.style.display = (roleText === "" || roleText === roleDisplay) ? "" : "none";
    });
});

// Search
document.getElementById("searchInput").addEventListener("input", function(){
    const q = this.value.toLowerCase();
    document.querySelectorAll("#userTable tr").forEach(r=>{
        r.style.display = r.innerText.toLowerCase().includes(q) ? "" : "none";
    });
});

// Add
const addModal = document.getElementById("addUserModal");

document.getElementById("addBtn").onclick = () => {
    document.getElementById("addUsername").value = "";
    document.getElementById("addGender").value = "Male";
    document.getElementById("addDob").value = "";
    document.getElementById("addContact").value = "";
    document.getElementById("addRoleId").value = "R01";
    document.getElementById("addPassword").value = "";
    document.getElementById("addConfirmPassword").value = "";

    generateAddUserId();
    openModal("addUserModal");
};

// Generate New User ID
function generateAddUserId() {
    const roleid = document.getElementById("addRoleId").value;

    fetch("generateUserId.php?roleid=" + encodeURIComponent(roleid))
        .then(res => res.text())
        .then(id => {
            document.getElementById("addUserId").value = id;
        })
        .catch(err => {
            console.error("Error generating UserID:", err);
            document.getElementById("addUserId").value = "";
        });
}

document.getElementById("addRoleId").addEventListener("change", generateAddUserId);

// Add- Save
document.getElementById("addSaveBtn").onclick = () => {
    const userid = document.getElementById("addUserId").value;
    const username = document.getElementById("addUsername").value;
    const gender = document.getElementById("addGender").value;
    const dob = document.getElementById("addDob").value;
    const contact = document.getElementById("addContact").value;
    const roleid = document.getElementById("addRoleId").value;
    const password = document.getElementById("addPassword").value;
    const confirmPassword = document.getElementById("addConfirmPassword").value;

    if (!userid || !username || !gender || !dob || !contact || !roleid || !password) {
        showToast("Please fill in all fields.", "error");
        return;
    }

    if (password !== confirmPassword) {
        showToast("Passwords do not match.", "error");
        return;
    }

    const formData = new FormData();
    formData.append("userid", userid);
    formData.append("username", username);
    formData.append("gender", gender);
    formData.append("dob", dob);
    formData.append("contact", contact);
    formData.append("roleid", roleid);
    formData.append("password", password);

    fetch("addUser.php", {
        method: "POST",
        body: formData
    })
    .then(res => res.text())
    .then(res => {
        if (res.trim() === "success") {
            closeModal("addUserModal");
            showToast("User added successfully!", "success");
            setTimeout(() => location.reload(), 800);
        } else {
            showToast("Add user failed: " + res, "error");
        }
    })
    .catch(err => {
        console.error(err);
        showToast("Network error, try again.", "error");
    });
};

// Delete
document.getElementById("deleteBtn").onclick = () => {
    if(!selectedUser){
        showToast("Please select a user first.", "error");
        return;
    }

    if(!confirm("Delete this user?")) return;

    fetch("deleteUser.php",{
        method:"POST",
        headers:{"Content-Type":"application/x-www-form-urlencoded"},
        body:"userid=" + encodeURIComponent(selectedUser.UserID)
    }).then(res => res.text())
      .then(() => {
          selectedUser = null;
          location.reload();
      });
};

const modal = document.getElementById("userModal");
const saveBtn = document.getElementById("saveBtn");

// Edit
document.getElementById("editBtn").onclick = () => {
    if (!selectedUser) {
        showToast("Please select a user first.", "error");
        return;
    }

    document.getElementById("userId").value = selectedUser.UserID;
    document.getElementById("name").value = selectedUser.Username;
    document.getElementById("gender").value = selectedUser.Gender;
    document.getElementById("dob").value = selectedUser.DOB;
    document.getElementById("contact").value = selectedUser.Contact;
    document.getElementById("role").value = selectedUser.RoleID;
    document.getElementById("roleHidden").value = selectedUser.RoleID;
    document.getElementById("lastActive").value = selectedUser.LastActive ? selectedUser.LastActive : "-";
    document.getElementById("password").value = "";
    document.getElementById("confirmPassword").value = "";

    openModal("userModal");
};

/*  toggle password */
document.querySelectorAll(".toggle-password").forEach(span=>{
    span.onclick = () => {
        const input = span.previousElementSibling;
        input.type = input.type==="password"?"text":"password";
    };
});

// Edit - Save
saveBtn.onclick = () => {
    const id = document.getElementById("userId").value;
    const username = document.getElementById("name").value;
    const gender = document.getElementById("gender").value;
    const dob = document.getElementById("dob").value;
    const contact = document.getElementById("contact").value;
    const roleid = document.getElementById("roleHidden").value;
    const password = document.getElementById("password").value;
    const confirmPassword = document.getElementById("confirmPassword").value;
    
    if(password !== confirmPassword){ 
        showToast("Passwords do not match", "error"); 
        return; 
    }

    let data = `userid=${id}&username=${username}&gender=${gender}&dob=${dob}&contact=${contact}&roleid=${roleid}`;
    // Passwords Field: null -> Old PW ; NOT null -> New PW
    if(password !== ""){ data += `&password=${password}`; }

    fetch("editUser.php",{
        method:"POST",
        headers:{"Content-Type":"application/x-www-form-urlencoded"},
        body:data
    }).then(res=>res.text())
      .then(res=>{
          if(res==="success"){
              modal.style.display = "none";
            showToast("User info updated successfully!", "success");
            setTimeout(() => location.reload(), 800);
          } else {
              showToast("Update failed: " + res, "error");
          }
      });
};

function closeModal(modalId) {
    document.getElementById(modalId).style.display = "none";
}

function openModal(modalId) {
    document.getElementById(modalId).style.display = "flex";
}

function showToast(message, type="info"){
    let toast = document.getElementById("toast");
    toast.innerText = message;
    toast.className = `toast show ${type}`;
    setTimeout(()=>toast.className="toast", 3000);
}

function logout() {
    const confirmAction = confirm("Are you sure you want to logout?");
    
    if (confirmAction) {
        window.location.href = "logout.php";
    }
}
</script>
<script src="hamburger.js"></script>
</body>
</html>