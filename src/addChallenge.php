<?php
include('conn.php');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $challengeId = $_POST['ChallengeID'];
    $title = $_POST['Title'];
    $description = $_POST['Description'];
    $points = intval($_POST['Points']);

    if (empty($challengeId) || empty($title) || empty($description) || $points === null) {
        echo "Please fill in all fields.";
        exit();
    }

    // INSERT WITHOUT CATEGORY
    $stmt = $dbConn->prepare("
        INSERT INTO challenge (ChallengeID, Title, Description, Points)
        VALUES (?, ?, ?, ?)
    ");

    if (!$stmt) {
        die("Prepare failed: " . $dbConn->error);
    }

    $stmt->bind_param("sssi", $challengeId, $title, $description, $points);

    if ($stmt->execute()) {
        echo "success";
    } else {
        echo "Execute failed: " . $stmt->error;
    }

    $stmt->close();
    $dbConn->close();
}
?>