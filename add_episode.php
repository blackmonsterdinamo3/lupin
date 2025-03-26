<?php
session_start();

if (!isset($_SESSION['username']) || !$_SESSION['is_admin']) {
    echo "Доступ запрещен!";
    exit();
}

$anime_folder = trim($_POST['anime_folder']);
$season_number = (int)$_POST['season_number'];
$episode_title = trim($_POST['episode_title']);
$video = $_FILES['video'];

// Проверка существования аниме и сезона
if (!file_exists("../anime/$anime_folder/data.json")) {
    echo "Аниме не найдено!";
    exit();
}

$data = json_decode(file_get_contents("../anime/$anime_folder/data.json"), true);

if (!isset($data['seasons'][$season_number - 1])) {
    echo "Сезон не найден!";
    exit();
}

// Создание папки для сезона, если она не существует
$season_folder = "../anime/$anime_folder/season$season_number";
if (!file_exists($season_folder)) {
    mkdir($season_folder, 0777, true);
}

// Загрузка видео
$video_name = uniqid() . '_' . $video['name'];
$upload_path = "$season_folder/$video_name";

if (move_uploaded_file($video['tmp_name'], $upload_path)) {
    // Добавление серии
    $data['seasons'][$season_number - 1]['episodes'][] = [
        'number' => count($data['seasons'][$season_number - 1]['episodes']) + 1,
        'title' => $episode_title,
        'video' => $video_name
    ];

    // Сохранение данных
    file_put_contents("../anime/$anime_folder/data.json", json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

    echo "Серия '$episode_title' успешно добавлена!";
} else {
    echo "Ошибка при загрузке видео!";
}
   // Сохранение данных с использованием JSON_UNESCAPED_UNICODE
    file_put_contents("../anime/$anime_folder/data.json", json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

    echo "Серия '$episode_title' успешно добавлена!";
} else {
    echo "Ошибка при загрузке видео!";
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Добавить серию</title>
    <link rel="stylesheet" href="css/admin.css">
</head>
<body>
    <header>
        <img src="logo.jpg" alt="Логотип" class="logo">
        <nav>
            <a href="index.php">Главная</a>
            <a href="logout.php">Выйти</a>
        </nav>
    </header>

    <div class="admin-panel">
        <h1>Добавить серию</h1>
        <?php if ($error): ?>
            <p class="error"><?= $error ?></p>
        <?php endif; ?>
        <form method="POST" enctype="multipart/form-data">
            <input type="text" name="anime_folder" placeholder="Название папки аниме" required>
            <input type="number" name="season_number" placeholder="Номер сезона" required>
            <input type="text" name="episode_title" placeholder="Название серии" required>
            <input type="file" name="video" accept="video/mp4" required>
            <button type="submit">Добавить серию</button>
        </form>
    </div>
</body>
</html><?php
session_start();

// Проверка, авторизован ли пользователь и является ли он администратором
if (!isset($_SESSION['username']) || !$_SESSION['is_admin']) {
    echo json_encode(['error' => 'Доступ запрещен']);
    exit();
}

$anime_folder = trim($_POST['anime_folder']);
$season_number = trim($_POST['season_number']);
$episode_title = trim($_POST['episode_title']);
$video = $_FILES['video'];

// Проверка существования аниме и сезона
if (!file_exists("anime/$anime_folder/data.json")) {
    echo json_encode(['error' => 'Аниме не найдено']);
    exit();
}

$data = json_decode(file_get_contents("anime/$anime_folder/data.json"), true);

if (!isset($data['seasons'][$season_number - 1])) {
    echo json_encode(['error' => 'Сезон не найден']);
    exit();
}

// Загрузка видео
$video_name = uniqid() . '_' . $video['name'];
$upload_path = "../anime/$anime_folder/season$season_number/$video_name";

if (!move_uploaded_file($video['tmp_name'], $upload_path)) {
    echo json_encode(['error' => 'Ошибка при загрузке видео']);
    exit();
}

// Добавление серии
$data['seasons'][$season_number - 1]['episodes'][] = [
    'number' => count($data['seasons'][$season_number - 1]['episodes']) + 1,
    'title' => $episode_title,
    'video' => $video_name
];

// Сохранение данных
file_put_contents("../anime/$anime_folder/data.json", json_encode($data, JSON_PRETTY_PRINT));

echo json_encode(['success' => 'Серия успешно добавлена']);
?><?php
session_start();

if (!isset($_SESSION['username']) || !$_SESSION['is_admin']) {
    echo "Доступ запрещен!";
    exit();
}

$anime_folder = trim($_POST['anime_folder']);
$season_number = (int)$_POST['season_number'];
$episode_title = trim($_POST['episode_title']);
$video = $_FILES['video'];

// Проверка существования аниме и сезона
if (!file_exists("../anime/$anime_folder/data.json")) {
    echo "Аниме не найдено!";
    exit();
}

$data = json_decode(file_get_contents("../anime/$anime_folder/data.json"), true);

if (!isset($data['seasons'][$season_number - 1])) {
    echo "Сезон не найден!";
    exit();
}

// Загрузка видео
$video_name = uniqid() . '_' . $video['name'];
$upload_path = "../anime/$anime_folder/season$season_number/$video_name";

if (move_uploaded_file($video['tmp_name'], $upload_path)) {
    // Добавление серии
    $data['seasons'][$season_number - 1]['episodes'][] = [
        'number' => count($data['seasons'][$season_number - 1]['episodes']) + 1,
        'title' => $episode_title,
        'video' => $video_name
    ];

    // Сохранение данных
    file_put_contents("../anime/$anime_folder/data.json", json_encode($data, JSON_PRETTY_PRINT));

    echo "Серия '$episode_title' успешно добавлена!";
} else {
    echo "Ошибка при загрузке видео!";
}
?>