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

$sqlLeaderboard = "SELECT u.UserID, u.Username, COALESCE(SUM(c.Points), 0) AS TotalPoints
                  FROM User u
                  JOIN Role r ON u.RoleID = r.RoleID
                  LEFT JOIN challenge_submission cs 
                    ON u.UserID = cs.StudentID AND cs.Status = 'Approved'
                  LEFT JOIN challenge c 
                    ON cs.ChallengeID = c.ChallengeID
                  WHERE r.Role_Name = 'student'
                  GROUP BY u.UserID, u.Username
                  ORDER BY TotalPoints DESC, u.Username ASC";

$resultLeaderboard = $dbConn->query($sqlLeaderboard);

$leaders = [];
while ($row = $resultLeaderboard->fetch_assoc()) {
    $leaders[] = $row;
}

$rankedLeaders = [];
$rank = 0;
$displayRank = 0;
$prevPoints = null;

foreach ($leaders as $leader) {
    $rank++;
    $currentPoints = $leader['TotalPoints'];

    if ($prevPoints === null || $currentPoints != $prevPoints) {
        $displayRank = $rank;
    }

    $leader['Rank'] = $displayRank;
    $rankedLeaders[] = $leader;

    $prevPoints = $currentPoints;
}

// Group User By Rank
$podiumRanks = [
    1 => [],
    2 => [],
    3 => []
];

foreach ($rankedLeaders as $leader) {
    if (isset($podiumRanks[$leader['Rank']])) {
        $podiumRanks[$leader['Rank']][] = $leader;
    }
}

function renderPodiumNames($players) {
    if (empty($players)) return "---";

    $html = "";
    foreach ($players as $p) {
        $html .= "<div class='podium-player'>" . htmlspecialchars($p['Username']) . "</div>";
    }
    return $html;
}

// Get Current Rank
$currentUserRank = null;
$currentUserPoints = 0;
$currentUsername = "";

foreach ($rankedLeaders as $leader) {
    if ($leader['UserID'] == $_SESSION['UserID']) {
        $currentUserRank = $leader['Rank'];
        $currentUserPoints = $leader['TotalPoints'];
        $currentUsername = $leader['Username'];
        break;
    }
}

include("profile.php");
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<link rel="stylesheet" href="styles.css">
<link rel="stylesheet" href="responsive.css">
<title>Leaderboard - <?php echo $user['Username']?></title>
<style>

.page-card {
    background: rgba(255,255,255,0.7);
    border-radius: 20px;
    padding: 30px;
    box-shadow: 0 10px 30px rgba(0,0,0,0.15);
}

.page-card h1 {
    text-align: center;
    color: #2e7d32;
    margin-bottom: 30px;
}

/* Podium */
.podium {
    display: flex;
    justify-content: center;
    align-items: end;
    gap: 20px;
    margin-bottom: 40px;
}

.podium-card {
    width: 220px;
    border-radius: 20px 20px 0 0;
    text-align: center;
    padding: 20px 15px 10px 15px; /* less bottom padding */
    color: white;
    box-shadow: 0 8px 20px rgba(0,0,0,0.15);
    display: flex;
    flex-direction: column;
    justify-content: flex-start; /* change this */
    align-items: center;
    height: 260px;
}

.podium-top {
    width: 100%;
}
 
.podium-player{
    font-size: 16px;
    font-weight: 600;
    margin-bottom: 6px;
    padding-bottom: 10px;
}

.podium-player:last-child {
    border-bottom: none;
    margin-bottom: 0;
    padding-bottom: 0;
}

.first {
    height: 300px;
    background: #d4af37;
}

.second {
    height: 240px;
    background: #9e9e9e;
}

.third {
    height: 210px;
    background: #cd7f32;
}

.multi-name{
    width: 100%;
    max-height: 30px;
    overflow-y: auto;
}

.multi-name::-webkit-scrollbar{
    display: none;
}

.rank-title {
    font-size: 22px;
    font-weight: bold;
    margin-bottom: 10px;
}

.player-name {
    font-size: 18px;
    font-weight: 600;
    margin-bottom: 8px;
}

.player-points {
    font-size: 16px;
    margin-bottom: 15px;
}

/* Mini forest */
.mini-forest {
    display: flex;
    flex-wrap: wrap;
    justify-content: center;
    align-items: flex-end;
    gap: 8px;
    min-height: 70px;
    margin-top: auto;
    padding-top: 10px;
    width: 100%;
}

.mini-tree {
    width: 35px;
    height: 50px;
    background-size: contain;
    background-repeat: no-repeat;
    background-position: bottom;
}

tr:nth-child(even) {
    background: #f4fff4;
}

tr:nth-child(odd) {
    background: #ffffff;
}

.current-rank-tab {
    position: fixed;
    bottom: 10px;
    left: 57%;
    transform: translateX(-50%);
    z-index: 50;
    pointer-events: none;
}

.rank-tab-content {
    pointer-events: none;
    min-width: 320px;
    max-width: 520px;
    width: fit-content;
    background: rgba(46, 125, 50, 0.95);
    color: white;
    border-radius: 16px;
    padding: 14px 22px;
    box-shadow: 0 10px 25px rgba(0,0,0,0.2);
    display: flex;
    gap: 18px;
    align-items: center;
    justify-content: center;
    font-weight: 600;
}

.rank-tab-label {
    opacity: 0.9;
}

.rank-tab-rank {
    font-size: 22px;
    font-weight: 700;
}

.rank-tab-name {
    max-width: 180px;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.rank-tab-points {
    background: rgba(255,255,255,0.18);
    padding: 6px 10px;
    border-radius: 10px;
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
                <a href="leaderboard.php" class="active">Leaderboard</a>
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

    <div class="main">
        <div class="page-card">
            <h1>Leaderboard</h1>

            <div class="podium">

                <!-- 2nd -->
                <div class="podium-card second">
                    <div class="podium-top">
                        <div class="rank-title">2nd</div>

                        <?php if (!empty($podiumRanks[2])): ?>
                            <div class="multi-name">
                                <?php echo renderPodiumNames($podiumRanks[2]); ?>
                            </div>
                            <div class="player-points">
                                <?php echo htmlspecialchars($podiumRanks[2][0]['TotalPoints']); ?> pts
                            </div>
                        <?php else: ?>
                            <div class="player-name">---</div>
                        <?php endif; ?>
                    </div>

                    <?php if (!empty($podiumRanks[2])): ?>
                        <div class="mini-forest" data-points="<?php echo $podiumRanks[2][0]['TotalPoints']; ?>"></div>
                    <?php endif; ?>
                </div>

                <!-- 1st -->
                <div class="podium-card first">

                    <div class="podium-top">
                        <div class="rank-title">1st</div>

                        <div class="multi-name">
                            <?php echo renderPodiumNames($podiumRanks[1]); ?>
                        </div>

                        <div class="player-points">
                            <?php echo $podiumRanks[1][0]['TotalPoints']; ?> pts
                        </div>
                    </div>

                    <div class="mini-forest" data-points="<?php echo $podiumRanks[1][0]['TotalPoints']; ?>"></div>

                </div>

                <!-- 3rd -->
                <div class="podium-card third">
                    <div class="podium-top">
                        <div class="rank-title">3rd</div>

                        <?php if (!empty($podiumRanks[3])): ?>
                            <div class="multi-name">
                                <?php echo renderPodiumNames($podiumRanks[3]); ?>
                            </div>
                            <div class="player-points">
                                <?php echo htmlspecialchars($podiumRanks[3][0]['TotalPoints']); ?> pts
                            </div>
                        <?php else: ?>
                            <div class="player-name">---</div>
                        <?php endif; ?>
                    </div>

                    <?php if (!empty($podiumRanks[3])): ?>
                        <div class="mini-forest" data-points="<?php echo $podiumRanks[3][0]['TotalPoints']; ?>"></div>
                    <?php endif; ?>
                </div>

            </div>

            <table class="table">
                <thead>
                    <tr>
                        <th>Rank</th>
                        <th>User ID</th>
                        <th>Username</th>
                        <th>Total Points</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($rankedLeaders as $leader): ?>
                    <tr>
                        <td data-label="Rank"><?php echo $leader['Rank']; ?></td>
                        <td data-label="User ID"><?php echo htmlspecialchars($leader['UserID']); ?></td>
                        <td data-label="Username"><?php echo htmlspecialchars($leader['Username']); ?></td>
                        <td data-label="Total Points"><?php echo htmlspecialchars($leader['TotalPoints']); ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <div class="current-rank-tab">
        <div class="rank-tab-content">
            <span class="rank-tab-label">Your Rank</span>
            <span class="rank-tab-rank">#<?php echo $currentUserRank; ?></span>
            <span class="rank-tab-name"><?php echo $currentUsername; ?></span>
            <span class="rank-tab-points"><?php echo $currentUserPoints; ?> pts</span>
        </div>
    </div>

<script>
    function renderMiniForest(container, totalPoints) {
        let treesCompleted = Math.floor(totalPoints / 100);
        let currentTreePoints = totalPoints % 100;

        for (let i = 0; i < treesCompleted; i++) {
            let tree = document.createElement("div");
            tree.classList.add("mini-tree");
            tree.style.backgroundImage = "url('largetree.png')";
            container.appendChild(tree);
        }

        if (currentTreePoints > 0 || treesCompleted === 0) {
            let tree = document.createElement("div");
            tree.classList.add("mini-tree");

            if (currentTreePoints < 30) {
                tree.style.backgroundImage = "url('seed.png')";
            } else if (currentTreePoints < 70) {
                tree.style.backgroundImage = "url('smalltree.png')";
            } else {
                tree.style.backgroundImage = "url('mediumtree.png')";
            }

            container.appendChild(tree);
        }
    }

    document.querySelectorAll(".mini-forest").forEach(forest => {
        const points = parseInt(forest.dataset.points) || 0;
        renderMiniForest(forest, points);
    });

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