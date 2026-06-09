<?php
session_start();
include 'conn.php';

if (!isset($_GET['submissionID'])) {
    die("Invalid request.");
}

$submissionID = $_GET['submissionID'];

/* Current suspicious submission */
$stmt = $dbConn->prepare("SELECT * FROM challenge_submission WHERE SubmissionID=? LIMIT 1");
$stmt->bind_param("s", $submissionID);
$stmt->execute();
$result = $stmt->get_result();

if (!$result || $result->num_rows == 0) {
    die("Submission not found.");
}

$current = $result->fetch_assoc();
$stmt->close();

$studentID = $current['StudentID'];
$currentProof = $current['Proof'];

/* Find earliest approved submission with similar proof */
$stmtApproved = $dbConn->prepare("
    SELECT * FROM challenge_submission
    WHERE StudentID=?
      AND Status='Approved'
      AND Proof LIKE CONCAT('%', ?, '%')
      AND SubmissionID <> ?
    ORDER BY Submission_Date ASC
    LIMIT 1
");
$stmtApproved->bind_param("sss", $studentID, $currentProof, $submissionID);
$stmtApproved->execute();
$resApproved = $stmtApproved->get_result();
$approved = $resApproved && $resApproved->num_rows > 0 ? $resApproved->fetch_assoc() : null;
$stmtApproved->close();

function formatDate24($str){
    return date('d/m/Y H:i:s', strtotime($str));
}

$statusClass = strtolower($current['Status']);
$flagClass = strtolower($current['FlagType']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Suspicious Submission Detail</title>
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

    .note-box{
        background:rgba(255,255,255,0.95);
        border:1px solid #e6e6e6;
        border-radius:16px;
        padding:18px;
        line-height:1.6;
        color:#333;
    }

    @media (max-width: 768px){
        .info-grid,
        .compare-grid{
            grid-template-columns:1fr;
        }
    }
</style>
</head>
<body>
<div class="modal-page">
    <div class="modal-topbar">
        <div class="modal-title">Suspicious Submission Details</div>
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
                <h4>Approved Activity</h4>
                <?php if($approved): ?>
                    <p class="upload-time"><strong>Uploaded:</strong><br><?= formatDate24($approved['Submission_Date']) ?></p>
                    <?php if(!empty($approved['Proof'])): ?>
                        <img src="<?= htmlspecialchars($approved['Proof']) ?>" class="activity-image" alt="Approved Activity">
                    <?php else: ?>
                        <p>No Proof Uploaded</p>
                    <?php endif; ?>
                <?php else: ?>
                    <p>No approved activity found</p>
                <?php endif; ?>
            </div>
        </div>

        <div class="note-box">
            <?php
            if ($statusClass == "approved") {
                echo "<strong>This suspicious submission has been approved.</strong>";
            } elseif ($statusClass == "rejected") {
                echo "<strong>This suspicious submission has been rejected.</strong>";
            } else {
                echo "<strong>This submission is still pending review.</strong>";
            }
            ?>
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