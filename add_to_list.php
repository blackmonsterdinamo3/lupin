<?php
session_start();

if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit();
}

$username = $_SESSION['username'];
$anime_folder = $_GET['anime'];
$list = $_GET['list']; // planned, favorites, dropped

$users = json_decode(file_get_contents('users.json'), true);

if (!isset($users[$username][$list])) {
    $users[$username][$list] = [];
}

if (!in_array($anime_folder, $users[$username][$list])) {
    $users[$username][$list][] = $anime_folder;
    file_put_contents('users.json', json_encode($users, JSON_PRETTY_PRINT));
}

header("Location: series.php?folder=$anime_folder");
exit();
?>