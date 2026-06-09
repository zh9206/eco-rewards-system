<?php
session_start();

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

// Get Normal Submission
$sql = "SELECT * FROM challenge_submission WHERE FlagType='normal' ORDER BY Submission_Date DESC";
$result = $dbConn->query($sql);

// Get Number of Pending Submission
$pendingCount = $dbConn->query("
    SELECT COUNT(*) as cnt 
    FROM challenge_submission 
    WHERE FlagType='normal' AND Status='Pending'
")->fetch_assoc()['cnt'] ?? 0;

// Get Number of Daily Submission
$today = date('Y-m-d');
$todayCount = $dbConn->query("
    SELECT COUNT(*) as cnt 
    FROM challenge_submission 
    WHERE FlagType='normal' AND DATE(Submission_Date)='$today'
")->fetch_assoc()['cnt'] ?? 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Normal Submission - <?php echo $user['Username']?></title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<link rel="stylesheet" href="styles.css">
<link rel="stylesheet" href="responsive.css">
<style>
.review-stats{
    display: flex;
    gap: 20px;
    margin: 20px 0;
    flex-wrap: wrap;
}

/* Card */
.stat-card{
    flex: 1;
    min-width: 180px;
    padding: 18px 20px;
    border-radius: 16px;

    background: rgba(255,255,255,0.6);
    backdrop-filter: blur(8px);

    box-shadow: 0 4px 12px rgba(0,0,0,0.08);
    transition: 0.3s;
}

/* Hover effect */
.stat-card:hover{
    transform: translateY(-3px);
    box-shadow: 0 8px 18px rgba(0,0,0,0.12);
}

/* Number */
.stat-card h3{
    margin: 0;
    font-size: 28px;
    color: #2f5d3a;
    font-weight: 600;
}

/* Label */
.stat-card p{
    margin: 5px 0 0;
    font-size: 14px;
    color: #555;
}

.viewBtn{
    padding: 6px 14px;
    border: none;
    border-radius: 20px;
    font-size: 13px;
    font-weight: 600;
    background: linear-gradient(135deg, #84B179, #A2CB8B);
    color: white;
    cursor: pointer;
    transition: all 0.25s ease;
}

.viewBtn:hover{
    transform: translateY(-2px);
    box-shadow: 0 6px 12px rgba(132,177,121,0.4);
}

.viewBtn:active{
    transform: scale(0.95);
}

.detail-modal{
    display: none;
    position: fixed;
    inset: 0;
    background: rgba(0,0,0,0.45);
    justify-content: center;
    align-items: center;
    z-index: 9999;
}

.detail-modal-content{
    width: min(1100px, 92vw);
    height: min(85vh, 800px);
    background: white;
    border-radius: 18px;
    position: relative;
    overflow: hidden;
    box-shadow: 0 12px 35px rgba(0,0,0,0.25);
}

.detail-close{
    position: absolute;
    top: 12px;
    right: 14px;
    width: 38px;
    height: 38px;
    border: none;
    border-radius: 50%;
    background: rgba(255,255,255,0.9);
    font-size: 24px;
    cursor: pointer;
    z-index: 2;
}

.detail-close:hover{
    background: #f3f3f3;
}

#detailFrame{
    width: 100%;
    height: 100%;
    display: block;
    background: white;
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
                <a href="moderator.php" class="active">Normal Submission</a>
                <a href="duplicate.php">Duplicated / Suspicious</a>
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
            <h1>Submissions List</h1>

                <div class="top-bar">
                    <!-- Search Bar -->
                    <div class="search-box">
                        <span class="search-icon">🔍</span>
                        <input type="text" id="searchInput" placeholder="Search submission...">
                    </div>

                    <!-- Role Filter -->
                    <div class="filter-container">
                        <select id="statusFilter" class="filter-dropdown">
                            <option value="all">All</option>
                            <option value="pending">Pending</option>
                            <option value="approved">Approved</option>
                            <option value="rejected">Rejected</option>
                        </select>
                    </div>
                </div>


            <div class="review-stats">
                <div class="stat-card">
                    <h3><?= $pendingCount ?></h3>
                    <p>Pending Reviews</p>
                </div>
                <div class="stat-card">
                    <h3><?= $todayCount ?></h3>
                    <p>Submitted Today</p>
                </div>
            </div>

            <table class="table">
                <thead>
                    <tr>
                        <th>Submission ID</th>
                        <th>Student ID</th>
                        <th>Challenge ID</th>
                        <th>Date</th>
                        <th>Proof</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody id="activityTable">
                <?php
                    if ($result && $result->num_rows > 0) {
                        while ($row = $result->fetch_assoc()) {
                            $date = date("Y-m-d H:i", strtotime($row['Submission_Date']));
                            $statusClass = strtolower($row['Status']);
                            $proof = !empty($row['Proof']) ? $row['Proof'] : "placeholder.jpg";
                            $statusClass = strtolower($row['Status']);

                            $rowAttr = ($row['Status'] == "Pending")
                                ? " class='pending-row' onclick=\"openDetailModal('activityPending.php?submissionID={$row['SubmissionID']}')\""
                                : "";

                            echo "<tr data-status='{$statusClass}'{$rowAttr}>";
                            echo "<td data-label='Submission ID'>{$row['SubmissionID']}</td>";
                            echo "<td data-label='Student ID'>{$row['StudentID']}</td>";
                            echo "<td data-label='Challenge ID'>{$row['ChallengeID']}</td>";
                            echo "<td data-label='Date'>{$date}</td>";
                            echo "<td data-label='Proof'><img src='{$proof}' width='60' style='border-radius:6px;'></td>";
                            echo "<td data-label='Status'><span class='status-badge status-{$statusClass}'>{$row['Status']}</span></td>";
                            echo "<td data-label='Action'>";

                            if ($row['Status'] == "Approved" || $row['Status'] == "Rejected") {
                                echo "<button class='btn viewBtn'>👁 View</button>";
                            } else {
                                echo "-";
                            }

                            echo "</td>";
                            echo "</tr>";
                        }
                    } else {
                        echo "<tr><td colspan='7' class='no-record'>No submission found</td></tr>";
                    }?>
                </tbody>
            </table>
        </div>
    </div>


    <div id="detailModal" class="detail-modal">
        <div class="detail-modal-content">
            <span class="close-btn" onclick="closeDetailModal()">✖</span>
            <iframe id="detailFrame" src="" frameborder="0"></iframe>
        </div>
    </div>
<script>
    function openDetailModal(url){
        document.getElementById("detailFrame").src = url;
        document.getElementById("detailModal").style.display = "flex";
        document.body.style.overflow = "hidden";
    }

    function closeDetailModal(){
        document.getElementById("detailModal").style.display = "none";
        document.getElementById("detailFrame").src = "";
        document.body.style.overflow = "";
    }

    const searchInput = document.getElementById("searchInput");
    const statusFilter = document.getElementById("statusFilter");
    const tableRows = document.querySelectorAll("#activityTable tr[data-status]");

    // Filter & Search
    function filterSubmissions() {
        const keyword = searchInput.value.toLowerCase().trim();
        const selectedStatus = statusFilter.value.toLowerCase();

        tableRows.forEach(row => {
            const rowText = row.innerText.toLowerCase();
            const rowStatus = row.getAttribute("data-status").toLowerCase();

            const matchKeyword = rowText.includes(keyword);
            const matchStatus = (selectedStatus === "all" || rowStatus === selectedStatus);

            if (matchKeyword && matchStatus) {
                row.style.display = "";
            } else {
                row.style.display = "none";
            }
        });

        statusFilter.classList.remove(
            "filter-all",
            "filter-pending",
            "filter-approved",
            "filter-rejected"
        );

        statusFilter.classList.add("filter-" + selectedStatus);
    }

    searchInput.addEventListener("input", filterSubmissions);
    statusFilter.addEventListener("change", filterSubmissions);

    document.querySelectorAll(".viewBtn").forEach(btn => {
        btn.addEventListener("click", function(e) {
            e.stopPropagation();
            const row = this.closest("tr");
            const submissionID = row.cells[0].innerText.trim();
            openDetailModal(`activityDetail.php?submissionID=${submissionID}`);
        });
    });

    function logout() {
        if (confirm("Are you sure you want to logout?")) {
            window.location.href = "logout.php";
        }
    }
</script>
<script src="hamburger.js"></script>
</body>
</html>