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


// Get All Submission History
$sqlHistory = "SELECT cs.SubmissionID, cs.Submission_Date, cs.Status, cs.Proof,
                      c.ChallengeID, c.Title AS ChallengeTitle, c.Points,
                      v.Review
               FROM challenge_submission cs
               JOIN challenge c ON cs.ChallengeID = c.ChallengeID
               LEFT JOIN verification v ON cs.SubmissionID = v.SubmissionID
               WHERE cs.StudentID = ?
               ORDER BY cs.Submission_Date DESC";

$stmtHistory = $dbConn->prepare($sqlHistory);
$stmtHistory->bind_param("s", $userID);
$stmtHistory->execute();
$resultHistory = $stmtHistory->get_result();

$submissions = [];
while ($row = $resultHistory->fetch_assoc()) {
    $submissions[] = $row;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<link rel="stylesheet" href="styles.css">
<link rel="stylesheet" href="responsive.css">
<title>Log Action - <?php echo $user['Username']?></title>
<style>

.history-table {
    width: 100%;
    border-collapse: collapse;
    overflow: hidden;
    border-radius: 14px;
    table-layout: fixed;
}

.history-table th,
.history-table td {
    padding: 14px;
    text-align: center;
    border-bottom: 1px solid #e5e5e5;
    word-wrap: break-word;
}

.history-table th {
    background: #84B179;
    color: white;
}

.history-table tr {
    background: white;
    cursor: pointer;
    transition: 0.2s;
}

.history-table tr:hover {
    background: #f3fff3;
}

/* detail modal */
.detail-modal {
    display: none;
    position: fixed;
    inset: 0;
    background: rgba(0,0,0,0.45);
    justify-content: center;
    align-items: center;
    z-index: 999;
}

.detail-content {
    background: white;
    width: 520px;
    max-width: 92%;
    border-radius: 18px;
    padding: 25px;
    box-shadow: 0 10px 30px rgba(0,0,0,0.2);
    position: relative;
}

.detail-content h2 {
    margin-bottom: 18px;
    color: #2e7d32;
}

.detail-row {
    margin-bottom: 12px;
}

.detail-label {
    font-weight: bold;
    color: #333;
}

.proof-img {
    width: 100%;
    max-height: 250px;
    object-fit: contain;
    border-radius: 10px;
    margin-top: 10px;
}

.proof-video {
    width: 100%;
    max-height: 250px;
    border-radius: 10px;
    margin-top: 10px;
}

.reason-box {
    margin-top: 10px;
    padding: 12px;
    border-radius: 10px;
    background: #fff5f5;
    border: 1px solid #f0c2c7;
    color: #721c24;
}

.file-link {
    color: #2e7d32;
    font-weight: 600;
    text-decoration: none;
}

.file-link:hover {
    text-decoration: underline;
}

.empty-text {
    text-align: center;
    color: #666;
    padding: 20px 0;
}
</style>
</head>
<body>
    
    <!-- Hamburger Button - Small Screen Only!! -->
    <?php include("hamburger.php")?>

    <div class="sidebar" id="sidebar">
        
        <div class="sidebar-top">
            <div class="logo">
                <a href="studentDashboard.php">
                    <img src="logo.png" alt="Logo" >
                </a>    
            </div>

            <div class="nav-links">
                <a href="logAction.php" class="active">Log Action</a>
                <a href="leaderboard.php">Leaderboard</a>
                <a href="rewards.php">Rewards Shop</a>
            </div>
        </div>

        <div class="sidebar-bottom">
            <div class="profile" onclick="openProfileModal()">
                <img src="profile-pic.png" alt="Profile Pic">
                <span><?php echo $user['Username']; ?></span>
            </div>

            <button class="btn" onclick="logout()">Logout</button>
        </div>
    </div>
    
    <div class="main">
        <div class="section">
            <h1>Submission History</h1>

            <div class="filter-container">
                <select id="statusFilter" class="filter-dropdown" onchange="filterSubmissions(this.value)">
                    <option value="all">All</option>
                    <option value="pending">Pending</option>
                    <option value="approved">Approved</option>
                    <option value="rejected">Rejected</option>
                </select>
            </div>

            <?php if (count($submissions) > 0): ?>
                <table class="history-table">
                    <thead>
                        <tr>
                            <th>Submission ID</th>
                            <th>Challenge</th>
                            <th>Points</th>
                            <th>Date</th>
                            <th>Status</th>
                        </tr>
                    </thead>    
                    <tbody>
                        <?php foreach ($submissions as $submission): ?>
                            <?php
                                $statusClass = "status-pending";
                                if (strtolower($submission['Status']) === "approved") $statusClass = "status-approved";
                                if (strtolower($submission['Status']) === "rejected") $statusClass = "status-rejected";
                            ?>
                            <tr data-status="<?php echo strtolower($submission['Status']); ?>"
                                onclick='openDetailModal(<?php echo json_encode($submission, JSON_HEX_APOS | JSON_HEX_QUOT); ?>)'>
                                <td data-label="Submission ID"><?php echo htmlspecialchars($submission['SubmissionID']); ?></td>
                                <td data-label="Challenge"><?php echo htmlspecialchars($submission['ChallengeTitle']); ?></td>
                                <td data-label="Points"><?php echo htmlspecialchars($submission['Points']); ?></td>
                                <td data-label="Date"><?php echo htmlspecialchars($submission['Submission_Date']); ?></td>
                                <td data-label="Status">
                                    <span class="status-badge <?php echo $statusClass; ?>">
                                        <?php echo htmlspecialchars($submission['Status']); ?>
                                    </span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <div class="empty-text">No submission history yet.</div>
            <?php endif; ?>
        </div>
    </div>

<!-- DETAIL MODAL -->
<div id="detailModal" class="detail-modal">
    <div class="detail-content">
        <span class="close-btn" onclick="closeDetailModal()">✖</span>
        <h2>Submission Details</h2>

        <div class="detail-row">
            <span class="detail-label">Submission ID:</span>
            <span id="detailSubmissionID"></span>
        </div>

        <div class="detail-row">
            <span class="detail-label">Challenge:</span>
            <span id="detailChallenge"></span>
        </div>

        <div class="detail-row">
            <span class="detail-label">Points:</span>
            <span id="detailPoints"></span>
        </div>

        <div class="detail-row">
            <span class="detail-label">Submission Date:</span>
            <span id="detailDate"></span>
        </div>

        <div class="detail-row">
            <span class="detail-label">Status:</span>
            <span id="detailStatus"></span>
        </div>

        <div class="detail-row">
            <span class="detail-label">Proof:</span>
            <div id="detailProofPreview"></div>
        </div>

        <div id="rejectReasonBox" class="reason-box" style="display:none;">
            <span>Rejection Reason:</span>
            <div id="detailRejectReason"></div>
        </div>
    </div>
</div>

<script>
// Filter
function filterSubmissions(status) {
    const rows = document.querySelectorAll(".history-table tr[data-status]");

    rows.forEach(row => {
        const rowStatus = row.getAttribute("data-status");

        if (status === "all" || rowStatus === status) {
            row.style.display = "";
        } else {
            row.style.display = "none";
        }
    });

    // 🔥 change dropdown color
    const dropdown = document.getElementById("statusFilter");

    dropdown.classList.remove(
        "filter-all",
        "filter-pending",
        "filter-approved",
        "filter-rejected"
    );

    dropdown.classList.add("filter-" + status);
}

function openDetailModal(submission) {
    document.getElementById("detailSubmissionID").innerText = submission.SubmissionID ?? "-";
    document.getElementById("detailChallenge").innerText = submission.ChallengeTitle ?? "-";
    document.getElementById("detailPoints").innerText = submission.Points ?? "-";
    document.getElementById("detailDate").innerText = submission.Submission_Date ?? "-";
    document.getElementById("detailStatus").innerText = submission.Status ?? "-";

    const preview = document.getElementById("detailProofPreview");

    if (submission.Proof && submission.Proof.trim() !== "") {

        let filePath = submission.Proof;

        // detect file type
        const extension = filePath.split('.').pop().toLowerCase();

        if (["jpg", "jpeg", "png", "gif", "webp"].includes(extension)) {
            preview.innerHTML = `<img src="${filePath}" class="proof-img">`;
        } else {
            preview.innerHTML = `
                <video controls class="proof-video">
                    <source src="${filePath}" type="video/${extension}">
                    Your browser does not support video.
                </video>
            `;
        } 
    } else {
        preview.innerHTML = "<p>-</p>";
    }

    const reasonBox = document.getElementById("rejectReasonBox");
    const reasonText = document.getElementById("detailRejectReason");

    if ((submission.Status) === "Rejected") {
        reasonText.innerText = submission.Review;
        reasonBox.style.display = "block";
    } else {
        reasonText.innerText = "";
        reasonBox.style.display = "none";
    }

    document.getElementById("detailModal").style.display = "flex";
}

function closeDetailModal() {
    document.getElementById("detailModal").style.display = "none";
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