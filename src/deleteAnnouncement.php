<?php
include("conn.php");
    if (!isset($_POST['announcementid'])) {
        echo "Missing Announcement ID.";
        exit();
    }

    $announcementId = trim($_POST['announcementid']);

    // Check announcement exists first
    $stmtCheck = $dbConn->prepare("SELECT AnnouncementID FROM announcement WHERE AnnouncementID = ?");
    if (!$stmtCheck) {
        echo "Prepare failed: " . $dbConn->error;
        exit();
    }

    $stmtCheck->bind_param("s", $announcementId);
    $stmtCheck->execute();
    $resultCheck = $stmtCheck->get_result();

    if ($resultCheck->num_rows === 0) {
        echo "Announcement not found.";
        $stmtCheck->close();
        $dbConn->close();
        exit();
    }
    $stmtCheck->close();

    // Delete record
    $stmtDelete = $dbConn->prepare("DELETE FROM announcement WHERE AnnouncementID = ?");
    if (!$stmtDelete) {
        echo "Prepare failed: " . $dbConn->error;
        $dbConn->close();
        exit();
    }

    $stmtDelete->bind_param("s", $announcementId);

    if ($stmtDelete->execute()) {
        echo "success";
    } else {
        echo "Delete failed: " . $stmtDelete->error;
    }

    $stmtDelete->close();
    $dbConn->close();
?>