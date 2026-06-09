<?php
session_start();
include 'conn.php';

if (!isset($_GET['submissionID'])) {
    die("Invalid request.");
}
$submissionID = $_GET['submissionID'];

/* Current submission */
$stmt = $dbConn->prepare("SELECT * FROM challenge_submission WHERE SubmissionID=? LIMIT 1");
$stmt->bind_param("s", $submissionID);
$stmt->execute();
$res = $stmt->get_result();

if (!$res || $res->num_rows == 0) {
    die("Submission not found.");
}

$current = $res->fetch_assoc();
$stmt->close();

$studentID = $current['StudentID'];
$challengeID = $current['ChallengeID'];
$flagType = $current['FlagType'];
$proof = $current['Proof'];

$statusClass = strtolower($current['Status']);
$flagClass = strtolower($current['FlagType']);

/* Matching suspicious records: same file hash but different student */
$proofHash = $current['ProofHash'] ?? '';

$stmtMatched = $dbConn->prepare("
    SELECT *
    FROM challenge_submission
    WHERE ProofHash = ?
      AND StudentID <> ?
      AND SubmissionID <> ?
    ORDER BY Submission_Date ASC
");
$stmtMatched->bind_param("sss", $proofHash, $studentID, $submissionID);
$stmtMatched->execute();
$resMatched = $stmtMatched->get_result();

$matchedSubmissions = [];
if ($resMatched && $resMatched->num_rows > 0) {
    while ($row = $resMatched->fetch_assoc()) {
        $matchedSubmissions[] = $row;
    }
}
$stmtMatched->close();

/* Approve / Reject */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = strtolower($_POST['action']);

    if (isset($_SESSION['ModeratorID'])) {
        $moderatorID = $_SESSION['ModeratorID'];
    } elseif (isset($_SESSION['UserID'])) {
        $moderatorID = $_SESSION['UserID'];
    } else {
        $checkMod = $dbConn->prepare("SELECT UserID FROM user WHERE UserID LIKE 'U%' AND RoleID = 'R02' LIMIT 1");
        $checkMod->execute();
        $modRes = $checkMod->get_result();
        $moderatorID = ($modRes && $modRes->num_rows > 0) ? $modRes->fetch_assoc()['UserID'] : 'U001';
        $checkMod->close();
    }

    if ($action === 'approve') {
        $stmtApprove = $dbConn->prepare("
            UPDATE challenge_submission
            SET Status='Approved', FlagType='normal'
            WHERE SubmissionID=?
        ");
        $stmtApprove->bind_param("s", $submissionID);
        $stmtApprove->execute();
        $stmtApprove->close();

        $review = "-";
        $stmtV = $dbConn->prepare("
            INSERT INTO verification (ModeratorID, SubmissionID, Review)
            VALUES (?, ?, ?)
        ");
        $stmtV->bind_param("sss", $moderatorID, $submissionID, $review);
        $stmtV->execute();
        $stmtV->close();
    } else {
        $stmtReject = $dbConn->prepare("
            UPDATE challenge_submission
            SET Status='Rejected', FlagType='suspicious'
            WHERE SubmissionID=?
        ");
        $stmtReject->bind_param("s", $submissionID);
        $stmtReject->execute();
        $stmtReject->close();

        $reason = "Duplicate image content detected.";
        $stmtV = $dbConn->prepare("
            INSERT INTO verification (ModeratorID, SubmissionID, Review)
            VALUES (?, ?, ?)
        ");
        $stmtV->bind_param("sss", $moderatorID, $submissionID, $reason);
        $stmtV->execute();
        $stmtV->close();
    }

    if (!empty($proof)) {
        $stmtReset = $dbConn->prepare("
            UPDATE challenge_submission
            SET FlagType='normal'
            WHERE Proof=? AND SubmissionID <> ?
        ");
        $stmtReset->bind_param("ss", $proof, $submissionID);
        $stmtReset->execute();
        $stmtReset->close();
    }

    echo "<script>
        alert('Review completed! All related records updated.');
        window.parent.location.reload();
    </script>";
    exit;
}

function formatDate24($str){
    return date('d/m/Y H:i:s', strtotime($str));
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Suspicious Submission - Pending Review</title>
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
    .reject-btn{
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

    @media (max-width: 768px){
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
        <div class="modal-title">Suspicious Submission - Pending Review</div>
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
        <p class="log-action">Suspicious submission review</p>

        <div class="compare-grid">
            <div class="compare-box">
                <h4>New Submission</h4>
                <p class="upload-time"><strong>Uploaded:</strong><br><?= formatDate24($current['Submission_Date']) ?></p>
                <?php if(!empty($current['Proof'])): ?>
                    <img src="<?= htmlspecialchars($current['Proof']) ?>" class="activity-image" alt="New Submission">
                <?php else: ?>
                    <p>No Proof Uploaded</p>
                <?php endif; ?>
            </div>

            <div class="compare-box">
                <h4>Matched Submission(s) From Other Student</h4>

                <?php if(!empty($matchedSubmissions)): ?>
                    <?php foreach($matchedSubmissions as $match): ?>
                        <p class="upload-time">
                            <strong>Student ID:</strong> <?= htmlspecialchars($match['StudentID']) ?><br>
                            <strong>Uploaded:</strong><br><?= formatDate24($match['Submission_Date']) ?>
                        </p>

                        <?php if(!empty($match['Proof'])): ?>
                            <img src="<?= htmlspecialchars($match['Proof']) ?>" class="activity-image" alt="Matched Submission">
                        <?php else: ?>
                            <p>No Proof Uploaded</p>
                        <?php endif; ?>

                        <hr style="margin:14px 0; border:0; border-top:1px solid #ddd;">
                    <?php endforeach; ?>
                <?php else: ?>
                    <p>No matched submission found.</p>
                <?php endif; ?>
            </div>
        </div>

        <div class="action-row">
            <div class="action-center">
                <?php if ($statusClass === 'pending'): ?>
                    <form method="post" style="display:inline;">
                        <button type="submit" name="action" value="approve" class="approve-btn" onclick="return confirm('Approve this suspicious submission?')">Approve</button>
                    </form>

                    <form method="post" style="display:inline;">
                        <button type="submit" name="action" value="reject" class="reject-btn" onclick="return confirm('Reject this suspicious submission?')">Reject</button>
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
    if (!event || event.target === modal || event.target.classList.contains("image-close-btn")) {
        modal.style.display = "none";
        modalImg.src = "";
    }
}
</script>
</body>
</html>