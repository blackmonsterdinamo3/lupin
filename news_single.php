<?php
include_once 'header.php';
$news = json_decode(file_get_contents('news.json'), true);
$id = $_GET['id'];
$news_item = null;

// Поиск новости по ID
foreach ($news as $item) {
    if ($item['id'] == $id) {
        $news_item = $item;
        break;
    }
}

if (!$news_item) {
    header("Location: news.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $news_item['title'] ?> - Lupin.su</title>
    <link rel="stylesheet" href="css/newssingle.css">
	<link rel="stylesheet" href="css/adblock.css">
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <!-- Подключение Fluid Player -->
    <link rel="stylesheet" href="https://cdn.fluidplayer.com/v3/current/fluidplayer.min.css" />
    <script src="https://cdn.fluidplayer.com/v3/current/fluidplayer.min.js"></script>
</head>
<body>
<div class="m-container">
            <div id="reklama">
    <div class="news-single-container">
        <div class="news-single">
            <h1><?= $news_item['title'] ?></h1>
            <div class="news-meta">
                <span class="news-date">Опубликовано: <?= $news_item['date'] ?></span>
            </div>
            <?php if (empty($news_item['image']) && !empty($news_item['video'])): ?>
                <!-- Fluid Player -->
                <video id="my-video" class="news-video" width="100%">
                    <source src="<?= $news_item['video'] ?>" type="video/mp4">
                    Ваш браузер не поддерживает видео.
                </video>
                <script>
                    // Инициализация Fluid Player
                    var myFP = fluidPlayer(
                        'my-video', // ID видеоэлемента
                        {
                            layoutControls: {
                                primaryColor: "#ff5722", // Основной цвет плеера
                                fillToContainer: true, // Растянуть плеер на весь контейнер
                                autoPlay: false, // Автовоспроизведение
                                mute: false, // Звук по умолчанию
                                keyboardControl: true, // Управление с клавиатуры
                                allowDownload: false, // Разрешить скачивание
                                posterImage: "<?= !empty($news_item['image']) ? $news_item['image'] : '' ?>" // Постер (если есть изображение)
                            },
                            vastOptions: {
                                adList: [
                                    // Пример настройки рекламы (опционально)
                                    {
                                        roll: 'preRoll', // Тип рекламы (preRoll, midRoll, postRoll)
                                        vastTag: 'https://www.example.com/vast.xml' // URL VAST-рекламы
                                    }
                                ]
                            }
                        }
                    );
                </script>
            <?php elseif (!empty($news_item['image'])): ?>
                <img src="<?= $news_item['image'] ?>" alt="<?= $news_item['title'] ?>" class="news-image">
            <?php endif; ?>
            <div class="news-description">
            </div>
            <div class="news-content">
                <p><?= $news_item['content'] ?></p>
            </div>
            <a href="news.php" class="back-link">
                <i class="fas fa-arrow-left"></i> Вернуться к списку новостей
            </a>
        </div>
    </div>
                       </div>
    </div>
            <!-- Футер -->
<footer class="site-footer">
    <div class="footer-content">
        <p>Вся информация, файлы и аудиовизуальные произведения предоставлены на сайте только для ознакомительного просмотра. Возрастное ограничение 18+</p>
        <p>&copy; Lupin.su 2024</p>
    </div>
</footer>
</body>
</html>