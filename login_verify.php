<?php
session_start();

if (!isset($_POST['submit'])) {
    header('Location: login.php');
    exit;
}

require_once "./functions/dbconn.php";
require_once "./functions/dbfunc.php";

$name = trim($_POST['name']);
$pass = trim($_POST['pass']);
$loc = $_POST['loc'];

// Database authentication
$name = sanitize($conn, $name);
$pass = sanitize($conn, $pass);
$pass = sha1($pass);

$query = "SELECT * FROM users WHERE username = ? AND pass = ?";
$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, "ss", $name, $pass);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

if (!$result || mysqli_num_rows($result) === 0) {
    header('Location: login.php?msg=1');
    exit;
}

$user = mysqli_fetch_assoc($result);

if ($user && $user['active'] == 1) {
    $setupArray = mysqli_query($conn, "SELECT * FROM setup");
    while($row = mysqli_fetch_array($setupArray)){
        $setup[$row[0]] = $row[1];
    }

    $roleResult = getDataById($conn, "roles", $user['role']);
    $role = mysqli_fetch_assoc($roleResult);
    
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['user_role'] = $role['rname'];
    $_SESSION['user_name'] = $user['fname'];
    $_SESSION['user_access'] = explode(';', $role['acc_code']);

    if ($loc !== "Master") {
        if ($role['rname'] === "Admin") {
            $_SESSION["id"] = $role['rname'];
            $_SESSION["loc"] = sanitize($conn, $loc);
            $_SESSION["locname"] = $loc;
            $_SESSION["lib"] = $setup['cname'] ?? 'Unknown';
            header("Location: index.php");
        } elseif ($role['rname'] === "User") {
            $_SESSION["id"] = $role['rname'];
            $_SESSION["loc"] = sanitize($conn, $loc);
            $_SESSION["locname"] = $loc;
            $_SESSION["lib"] = $setup['cname'] ?? 'Unknown';
            header("Location: dash.php");
        }
    } elseif ($loc === "Master" && $role['rname'] === "Master") {
        $_SESSION["id"] = $role['rname'];
        $_SESSION["loc"] = "Master";
        $_SESSION["lib"] = "Master";
        header("Location: index.php");
    } else {
        header('Location: login.php?msg=1');
    }
} else {
    header('Location: login.php?msg=3');
}

mysqli_close($conn);
?>
