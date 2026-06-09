<?php
session_start();
include('conn.php');

if (!isset($_GET['submissionID'])) {
    die("Invalid request.");
}
$submissionID = $_GET['submissionID'];

// Get Current Submission
$stmt = $dbConn->prepare("SELECT * FROM challenge_submission WHERE SubmissionID = ? LIMIT 1");
$stmt->bind_param("s", $submissionID);
$stmt->execute();
$res = $stmt->get_result();

if (!$res || $res->num_rows == 0) {
    die("Submission not found.");
}

$current = $res->fetch_assoc();
$stmt->close();

$studentID   = $current['StudentID'];
$challengeID = $current['ChallengeID'];
$flagType    = $current['FlagType'];
$proof       = $current['Proof'];

$statusClass = strtolower($current['Status']);
$flagClass   = strtolower($current['FlagType']);

// Handle Action - Approve / Reject
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];
    $currentID = $_POST['submissionID'];

    if (isset($_SESSION['ModeratorID'])) {
        $moderatorID = $_SESSION['ModeratorID'];
    } elseif (isset($_SESSION['UserID'])) {
        $moderatorID = $_SESSION['UserID'];
    } else {
        $modStmt = $dbConn->prepare("SELECT UserID FROM user WHERE UserID LIKE 'U%' AND RoleID = 'R02' LIMIT 1");
        $modStmt->execute();
        $modRes = $modStmt->get_result();
        $moderatorID = ($modRes && $modRes->num_rows > 0) ? $modRes->fetch_assoc()['UserID'] : 'U001';
        $modStmt->close();
    }

    if ($action === 'approve') {
        // Approve Latest Submission
        $stmtApprove = $dbConn->prepare("
            UPDATE challenge_submission
            SET Status = 'Approved', FlagType = 'normal'
            WHERE SubmissionID = ?
        ");
        $stmtApprove->bind_param("s", $currentID);
        $stmtApprove->execute();
        $stmtApprove->close();

        $approveReview = "Approved. This is the latest valid submission.";
        $stmtVerify = $dbConn->prepare("
            INSERT INTO verification (ModeratorID, SubmissionID, Review)
            VALUES (?, ?, ?)
        ");
        $stmtVerify->bind_param("sss", $moderatorID, $currentID, $approveReview);
        $stmtVerify->execute();
        $stmtVerify->close();

        // Reject Other Submission
        $stmtOthers = $dbConn->prepare("
            SELECT SubmissionID
            FROM challenge_submission
            WHERE StudentID = ?
              AND ChallengeID = ?
              AND SubmissionID <> ?
              AND Status = 'Pending'
        ");
        $stmtOthers->bind_param("sss", $studentID, $challengeID, $currentID);
        $stmtOthers->execute();
        $resOthers = $stmtOthers->get_result();

        while ($other = $resOthers->fetch_assoc()) {
            $otherID = $other['SubmissionID'];

            $stmtRejectOld = $dbConn->prepare("
                UPDATE challenge_submission
                SET Status = 'Rejected', FlagType = 'duplicate'
                WHERE SubmissionID = ?
            ");
            $stmtRejectOld->bind_param("s", $otherID);
            $stmtRejectOld->execute();
            $stmtRejectOld->close();

            $note = "Duplicate of $currentID";
            $stmtOldVerify = $dbConn->prepare("
                INSERT INTO verification (ModeratorID, SubmissionID, Review)
                VALUES (?, ?, ?)
            ");
            $stmtOldVerify->bind_param("sss", $moderatorID, $otherID, $note);
            $stmtOldVerify->execute();
            $stmtOldVerify->close();
        }
        $stmtOthers->close();

        echo "<script>
            alert('Approve successful. Latest approved, others auto-rejected.');
            window.parent.location.reload();
        </script>";
        exit;
    }

    if ($action === 'reject') {
        // Reject All Submission
        $stmtGroup = $dbConn->prepare("
            SELECT SubmissionID
            FROM challenge_submission
            WHERE StudentID = ?
              AND ChallengeID = ?
              AND Status = 'Pending'
            ORDER BY Submission_Date ASC
        ");
        $stmtGroup->bind_param("ss", $studentID, $challengeID);
        $stmtGroup->execute();
        $resGroup = $stmtGroup->get_result();

        $allIds = [];
        while ($item = $resGroup->fetch_assoc()) {
            $allIds[] = $item['SubmissionID'];
        }
        $stmtGroup->close();

        foreach ($allIds as $idToReject) {
            $stmtReject = $dbConn->prepare("
                UPDATE challenge_submission
                SET Status = 'Rejected', FlagType = 'duplicate'
                WHERE SubmissionID = ?
            ");
            $stmtReject->bind_param("s", $idToReject);
            $stmtReject->execute();
            $stmtReject->close();

            $otherId = ($idToReject === $allIds[0] && isset($allIds[1])) ? $allIds[1] : $allIds[0];

            if ($idToReject === $otherId) {
                $rejectNote = "Rejected: Duplicate submission detected.";
            } else {
                $rejectNote = "Rejected: Duplicate content. Same as $otherId.";
            }

            $stmtRejectVerify = $dbConn->prepare("
                INSERT INTO verification (ModeratorID, SubmissionID, Review)
                VALUES (?, ?, ?)
            ");
            $stmtRejectVerify->bind_param("sss", $moderatorID, $idToReject, $rejectNote);
            $stmtRejectVerify->execute();
            $stmtRejectVerify->close();
        }

        echo "<script>
            alert('All related submissions rejected and linked.');
            window.parent.location.reload();
        </script>";
        exit;
    }
}

// Get Record For Comparison
$stmtDup = $dbConn->prepare("
    SELECT * FROM challenge_submission
    WHERE StudentID = ?
      AND ChallengeID = ?
    ORDER BY Submission_Date ASC
");
$stmtDup->bind_param("ss", $studentID, $challengeID);
$stmtDup->execute();
$duplicatesRes = $stmtDup->get_result();

$duplicates = [];
if ($duplicatesRes && $duplicatesRes->num_rows > 0) {
    while ($dRow = $duplicatesRes->fetch_assoc()) {
        $duplicates[] = $dRow;
    }
}
$stmtDup->close();

function formatDate24($str){
    return date('d/m/Y H:i:s', strtotime($str));
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Duplicate Pending Review</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<link rel="stylesheet" href="styles.css">
<link rel="stylesheet" href="moderator.css">
<link rel="stylesheet" href="function.css">
<style>
    body{
        margin:0;
        background:transparent;
        min-height:auto;
        display:block;
    }

    .modal-page{
        padding:24px;
        background:#fff;
        min-height:100vh;
        box-sizing:border-box;
    }

    .modal-topbar{
        display:flex;
        justify-content:space-between;
        align-items:center;
        gap:12px;
        margin-bottom:20px;
    }

    .modal-title{
        font-size:24px;
        font-weight:700;
        color:#2f5d3a;
    }

    .info-grid{
        display:grid;
        grid-template-columns:repeat(3, minmax(180px, 1fr));
        gap:16px;
        margin-bottom:20px;
    }

    .info-card{
        background:rgba(132,177,121,0.08);
        border:1px solid rgba(132,177,121,0.18);
        border-radius:14px;
        padding:14px 16px;
    }

    .info-card p{
        margin:6px 0;
        font-size:14px;
        color:#333;
    }

    .detail-card{
        background:#fff;
        border-radius:18px;
        box-shadow:0 8px 24px rgba(0,0,0,0.08);
        padding:20px;
    }

    .log-action{
        margin-bottom:16px;
        font-size:16px;
        color:#2f5d3a;
        font-weight:600;
    }

    .compare-grid{
        display:grid;
        grid-template-columns:repeat(2, minmax(260px, 1fr));
        gap:20px;
        margin-bottom:20px;
    }

    .compare-box{
        background:rgba(255,255,255,0.96);
        border:1px solid #e6e6e6;
        border-radius:16px;
        padding:16px;
        text-align:center;
    }

    .compare-box h4{
        margin-bottom:10px;
        color:#2f5d3a;
    }

    .upload-time{
        margin-bottom:12px;
        font-size:14px;
        color:#444;
    }

    .activity-image{
        width:100%;
        max-width:320px;
        border-radius:16px;
        object-fit:cover;
        box-shadow:0 6px 18px rgba(0,0,0,0.12);
        cursor:pointer;
    }

    .status-text{
        margin-top:20px;
        font-weight:600;
        font-size:15px;
        color:#333;
    }

    .action-row{
        margin-top:22px;
        display:flex;
        justify-content:space-between;
        align-items:center;
        gap:14px;
        flex-wrap:wrap;
    }

    .action-center{
        display:flex;
        gap:12px;
        flex-wrap:wrap;
    }

    .approve-btn,
    .reject-btn,
    .back-btn{
        border:none;
        border-radius:12px;
        padding:10px 18px;
        font-weight:600;
        cursor:pointer;
        transition:0.25s;
    }

    .approve-btn{
        background:#84B179;
        color:white;
    }

    .approve-btn:hover{
        background:#6ea861;
    }

    .reject-btn{
        background:#f8d7da;
        color:#721c24;
    }

    .reject-btn:hover{
        background:#f2c7cc;
    }

    @media (max-width: 435px){
        .info-grid,
        .compare-grid{
            grid-template-columns:1fr;
        }

        .action-row{
            flex-direction:column;
            align-items:stretch;
        }

        .action-center{
            justify-content:center;
        }
    }
</style>
</head>
<body>
<div class="modal-page">
    <div class="modal-topbar">
        <div class="modal-title">Duplicate Pending Review</div>
    </div>

    <div class="info-grid">
        <div class="info-card">
            <p><strong>Submission ID:</strong> <?= htmlspecialchars($current['SubmissionID']) ?></p>
            <p><strong>Student ID:</strong> <?= htmlspecialchars($current['StudentID']) ?></p>
        </div>
        <div class="info-card">
            <p>
                <strong>Status:</strong>
                <span class="status-badge status-<?= $statusClass ?>">
                    <?= htmlspecialchars(ucfirst($current['Status'])) ?>
                </span>
            </p>
            <p>
                <strong>Flag Type:</strong>
                <span class="flag-badge flag-<?= $flagClass ?>">
                    <?= htmlspecialchars(ucfirst($current['FlagType'])) ?>
                </span>
            </p>
        </div>
        <div class="info-card">
            <p><strong>Challenge ID:</strong> <?= htmlspecialchars($current['ChallengeID']) ?></p>
            <p><strong>Submission Date:</strong> <?= formatDate24($current['Submission_Date']) ?></p>
        </div>
    </div>

    <div class="detail-card">
        <p class="log-action">Duplicate submission review</p>

        <div class="compare-grid">
            <?php if (count($duplicates) > 0): ?>
                <?php
                    $i = 1;
                    foreach ($duplicates as $row):
                        $label = ($i == 1) ? "First time uploaded" : "Second time uploaded";
                ?>
                    <div class="compare-box">
                        <h4><?= $label ?></h4>
                        <p class="upload-time"><?= formatDate24($row['Submission_Date']) ?></p>
                        <img src="<?= !empty($row['Proof']) ? htmlspecialchars($row['Proof']) : 'uploads/placeholder.jpg' ?>"
                             class="activity-image"
                             alt="Duplicate Proof">
                    </div>
                <?php
                    $i++;
                    endforeach;
                ?>
            <?php else: ?>
                <div class="compare-box">
                    <p>Only one submission found.</p>
                </div>
            <?php endif; ?>
        </div>

        <p class="status-text">
            <?php
            if ($statusClass == "approved") {
                echo "This duplicate submission has been approved.";
            } elseif ($statusClass == "rejected") {
                echo "This duplicate submission has been rejected.";
            } else {
                echo "This submission is still pending review.";
            }
            ?>
        </p>

        <div class="action-row">
            <div class="action-center">
                <?php if ($statusClass === 'pending'): ?>
                    <form method="post" style="display:inline;">
                        <input type="hidden" name="action" value="approve">
                        <input type="hidden" name="submissionID" value="<?= htmlspecialchars($current['SubmissionID']) ?>">
                        <button type="submit" class="approve-btn" onclick="return confirm('Approve this duplicate submission?')">Approve</button>
                    </form>

                    <form method="post" style="display:inline;">
                        <input type="hidden" name="action" value="reject">
                        <input type="hidden" name="submissionID" value="<?= htmlspecialchars($current['SubmissionID']) ?>">
                        <button type="submit" class="reject-btn" onclick="return confirm('Reject all related duplicate submissions?')">Reject</button>
                    </form>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<div id="imageModal" class="image-modal" onclick="closeImageModal(event)">
    <div class="image-modal-content">
        <img id="modalImg" alt="Preview">
    </div>
</div>

<script>
const modal = document.getElementById("imageModal");
const modalImg = document.getElementById("modalImg");

document.querySelectorAll(".activity-image").forEach(img => {
    img.onclick = () => {
        modal.style.display = "flex";
        modalImg.src = img.src;
    };
});

function closeImageModal(event){
    if (!event || event.target === modal || event.target.classList.contains("close-btn")) {
        modal.style.display = "none";
        modalImg.src = "";
    }
}
</script>
</body>
</html>