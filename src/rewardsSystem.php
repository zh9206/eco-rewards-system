<?php
session_start();


if (!isset($_SESSION['UserID'])) {
    header("Location: index.html");
    exit();
}

include ("conn.php");

$userID = $_SESSION['UserID'];

// Get User's Profile
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

// Get All Rewards Details
$result = $dbConn->query("SELECT * FROM reward ORDER BY RewardID ASC");
$rewards = [];
if($result){
    while($row = $result->fetch_assoc()){
        $rewards[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Rewards System - <?php echo $user['Username']?></title>
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
</style>
</head>
<body>
 
    <!-- Hamburger Button - Small Screen Only!! -->
    <?php include("hamburger.php")?>

    <!-- ===== SIDEBAR ===== -->
    <div class="sidebar" id="sidebar">
        <div class="sidebar-top">
            <div class="logo">
                <img src="logo.png" alt="Logo">
            </div>

            <div class="nav-links">
                <a href="admin.php">Manage Users</a>
                <a href="SustainabilityChallenges.php">Sustainability Challenges</a>
                <a href="viewReport.php">View Report & Statistics</a>
                <a href="rewardsSystem.php" class="active">Rewards System</a>
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

    <!-- main content -->
    <div class="main">

        <!-- Table -->
        <div class="section">

            <h1>Rewards List</h1>

            <div class="top-bar">
                <!-- Search Bar -->
                <div class="search-box">
                    <span class="search-icon">🔍</span>
                    <input type="text" id="searchInput" placeholder="Search reward...">
                </div>

                <div class="stats">Total: <b id="totalRewards"><?php echo $result->num_rows; ?></b></div>
            </div>

            <table class="table">
                <thead>
                    <tr>
                        <th>Reward ID</th>
                        <th>Title</th>
                        <th>Type</th>
                        <th>Validity (days)</th>
                        <th>Points</th>
                    </tr>
                </thead>
                <tbody id="rewardTable">
                    <?php foreach ($rewards as $r): ?>
                        <tr onclick='selectReward(<?php echo json_encode($r, JSON_HEX_APOS | JSON_HEX_QUOT); ?>, this)'>
                            <td><?php echo htmlspecialchars($r['RewardID']); ?></td>
                            <td><?php echo htmlspecialchars($r['Title']); ?></td>
                            <td><?php echo htmlspecialchars($r['Type']); ?></td>
                            <td><?php echo htmlspecialchars($r['Validity']); ?></td>
                            <td><?php echo htmlspecialchars($r['Points']); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <!-- buttons -->
        <div class="section">
            <div class="action-panel">
                <button class="btn action-btn" id="addBtn">Add</button>
                <button class="btn action-btn" id="editBtn">Edit</button>
                <button class="btn action-btn delete-btn" id="deleteBtn">Delete</button>
            </div>
        </div>
    </div>

<!-- Add Modal -->
<div id="addRewardModal" class="modal">
    <div class="modal-content">
        <span class="close-btn" onclick="closeModal('addRewardModal')">✖</span>
        <h2>Add Reward</h2>

        <div class="form-group">
            <label>Reward ID</label>
            <input type="text" id="addRewardId" readonly>
        </div>

        <div class="form-group">
            <label>Title</label>
            <input type="text" id="addTitle" required>
        </div>

        <div class="form-group">
            <label>Type</label>
            <select id="addType">
                <option value="Voucher">Voucher</option>
                <option value="Merchandise">Merchandise</option>
            </select>
        </div>

        <div class="form-group" id="addValidityGroup">
            <label id="addValidityLabel">Validity (days)</label>
            <input type="number" id="addValidity" min="0">
        </div>

        <div class="form-group">
            <label>Points</label>
            <input type="number" id="addPoints" min="1" required>
        </div>

        <button type="button" class="btn" id="addSaveBtn">SAVE</button>
    </div>
</div>

<!-- Edit Modal -->
<div id="rewardModal" class="modal">
    <div class="modal-content">
        <span class="close-btn" onclick="closeModal('rewardModal')">✖</span>
        <h2>Edit Reward</h2>

        <div class="form-group">
            <label>Reward ID</label>
            <input type="text" id="rewardId" readonly>
        </div>

        <div class="form-group">
            <label>Title</label>
            <input type="text" id="title" required>
        </div>

        <div class="form-group">
            <label>Type</label>
            <select id="type">
                <option value="Voucher">Voucher</option>
                <option value="Merchandise">Merchandise</option>
            </select>
        </div>

        <div class="form-group" id="validityGroup">
            <label id="validityLabel">Validity (days)</label>
            <input type="number" id="validity" min="0">
        </div>

        <div class="form-group">
            <label>Points</label>
            <input type="number" id="points" min="1" required>
        </div>

        <button type="button" class="btn" id="editSaveBtn">SAVE</button>
    </div>
</div>

<!-- Toast -->
<div id="toast" class="toast"></div>

<script>
// Modal
function closeModal(modalId) {
    document.getElementById(modalId).style.display = "none";
}

function openModal(modalId) {
    document.getElementById(modalId).style.display = "flex";
}

// ===== Variables =====
const modal = document.getElementById("rewardModal");
const modalBox = modal.querySelector(".modal-content");
const closeBtn = modal.querySelector(".close");
const cancelBtn = document.getElementById("modalCancel");
const tableBody = document.getElementById("rewardTable");
const editBtn = document.getElementById("editBtn");
const deleteBtn = document.getElementById("deleteBtn");
const searchInput = document.getElementById("searchInput");
const toast = document.getElementById("toast");

// Type Select - Merchandise -> Disable Validity
const typeSelect = document.getElementById("type");
const validityInput = document.getElementById("validity");
const validityLabel = document.getElementById("validityLabel");

typeSelect.addEventListener("change", () => {
    if (typeSelect.value === "Merchandise") {
        validityInput.value = 0;
        document.getElementById("validityGroup").style.display = "none";
    } else {
        document.getElementById("validityGroup").style.display = "block";
    }
});

// Select Row
let selectedReward = null;
function selectReward(reward, row) {
    document.querySelectorAll("#rewardTable tr").forEach(r => r.classList.remove("selected"));
    row.classList.add("selected");
    selectedReward = reward;
}

// Edit
document.getElementById("editBtn").onclick = () => {
    if (!selectedReward) {
        showToast("Please select a reward first.", "error");
        return;
    }

    document.getElementById("rewardId").value = selectedReward.RewardID;
    document.getElementById("title").value = selectedReward.Title;
    document.getElementById("type").value = selectedReward.Type;
    document.getElementById("validity").value = selectedReward.Validity;
    document.getElementById("points").value = selectedReward.Points;

    openModal("rewardModal");
};

// Edit - Save
document.getElementById("editSaveBtn").onclick = () => {
    const rewardId = document.getElementById("rewardId").value;
    const title = document.getElementById("title").value.trim();
    const type = document.getElementById("type").value;
    const validity = document.getElementById("validity").value;
    const points = document.getElementById("points").value;

    if (!rewardId || !title || !type || points === "") {
        showToast("Please fill in all fields.", "error");
        return;
    }

    const finalValidity = (type === "Merchandise") ? 0 : validity;

    if (type !== "Merchandise" && finalValidity === "") {
        showToast("Please enter validity.", "error");
        return;
    }

    const formData = new FormData();
    formData.append("RewardID", rewardId);
    formData.append("Title", title);
    formData.append("Type", type);
    formData.append("Validity", finalValidity);
    formData.append("Points", points);

    fetch("editReward.php", {
        method: "POST",
        body: formData
    })
    .then(res => res.text())
    .then(res => {
        if (res.trim() === "success") {
            closeModal("rewardModal");
            showToast("Reward updated successfully!", "success");
            setTimeout(() => location.reload(), 800);
        } else {
            showToast("Update failed: " + res, "error");
        }
    })
    .catch(err => {
        console.error(err);
        showToast("Network error, try again.", "error");
    });
};

// Add
document.getElementById("addBtn").onclick = () => {
    document.getElementById("addTitle").value = "";
    document.getElementById("addType").value = "";
    document.getElementById("addValidity").value = "";
    document.getElementById("addPoints").value = "";

    generateRewardId();
    openModal("addRewardModal");
};

// Generate New Reward ID
function generateRewardId() {
    fetch("generateRewardId.php")
        .then(response => response.text())
        .then(id => {
            document.getElementById("addRewardId").value = id;
        })
        .catch(error => {
            console.error("Error generating Reward ID:", error);
        });
}

// Add - Save
document.getElementById("addSaveBtn").onclick = () => {
    const rewardId = document.getElementById("addRewardId").value.trim();
    const title = document.getElementById("addTitle").value.trim();
    const type = document.getElementById("addType").value;
    const validity = document.getElementById("addValidity").value;
    const points = document.getElementById("addPoints").value;

    if (!rewardId || !title || !type || points === "") {
        showToast("Please fill in all fields.", "error");
        return;
    }

    const finalValidity = (type === "Merchandise") ? 0 : validity;

    if (type !== "Merchandise" && finalValidity === "") {
        showToast("Please enter validity.", "error");
        return;
    }

    const formData = new FormData();
    formData.append("rewardId", rewardId);
    formData.append("title", title);
    formData.append("type", type);
    formData.append("validity", finalValidity);
    formData.append("points", points);

    fetch("addReward.php", {
        method: "POST",
        body: formData
    })
    .then(res => res.text())
    .then(res => {
        if (res.trim() === "success") {
            closeModal("addRewardModal");
            showToast("Reward added successfully!", "success");
            setTimeout(() => location.reload(), 800);
        } else {
            showToast("Add reward failed", "error");
        }
    })
    .catch(err => {
        console.error(err);
        showToast("Network error, try again.", "error");
    });
};

// ===== Delete =====
document.getElementById("deleteBtn").onclick = () => {
    if (!selectedReward) {
        showToast("Please select a reward first.", "error");
        return;
    }

    if (!confirm("Delete this reward?")) return;

    fetch("deleteReward.php", {
        method: "POST",
        headers: {"Content-Type": "application/x-www-form-urlencoded"},
        body: "rewardid=" + encodeURIComponent(selectedReward.RewardID)
    })
    .then(res => res.text())
    .then(res => {
        if (res.trim() === "success") {
            showToast("Reward deleted successfully!", "success");
            setTimeout(() => location.reload(), 500);
        } else {
            showToast("Delete failed: " + res, "error");
        }
    })
    .catch(err => {
        console.error(err);
        showToast("Network error, try again.", "error");
    });
};

// Search
searchInput.addEventListener("input", function(){
    const q = this.value.toLowerCase();
    tableBody.querySelectorAll("tr").forEach(row=>{
        const title = row.cells[1].innerText.toLowerCase();
        const desc = row.cells[2].innerText.toLowerCase();
        const cat = row.cells[3].innerText.toLowerCase();
        row.style.display = (title.includes(q)||desc.includes(q)||cat.includes(q)) ? "" : "none";
    });
    updateStats();
});

// Status Update
function updateStats(){
    const rows = Array.from(tableBody.querySelectorAll("tr")).filter(r=>r.style.display!=="none");
    let totalPoints = 0;
    rows.forEach(r=>totalPoints+=Number(r.cells[4].innerText));
    document.getElementById("totalRewards").innerText = rows.length;
}
updateStats();

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