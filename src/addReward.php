<?php
include('conn.php');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $rewardId = trim($_POST['rewardId'] ?? '');
    $title = trim($_POST['title'] ?? '');
    $type = trim($_POST['type'] ?? '');
    $validity = $_POST['validity'] ?? '';
    $points = $_POST['points'] ?? '';

    if ($rewardId === '' || $title === '' || $type === '' || $points === '') {
        echo "Please fill in all fields.";
        exit();
    }

    if ($type !== "Merchandise" && $validity === '') {
        echo "Please enter validity.";
        exit();
    }

    $sql = "INSERT INTO reward (RewardID, Title, Type, Validity, Points) VALUES (?, ?, ?, ?, ?)";
    $stmt = $dbConn->prepare($sql);

    if (!$stmt) {
        echo "Prepare failed.";
        exit();
    }

    $stmt->bind_param("sssii", $rewardId, $title, $type, $validity, $points);

    if ($stmt->execute()) {
        echo "success";
    } else {
        echo "error";
    }

    $stmt->close();
    $dbConn->close();
} else {
    echo "Invalid request.";
}
?>