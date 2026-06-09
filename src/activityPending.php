<?php
session_start();
include('conn.php');

if (isset($_SESSION['ModeratorID'])) {
    $moderatorID = $_SESSION['ModeratorID'];
} else {
    $checkMod = $dbConn->query("SELECT UserID FROM user WHERE UserID LIKE 'U%' AND RoleID = 'R02' LIMIT 1");
    $moderatorID = ($checkMod && $checkMod->num_rows > 0) ? $checkMod->fetch_assoc()['UserID'] : 'U001';
}

if (!isset($_GET['submissionID'])) {
    die("Invalid request.");
}
$submissionID = $_GET['submissionID'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'approve') {
        $stmt = $dbConn->prepare("UPDATE challenge_submission SET Status='Approved' WHERE SubmissionID=?");
        $stmt->bind_param("s", $submissionID);
        $stmt->execute();
        $stmt->close();

        $reviewText = "-";
        $stmtV = $dbConn->prepare("INSERT INTO verification (ModeratorID, SubmissionID, Review) VALUES (?, ?, ?)");
        $stmtV->bind_param("sss", $moderatorID, $submissionID, $reviewText);
        $stmtV->execute();
        $stmtV->close();

        echo "<script>
                alert('Activity Approved!');
                window.parent.location.reload();
              </script>";
        exit;
    }

    if ($action === 'reject') {
        $rejectReason = $_POST['rejectReason'] ?? 'No reason provided';

        $stmt = $dbConn->prepare("UPDATE challenge_submission SET Status='Rejected' WHERE SubmissionID=?");
        $stmt->bind_param("s", $submissionID);
        $stmt->execute();
        $stmt->close();

        $stmtV = $dbConn->prepare("INSERT INTO verification (ModeratorID, SubmissionID, Review) VALUES (?, ?, ?)");
        $stmtV->bind_param("sss", $moderatorID, $submissionID, $rejectReason);
        $stmtV->execute();
        $stmtV->close();

        echo "<script>
                alert('Activity Rejected!');
                window.parent.location.reload();
              </script>";
        exit;
    }
}

$stmt = $dbConn->prepare("SELECT * FROM challenge_submission WHERE SubmissionID=?");
$stmt->bind_param("s", $submissionID);
$stmt->execute();
$result = $stmt->get_result();
if (!$result || $result->num_rows == 0) die("Submission not found.");
$submission = $result->fetch_assoc();
$stmt->close();

$challengeTitle = "Unknown";
$stmt2 = $dbConn->prepare("SELECT Title FROM challenge WHERE ChallengeID=?");
$stmt2->bind_param("s", $submission['ChallengeID']);
$stmt2->execute();
$res2 = $stmt2->get_result();
if ($res2 && $res2->num_rows > 0) {
    $challengeTitle = $res2->fetch_assoc()['Title'];
}
$stmt2->close();

$statusClass = strtolower($submission['Status']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Pending Activity Review</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="styles.css">
    <link rel="stylesheet" href="responsive.css">
    <style>
        body{
            margin:0;
            background: transparent;
            min-height: auto;
            display:block;
        }

        .modal-page{
            padding: 24px;
            background: #fff;
            min-height: 100vh;
            box-sizing: border-box;
        }

        .modal-topbar{
            display:flex;
            justify-content: space-between;
            align-items:center;
            gap:12px;
            margin-bottom:20px;
        }

        .modal-title{
            font-size: 24px;
            font-weight: 700;
            color: #2f5d3a;
        }

        .info-grid{
            display:grid;
            grid-template-columns: repeat(2, minmax(220px, 1fr));
            gap:16px;
            margin-bottom:20px;
        }

        .info-card{
            background: rgba(132,177,121,0.08);
            border: 1px solid rgba(132,177,121,0.18);
            border-radius: 14px;
            padding: 14px 16px;
        }

        .info-card p{
            margin: 6px 0;
            color:#333;
            font-size:14px;
        }

        .review-card{
            background:#fff;
            border-radius:18px;
            box-shadow: 0 8px 24px rgba(0,0,0,0.08);
            padding:20px;
        }

        .log-action{
            margin-bottom:16px;
            font-size:16px;
            color:#2f5d3a;
        }

        .content{
            display:grid;
            grid-template-columns: minmax(280px, 360px) 1fr;
            gap:24px;
            align-items:start;
        }

        .image-box{
            text-align:center;
        }

        .activity-image{
            width:100%;
            max-width:320px;
            border-radius:16px;
            object-fit:cover;
            box-shadow: 0 6px 18px rgba(0,0,0,0.12);
        }

        .action-buttons{
            margin-top:14px;
            display:flex;
            gap:10px;
            justify-content:center;
            flex-wrap:wrap;
        }

        .approve-btn,
        .reject-btn,
        .done-btn{
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

        .done-btn{
            background:#84B179;
            color:white;
            margin-top:14px;
        }

        .done-btn:disabled{
            background:#ccc;
            cursor:not-allowed;
        }

        .feedback-box{
            background: rgba(255,255,255,0.95);
            border:1px solid #e6e6e6;
            border-radius:16px;
            padding:18px;
        }

        .reason-title,
        .feedback-title{
            margin-bottom:10px;
        }

        .reject-options{
            display:flex;
            flex-direction:column;
            gap:12px;
        }

        .reject-card{
            display:flex;
            align-items:flex-start;
            gap:10px;
            padding:12px 14px;
            border:1px solid #ddd;
            border-radius:12px;
            cursor:pointer;
            transition:0.2s;
        }

        .reject-card:hover{
            background:#fafafa;
            border-color:#84B179;
        }

        .card-text{
            font-size:14px;
            color:#333;
        }

        @media (max-width: 768px){
            .content{
                grid-template-columns: 1fr;
            }

            .info-grid{
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="modal-page">
        <div class="modal-topbar">
            <div class="modal-title">Pending Activity Review</div>
        </div>

        <div class="info-grid">
            <div class="info-card">
                <p><strong>Submission ID:</strong> <?= htmlspecialchars($submission['SubmissionID']) ?></p>
                <p><strong>Student ID:</strong> <?= htmlspecialchars($submission['StudentID']) ?></p>
                <p><strong>Challenge ID:</strong> <?= htmlspecialchars($submission['ChallengeID']) ?></p>
            </div>

            <div class="info-card">
                <p><strong>Submission Date:</strong> <?= date("d/m/Y H:i:s", strtotime($submission['Submission_Date'])) ?></p>
                <p>
                    <strong>Status:</strong>
                    <span class="status-badge status-<?= $statusClass ?>">
                        <?= htmlspecialchars($submission['Status']) ?>
                    </span>
                </p>
            </div>
        </div>

        <div class="review-card">
            <p class="log-action"><strong>Log Action:</strong> <?= htmlspecialchars($challengeTitle) ?></p>

            <div class="content">
                <div class="image-box">
                    <img class="activity-image"
                         src="<?= !empty($submission['Proof']) ? htmlspecialchars($submission['Proof']) : 'placeholder.jpg' ?>"
                         alt="Activity Photo">

                    <?php if ($submission['Status'] === 'Pending'): ?>
                        <div id="actionButtons" class="action-buttons">
                            <form id="approveForm" method="post" style="display:inline-block;">
                                <input type="hidden" name="action" value="approve">
                                <button type="button" id="approveBtn" class="approve-btn">Approve</button>
                            </form>

                            <button type="button" id="rejectBtn" class="reject-btn">Reject</button>
                        </div>
                    <?php endif; ?>
                </div>

                <?php if ($submission['Status'] === 'Pending'): ?>
                    <div class="feedback-box" id="rejectFeedbackBox" style="display:none;">
                        <form id="rejectForm" method="post">
                            <input type="hidden" name="action" value="reject">

                            <div class="reject-options">
                                <p class="reason-title"><strong>What is the reason for rejection?</strong></p>
                                <p class="feedback-title"><strong>Feedback / Comment <span class="required">*</span></strong></p>

                                <label class="reject-card">
                                    <input type="radio" name="rejectReason" value="Photo not match with the log action.">
                                    <span class="card-text">Photo not match with the log action.</span>
                                </label>

                                <label class="reject-card">
                                    <input type="radio" name="rejectReason" value="Prohibited or inappropriate content.">
                                    <span class="card-text">Prohibited or inappropriate content.</span>
                                </label>

                                <label class="reject-card">
                                    <input type="radio" name="rejectReason" value="The uploaded photo is unclear.">
                                    <span class="card-text">The uploaded photo is unclear.</span>
                                </label>

                                <button type="submit" class="done-btn" id="doneRejectBtn" disabled>Done</button>
                            </div>
                        </form>
                    </div>
                <?php else: ?>
                    <div class="feedback-box">
                        <p>This submission has already been reviewed.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script>
        const approveBtn = document.getElementById('approveBtn');
        const approveForm = document.getElementById('approveForm');
        const rejectBtn = document.getElementById('rejectBtn');
        const rejectFeedbackBox = document.getElementById('rejectFeedbackBox');
        const doneRejectBtn = document.getElementById('doneRejectBtn');
        const rejectRadios = document.querySelectorAll('input[name="rejectReason"]');
        const actionButtons = document.getElementById('actionButtons');

        if (approveBtn && approveForm) {
            approveBtn.onclick = () => {
                if (confirm("Approve this activity?")) {
                    approveForm.submit();
                }
            };
        }

        if (rejectBtn && rejectFeedbackBox && actionButtons) {
            rejectBtn.onclick = () => {
                rejectFeedbackBox.style.display = "block";
                actionButtons.style.display = "none";
            };
        }

        rejectRadios.forEach(radio => {
            radio.onchange = () => {
                doneRejectBtn.disabled = false;
            };
        });
    </script>
</body>
</html>