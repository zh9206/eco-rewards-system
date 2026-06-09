<?php
include('conn.php');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $id = $_POST['ChallengeID'] ?? '';
    $title = $_POST['Title'] ?? '';
    $description = $_POST['Description'] ?? '';
    $points = isset($_POST['Points']) ? intval($_POST['Points']) : null;

    if (empty($id) || empty($title) || empty($description) || $points === null) {
        echo "Please fill in all fields.";
        exit();
    }

    // Check Duplicate Title
    $stmtCheck = $dbConn->prepare("
        SELECT ChallengeID FROM challenge 
        WHERE Title = ? AND ChallengeID <> ?
    ");

    if (!$stmtCheck) {
        echo "Prepare failed: " . $dbConn->error;
        exit();
    }

    $stmtCheck->bind_param("ss", $title, $id);
    $stmtCheck->execute();
    $resCheck = $stmtCheck->get_result();

    if ($resCheck->num_rows > 0) {
        echo "Challenge title already exists!";
        exit();
    }

    $stmt = $dbConn->prepare("
        UPDATE challenge 
        SET Title = ?, Description = ?, Points = ?
        WHERE ChallengeID = ?
    ");

    if (!$stmt) {
        echo "Prepare failed: " . $dbConn->error;
        exit();
    }

    $stmt->bind_param("ssis", $title, $description, $points, $id);

    if ($stmt->execute()) {
        echo "success";
    } else {
        echo "Error: " . $stmt->error;
    }

    $stmtCheck->close();
    $stmt->close();
    $dbConn->close();
}
?>