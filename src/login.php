<?php
session_start();

include("conn.php");

$login_id = $_POST['loginId'];
$password = trim($_POST['password']);
$roleSelected = $_POST['role'];

// Search User If Exists
$sql = "SELECT * FROM user WHERE UserID = ? AND RoleID = ?";
$stmt = $dbConn->prepare($sql);
$stmt->bind_param("ss", $login_id, $roleSelected);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 1) {

    $user = $result->fetch_assoc();

    // Verify Hashed Passwords
    if (password_verify($password, $user['Passwords'])) {

        // Store session
        $_SESSION['UserID'] = $user['UserID'];
        $_SESSION['Username'] = $user['Username'];
        $_SESSION['Role'] = $user['RoleID'];

        // Update Last Active
        $sqlUpdate = "UPDATE user SET LastActive = NOW() WHERE UserID = ?";
        $stmtUpdate = $dbConn->prepare($sqlUpdate);
        $stmtUpdate->bind_param("s", $user['UserID']);
        $stmtUpdate->execute();
        $stmtUpdate->close();

        // Redirect based on role
        switch ($user['RoleID']) {
            case "R01":
                header("Location: studentDashboard.php");
                exit();

            case "R02":
                header("Location: moderator.php");
                exit();

            case "R03":
                header("Location: admin.php");
                exit();

            default:
                echo "<script>
                        alert('Invalid role.');
                        window.location.href='index.html';
                      </script>";
                exit();
        }

    } else {
        echo "<script>
                alert('Invalid Password!');
                window.location.href='index.html';
              </script>";
    }

} else {
    echo "<script>
            alert('Invalid ID!');
            window.location.href='index.html';
          </script>";
}

$stmt->close();
$dbConn->close();
?>