<?php
include("conn.php");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $announcementId = trim($_POST['AnnouncementID']);
    $title = trim($_POST['Title']);
    $description = trim($_POST['Description']);
    $startDate = trim($_POST['StartDate']);
    $endDate = trim($_POST['EndDate']);
    $adminId = trim($_POST['AdminID']);

    if ($announcementId === "" || $title === "" || $description === "" || $startDate === "" || $endDate === "" || $adminId === "") {
        echo "Please fill in all fields.";
        exit();
    }

    if ($startDate > $endDate) {
        echo "Start date cannot be later than end date.";
        exit();
    }

    // Check announcement exists
    $stmtCheck = $dbConn->prepare("SELECT AnnouncementID FROM announcement WHERE AnnouncementID = ?");
    $stmtCheck->bind_param("s", $announcementId);
    $stmtCheck->execute();
    $resultCheck = $stmtCheck->get_result();

    if ($resultCheck->num_rows === 0) {
        echo "Announcement not found.";
        $stmtCheck->close();
        exit();
    }
    $stmtCheck->close();

    // Check admin is valid
    $stmtAdmin = $dbConn->prepare("SELECT UserID FROM user WHERE UserID = ? AND RoleID = 'R03'");
    $stmtAdmin->bind_param("s", $adminId);
    $stmtAdmin->execute();
    $resultAdmin = $stmtAdmin->get_result();

    if ($resultAdmin->num_rows === 0) {
        echo "Selected admin is invalid.";
        $stmtAdmin->close();
        exit();
    }
    $stmtAdmin->close();

    // Calculate status
    $today = date('Y-m-d');
    if ($startDate > $today) {
        $status = 'Scheduled';
    } elseif ($startDate <= $today && $endDate >= $today) {
        $status = 'Active';
    } else {
        $status = 'Expired';
    }

    $stmt = $dbConn->prepare("
        UPDATE announcement
        SET Title = ?, Description = ?, StartDate = ?, EndDate = ?, AdminID = ?, Status = ?
        WHERE AnnouncementID = ?
    ");

    if (!$stmt) {
        echo "Prepare failed: " . $dbConn->error;
        exit();
    }

    $stmt->bind_param("sssssss", $title, $description, $startDate, $endDate, $adminId, $status, $announcementId);

    if ($stmt->execute()) {
        echo "success";
    } else {
        echo "Update failed: " . $stmt->error;
    }

    $stmt->close();
    $dbConn->close();

} else {
    echo "Invalid request.";
}
?>