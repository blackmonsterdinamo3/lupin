<?php // сохраняет в истории местное время пользователя
session_start();

if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit();
}

// Получаем данные из запроса
$data = json_decode(file_get_contents('php://input'), true);
$timeZone = $data['timeZone'];
$anime_folder = $data['animeFolder'];
$season = $data['season'];
$episode = $data['episode'];

// Устанавливаем временную зону пользователя
date_default_timezone_set($timeZone);

// Загружаем данные пользователей
$users = json_decode(file_get_contents('users.json'), true);
$username = $_SESSION['username'];

// Добавляем аниме в историю просмотров
if (isset($users[$username])) {
    $users[$username]['history'][$anime_folder] = [
        'folder' => $anime_folder,
        'season' => $season,
        'episode' => $episode,
        'timestamp' => time(), // Текущее время в формате Unix
        'date' => date('Y-m-d H:i:s') // Текущая дата и время по местному времени пользователя
    ];

    // Сохраняем обновленные данные
    file_put_contents('users.json', json_encode($users, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
}

echo json_encode(['success' => true]);
?>