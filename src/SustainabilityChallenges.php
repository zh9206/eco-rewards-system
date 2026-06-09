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

// Get All Challenges
$query = "SELECT * FROM challenge ORDER BY ChallengeID ASC";
$result = $dbConn->query($query);
$challenges = [];
if($result){
    while($row = $result->fetch_assoc()){
        $challenges[] = $row;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Sustainability Challenges - <?php echo $user['Username']?></title>
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

    <div class="sidebar" id="sidebar">
        <div class="sidebar-top">
            <div class="logo">
                <img src="logo.png" alt="Logo">
            </div>

            <div class="nav-links">
                <a href="admin.php">Manage Users</a>
                <a href="SustainabilityChallenges.php" class="active">Sustainability Challenges</a>
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

            <h1>Challenges List</h1>

            <div class="top-bar">
                <!-- Search Bar -->
                <div class="search-box">
                    <span class="search-icon">🔍</span>
                    <input type="text" id="searchInput" placeholder="Search challenge...">
                </div>

                <div class="stats" id="stats"></div>
            </div>

            <table class="table">
                <thead>
                    <tr>
                        <th>Challenge ID</th>
                        <th class="sortable" data-key="title">Title</th>
                        <th>Description</th>
                        <th class="sortable" data-key="points">Points</th>
                    </tr>
                </thead>
                <tbody id="challengeTable">
                    <?php foreach($challenges as $c): ?>
                        <tr onclick='selectChallenge(<?php echo json_encode($c, JSON_HEX_APOS | JSON_HEX_QUOT); ?>, this)'>
                            <td data-label="Challenge ID"><?= htmlspecialchars($c['ChallengeID']) ?></td>
                            <td data-label="Title"><?= htmlspecialchars($c['Title']) ?></td>
                            <td data-label="Description"><?= htmlspecialchars($c['Description']) ?></td>
                            <td data-label="Points"><?= htmlspecialchars($c['Points']) ?></td>
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

<!-- Add Modal -->
<div id="addChallengeModal" class="modal">
    <div class="modal-content">
        <span class="close-btn" onclick="closeModal('addChallengeModal')">✖</span>
        <h2>Add Challenge</h2>

        <div class="form-group">
            <label>Challenge ID</label>
            <input type="text" id="addChallengeId" readonly>
        </div>

        <div class="form-group">
            <label>Title</label>
            <input type="text" id="addTitle" required>
        </div>

        <div class="form-group">
            <label>Description</label>
            <textarea id="addDescription" rows="3" required></textarea>
        </div>

        <div class="form-group">
            <label>Points</label>
            <input type="number" id="addPoints" min="0" required>
        </div>

        <button class="btn" id="addSaveBtn">SAVE</button>
    </div>
</div>

<!-- Edit Modal -->
<div id="challengeModal" class="modal">
    <div class="modal-content">
        <span class="close-btn" onclick="closeModal('challengeModal')">✖</span>
        <h2>Edit Challenge</h2>

        <div class="form-group">
            <label>Challenge ID</label>
            <input type="text" id="challengeId" readonly>
        </div>

        <div class="form-group">
            <label>Title</label>
            <input type="text" id="title" required>
        </div>

        <div class="form-group">
            <label>Description</label>
            <textarea id="description" rows="3" required></textarea>
        </div>

        <div class="form-group">
            <label>Points</label>
            <input type="number" id="points" min="0" required>
        </div>

        <button type="button" class="btn" id="editSaveBtn">SAVE</button>
    </div>
</div>

<!-- Toast -->
<div id="toast" class="toast"></div>

<script>
// Modal
function openModal(modalId) {
    document.getElementById(modalId).style.display = "flex";
}

function closeModal(modalId) {
    document.getElementById(modalId).style.display = "none";
}

// ===== Variables =====
const modal = document.getElementById("challengeModal");
const modalBox = modal.querySelector(".modal-content");
const closeBtn = modal.querySelector(".close-btn");
const editBtn = document.getElementById("editBtn");
const deleteBtn = document.getElementById("deleteBtn");
const tableBody = document.querySelector("tbody");
const searchInput = document.getElementById("searchInput");
const statsBox = document.getElementById("stats");

let sortOrder = {title:'asc', points:'asc'};

// Select Row
let selectedChallenge = null;

function selectChallenge(challenge, row) {
    document.querySelectorAll("#challengeTable tr").forEach(r => r.classList.remove("selected"));
    row.classList.add("selected");
    selectedChallenge = challenge;
}

// Edit
document.getElementById("editBtn").onclick = () => {
    if (!selectedChallenge) {
        showToast("Please select a challenge first.", "error");
        return;
    }

    document.getElementById("challengeId").value = selectedChallenge.ChallengeID;
    document.getElementById("title").value = selectedChallenge.Title;
    document.getElementById("description").value = selectedChallenge.Description;
    document.getElementById("points").value = selectedChallenge.Points;

    openModal("challengeModal");
};

// Edit - Save
document.getElementById("editSaveBtn").onclick = () => {
    const challengeId = document.getElementById("challengeId").value;
    const title = document.getElementById("title").value;
    const description = document.getElementById("description").value;
    const points = document.getElementById("points").value;

    if (!challengeId || !title || !description || points === "") {
        showToast("Please fill in all fields.", "error");
        return;
    }

    const formData = new FormData();
    formData.append("ChallengeID", challengeId);
    formData.append("Title", title);
    formData.append("Description", description);
    formData.append("Points", points);

    fetch("editChallenge.php", {
        method: "POST",
        body: formData
    })
    .then(res => res.text())
    .then(res => {
        if (res.trim() === "success") {
            closeModal("challengeModal");
            showToast("Challenge updated successfully!", "success");
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
    document.getElementById("addDescription").value = "";
    document.getElementById("addPoints").value = "";

    generateChallengeId();
    openModal("addChallengeModal");
};

// Generate New Challenge ID
function generateChallengeId() {
    fetch("generateChallengeId.php")
        .then(response => response.text())
        .then(id => {
            document.getElementById("addChallengeId").value = id;
        })
        .catch(error => {
            console.error("Error generating Challenge ID:", error);
        });
}

// Add - Save
document.getElementById("addSaveBtn").onclick = () => {
    const challengeId = document.getElementById("addChallengeId").value;
    const title = document.getElementById("addTitle").value;
    const description = document.getElementById("addDescription").value;
    const points = document.getElementById("addPoints").value;

    if (!challengeId || !title || !description || points === "") {
        showToast("Please fill in all fields.", "error");
        return;
    }

    const formData = new FormData();
    formData.append("ChallengeID", challengeId);
    formData.append("Title", title);
    formData.append("Description", description);
    formData.append("Points", points);

    fetch("addChallenge.php", {
        method: "POST",
        body: formData
    })
    .then(res => res.text())
    .then(res => {
        if (res.trim() === "success") {
            closeModal("addChallengeModal");
            showToast("Challenge added successfully!", "success");
            setTimeout(() => location.reload(), 800);
        } else {
            showToast("Add challenge failed: " + res, "error");
        }
    })
    .catch(err => {
        console.error(err);
        showToast("Network error, try again.", "error");
    });
};

// Delete
document.getElementById("deleteBtn").onclick = () => {
    if(!selectedChallenge){
        showToast("Please select a challenge first.", "error");
        return;
    }

    if(!confirm("Delete this challenge?")) return;

    fetch("deleteChallenge.php",{
        method:"POST",
        headers:{"Content-Type":"application/x-www-form-urlencoded"},
        body:"challengeid=" + encodeURIComponent(selectedChallenge.ChallengeID)
    }).then(res => res.text())
      .then(() => {
          selectedChallenge = null;
          location.reload();
      });
};

// Search
searchInput.addEventListener("input", function(){
    const q = this.value.toLowerCase();
    tableBody.querySelectorAll("tr").forEach(row=>{
        const title = row.cells[1].innerText.toLowerCase();
        const desc = row.cells[2].innerText.toLowerCase();
        row.style.display = (title.includes(q)||desc.includes(q)) ? "" : "none";
    });
    updateStats();
});

// Sorting
document.querySelectorAll("th.sortable").forEach(th=>{
    th.onclick = ()=>{
        const key = th.dataset.key;
        const rows = Array.from(tableBody.querySelectorAll("tr"));

        rows.sort((a,b)=>{
            let A, B;

            if (key === 'title') {
                A = a.cells[1].innerText.toLowerCase();
                B = b.cells[1].innerText.toLowerCase();
            } else if (key === 'points') {
                A = Number(a.cells[3].innerText);
                B = Number(b.cells[3].innerText);
            }

            if (A < B) return sortOrder[key] === 'asc' ? -1 : 1;
            if (A > B) return sortOrder[key] === 'asc' ? 1 : -1;
            return 0;
        });

        rows.forEach(r => tableBody.appendChild(r));
        sortOrder[key] = sortOrder[key] === 'asc' ? 'desc' : 'asc';
    };
});

// Status Update
function updateStats(){
    const rows = Array.from(tableBody.querySelectorAll("tr")).filter(r => r.style.display !== "none");
    statsBox.innerText = `Total: ${rows.length}`;
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