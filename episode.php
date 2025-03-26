<?php
session_start();
include_once 'header.php';

// Проверка наличия параметров
if (!isset($_GET['folder']) || !isset($_GET['season']) || !isset($_GET['episode'])) {
    die("Не указаны параметры для загрузки серии.");
}

// Декодируем название папки
$anime_folder = urldecode($_GET['folder']);
$season = (int)$_GET['season'];
$episode = (int)$_GET['episode'];

// Проверка существования файла данных
$data_file = "anime/$anime_folder/data.json";
if (!file_exists($data_file)) {
    die("Данные аниме не найдены.");
}

// Загружаем данные аниме
$anime_data = json_decode(file_get_contents($data_file), true);
if (!$anime_data) {
    die("Ошибка при чтении данных аниме.");
}

// Проверяем, существует ли сезон и серия
if (!isset($anime_data['seasons'][$season - 1]['episodes'][$episode - 1])) {
    die("Сезон или серия не найдены.");
}

// Получаем информацию о текущей серии
$current_season = $anime_data['seasons'][$season - 1];
$current_episode = $current_season['episodes'][$episode - 1];

// Проверяем, есть ли версия без озвучки
$has_nodub = isset($current_episode['video_nodub']);

// Формируем путь к видеофайлу
$video_path = "anime/$anime_folder/season$season/" . $current_episode['video'];
if ($has_nodub) {
    $video_path_nodub = "anime/$anime_folder/season$season/nodub/" . $current_episode['video_nodub'];
}

if (!file_exists($video_path)) {
    die("Видеофайл не найден: $video_path");
}

// Загружаем данные пользователей
$users_file = 'users.json';
if (!file_exists($users_file)) {
    die("Файл пользователей не найден.");
}

$users = json_decode(file_get_contents($users_file), true);
$username = $_SESSION['username'];

// Добавляем аниме в историю просмотров
if (isset($users[$username])) {
    $users[$username]['history'][$anime_folder] = [
        'folder' => $anime_folder,
        'season' => $season,
        'episode' => $episode,
        'timestamp' => time(),
        'date' => date('Y-m-d H:i:s')
    ];

    file_put_contents($users_file, json_encode($users, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($anime_data['title']) ?> - Сезон <?= $season ?>, Серия <?= $episode ?></title>
    <link rel="stylesheet" href="css/episode.css">
    <link rel="stylesheet" href="css/adblock.css">
    <link rel="stylesheet" href="https://cdn.fluidplayer.com/v3/current/fluidplayer.min.css">
    <link rel="stylesheet" href="css/header.css">
    <style>
    /* Основные стили */
    body {
        font-family: 'Roboto', sans-serif;
        margin: 0;
        padding: 0;
        background-color: #1a1a1a;
        color: #fff;
        line-height: 1.6;
    }

    .wrapper {
        max-width: 1200px;
        margin: 0 auto;
        padding: 20px;
    }

    .episode-container {
        background-color: #222;
        border-radius: 15px;
        box-shadow: 0 8px 20px rgba(0, 0, 0, 0.5);
        padding: 20px;
        margin-top: 20px;
    }

    h1 {
        font-size: 2.5em;
        color: #ffcc00;
        margin: 0 0 10px;
    }

    h2, h3 {
        font-size: 1.8em;
        color: #ccc;
        margin: 0 0 20px;
    }

    /* Контейнер для Fluid Player с уменьшенной высотой */
    .fluid-player-container {
        width: 100%;
        height: 75vh; /* 40% от высоты viewport */
        max-height: 500px;
        margin: 20px 0;
        border-radius: 10px;
        overflow: hidden;
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.3);
    }

    /* Навигация между сериями */
    .episode-navigation {
        display: flex;
        justify-content: space-between;
        gap: 10px;
        margin-top: 20px;
    }

    .nav-button {
        padding: 10px 20px;
        background-color: #ff5722;
        color: #fff;
        text-decoration: none;
        border-radius: 30px;
        font-weight: bold;
        transition: background-color 0.3s, transform 0.3s;
    }

    .nav-button:hover {
        background-color: #e64a19;
        transform: translateY(-2px);
    }

    .list-button {
        background-color: #555;
    }

    /* Стили для кнопок переключения версий */
    .version-switcher {
        margin-top: 20px;
        text-align: center;
    }

    .version-button {
        display: inline-block;
        padding: 10px 20px;
        border: none;
        border-radius: 20px;
        font-size: 1em;
        font-weight: bold;
        cursor: pointer;
        transition: background-color 0.3s, transform 0.3s;
        margin: 5px;
    }

    .version-button:hover {
        transform: translateY(-2px);
    }

    .dub-button {
        background-color: #4CAF50;
        color: white;
    }

    .dub-button:hover {
        background-color: #45a049;
    }

    .nodub-button {
        background-color: #2196F3;
        color: white;
    }

    .nodub-button:hover {
        background-color: #1e88e5;
    }

    /* Стили для блока паузы */
    .fp-pause-block {
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        text-align: center;
        padding: 20px;
        background-color: rgba(0, 0, 0, 0.8);
        height: 100%;
    }
    
    .fp-pause-block img.logo {
        max-width: 200px;
        margin-bottom: 20px;
    }
    
    .fp-pause-block h2 {
        color: #fff;
        margin: 20px 0;
        font-size: 1.5em;
        max-width: 80%;
    }
    
    .pause-buttons {
        display: flex;
        gap: 15px;
        margin-top: 20px;
    }
    
    .pause-button {
        padding: 10px 20px;
        background-color: #ff5722;
        color: white;
        text-decoration: none;
        border-radius: 30px;
        font-weight: bold;
        transition: all 0.3s ease;
    }
    
    .pause-button:hover {
        background-color: #e64a19;
        transform: translateY(-2px);
    }

    /* Адаптивные стили */
    @media (max-width: 768px) {
        .episode-navigation {
            flex-direction: column;
        }
        
        .nav-button, .version-button {
            width: 100%;
        }
        
        .pause-buttons {
            flex-direction: column;
        }
        
        h1 {
            font-size: 2em;
        }
        
        h2, h3 {
            font-size: 1.5em;
        }
        
        /* Уменьшаем высоту плеера на мобильных */
        .fluid-player-container {
            height: 30vh;
        }
    }

    @media (max-width: 480px) {
        .wrapper {
            padding: 10px;
        }
        
        .fp-pause-block h2 {
            font-size: 1em;
        }
        
        .fp-pause-block img.logo {
            max-width: 150px;
        }
        
        /* Еще меньше высота плеера на маленьких экранах */
        .fluid-player-container {
            height: 25vh;
        }
    }

    /* Переопределение стилей Fluid Player */
    .fluid_controls {
        background: linear-gradient(to top, rgba(0,0,0,0.7), transparent) !important;
    }
    
    .fluid_button:hover {
        background-color: rgba(255,255,255,0.1) !important;
    }
    
    .fluid_progress {
        background-color: rgba(255,255,255,0.2) !important;
    }
    
    .fluid_played {
        background-color: #ff5722 !important;
    }
    
    .fluid_logo {
        display: none !important;
    }
    </style>
</head>
<body>
    <?php include_once 'header.php'; ?>

    <div class="wrapper">
        <div class="m-container">
            <div id="reklama">
                <div class="episode-container">
                    <h1><?= htmlspecialchars($anime_data['title']) ?></h1>
                    <h2>Сезон <?= $season ?>, Серия <?= $episode ?></h2>
                    <?php if (isset($current_episode['title'])): ?>
                        <h3><?= htmlspecialchars($current_episode['title']) ?></h3>
                    <?php endif; ?>

                    <!-- Рекламный блок 1 -->
                    <div id="yandex-rtb-ad-1" class="ad-container">
                    </div>

                    <!-- Контейнер для Fluid Player с уменьшенной высотой -->
                    <div class="fluid-player-container">
                        <video id="my-video" poster="anime/<?= htmlspecialchars($anime_folder) ?>/poster.jpg">
                            <source src="<?= $video_path ?>" type="video/mp4">
                            <?php if ($has_nodub): ?>
                                <source src="<?= $video_path_nodub ?>" type="video/mp4">
                            <?php endif; ?>
                        </video>
                    </div>

                    <!-- Рекламный блок 2 -->
                    <div id="yandex-rtb-ad-2" class="ad-container">
                    </div>

                    <!-- Навигация между сериями -->
                    <div class="episode-navigation">
                        <?php if ($episode > 1): ?>
                            <a href="episode.php?folder=<?= urlencode($anime_folder) ?>&season=<?= $season ?>&episode=<?= $episode - 1 ?>" class="nav-button prev-button">Предыдущая серия</a>
                        <?php endif; ?>

                        <a href="series.php?folder=<?= urlencode($anime_folder) ?>" class="nav-button list-button">Список серий</a>

                        <?php if ($episode < count($current_season['episodes'])): ?>
                            <a href="episode.php?folder=<?= urlencode($anime_folder) ?>&season=<?= $season ?>&episode=<?= $episode + 1 ?>" class="nav-button next-button">Следующая серия</a>
                        <?php else: ?>
                            <?php if (isset($anime_data['seasons'][$season])): ?>
                                <a href="episode.php?folder=<?= urlencode($anime_folder) ?>&season=<?= $season + 1 ?>&episode=1" class="nav-button next-button">Следующий сезон</a>
                            <?php endif; ?>
                        <?php endif; ?>
                    </div>

                    <!-- Кнопки переключения версий -->
                    <?php if ($has_nodub): ?>
                        <div class="version-switcher">
                            <button id="switch-to-dub" class="version-button dub-button">С озвучкой</button>
                            <button id="switch-to-nodub" class="version-button nodub-button">С субтитрами</button>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Yandex.RTB загрузчик -->
    <script src="https://yandex.ru/ads/system/context.js" async></script>

    <!-- Скрипты для рекламных блоков -->
    <script>
        // Инициализация рекламы
        window.yaContextCb = window.yaContextCb || [];
        
        // Первый рекламный блок
        window.yaContextCb.push(() => {
            Ya.Context.AdvManager.render({
                renderTo: 'yandex-rtb-ad-1',
                blockId: '<?= $ads_data['yandex_rtb']['blockId'] ?>',
                type: '<?= $ads_data['yandex_rtb']['type'] ?>',
                platform: '<?= $ads_data['yandex_rtb']['platform'] ?>'
            });
        });

        // Второй рекламный блок
        window.yaContextCb.push(() => {
            Ya.Context.AdvManager.render({
                renderTo: 'yandex-rtb-ad-2',
                blockId: '<?= $ads_data['yandex_rtb']['blockId'] ?>',
                type: '<?= $ads_data['yandex_rtb']['type'] ?>',
                platform: '<?= $ads_data['yandex_rtb']['platform'] ?>'
            });
        });
    </script>

    <!-- Fluid Player -->
    <script src="https://cdn.fluidplayer.com/v3/current/fluidplayer.min.js"></script>
    <script>
        var myFP = fluidPlayer(
            'my-video',
            {
                layoutControls: {
                    controlBar: {
                        autoHideTimeout: 3,
                        animated: true,
                        autoHide: true
                    },
                    htmlOnPauseBlock: {
                       html: `<div class="fp-pause-block">
                                  <img src="logo.png" alt="Логотип" class="logo">
                                  <div class="pause-buttons">
                                      <a href="https://vk.com/lupinsu" class="pause-button" target="_blank">Наша группа в VK</a>
                                      <a href="https://t.me/lupinsuanime" class="pause-button" target="_blank">Наш Telegram</a>
                                  </div>
                               </div>`,
                        height: null,
                        width: null
                    },
                    autoPlay: false,
                    mute: false,
                    allowTheatre: true,
                    playPauseAnimation: true,
                    playbackRateEnabled: false,
                    allowDownload: false,
                    playButtonShowing: true,
                    fillToContainer: true,
                    posterImage: "anime/<?= htmlspecialchars($anime_folder) ?>/poster.jpg",
                    primaryColor: "#ff5722"
                },
                vastOptions: {
                    adList: [],
                    adCTAText: false,
                    adCTATextPosition: ""
                }
            }
        );

        // Переключение между версиями
        <?php if ($has_nodub): ?>
            const videoElement = document.getElementById('my-video');
            const sourceDub = videoElement.querySelector('source[type="video/mp4"]');
            const sourceNodub = videoElement.querySelector('source[src="<?= $video_path_nodub ?>"]');

            document.getElementById('switch-to-dub').addEventListener('click', () => {
                videoElement.src = sourceDub.src;
                videoElement.load();
                myFP.play();
            });

            document.getElementById('switch-to-nodub').addEventListener('click', () => {
                videoElement.src = sourceNodub.src;
                videoElement.load();
                myFP.play();
            });
        <?php endif; ?>

        // Отправка данных о просмотре
        const userTimeZone = Intl.DateTimeFormat().resolvedOptions().timeZone;
        fetch('save_history.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                timeZone: userTimeZone,
                animeFolder: '<?= $anime_folder ?>',
                season: '<?= $season ?>',
                episode: '<?= $episode ?>'
            }),
        });
    </script>
</body>
</html>