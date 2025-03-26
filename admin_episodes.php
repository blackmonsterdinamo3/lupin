<?php
session_start();
include_once 'headeradmin.php';

// Включение отображения ошибок
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Проверка, авторизован ли пользователь и является ли он администратором
if (!isset($_SESSION['username']) || !$_SESSION['is_admin']) {
    header("Location: index.php");
    exit();
}

// Проверка наличия параметров folder и season
if (!isset($_GET['folder']) || !isset($_GET['season'])) {
    header("Location: admin.php");
    exit();
}

// Декодирование названия папки
$folder = urldecode(basename($_GET['folder'])); // Защита от path traversal
$season_number = (int)$_GET['season']; // Приведение к числу
$data_file = "anime/$folder/data.json";

// Проверка существования файла данных
if (!file_exists($data_file)) {
    die("Файл данных не найден: " . htmlspecialchars($data_file));
}

// Загрузка данных аниме
$anime_data = json_decode(file_get_contents($data_file), true);
if (!$anime_data) {
    die("Ошибка при чтении файла данных.");
}

// Проверка существования сезона
if (!isset($anime_data['seasons'][$season_number - 1])) {
    header("Location: admin_seasons.php?folder=" . urlencode($folder));
    exit();
}

$season = $anime_data['seasons'][$season_number - 1];
$episodes = $season['episodes'];
$error = '';

// Обработка добавления новой серии
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $episode_title = trim($_POST['episode_title']);
    $video = $_FILES['video'];
    $video_nodub = $_FILES['video_nodub']; // Видео без озвучки

    // Проверка на ошибки загрузки файла с озвучкой
    if ($video['error'] !== UPLOAD_ERR_OK) {
        $error = "Ошибка загрузки файла с озвучкой: " . $video['error'];
    } else {
        // Проверка формата файла с озвучкой
        $file_type = mime_content_type($video['tmp_name']);
        if (strpos($file_type, 'video/mp4') === false) {
            $error = "Файл с озвучкой должен быть в формате MP4!";
        } else {
            // Создание уникального имени файла с озвучкой
            $video_name = uniqid() . '_' . basename($video['name']);
            $upload_dir = "anime/$folder/season$season_number/";

            // Создание папки для сезона, если она не существует
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }

            // Перемещение файла с озвучкой
            if (move_uploaded_file($video['tmp_name'], $upload_dir . $video_name)) {
                // Обработка видео без озвучки, если оно загружено
                $video_nodub_name = null;
                if ($video_nodub['error'] === UPLOAD_ERR_OK) {
                    $file_type_nodub = mime_content_type($video_nodub['tmp_name']);
                    if (strpos($file_type_nodub, 'video/mp4') === false) {
                        $error = "Файл без озвучки должен быть в формате MP4!";
                    } else {
                        $video_nodub_name = uniqid() . '_' . basename($video_nodub['name']);
                        $upload_dir_nodub = $upload_dir . "nodub/";

                        // Создание папки для версии без озвучки, если она не существует
                        if (!is_dir($upload_dir_nodub)) {
                            mkdir($upload_dir_nodub, 0777, true);
                        }

                        // Перемещение файла без озвучки
                        if (!move_uploaded_file($video_nodub['tmp_name'], $upload_dir_nodub . $video_nodub_name)) {
                            $error = "Ошибка перемещения файла без озвучки.";
                        }
                    }
                }

                // Добавление серии в data.json с временем создания
                $anime_data['seasons'][$season_number - 1]['episodes'][] = [
                    'number' => count($episodes) + 1,
                    'title' => $episode_title,
                    'video' => $video_name, // Видео с озвучкой
                    'video_nodub' => $video_nodub_name, // Видео без озвучки (может быть null)
                    'date_added' => date("Y-m-d H:i:s") // Добавляем текущую дату и время
                ];

                // Сохраняем данные с отключением экранирования Unicode и слэшей
                if (file_put_contents($data_file, json_encode($anime_data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT))) {
                    $error = "Серия '$episode_title' успешно добавлена!";
                } else {
                    $error = "Ошибка при сохранении данных!";
                }
            } else {
                $error = "Ошибка перемещения файла с озвучкой.";
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Управление сериями - <?= htmlspecialchars($season['title']) ?></title>
    <link rel="stylesheet" href="css/admin.css">
    <style>
    #progressBar {
        width: 100%;
        height: 5px;
        background-color: #f1f1f1;
        margin-top: 10px;
        border-radius: 5px;
        overflow: hidden;
    }

    #progressBar div {
        height: 100%;
        background-color: #3498db;
        width: 0%;
        transition: width 0.3s ease;
    }
    </style>
</head>
<body>

    <div class="admin-panel">
        <h1>Управление сериями - <?= htmlspecialchars($season['title']) ?></h1>
        <?php if ($error): ?>
            <p class="error"><?= htmlspecialchars($error) ?></p>
        <?php endif; ?>

        <!-- Список серий -->
        <h2>Серии</h2>
        <div class="episode-list">
            <?php foreach ($episodes as $episode): ?>
                <div class="episode-item">
                    <h3><?= htmlspecialchars($episode['title']) ?></h3>
                    <p>Добавлено: <?= htmlspecialchars($episode['date_added'] ?? 'Дата не указана') ?></p>
                    <p>Тип: <?= isset($episode['video_nodub']) ? 'С озвучкой и без' : 'Только с озвучкой' ?></p>
                    <video controls width="320" height="240">
                        <source src="anime/<?= htmlspecialchars($folder) ?>/season<?= htmlspecialchars($season_number) ?>/<?= htmlspecialchars($episode['video']) ?>" type="video/mp4">
                        <?php if (isset($episode['video_nodub'])): ?>
                            <source src="anime/<?= htmlspecialchars($folder) ?>/season<?= htmlspecialchars($season_number) ?>/nodub/<?= htmlspecialchars($episode['video_nodub']) ?>" type="video/mp4">
                        <?php endif; ?>
                    </video>
                </div>
            <?php endforeach; ?>
        </div>

        <!-- Форма добавления серии -->
			<h2>Добавить серию</h2>
				<form method="POST" enctype="multipart/form-data" id="uploadForm">
   					 <input type="text" name="episode_title" placeholder="Название серии" required>
    				<input type="file" name="video" accept="video/mp4" required>
    
    				<!-- Добавленная надпись и поле для видео без озвучки -->
    				<label for="video_nodub">Видео без озвучки:</label>
    				<input type="file" name="video_nodub" id="video_nodub" accept="video/mp4">
    
    				<button type="submit">Добавить серию</button>
    				<div id="progressBar"><div></div></div>
			</form>

    <script>
    document.getElementById('uploadForm').addEventListener('submit', function (e) {
        e.preventDefault(); // Остановить стандартную отправку формы

        const form = e.target;
        const formData = new FormData(form); // Создать объект FormData
        const xhr = new XMLHttpRequest(); // Создать объект XMLHttpRequest

        // Элементы для отображения прогресса
        const progressBarInner = document.querySelector('#progressBar div');

        // Отслеживание прогресса загрузки
        xhr.upload.addEventListener('progress', function (event) {
            if (event.lengthComputable) {
                const percentComplete = (event.loaded / event.total) * 100;
                progressBarInner.style.width = percentComplete + '%';
            }
        });

        // Обработка завершения загрузки
        xhr.addEventListener('load', function () {
            if (xhr.status === 200) {
                alert('Файл успешно загружен!');
                location.reload(); // Перезагрузить страницу
            } else {
                alert('Ошибка загрузки файла: ' . xhr.statusText);
            }
        });

        // Обработка ошибок
        xhr.addEventListener('error', function () {
            alert('Ошибка загрузки файла.');
        });

        // Отправить запрос
        xhr.open('POST', form.action, true);
        xhr.send(formData);
    });
    </script>
</body>
</html>