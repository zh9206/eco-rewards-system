<?php
session_start();
include("conn.php");

if (!isset($_SESSION['UserID'])) {
    die("User not logged in.");
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    die("Invalid request.");
}

$studentID   = $_SESSION['UserID'];
$challengeID = $_POST['challengeID'] ?? '';

if (empty($challengeID)) {
    die("Challenge ID is required.");
}

if (!isset($_FILES['proofFile'])) {
    die("No file uploaded.");
}

// File Upload Validation
$uploadFolder = "proof/";

if (!is_dir($uploadFolder)) {
    mkdir($uploadFolder, 0777, true);
}

$fileName  = $_FILES['proofFile']['name'];
$fileTmp   = $_FILES['proofFile']['tmp_name'];
$fileSize  = $_FILES['proofFile']['size'];
$fileError = $_FILES['proofFile']['error'];

// Only Allow Images
$allowedTypes = ['jpg', 'jpeg', 'png'];
$fileExt = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

if (!in_array($fileExt, $allowedTypes)) {
    die("Invalid file type. Only JPG, JPEG, PNG are allowed.");
}

if ($fileError !== 0) {
    die("Upload error.");
}

if ($fileSize > 100000000) { // 100MB
    die("File too large.");
}

// Generate Random File Name
$newFileName = time() . "_" . rand(1000, 9999) . "." . $fileExt;
$targetPath = $uploadFolder . $newFileName;

// Move Uploaded Proof File
if (!move_uploaded_file($fileTmp, $targetPath)) {
    die("Failed to upload file.");
}

// Generate Proof Hash
$proofHash = hash_file('sha256', $targetPath);

if ($proofHash === false) {
    unlink($targetPath);
    die("Failed to generate file hash.");
}

// Detect Flag Type
$flagType = 'normal';

// Duplicate: same student + same challenge + same file hash
$stmtDup = $dbConn->prepare("
    SELECT SubmissionID
    FROM challenge_submission
    WHERE StudentID = ?
      AND ChallengeID = ?
      AND ProofHash = ?
");
$stmtDup->bind_param("sss", $studentID, $challengeID, $proofHash);
$stmtDup->execute();
$dupResult = $stmtDup->get_result();

if ($dupResult && $dupResult->num_rows > 0) {
    $flagType = 'duplicate';

    while ($row = $dupResult->fetch_assoc()) {
        $oldID = $row['SubmissionID'];

        $stmtUpdateOldDup = $dbConn->prepare("
            UPDATE challenge_submission
            SET FlagType = 'duplicate'
            WHERE SubmissionID = ?
        ");
        $stmtUpdateOldDup->bind_param("s", $oldID);
        $stmtUpdateOldDup->execute();
        $stmtUpdateOldDup->close();
    }
} else {
    // Suspicious: same file hash but different student
    $stmtSus = $dbConn->prepare("
        SELECT SubmissionID
        FROM challenge_submission
        WHERE ProofHash = ?
          AND StudentID <> ?
    ");
    $stmtSus->bind_param("ss", $proofHash, $studentID);
    $stmtSus->execute();
    $susResult = $stmtSus->get_result();

    if ($susResult && $susResult->num_rows > 0) {
        $flagType = 'suspicious';

        while ($row = $susResult->fetch_assoc()) {
            $oldID = $row['SubmissionID'];

            $stmtUpdateOldSus = $dbConn->prepare("
                UPDATE challenge_submission
                SET FlagType = 'suspicious'
                WHERE SubmissionID = ?
                  AND FlagType = 'normal'
            ");
            $stmtUpdateOldSus->bind_param("s", $oldID);
            $stmtUpdateOldSus->execute();
            $stmtUpdateOldSus->close();
        }
    }

    if (isset($stmtSus)) {
        $stmtSus->close();
    }
}
$stmtDup->close();

// Generate Submission ID
$sqlID = "SELECT SubmissionID FROM challenge_submission ORDER BY SubmissionID DESC LIMIT 1";
$resultID = $dbConn->query($sqlID);

if ($resultID && $resultID->num_rows > 0) {
    $row = $resultID->fetch_assoc();
    $lastID = $row['SubmissionID'];
    $num = intval(substr($lastID, 1));
    $num++;
    $newSubmissionID = "S" . str_pad($num, 3, "0", STR_PAD_LEFT);
} else {
    $newSubmissionID = "S001";
}

// Insert New Submission
$sql = "
    INSERT INTO challenge_submission
    (SubmissionID, Submission_Date, Proof, ProofHash, Status, FlagType, StudentID, ChallengeID)
    VALUES (?, NOW(), ?, ?, 'Pending', ?, ?, ?)
";

$stmt = $dbConn->prepare($sql);
$stmt->bind_param("ssssss", $newSubmissionID, $targetPath, $proofHash, $flagType, $studentID, $challengeID);

if ($stmt->execute()) {
    echo "<script>
        alert('Submission successful!\\nSubmission ID: {$newSubmissionID}\\nFlag Type: {$flagType}');
        window.location.href='studentDashboard.php';
    </script>";
} else {
    if (file_exists($targetPath)) {
        unlink($targetPath);
    }
    echo "Database error: " . $stmt->error;
}

$stmt->close();
$dbConn->close();
?>