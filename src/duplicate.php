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

// latest row for each duplicate/suspicious group
$sql = "SELECT * FROM challenge_submission 
        WHERE SubmissionID IN (
            SELECT MAX(SubmissionID) 
            FROM challenge_submission 
            WHERE FlagType IN ('duplicate', 'suspicious')
            GROUP BY StudentID, ChallengeID, Proof
        )
        ORDER BY Submission_Date DESC";
$result = $dbConn->query($sql);

// stats
$pendingCount = $dbConn->query("
    SELECT COUNT(*) as cnt 
    FROM challenge_submission 
    WHERE FlagType IN ('duplicate','suspicious') 
    AND Status='Pending'
")->fetch_assoc()['cnt'] ?? 0;

$today = date('Y-m-d');
$todayCount = $dbConn->query("
    SELECT COUNT(*) as cnt 
    FROM challenge_submission 
    WHERE FlagType IN ('duplicate','suspicious') 
    AND DATE(Submission_Date)='$today'
")->fetch_assoc()['cnt'] ?? 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Duplicated / Suspicious Submissions - <?php echo $user['Username']; ?></title>
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

        .stat-card:hover{
            transform: translateY(-3px);
            box-shadow: 0 8px 18px rgba(0,0,0,0.12);
        }

        .stat-card h3{
            margin: 0;
            font-size: 28px;
            color: #2f5d3a;
            font-weight: 600;
        }

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

        .empty-text{
            padding: 20px;
            text-align: center;
            color: #666;
            background: rgba(255,255,255,0.7);
            border-radius: 14px;
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
                <a href="moderator.php">Normal Submission</a>
                <a href="duplicate.php" class="active">Duplicated / Suspicious</a>
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
            <h1>Duplicated / Suspicious Submissions</h1>

            <div class="top-bar">
                <div class="search-box">
                    <span class="search-icon">🔍</span>
                    <input type="text" id="searchInput" placeholder="Search submission...">
                </div>

                <div class="filter-container">
                    <select id="flagFilter" class="filter-dropdown">
                        <option value="all">All</option>
                        <option value="duplicate">Duplicate</option>
                        <option value="suspicious">Suspicious</option>
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

            <div class="table-wrapper">
                <?php if ($result && $result->num_rows > 0): ?>
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Submission ID</th>
                                <th>Date</th>
                                <th>Proof</th>
                                <th>Status</th>
                                <th>Flag Type</th>
                                <th>Student ID</th>
                                <th>Challenge ID</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody id="flaggedTable">
                            <?php while($row = $result->fetch_assoc()): ?>
                                <?php
                                    $statusLower = strtolower($row['Status']);
                                    $flagLower = strtolower($row['FlagType']);
                                    $proofImg = $row['Proof'] ;

                                    $rowClick = "";
                                    if ($statusLower === "pending") {
                                        $rowClick = "onclick=\"openFlagModal('".($flagLower === 'duplicate' ? "duplicatePending.php" : "suspiciousPending.php")."?submissionID={$row['SubmissionID']}')\"";
                                    }
                                ?>
                                <tr data-flag="<?= $flagLower ?>" data-status="<?= $statusLower ?>" <?= $rowClick ?>>
                                    <td data-label="Submission ID"><?= htmlspecialchars($row['SubmissionID']) ?></td>
                                    <td data-label="Date"><?= date('Y-m-d H:i', strtotime($row['Submission_Date'])) ?></td>
                                    <td data-label="Proof">
                                        <img src="<?= htmlspecialchars($proofImg) ?>" width="60" style="border-radius:8px;">
                                    </td>
                                    <td data-label="Status">
                                        <span class="status-badge status-<?= $statusLower ?>">
                                            <?= htmlspecialchars($row['Status']) ?>
                                        </span>
                                    </td>
                                    <td data-label="Flag Type">
                                        <span class="flag-badge flag-<?= $flagLower ?>">
                                            <?= htmlspecialchars(ucfirst($row['FlagType'])) ?>
                                        </span>
                                    </td>
                                    <td data-label="Student ID"><?= htmlspecialchars($row['StudentID']) ?></td>
                                    <td data-label="Challenge ID"><?= htmlspecialchars($row['ChallengeID']) ?></td>
                                    <td data-label="Action">
                                        <?php if($statusLower !== 'pending'): ?>
                                            <button class="btn viewBtn"
                                                onclick="openFlagModal('<?= $flagLower ?>Detail.php?submissionID=<?= $row['SubmissionID'] ?>')">
                                                👁 View
                                            </button>
                                        <?php else: ?>
                                            -
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <div class="empty-text">No submissions found.</div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div id="detailModal" class="detail-modal">
        <div class="detail-modal-content">
            <span class="close-btn" onclick="closeFlagModal()">✖</span>
            <iframe id="detailFrame" src="" frameborder="0"></iframe>
        </div>
    </div>

    <script>
        const searchInput = document.getElementById("searchInput");
        const flagFilter = document.getElementById("flagFilter");
        const tableRows = document.querySelectorAll("#flaggedTable tr[data-flag]");

        // Filter & Search
        function filterFlaggedTable() {
            const keyword = searchInput.value.toLowerCase().trim();
            const selectedFlag = flagFilter.value.toLowerCase();

            tableRows.forEach(row => {
                const rowText = row.innerText.toLowerCase();
                const rowFlag = row.getAttribute("data-flag").toLowerCase();

                const matchKeyword = rowText.includes(keyword);
                const matchFlag = (selectedFlag === "all" || rowFlag === selectedFlag);

                row.style.display = (matchKeyword && matchFlag) ? "" : "none";
            });

            flagFilter.classList.remove(
                "filter-all",
                "filter-duplicate",
                "filter-suspicious"
            );

            flagFilter.classList.add("filter-" + selectedFlag);
        }

        searchInput.addEventListener("input", filterFlaggedTable);
        flagFilter.addEventListener("change", filterFlaggedTable);

        function openFlagModal(url){
            document.getElementById("detailFrame").src = url;
            document.getElementById("detailModal").style.display = "flex";
            document.body.style.overflow = "hidden";
        }

        function closeFlagModal(){
            document.getElementById("detailModal").style.display = "none";
            document.getElementById("detailFrame").src = "";
            document.body.style.overflow = "";
        }

        function logout() {
            if (confirm("Are you sure you want to logout?")) {
                window.location.href = "logout.php";
            }
        }

    </script>
    <script src="hamburger.js"></script>
</body>
</html>