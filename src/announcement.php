<?php
session_start();

// To prevent error
if (!isset($_SESSION['UserID'])) {
    header("Location: index.html");
    exit();
}

include("conn.php");

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

// Auto Update Announcement Status
$today = date('Y-m-d');
$stmtStatus = $dbConn->prepare("
    UPDATE announcement
    SET Status = CASE
        WHEN StartDate > ? THEN 'Scheduled'
        WHEN StartDate <= ? AND EndDate >= ? THEN 'Active'
        WHEN EndDate < ? THEN 'Expired'
    END
");
$stmtStatus->bind_param("ssss", $today, $today, $today, $today);
$stmtStatus->execute();
$stmtStatus->close();

// Get All Announcements
$sql = "SELECT * FROM announcement ORDER BY StartDate DESC, EndDate DESC";
$result = $dbConn->query($sql);
$announcements = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];

// Get All Admins
$admins = [];
$adminResult = $dbConn->query("SELECT UserID, Username FROM user WHERE RoleID='R03'");
if ($adminResult) {
    while ($row = $adminResult->fetch_assoc()) {
        $admins[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Announcement - <?php echo htmlspecialchars($user['Username']); ?></title>
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

.action-panel{
    width:180px;
    position:sticky;
    top:20px;
    display:flex;
    flex-direction:column;
    gap:14px;
    align-self:flex-start;
}

.action-btn{
    width:100%;
    padding:12px 16px;
    border:none;
    border-radius:14px;
    font-size:14px;
    font-weight:600;
    cursor:pointer;
    box-shadow:0 4px 12px rgba(0,0,0,0.08);
    transition:0.25s ease;
}

.action-btn:hover{
    transform:translateY(-2px);
}

.action-panel .btn:first-child{
    background:#A2CB8B;
    color:#fff;
}

#editBtn{
    background:#84B179;
    color:#fff;
}

.delete-btn{
    background:#dc3545;
    color:#fff;
}


.table tbody tr.selected{
    background:rgba(132,177,121,0.22);
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
                <a href="admin.php">Manage Users</a>
                <a href="SustainabilityChallenges.php">Sustainability Challenges</a>
                <a href="viewReport.php">View Report & Statistics</a>
                <a href="rewardsSystem.php">Rewards System</a>
                <a href="announcement.php" class="active">Announcement</a>
            </div>
        </div>

        <div class="sidebar-bottom">
            <div class="profile" onclick="openProfileModal()">
                <img src="profile-pic.png" alt="Profile">
                <span><?php echo htmlspecialchars($user['Username']); ?></span>
            </div>
            <button class="btn" onclick="logout()">Logout</button>
        </div>
    </div>

    <div class="main">
        <div class="section">

            <h1>Announcements List</h1>

            <div class="top-bar">
                <div class="search-box">
                    <span class="search-icon">🔍</span>
                    <input type="text" id="searchInput" placeholder="Search announcement...">
                </div>

                <div class="stats">
                    Total: <b id="totalAnnouncements"><?php echo count($announcements); ?></b>
                </div>
            </div>

            <table class="table">
                <thead>
                    <tr>
                        <th>Announcement ID</th>
                        <th>Title</th>
                        <th>Description</th>
                        <th>Start Date</th>
                        <th>End Date</th>
                        <th>Admin ID</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody id="announcementTable">
                    <?php foreach ($announcements as $a): ?>
                        <?php $statusClass = 'status-' . strtolower($a['Status']); ?>
                        <tr onclick='selectAnnouncement(<?php echo json_encode($a, JSON_HEX_APOS | JSON_HEX_QUOT); ?>, this)'>
                            <td data-label="Announcement ID"><?php echo htmlspecialchars($a['AnnouncementID']); ?></td>
                            <td data-label="Title"><?php echo htmlspecialchars($a['Title']); ?></td>
                            <td data-label="Description"><?php echo htmlspecialchars($a['Description']); ?></td>
                            <td data-label="Start Date"><?php echo htmlspecialchars($a['StartDate']); ?></td>
                            <td data-label="End Date"><?php echo htmlspecialchars($a['EndDate']); ?></td>
                            <td data-label="Admin ID"><?php echo htmlspecialchars($a['AdminID']); ?></td>
                            <td data-label="Status">
                                <span class="status-pill <?php echo $statusClass; ?>">
                                    <?php echo htmlspecialchars($a['Status']); ?>
                                </span>
                            </td>
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
<div id="addAnnouncementModal" class="modal">
    <div class="modal-content">
        <span class="close-btn" onclick="closeModal('addAnnouncementModal')">✖</span>
        <h2>Add Announcement</h2>

        <div class="form-group">
            <label>Announcement ID</label>
            <input type="text" id="addAnnouncementId" readonly>
        </div>

        <div class="form-group">
            <label>Title</label>
            <input type="text" id="addTitle" required>
        </div>

        <div class="form-group">
            <label>Description</label>
            <textarea id="addDescription" maxlength="300" required></textarea>
        </div>

        <div class="form-group">
            <label>Start Date</label>
            <input type="date" id="addStartDate" required>
        </div>

        <div class="form-group">
            <label>End Date</label>
            <input type="date" id="addEndDate" required>
        </div>

        <div class="form-group">
            <label>Admin</label>
            <select id="addAdminId" required>
                <option value="" disabled selected>Select Admin</option>
                <?php foreach ($admins as $admin): ?>
                    <option value="<?php echo htmlspecialchars($admin['UserID']); ?>">
                        <?php echo htmlspecialchars($admin['Username']) . " (" . htmlspecialchars($admin['UserID']) . ")"; ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <button type="button" class="btn" id="addSaveBtn">SAVE</button>
    </div>
</div>

<!-- Edit Modal -->
<div id="announcementModal" class="modal">
    <div class="modal-content">
        <span class="close-btn" onclick="closeModal('announcementModal')">✖</span>
        <h2>Edit Announcement</h2>

        <div class="form-group">
            <label>Announcement ID</label>
            <input type="text" id="announcementId" readonly>
        </div>

        <div class="form-group">
            <label>Title</label>
            <input type="text" id="title" required>
        </div>

        <div class="form-group">
            <label>Description</label>
            <textarea id="description" maxlength="300" required></textarea>
        </div>

        <div class="form-group">
            <label>Start Date</label>
            <input type="date" id="startDate" required>
        </div>

        <div class="form-group">
            <label>End Date</label>
            <input type="date" id="endDate" required>
        </div>

        <div class="form-group">
            <label>Admin</label>
            <select id="adminId" required>
                <option value="">-- Select Admin --</option>
                <?php foreach ($admins as $admin): ?>
                    <option value="<?php echo htmlspecialchars($admin['UserID']); ?>">
                        <?php echo htmlspecialchars($admin['Username']) . " (" . htmlspecialchars($admin['UserID']) . ")"; ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <button type="button" class="btn" id="editSaveBtn">SAVE</button>
    </div>
</div>

<div id="toast" class="toast"></div>

<script>
function openModal(modalId){
    document.getElementById(modalId).style.display = "flex";
}

function closeModal(modalId){
    document.getElementById(modalId).style.display = "none";
}

function showToast(message, type="info"){
    let toast = document.getElementById("toast");
    toast.innerText = message;
    toast.className = `toast show ${type}`;
    setTimeout(()=>toast.className="toast", 3000);
}

// Select Announcement
const tableBody = document.getElementById("announcementTable");
const searchInput = document.getElementById("searchInput");
const totalAnnouncements = document.getElementById("totalAnnouncements");
const editBtn = document.getElementById("editBtn");
const deleteBtn = document.getElementById("deleteBtn");

let selectedAnnouncement = null;
let selectedRow = null;

function selectAnnouncement(announcement, row){
    document.querySelectorAll("#announcementTable tr").forEach(r => r.classList.remove("selected"));
    row.classList.add("selected");
    selectedAnnouncement = announcement;
    selectedRow = row;
}

// Add
document.getElementById("addBtn").onclick = () => {
    document.getElementById("addAnnouncementId").value = "";
    document.getElementById("addTitle").value = "";
    document.getElementById("addDescription").value = "";
    document.getElementById("addStartDate").value = "";
    document.getElementById("addEndDate").value = "";
    document.getElementById("addAdminId").value = "";

    fetch("generateAnnouncementId.php")
        .then(res => res.text())
        .then(id => {
            document.getElementById("addAnnouncementId").value = id.trim();
            openModal("addAnnouncementModal");
        })
        .catch(() => {
            showToast("Failed to generate Announcement ID.", "error");
        });
};

// Edit
editBtn.onclick = () => {
    if (!selectedAnnouncement) {
        showToast("Please select an announcement first.", "error");
        return;
    }

    document.getElementById("announcementId").value = selectedAnnouncement.AnnouncementID;
    document.getElementById("title").value = selectedAnnouncement.Title;
    document.getElementById("description").value = selectedAnnouncement.Description;
    document.getElementById("startDate").value = selectedAnnouncement.StartDate;
    document.getElementById("endDate").value = selectedAnnouncement.EndDate;
    document.getElementById("adminId").value = selectedAnnouncement.AdminID;

    openModal("announcementModal");
};

// Add-Save
document.getElementById("addSaveBtn").onclick = () => {
    const announcementId = document.getElementById("addAnnouncementId").value.trim();
    const title = document.getElementById("addTitle").value.trim();
    const description = document.getElementById("addDescription").value.trim();
    const startDate = document.getElementById("addStartDate").value;
    const endDate = document.getElementById("addEndDate").value;
    const adminId = document.getElementById("addAdminId").value;

    if (!announcementId || !title || !description || !startDate || !endDate || !adminId) {
        showToast("Please fill in all fields.", "error");
        return;
    }

    if (endDate < startDate) {
        showToast("End date cannot be earlier than start date.", "error");
        return;
    }

    const formData = new FormData();
    formData.append("AnnouncementID", announcementId);
    formData.append("Title", title);
    formData.append("Description", description);
    formData.append("StartDate", startDate);
    formData.append("EndDate", endDate);
    formData.append("AdminID", adminId);

    fetch("addAnnouncement.php", {
        method: "POST",
        body: formData
    })
    .then(res => res.text())
    .then(res => {
        if (res.trim() === "success") {
            closeModal("addAnnouncementModal");
            showToast("Announcement added successfully!", "success");
            setTimeout(() => location.reload(), 700);
        } else {
            showToast("Add failed: " + res, "error");
        }
    })
    .catch(() => {
        showToast("Network error, try again.", "error");
    });
};

// Edit-Save
document.getElementById("editSaveBtn").onclick = () => {
    const announcementId = document.getElementById("announcementId").value.trim();
    const title = document.getElementById("title").value.trim();
    const description = document.getElementById("description").value.trim();
    const startDate = document.getElementById("startDate").value;
    const endDate = document.getElementById("endDate").value;
    const adminId = document.getElementById("adminId").value;

    if (!announcementId || !title || !description || !startDate || !endDate || !adminId) {
        showToast("Please fill in all fields.", "error");
        return;
    }

    if (endDate < startDate) {
        showToast("End date cannot be earlier than start date.", "error");
        return;
    }

    const formData = new FormData();
    formData.append("AnnouncementID", announcementId);
    formData.append("Title", title);
    formData.append("Description", description);
    formData.append("StartDate", startDate);
    formData.append("EndDate", endDate);
    formData.append("AdminID", adminId);

    fetch("editAnnouncement.php", {
        method: "POST",
        body: formData
    })
    .then(res => res.text())
    .then(res => {
        if (res.trim() === "success") {
            closeModal("announcementModal");
            showToast("Announcement updated successfully!", "success");
            setTimeout(() => location.reload(), 700);
        } else {
            showToast("Update failed: " + res, "error");
        }
    })
    .catch(() => {
        showToast("Network error, try again.", "error");
    });
};

// Delete
deleteBtn.onclick = () => {
    if (!selectedAnnouncement) {
        showToast("Please select an announcement first.", "error");
        return;
    }

    if (!confirm("Delete this announcement?")) return;

    fetch("deleteAnnouncement.php", {
        method: "POST",
        headers: {
            "Content-Type": "application/x-www-form-urlencoded"
        },
        body: "announcementid=" + encodeURIComponent(selectedAnnouncement.AnnouncementID)
    })
    .then(res => res.text())
    .then(res => {
        if (res.trim() === "success") {
            showToast("Announcement deleted successfully!", "success");
            setTimeout(() => location.reload(), 600);
        } else {
            showToast("Delete failed: " + res, "error");
        }
    })
    .catch(() => {
        showToast("Network error, try again.", "error");
    });
};

// Search
searchInput.addEventListener("input", function(){
    const q = this.value.toLowerCase();

    tableBody.querySelectorAll("tr").forEach(row => {
        const text = row.innerText.toLowerCase();
        row.style.display = text.includes(q) ? "" : "none";
    });

    updateStats();
});

function updateStats(){
    const rows = Array.from(tableBody.querySelectorAll("tr")).filter(r => r.style.display !== "none");
    totalAnnouncements.innerText = rows.length;
}
updateStats();

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