<?php
include_once 'header.php';
session_start();

// Загрузка новостей из файла
$news = json_decode(file_get_contents('news.json'), true);

// Сортировка новостей по дате (от новых к старым)
usort($news, function ($a, $b) {
    return strtotime($b['date']) - strtotime($a['date']);
});
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Новости - Lupin.su</title>
    <link rel="stylesheet" href="css/news.css">
	<link rel="stylesheet" href="css/adblock.css">
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <!-- Подключение Fluid Player -->
    <link rel="stylesheet" href="https://cdn.fluidplayer.com/v3/current/fluidplayer.min.css" />
    <script src="https://cdn.fluidplayer.com/v3/current/fluidplayer.min.js"></script>
</head>
<body>
    <h1>Новости</h1>
    <h2>Все самое интересное из мира аниме</h2>
<div class="wrapper">
    <div class="m-container">
        <div id="reklama">
            <p>Спасибо!</p>
             <p>В видео нет встроенной рекламы!</p>
    <div class="news-container">
        <?php foreach ($news as $item): ?>
            <div class="news-card">
                <a href="news_single.php?id=<?= $item['id'] ?>" class="news-link">
                    <?php if (!empty($item['image'])): ?>
                        <div class="news-image" style="background-image: url('<?= $item['image'] ?>');"></div>
                    <?php else: ?>
                        <div class="video-container">
                            <video id="video-<?= $item['id'] ?>" class="news-video">
                                <source src="<?= $item['video'] ?>" type="video/mp4">
                                Ваш браузер не поддерживает видео.
                            </video>
                        </div>
                    <?php endif; ?>
                    <div class="news-content">
                        <h2><?= $item['title'] ?></h2>
                        <p><?= substr($item['content'], 0, 150) ?>...</p>
                        <span class="news-date"><?= $item['date'] ?></span>
                    </div>
                </a>
            </div>
        <?php endforeach; ?>
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
    <script>
        // Инициализация Fluid Player для всех видео
        document.querySelectorAll('.news-video').forEach(video => {
            console.log("Инициализация Fluid Player для видео:", video.id); // Отладочное сообщение
            fluidPlayer(video.id, {
                layoutControls: {
                    primaryColor: "#ff5722",
                    posterImage: "<?= $item['image'] ?>",
                    autoPlay: false,
                    mute: false,
                    allowDownload: false,
                    playbackRateEnabled: true,
                    fillToContainer: true,
                    controlBar: {
                        autoHide: true,
                        autoHideTimeout: 3,
                    },
                },
            });
        });
    </script>
    <script>
// Функция проверки AdBlock
function checkAdBlock() {
    return new Promise((resolve) => {
        const ad = document.createElement('div');
        ad.innerHTML = '&nbsp;';
        ad.className = 'adsbox advert ad-placeholder ad-banner ad-unit ad-wrapper';
        ad.style.cssText = 'position:absolute;top:-9999px;left:-9999px;';
        document.body.appendChild(ad);
        
        setTimeout(() => {
            const isBlocked = ad.offsetHeight === 0;
            document.body.removeChild(ad);
            resolve(isBlocked);
        }, 100);
    });
}

// Показать или скрыть сообщение об AdBlock
async function handleAdBlockDetection() {
    const mContainer = document.querySelector('.m-container');
    const isBlocked = await checkAdBlock();

    if (isBlocked) {
        mContainer.classList.add('adblock-disabled'); // Добавляем класс, если AdBlock включен
    } else {
        mContainer.classList.remove('adblock-disabled'); // Убираем класс, если AdBlock отключен
    }
}

// Проверка при загрузке
window.addEventListener('load', handleAdBlockDetection);
</script>
</body>
</html>