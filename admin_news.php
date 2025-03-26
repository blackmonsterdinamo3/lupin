<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
include_once 'headeradmin.php';

if (!isset($_SESSION['username']) || !$_SESSION['is_admin']) {
    header("Location: index.php");
    exit();
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title']);
    $content = trim($_POST['content']);
    $send_to_telegram = isset($_POST['send_to_telegram']);

    // Загрузка изображения (необязательно)
    $image_name = null;
    if (!empty($_FILES['image']['name']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $image = $_FILES['image'];
        $image_name = uniqid() . '_' . $image['name'];
        if (!move_uploaded_file($image['tmp_name'], "uploads/news/$image_name")) {
            $error = "Ошибка при загрузке изображения на сервер.";
        }
    }

    // Загрузка видео (если есть)
    $video_name = null;
    $allowed_video_types = ['video/mp4', 'video/quicktime'];
    if (!empty($_FILES['video']['name']) && $_FILES['video']['error'] === UPLOAD_ERR_OK) {
        if (!in_array($_FILES['video']['type'], $allowed_video_types)) {
            $error = "Недопустимый тип видео. Разрешены только MP4.";
        } else {
            $video = $_FILES['video'];
            $video_name = uniqid() . '_' . $video['name'];
            if (!move_uploaded_file($video['tmp_name'], "uploads/news/$video_name")) {
                $error = "Ошибка при загрузке видео на сервер.";
            }
        }
    }

    // Отправка в Telegram
    if ($send_to_telegram && empty($error)) {
        $telegram_token = '7871265600:AAESPVv8llxLFzOS9mRmYNAqiAc7l2_F5sc';
        $chat_id = '-1002378092251';
        $message = "$title\n\n$content";

        // Функция для отправки файлов в Telegram
        function sendTelegramFile($url, $file_path, $file_type, $chat_id, $caption = '') {
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

            // Подготовка файла для отправки
            $file = new CURLFile($file_path, mime_content_type($file_path), basename($file_path));
            $post_data = [
                'chat_id' => $chat_id,
                $file_type => $file,
                'caption' => $caption,
            ];

            curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);
            $response = curl_exec($ch);
            curl_close($ch);
            return $response;
        }

        // Если есть изображение, отправляем его с текстом
        if ($image_name) {
            $image_url = "https://api.telegram.org/bot$telegram_token/sendPhoto";
            $image_response = sendTelegramFile($image_url, "uploads/news/$image_name", 'photo', $chat_id, $message);
            $image_response_data = json_decode($image_response, true);

            if (!$image_response_data['ok']) {
                $error = "Ошибка при отправке изображения в Telegram: " . $image_response_data['description'];
            } else {
                echo "Изображение с текстом успешно отправлено в Telegram.<br>";
            }
        }
        // Если есть видео, отправляем его с текстом
        elseif ($video_name) {
            $video_url = "https://api.telegram.org/bot$telegram_token/sendVideo";
            $video_response = sendTelegramFile($video_url, "uploads/news/$video_name", 'video', $chat_id, $message);
            $video_response_data = json_decode($video_response, true);

            if (!$video_response_data['ok']) {
                $error = "Ошибка при отправке видео в Telegram: " . $video_response_data['description'];
            } else {
                echo "Видео с текстом успешно отправлено в Telegram.<br>";
            }
        }
        // Если нет медиа, отправляем только текст
        else {
            $text_url = "https://api.telegram.org/bot$telegram_token/sendMessage";
            $text_data = [
                'chat_id' => $chat_id,
                'text' => $message,
            ];
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $text_url);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $text_data);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            $text_response = curl_exec($ch);
            curl_close($ch);

            $text_response_data = json_decode($text_response, true);
            if (!$text_response_data['ok']) {
                $error = "Ошибка при отправке текста в Telegram: " . $text_response_data['description'];
            } else {
                echo "Текст успешно отправлен в Telegram.<br>";
            }
        }
    }

    // Добавление новости в news.json
    if (empty($error)) {
        $news = json_decode(file_get_contents('news.json'), true);
        $new_news_item = [
            'id' => uniqid(),
            'title' => $title,
            'content' => $content,
            'image' => $image_name ? "uploads/news/$image_name" : null,
            'video' => $video_name ? "uploads/news/$video_name" : null,
            'date' => date('Y-m-d H:i:s'),
        ];
        $news[] = $new_news_item;
        file_put_contents('news.json', json_encode($news, JSON_PRETTY_PRINT));
        $error = "Новость успешно добавлена!";
    }
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Управление новостями</title>
    <link rel="stylesheet" href="css/adminnews.css">
</head>
<body>
    <div class="admin-panel">
        <h1>Управление новостями</h1>
<p>Отправляй либо Фото+текс либо видео+текст</p>
        <?php if ($error): ?>
            <p class="error"><?= $error ?></p>
        <?php endif; ?>
        <form method="POST" enctype="multipart/form-data">
            <input type="text" name="title" placeholder="Заголовок новости" required>
            <textarea name="content" placeholder="Текст новости" required></textarea>
            <label>Изображение (опционально):</label>
            <input type="file" name="image" accept="image/jpeg">
            <label>Видео (опционально, до 50 МБ):</label>
            <input type="file" name="video" accept="video/mp4">
            <label>
                <input type="checkbox" name="send_to_telegram"> Отправить в Telegram
            </label>
            <button type="submit">Добавить новость</button>
        </form>
    </div>
</body>
</html>