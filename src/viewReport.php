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


$type = $_GET['type'] ?? 'user';
$time = $_GET['time'] ?? 'today';

// Role ID -> Role Title
$roleNames = ['R01'=>'Student','R02'=>'Moderator','R03'=>'Administrator'];

// Get Total Number Of Users
$totalUsers = 0;
$result = $dbConn->query("SELECT COUNT(*) as cnt FROM user");
if($result) $totalUsers = (int)$result->fetch_assoc()['cnt'];

// Download CSV
// Check if user clicks download
if(isset($_GET['download'])){
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="'.$type.'_report.csv"');

    $out = fopen('php://output','w');

    if($type==='user'){
        fputcsv($out,['UserID','Username','Gender','DOB','Contact','Role']);
        // get all user
        $sql = "SELECT * FROM user";
        $result = $dbConn->query($sql);
        // loop each user
        while($row = $result->fetch_assoc()){
            $roleName = $roleNames[$row['RoleID']] ?? $row['RoleID'];
            fputcsv($out,[$row['UserID'],$row['Username'],$row['Gender'],$row['DOB'],$row['Contact'],$roleName]);
        }

    } elseif($type==='activity'){
        fputcsv($out,['UserID','Username','LastActive','Role']);
        $sql = "SELECT * FROM user WHERE LastActive IS NOT NULL";
        $res = $dbConn->query($sql);
        $now = new DateTime();
        while($row = $res->fetch_assoc()){
            $last = new DateTime($row['LastActive']);
            $include=false;
            if($time==='today' && $last->format('Y-m-d')==$now->format('Y-m-d')) $include=true;
            elseif($time==='monthly' && $last->format('Y-m')==$now->format('Y-m')) $include=true;
            elseif($time==='yearly' && $last->format('Y')==$now->format('Y')) $include=true;
            if($include){
                $roleName = $roleNames[$row['RoleID']] ?? $row['RoleID'];
                fputcsv($out,[$row['UserID'],$row['Username'],$row['LastActive'],$roleName]);
            }
        }
    }
    fclose($out);
    exit();
}

include("profile.php");

// Get Data
$users=[];
$activityData=[];
$roleCounts=[];
$activityCounts=[];

$now = new DateTime();

if($type==='user'){
    $res = $dbConn->query("SELECT * FROM user");
    while($row = $res->fetch_assoc()) $users[] = $row;
    foreach($users as $u){
        $r = $roleNames[$u['RoleID']] ?? $u['RoleID'];
        if(!isset($roleCounts[$r])) $roleCounts[$r]=0;
        $roleCounts[$r]++;
    }

} elseif($type==='activity'){
    $res = $dbConn->query("SELECT * FROM user WHERE LastActive IS NOT NULL");
    while($row = $res->fetch_assoc()){
        $last = new DateTime($row['LastActive']);
        $include = false;
        $key = '';

        if($time === 'today' && $last->format('Y-m-d') === $now->format('Y-m-d')){
            $include = true;
            $key = $last->format('H');
        } elseif($time === 'monthly' && $last->format('Y-m') === $now->format('Y-m')){
            $include = true;
            $key = $last->format('d');
        } elseif($time === 'yearly' && $last->format('Y') === $now->format('Y')){
            $include = true;
            $key = $last->format('m');
        }

        if($include){
            if(!isset($activityCounts[$key])) $activityCounts[$key] = 0;
            $activityCounts[$key]++;
            $activityData[] = $row;
        }
    }
    ksort($activityCounts);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>View Reports & Statistics - <?php echo $user['Username']?></title>
<link rel="stylesheet" href="styles.css">
<link rel="stylesheet" href="responsive.css">
<style>

.report-top-bar{
    display:flex;
    justify-content:space-between;
    align-items:flex-end;
    gap:20px;
    flex-wrap:wrap;
    margin-bottom:18px;
}

.filter-group{
    display:flex;
    gap:14px;
    flex-wrap:wrap;
    align-items:flex-end;
}

.filter-box{
    min-width:220px;
}

.filter-box label{
    display:block;
    margin-bottom:6px;
    font-weight:600;
    color:#333;
    font-size:14px;
}

.filter-box select{
    width:100%;
    padding:10px 12px;
    border:1px solid #d9d9d9;
    border-radius:12px;
    font-size:14px;
    background:#fff;
    outline:none;
    transition:0.25s ease;
}

.filter-box select:focus{
    border-color:#84B179;
    box-shadow:0 0 0 2px rgba(132,177,121,0.2);
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

.charts{
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 15px;
    margin-bottom: 20px;
}

.chart-container{
    width: 100%;
    max-width: 400px;
    height: 400px;
}

/* stats grid */
.chart-stats{
    width: 100%;
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(140px, 1fr));
    gap: 12px;
}

/* each stat card */
.chart-stats div{
    background: rgba(255,255,255,0.6);
    backdrop-filter: blur(10px);
    border-radius: 14px;
    padding: 10px 12px;
    font-size: 13px;
    font-weight: 500;
    color: #333;
    box-shadow: 0 4px 12px rgba(0,0,0,0.06);
}

.chart-stats small{
    color:#888;
    font-size:12px;
}

/* hover effect */
.chart-stats div:hover{
    transform: translateY(-2px);
    box-shadow: 0 6px 16px rgba(0,0,0,0.1);
}

.charts canvas{
    width: 100% !important;
    height: 100% !important;
}

.actions{
    margin: 5px;
    display: flex;
    justify-content: center;
    margin-top: 20px;
}
</style>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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
                <a href="SustainabilityChallenges.php">Sustainability Challenges</a>
                <a href="viewReport.php" class="active">View Report & Statistics</a>
                <a href="rewardsSystem.php">Rewards System</a>
                <a href="announcement.php">Announcement</a>
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

            <h1>View Report & Statistics</h1>

            <div class="top-bar report-top-bar">
                <div class="filter-group">
                    <div class="filter-box">
                        <label for="reportType">Report Type</label>
                        <select id="reportType" onchange="changeType()">
                            <option value="user" <?= $type === 'user' ? 'selected' : '' ?>>User Report</option>
                            <option value="activity" <?= $type === 'activity' ? 'selected' : '' ?>>Activity Report</option>
                        </select>
                    </div>

                    <?php if ($type === 'activity'): ?>
                    <div class="filter-box">
                        <label for="timeFilter">Time Filter</label>
                        <select id="timeFilter" onchange="changeTime()">
                            <option value="today" <?= $time === 'today' ? 'selected' : '' ?>>Today</option>
                            <option value="monthly" <?= $time === 'monthly' ? 'selected' : '' ?>>This Month</option>
                            <option value="yearly" <?= $time === 'yearly' ? 'selected' : '' ?>>This Year</option>
                        </select>
                    </div>
                    <?php endif; ?>
                </div>
            </div>

        <div class="charts">
            <div class="chart-container">
                <canvas id="reportChart"></canvas>
            </div>
            <div class="chart-stats" id="chartStats"></div>
        </div>

        <div class="stats">
            Total:
            <b>
                <?php
                if ($type === 'user') {
                    echo count($users);
                } else {
                    echo count($activityData);
                }?>
            </b>
        </div>

        <div class="table">
                <table class="table">
                    <thead>
                    <?php if($type==='user'): ?>
                        <tr><th>UserID</th><th>Username</th><th>Gender</th><th>DOB</th><th>Contact</th><th>Role</th></tr>
                    <?php elseif($type==='activity'): ?>
                        <tr><th>UserID</th><th>Username</th><th>LastActive</th><th>Role</th></tr>
                    <?php endif; ?>
                    </thead>
                    <tbody id="reportTable">
                    <?php if($type==='user'):?>
                        <?php foreach ($users as $u): ?>
                            <tr>
                                <td data-label="User ID"><?= htmlspecialchars($u['UserID']) ?></td>
                                <td data-label="Username"><?= htmlspecialchars($u['Username']) ?></td>
                                <td data-label="Gender"><?= htmlspecialchars($u['Gender']) ?></td>
                                <td data-label="DOB"><?= htmlspecialchars($u['DOB']) ?></td>
                                <td data-label="Contact"><?= htmlspecialchars($u['Contact']) ?></td>
                                <td data-label="Role ID"><?= htmlspecialchars($u['RoleID']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php elseif($type==='activity'):?>
                        <?php foreach ($activityData as $a): ?>
                            <tr>
                                <td data-label="User ID"><?= htmlspecialchars($a['UserID']) ?></td>
                                <td data-label="Username"><?= htmlspecialchars($a['Username']) ?></td>
                                <td data-label="Last Active"><?= htmlspecialchars($a['LastActive']) ?></td>
                                <td data-label="Role"><?= htmlspecialchars($roleNames[$a['RoleID']] ?? $a['RoleID']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                    </tbody>
                </table>
        </div>
            <div class="actions">
                <button class="btn" onclick="window.location.href='viewReport.php?type=<?= $type ?>&time=<?= $time ?>&download=1'">Download CSV</button>
            </div>
        </div>
    </div>

<script>
const chartStats = document.getElementById('chartStats');
const ctx = document.getElementById('reportChart').getContext('2d');
let chartInstance = null;

function renderChart(dataObj,title){
    if(chartInstance) chartInstance.destroy();
    chartInstance = new Chart(ctx,{
        type: 'pie',
        data: {
            labels: Object.keys(dataObj),
            datasets:[{
                data: Object.values(dataObj).map(v=>typeof v==='object'?v.count:v),
                backgroundColor: ["#5DADE2","#48C9B0","#AF7AC5","#F5B041","#EC7063","#73C6B6","#7FB3D5","#BB8FCE"]
            }]
        },
        options:{
            responsive:true,
            plugins:{
                legend:{position:'top'},
                title:{display:true,text:title},
                tooltip:{
                    callbacks:{
                        label:function(context){
                            const total = context.dataset.data.reduce((a,b)=>a+b,0);
                            const value = context.raw;
                            const percent = ((value/total)*100).toFixed(1);
                            return `${context.label}: ${value} (${percent}%)`;
                        }
                    }
                }
            }
        }
    });

chartStats.innerHTML = '';
    const total = Object.values(dataObj).reduce((a, b) => a + (typeof b === 'object' ? b.count : b), 0);
    
    for (const key in dataObj) {
        const value = typeof dataObj[key] === 'object' ? dataObj[key].count : dataObj[key];
        const percent = ((value / total) * 100).toFixed(1);

        // --- 处理 Label 逻辑 ---
        let label = key;
        const timeFilter = document.getElementById('timeFilter')?.value;

        if (timeFilter === 'today') {
            label = key + ":00"; // 变成 13:00, 04:00
        } else if (timeFilter === 'monthly') {
            label = "Day " + key; // 变成 Day 28
        } else if (timeFilter === 'yearly') {
            const months = ["Jan", "Feb", "Mar", "Apr", "May", "Jun", "Jul", "Aug", "Sep", "Oct", "Nov", "Dec"];
            label = months[parseInt(key) - 1] || "Month " + key;
        }

        const div = document.createElement('div');
        div.innerHTML = `
            <strong>${label}</strong><br>  <span>${value} User(s)</span><br>
            <small>${percent}%</small>
        `;
        chartStats.appendChild(div);
    }
}

<?php if($type==='user'): ?>
renderChart(<?= json_encode($roleCounts) ?>,'User Role Distribution');
<?php elseif($type==='activity'): ?>
renderChart(<?= json_encode($activityCounts) ?>,'Active Users Distribution');
<?php endif; ?>

function changeType(){
    const val = document.getElementById('reportType').value;
    window.location.href='viewReport.php?type='+val+'&time=<?= $time ?>';
}
function changeTime(){
    const val = document.getElementById('timeFilter').value;
    window.location.href='viewReport.php?type=<?= $type ?>&time='+val;
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