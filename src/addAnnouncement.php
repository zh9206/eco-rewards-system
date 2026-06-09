<?php
include("conn.php");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $announcementId = trim($_POST['AnnouncementID'] ?? '');
    $title = trim($_POST['Title'] ?? '');
    $description = trim($_POST['Description'] ?? '');
    $startDate = trim($_POST['StartDate'] ?? '');
    $endDate = trim($_POST['EndDate'] ?? '');
    $adminId = trim($_POST['AdminID'] ?? '');

    if (empty($announcementId) || empty($title) || empty($description) || empty($startDate) || empty($endDate) || empty($adminId)) {
        echo "Please fill in all fields.";
        exit();
    }

    if ($endDate < $startDate) {
        echo "End date cannot be earlier than start date.";
        exit();
    }

    $stmt = $dbConn->prepare("INSERT INTO announcement (AnnouncementID, Title, Description, StartDate, EndDate, AdminID) VALUES (?, ?, ?, ?, ?, ?)");
    if (!$stmt) {
        echo "Prepare failed: " . $dbConn->error;
        $dbConn->close();
        exit();
    }

    $stmt->bind_param("ssssss", $announcementId, $title, $description, $startDate, $endDate, $adminId);

    if ($stmt->execute()) {
        echo "success";
    } else {
        echo "Execute failed: " . $stmt->error;
    }

    $stmt->close();
    $dbConn->close();

} else {
    echo "Invalid request.";
}
?>