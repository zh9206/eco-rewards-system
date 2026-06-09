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

// Get Total Points
$sqlPoints = "SELECT COALESCE(SUM(c.Points), 0) AS TotalPoints
              FROM challenge_submission cs
              JOIN challenge c ON cs.ChallengeID = c.ChallengeID
              WHERE cs.StudentID = ?
              AND cs.Status = 'Approved'";

$stmtPoints = $dbConn->prepare($sqlPoints);
$stmtPoints->bind_param("s", $userID);
$stmtPoints->execute();
$resultPoints = $stmtPoints->get_result();
$pointsRow = $resultPoints->fetch_assoc();
$totalPoints = $pointsRow['TotalPoints'];

// Get Current Rank
$sqlRank = "
    SELECT ranked.UserID, ranked.Username, ranked.TotalPoints, ranked.CurrentRank
    FROM (
        SELECT 
            u.UserID,
            u.Username,
            COALESCE(SUM(c.Points), 0) AS TotalPoints,
            DENSE_RANK() OVER (ORDER BY COALESCE(SUM(c.Points), 0) DESC) AS CurrentRank
        FROM User u
        JOIN Role r ON u.RoleID = r.RoleID
        LEFT JOIN challenge_submission cs 
            ON u.UserID = cs.StudentID AND cs.Status = 'Approved'
        LEFT JOIN challenge c 
            ON cs.ChallengeID = c.ChallengeID
        WHERE r.Role_Name = 'student'
        GROUP BY u.UserID, u.Username
    ) ranked
    WHERE ranked.UserID = ?";

$stmtRank = $dbConn->prepare($sqlRank);
$stmtRank->bind_param("s", $userID);
$stmtRank->execute();
$resultRank = $stmtRank->get_result();
$rankRow = $resultRank->fetch_assoc();
$currentRank = $rankRow ? $rankRow['CurrentRank'] : "-";

// Get All Announcement
$sqlAnnouncement = "SELECT AnnouncementID, Title, Description, StartDate, EndDate
                    FROM announcement
                    WHERE EndDate >= CURDATE()
                    ORDER BY StartDate ASC, AnnouncementID ASC";

$resultAnnouncement = $dbConn->query($sqlAnnouncement);

$announcements = [];
while ($row = $resultAnnouncement->fetch_assoc()) {
    $announcements[] = $row;
}

// Get All Challenges (Log Action Submission)
$sqlChallenge = "SELECT ChallengeID, Title, Points
                 FROM challenge
                 ORDER BY ChallengeID ASC";

$resultChallenge = $dbConn->query($sqlChallenge);

$challenges = [];
while ($row = $resultChallenge->fetch_assoc()) {
    $challenges[] = $row;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<link rel="stylesheet" href="styles.css">
<link rel="stylesheet" href="responsive.css">
<title>EcoRewards Dashboard - <?php echo $user['Username']?></title>

<style>
/* ===== GENERAL STYLES ===== */
body {
    margin: 0;
    padding: 0;
    display: flex;
    justify-content: flex-start;
    align-items: stretch;
    min-height: 100vh;
}

.dashboard-bottom{
    display:grid;
    grid-template-columns: 1fr 1fr;
    align-items: stretch;
    gap:30px;
    margin-top:30px;
}

.stats-section {
    display: flex;
    justify-content: center;
    gap: 20px;
    flex-wrap: wrap;
    margin-bottom: 25px;
}

.stats-section a{
    text-decoration: none;
}

.tree-area {
    display: flex;
    flex-direction: column;
    justify-content: space-between; /* pushes top content up, bottom content down */
    padding: 20px;
    border-radius: 20px;
    margin-top: 30px;
    background: rgba(255,255,255,0.75);
    box-shadow: 0 10px 30px rgba(0,0,0,0.12);
}

.wood-banner {
    width: 200px;
    height: 150px;
    background: url('banner.png') no-repeat center;
    background-size: contain;
    display: flex;
    flex-direction: column;
    justify-content: center;
    align-items: center;
    text-align: center;
    padding: 10px;
    cursor: pointer;
    transition: transform 0.2s ease;
}

.wood-banner:hover {
    transform: translateY(-3px);
}

.banner-label {
    font-size: 19px;
    font-weight: 600;
    color: #4b2e1f;
    margin-top: 30px;
}

.banner-value {
    font-size: 30px;
    font-weight: bold;
    color: #2e1b0f;
    margin: 0;
}

.log-section {
    margin-top: 30px;
    background: rgba(255,255,255,0.75);
    padding: 25px;
    border-radius: 20px;
    backdrop-filter: blur(12px);
    box-shadow: 0 15px 35px rgba(0,0,0,0.1);
    text-align: left;
}

.log-section h2 {
    margin-bottom: 20px;
    text-align: center;
    color: #2e7d32;
}

/* Labels */
.log-section label {
    font-weight: 600;
    font-size: 14px;
    color: #2e7d32;
}

/* Select & Input */
.log-section select,
.log-section input[type="file"] {
    width: 100%;
    padding: 12px;
    margin-top: 6px;
    margin-bottom: 18px;
    border-radius: 12px;
    border: 1px solid #dcdcdc;
    background: #ffffff;
    font-size: 14px;
    transition: 0.3s ease;
}

.log-section select:focus,
.log-section input[type="file"]:focus {
    outline: none;
    border: 1px solid #2ECC71;
    box-shadow: 0 0 8px rgba(46,204,113,0.3);
}

/* Submit Button */
.log-section button {
    width: 100%;
    padding: 12px;  
}

.upload-box {
    display: flex;
    justify-content: center;
    align-items: center;
    border: 2px dashed #84B179;
    border-radius: 15px;
    padding: 20px;
    cursor: pointer;
    margin-bottom: 20px;
    background: #f6fff9;
    transition: 0.3s ease;
    text-align: center;
    font-weight: 500;
    color: #2e7d32;
}

.upload-box:hover {
    background: #e8fff0;
}

/* Tree */
.tree-container {
    width: 100%;
    display: flex;
    justify-content: center;
}

#forest {
    display: flex;
    flex-wrap: wrap;
    gap: 10px;
    justify-content: center;
    align-items: flex-end;
}

.tree {
    width: 70px;
    height: 100px;
    background-size: contain;
    background-repeat: no-repeat;
    background-position: bottom;
}

#tree-img {
    width: 100px;
    height: auto;
    transition: transform 0.5s ease, opacity 0.5s ease;
}

/* Progress Bar */
.progress-bar {
    width: 80%;
    height: 25px;
    margin-top: 5px;
    background: #e0e0e0;
    border-radius: 12px;
    overflow: hidden;
    position: relative;
}

#progress {
    height: 100%;
    width: 0%;
    background: #84B179;
    transition: width 0.5s ease;
    border-radius: 12px;
}

#progressText {
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    font-size: 15px;
    font-weight: bold;
    color: #2e2e2e;
    z-index: 2;
    pointer-events: none;
}

.progress-text {
    margin: 2px 0 0 0;
    font-size: 13px;
    font-weight: 600;
    color: #2e7d32;
    text-align: center;
}

.tree-area {
    display: flex;
    flex-direction: column;
    justify-content: center;
    align-items: center;
    gap: 5px;
}

.progress-text {
    margin: 0 0 3px 0;
    font-size: 13px;
    font-weight: 600;
    color: #2e7d32;
    text-align: center;
}

/* Announcement Bar */
.announcement-bar {
    width: 100%;
    background: rgba(92, 58, 30, 0.9);
    color: #fff8dc;
    border-radius: 14px;
    overflow: hidden;
    padding: 12px 0;
    margin-bottom: 20px;
    cursor: pointer;
    box-shadow: 0 6px 15px rgba(0,0,0,0.15);
    position: relative;
}

.announcement-track {
    width: 100%;
    overflow: hidden;
    white-space: nowrap;
    position: relative;
    height: 24px;
}

#announcementText {
    display: inline-block;
    position: absolute;
    white-space: nowrap;
    font-weight: 600;
    font-size: 15px;
    left: 100%;
}

/* Announcement Modal */
.announcement-modal {
    display: none;
    position: fixed;
    inset: 0;
    background: rgba(0,0,0,0.5);
    justify-content: center;
    align-items: center;
    z-index: 999;
}

.announcement-content {
    position: relative;
    background: white;
    width: 420px;
    max-width: 90%;
    max-height: 80vh;
    overflow-y: auto;
    border-radius: 18px;
    padding: 25px;
    box-shadow: 0 10px 30px rgba(0,0,0,0.25);
    text-align: left;
}

.announcement-content h2 {
    margin-bottom: 15px;
    color: #2e7d32;
}

.announcement-content .close-btn {
    position: absolute;
    top: 12px;
    right: 15px;
    font-size: 18px;
    font-weight: bold;
    cursor: pointer;
    color: #555;
    transition: 0.2s;
}

.announcement-content .close-btn:hover {
    color: #e74c3c;
}

.announcement-item {
    padding: 12px 0;
    border-bottom: 1px solid #ddd;
}

.announcement-item h3 {
    margin-bottom: 8px;
    color: #5c3a1e;
}

.announcement-item p {
    margin-bottom: 6px;
    line-height: 1.5;
}

.announcement-item small {
    color: #666;
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
                <a href="logAction.php">Log Action</a>
                <a href="leaderboard.php">Leaderboard</a>
                <a href="rewards.php">Rewards Shop</a>
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

<!-- ===== DASHBOARD ===== -->
<div class="main">

    <!-- Announcement -->
    <div class="announcement-bar" onclick="openAnnouncementModal()">
        <div class="announcement-track">
            <span id="announcementText">
                <?php echo count($announcements) > 0 ? htmlspecialchars($announcements[0]['Title']) : 'No current announcements'; ?>
            </span>
        </div>
    </div>

    <!-- TOP: Banners -->
    <div class="stats-section">
        <a href="rewards.php">
            <div class="wood-banner">
                <p class="banner-label">Points</p>
                <h2 class="banner-value" id="points"><?php echo $totalPoints; ?></h2>
            </div>
        </a>

        <a href="leaderboard.php">
            <div class="wood-banner">
                <p class="banner-label">Current Rank</p>
                <h2 class="banner-value">#<?php echo $currentRank; ?></h2>
            </div>
        </a>
    </div>

    <!-- BOTTOM -->
    <div class="dashboard-bottom">

        <!-- LEFT:Points -->
        <div class="tree-area">
            <div class="tree-container">
                <div id="forest"></div>
            </div>
 
            <div class="progress-bar" id="progressContainer">
                <div id="progress"></div>
                <span id="progressText">0%</span>
            </div>
            
        </div>
        
        <!-- Right:Submit -->
        <div class="log-section">
            <h2>Log Green Action</h2>

            <form id="logForm" action="submitChallenge.php" method="POST" enctype="multipart/form-data">

                <label for="challengeSelect">Select Challenge</label>
                <select name="challengeID" required>
                    <option value="" disabled selected>Select Action</option>
                    <?php foreach ($challenges as $challenge): ?>
                        <option value="<?php echo htmlspecialchars($challenge['ChallengeID']); ?>">
                            <?php echo htmlspecialchars($challenge['Title']) . " (+" . $challenge['Points'] . " points)"; ?>
                        </option>
                    <?php endforeach; ?>
                </select>

                <label>Upload Proof</label>

                <label class="upload-box" for="proofFile">
                    <input type="file" id="proofFile" name="proofFile" accept="image/*" required hidden>
                    <span id="uploadText">📸 Click to upload image</span>
                </label>

                <button type="submit" class="btn">Submit for Approval</button>
            </form>
        </div>
    </div>
    
</div>

<div id="announcementModal" class="announcement-modal">
    <div class="announcement-content">
        <span class="close-btn" onclick="closeAnnouncementModal()">✖</span>
        <h2 id="modalAnnouncementTitle">Announcement</h2>
        <p id="modalAnnouncementDescription"></p>
        <small id="modalAnnouncementDate"></small>
    </div>
</div>

<script>
const announcements = <?php echo json_encode($announcements); ?>;

let currentAnnouncementIndex = 0;

function startAnnouncementTicker() {
    if (!announcements || announcements.length === 0) {
        return;
    }

    const textEl = document.getElementById("announcementText");

    function showNextAnnouncement() {
        const current = announcements[currentAnnouncementIndex];
        textEl.textContent = `📢 ${current.Title}`;

        // reset starting position
        textEl.style.transition = "none";
        textEl.style.left = "100%";

        // wait one frame, then animate
        requestAnimationFrame(() => {
            requestAnimationFrame(() => {
                const textWidth = textEl.offsetWidth;
                const containerWidth = textEl.parentElement.offsetWidth;

                // speed control: bigger number = slower
                const distance = containerWidth + textWidth;
                const duration = Math.max(4, distance / 200);

                textEl.style.transition = `left ${duration}s linear`;
                textEl.style.left = `-${textWidth}px`;

                setTimeout(() => {
                    currentAnnouncementIndex = (currentAnnouncementIndex + 1) % announcements.length;
                    showNextAnnouncement();
                }, duration * 1000);
            });
        });
    }

    showNextAnnouncement();
}

function openAnnouncementModal() {
    if (!announcements || announcements.length === 0) return;

    const current = announcements[currentAnnouncementIndex];

    document.getElementById("modalAnnouncementTitle").innerText = current.Title;
    document.getElementById("modalAnnouncementDescription").innerText = current.Description;
    document.getElementById("modalAnnouncementDate").innerText =
        `${current.StartDate} until ${current.EndDate}`;

    document.getElementById("announcementModal").style.display = "flex";
}

function closeAnnouncementModal() {
    document.getElementById("announcementModal").style.display = "none";
}

window.addEventListener("load", startAnnouncementTicker);

let points = <?php echo $totalPoints; ?>;
const maxPoints = 1500;
const pointsDisplay = document.getElementById('points');
const progressBar = document.getElementById('progress');
const nextStageText = document.getElementById('nextStageText');
const progressContainer = document.getElementById('progressContainer');

pointsDisplay.innerText = points;
renderForest(points);
updateProgress(points);

function renderForest(totalPoints) {

    const forest = document.getElementById("forest");
    forest.innerHTML = "";

    let treesCompleted = Math.floor(totalPoints / 100);
    let currentTreePoints = totalPoints % 100;

    // Render completed large trees
    for (let i = 0; i < treesCompleted; i++) {
        let tree = document.createElement("div");
        tree.classList.add("tree");
        tree.style.backgroundImage = "url('largetree.png')";
        forest.appendChild(tree);
    }

    // Render growing tree
    if (currentTreePoints > 0 || treesCompleted === 0) {

        let tree = document.createElement("div");
        tree.classList.add("tree");

        if (currentTreePoints < 30) {
            tree.style.backgroundImage = "url('seed.png')";
        } else if (currentTreePoints < 70) {
            tree.style.backgroundImage = "url('smalltree.png')";
        } else {
            tree.style.backgroundImage = "url('mediumtree.png')";
        }

        forest.appendChild(tree);
    }
}

function updateProgress(score) {
    let stageProgress = score % 100;
    let pointsLeft = 100 - stageProgress;

    if (score > 0 && stageProgress === 0) {
        stageProgress = 100;
        pointsLeft = 0;
    }

    progressBar.style.width = stageProgress + '%';

    // default text
    progressText.innerText = stageProgress + '%';

    // store values for hover
    progressContainer.dataset.current = stageProgress;
    progressContainer.dataset.total = 100;

}

function logout() {
    const confirmAction = confirm("Are you sure you want to logout?");
    
    if (confirmAction) {
        window.location.href = "logout.php";
    }
}

document.addEventListener("DOMContentLoaded", function () {
    const proofInput = document.getElementById("proofFile");
    const uploadText = document.getElementById("uploadText");

    if (!proofInput) return;

    proofInput.addEventListener("change", function () {
        const file = this.files[0];

        if (!file) {
            uploadText.innerText = "📸 Click to upload image";
            return;
        }

        uploadText.innerText = file.name;
    });
});

progressContainer.addEventListener("mouseenter", function () {
    const current = this.dataset.current;
    const total = this.dataset.total;

    progressText.innerText = `${current} / ${total} points`;
});

progressContainer.addEventListener("mouseleave", function () {
    const current = this.dataset.current;

    progressText.innerText = current + "%";
});
</script>
<script src="hamburger.js"></script>
</body>
</html>