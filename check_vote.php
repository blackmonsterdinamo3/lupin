<?php
session_start();

if (!isset($_SESSION['username'])) {
    echo json_encode(['voted' => false]);
    exit();
}

$input = json_decode(file_get_contents('php://input'), true);
$anime_folder = $input['anime'];
$username = $input['username'];

$users = json_decode(file_get_contents('users.json'), true);

if (isset($users[$username]['rated'][$anime_folder])) {
    echo json_encode(['voted' => true]);
} else {
    echo json_encode(['voted