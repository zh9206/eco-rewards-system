<?php
session_start();
include('conn.php');

if (!isset($_GET['submissionID'])) {
    die("Invalid request.");
}
$submissionID = $_GET['submissionID'];

// Get All Submission
$stmt = $dbConn->prepare("SELECT * FROM challenge_submission WHERE SubmissionID=?");
$stmt->bind_param("s", $submissionID);
$stmt->execute();
$result = $stmt->get_result();

if (!$result || $result->num_rows === 0) {
    die("Submission not found.");
}
$submission = $result->fetch_assoc();
$stmt->close();

// Get Moderators and Reviews
$rejectReason = "-";
$moderatorID = $submission['ModeratorID'] ?? '-';

$stmt2 = $dbConn->prepare("SELECT ModeratorID, Review FROM verification WHERE SubmissionID=?");
$stmt2->bind_param("s", $submissionID);
$stmt2->execute();
$res2 = $stmt2->get_result();

if ($res2 && $res2->num_rows > 0) {
    $verifyData = $res2->fetch_assoc();
    $rejectReason = !empty($verifyData['Review']) ? $verifyData['Review'] : "-";

    if ($moderatorID === '-' || empty($moderatorID)) {
        $moderatorID = $verifyData['ModeratorID'];
    }
}
$stmt2->close();

// Get Challenge Title
$challengeTitle = "Unknown";
if (!empty($submission['ChallengeID'])) {
    $stmt3 = $dbConn->prepare("SELECT Title FROM challenge WHERE ChallengeID=?");
    $stmt3->bind_param("s", $submission['ChallengeID']);
    $stmt3->execute();
    $res3 = $stmt3->get_result();

    if ($res3 && $res3->num_rows > 0) {
        $challengeTitle = $res3->fetch_assoc()['Title'];
    }
    $stmt3->close();
}

$statusClass = strtolower($submission['Status']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Activity Detail</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<link rel="stylesheet" href="styles.css">
<link rel="stylesheet" href="responsive.css">
<style>
    body{
        margin: 0;
        background: transparent;
        min-height: auto;
        display: block;
    }

    .modal-page{
        padding: 24px;
        background: #fff;
        min-height: 100vh;
        box-sizing: border-box;
    }

    .modal-topbar{
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 12px;
        margin-bottom: 20px;
    }

    .modal-title{
        font-size: 24px;
        font-weight: 700;
        color: #2f5d3a;
    }

    .info-grid{
        display: grid;
        grid-template-columns: repeat(2, minmax(220px, 1fr));
        gap: 16px;
        margin-bottom: 20px;
    }

    .info-card{
        background: rgba(132,177,121,0.08);
        border: 1px solid rgba(132,177,121,0.18);
        border-radius: 14px;
        padding: 14px 16px;
    }

    .info-card p{
        margin: 6px 0;
        color: #333;
        font-size: 14px;
    }

    .detail-card{
        background: #fff;
        border-radius: 18px;
        box-shadow: 0 8px 24px rgba(0,0,0,0.08);
        padding: 20px;
    }

    .log-action{
        margin-bottom: 16px;
        font-size: 16px;
        color: #2f5d3a;
    }

    .content{
        display: grid;
        grid-template-columns: minmax(280px, 360px) 1fr;
        gap: 24px;
        align-items: start;
    }

    .image-box{
        text-align: center;
    }

    .activity-image{
        width: 100%;
        max-width: 320px;
        border-radius: 16px;
        object-fit: cover;
        box-shadow: 0 6px 18px rgba(0,0,0,0.12);
    }

    .status-text{
        margin-top: 12px;
        font-weight: 600;
        font-size: 14px;
    }

    .status-text.approved{
        color: #155724;
    }

    .status-text.rejected{
        color: #721c24;
    }

    .detail-box{
        background: rgba(255,255,255,0.95);
        border: 1px solid #e6e6e6;
        border-radius: 16px;
        padding: 18px;
        min-height: 120px;
    }

    .detail-box p{
        margin: 0 0 10px;
        font-size: 14px;
        color: #333;
        line-height: 1.6;
    }

    .reason-title{
        margin-bottom: 10px;
        color: #2f5d3a;
    }

    @media (max-width: 440px){
        .content{
            grid-template-columns: 1fr;
        }

        .info-grid{
            grid-template-columns: 1fr;
        }

        .back-row{
            justify-content: center;
        }
    }
</style>
</head>
<body>
    <div class="modal-page">
        <div class="modal-topbar">
            <div class="modal-title">Submission Details</div>
        </div>

        <div class="info-grid">
            <div class="info-card">
                <p><strong>Submission ID:</strong> <?= htmlspecialchars($submission['SubmissionID']) ?></p>
                <p><strong>Student ID:</strong> <?= htmlspecialchars($submission['StudentID']) ?></p>
                <p><strong>Challenge ID:</strong> <?= htmlspecialchars($submission['ChallengeID']) ?></p>
            </div>

            <div class="info-card">
                <p>
                    <strong>Status:</strong>
                    <span class="status-badge status-<?= $statusClass ?>">
                        <?= htmlspecialchars($submission['Status']) ?>
                    </span>
                </p>
                <p><strong>Moderator ID:</strong> <?= htmlspecialchars($moderatorID) ?></p>

                <?php if (!empty($submission['Reviewed_Time'])): ?>
                    <?php if ($statusClass === 'approved'): ?>
                        <p><strong>Approved Time:</strong> <?= date("d/m/Y H:i:s", strtotime($submission['Reviewed_Time'])) ?></p>
                    <?php elseif ($statusClass === 'rejected'): ?>
                        <p><strong>Rejected Time:</strong> <?= date("d/m/Y H:i:s", strtotime($submission['Reviewed_Time'])) ?></p>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>

        <div class="detail-card">
            <p class="log-action"><strong>Log Action:</strong> <?= htmlspecialchars($challengeTitle) ?></p>

            <div class="content">
                <div class="image-box">
                    <img src="<?= !empty($submission['Proof']) ? htmlspecialchars($submission['Proof']) : 'placeholder.jpg' ?>"
                         class="activity-image"
                         alt="Activity Image">

                    <?php if ($statusClass === 'approved'): ?>
                        <p class="status-text approved">This activity has been approved.</p>
                    <?php elseif ($statusClass === 'rejected'): ?>
                        <p class="status-text rejected">This activity was rejected.</p>
                    <?php endif; ?>
                </div>

                <div class="detail-box">
                    <?php if ($statusClass === 'rejected'): ?>
                        <p class="reason-title"><strong>Feedback / Comment:</strong></p>
                        <p><?= htmlspecialchars($rejectReason) ?></p>
                    <?php elseif ($statusClass === 'approved'): ?>
                        <p class="reason-title"><strong>Review Result:</strong></p>
                        <p>This submission was reviewed and approved by the moderator.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</body>
</html>