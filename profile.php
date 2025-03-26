<?php
session_start();
include_once 'header.php';

if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit();
}

$username = $_SESSION['username'];
$users = json_decode(file_get_contents('users.json'), true);

// Проверка, существует ли пользователь
if (!isset($users[$username])) {
    die("Пользователь не найден.");
}

$user = $users[$username];

// Получаем список аниме
$anime_list = [];
$anime_folders = glob('anime/*', GLOB_ONLYDIR);
foreach ($anime_folders as $folder) {
    $data_file = $folder . '/data.json';
    if (file_exists($data_file)) {
        $anime_data = json_decode(file_get_contents($data_file), true);
        if ($anime_data) {
            $anime_data['folder'] = basename($folder);
            $anime_list[] = $anime_data;
        }
    }
}

// Ваш топ (оцененные аниме)
$rated_anime = [];
if (!empty($user['rated'])) {
    foreach ($user['rated'] as $anime_folder => $rating) {
        foreach ($anime_list as $anime) {
            if ($anime['folder'] === $anime_folder) {
                $anime['user_rating'] = $rating;
                $rated_anime[] = $anime;
            }
        }
    }
    // Сортировка по оценке
    usort($rated_anime, function($a, $b) {
        return $b['user_rating'] - $a['user_rating'];
    });
}

// Запланированное
$planned_anime = [];
if (!empty($user['planned'])) {
    foreach ($user['planned'] as $anime_folder) {
        foreach ($anime_list as $anime) {
            if ($anime['folder'] === $anime_folder) {
                $planned_anime[] = $anime;
            }
        }
    }
}

// Избранное
$favorite_anime = [];
if (!empty($user['favorites'])) {
    foreach ($user['favorites'] as $anime_folder) {
        foreach ($anime_list as $anime) {
            if ($anime['folder'] === $anime_folder) {
                $favorite_anime[] = $anime;
            }
        }
    }
}

// История просмотра
$history_anime = [];
if (!empty($user['history'])) {
    // Сортируем историю по дате просмотра (последние сначала)
    uasort($user['history'], function($a, $b) {
        return strtotime($b['date']) - strtotime($a['date']);
    });

    // Ограничиваем до последних 5 аниме
    $recent_history = array_slice($user['history'], 0, 5);

    // Добавляем данные аниме
    foreach ($recent_history as $anime_folder => $history_data) {
        foreach ($anime_list as $anime) {
            if ($anime['folder'] === $anime_folder) {
                // Извлекаем номер сезона и серии из последнего просмотренного эпизода
                $last_episode = $history_data['episode'];
                preg_match('/Сезон (\d+), Серия (\d+)/', $last_episode, $matches);
                $last_season = $matches[1] ?? 1; // По умолчанию сезон 1
                $last_episode_number = $matches[2] ?? 1; // По умолчанию серия 1

                $anime['last_episode'] = $last_episode;
                $anime['last_watched_date'] = $history_data['date'];
                $anime['last_season'] = $last_season;
                $anime['last_episode_number'] = $last_episode_number;
                $history_anime[$anime_folder] = $anime;
                break; // Прерываем цикл, если аниме найдено
            }
        }
    }
}

// Заброшенное
$dropped_anime = [];
if (!empty($user['dropped'])) {
    foreach ($user['dropped'] as $anime_folder) {
        foreach ($anime_list as $anime) {
            if ($anime['folder'] === $anime_folder) {
                $dropped_anime[] = $anime;
            }
        }
    }
}

// Рекомендуемое (основываясь на жанрах и действиях пользователя)
$recommended_anime = [];
if (!empty($user['rated']) || !empty($user['favorites']) || !empty($user['history'])) {
    // Сбор жанров из предпочтений пользователя
    $user_genres = [];

    // Жанры из оцененных аниме
    if (!empty($user['rated'])) {
        foreach ($rated_anime as $anime) {
            $user_genres = array_merge($user_genres, $anime['genres']);
        }
    }

    // Жанры из избранного
    if (!empty($user['favorites'])) {
        foreach ($favorite_anime as $anime) {
            $user_genres = array_merge($user_genres, $anime['genres']);
        }
    }

    // Жанры из истории просмотров
    if (!empty($user['history'])) {
        foreach ($history_anime as $anime) {
            $user_genres = array_merge($user_genres, $anime['genres']);
        }
    }

    // Убираем дубликаты жанров
    $user_genres = array_unique($user_genres);

    // Формируем список рекомендованных аниме
    foreach ($anime_list as $anime) {
        // Исключаем аниме, которые пользователь уже оценил, добавил в избранное, просмотрел или забросил
        if (
            !isset($user['rated'][$anime['folder']]) &&
            !in_array($anime['folder'], $user['favorites'] ?? []) &&
            !isset($user['history'][$anime['folder']]) &&
            !in_array($anime['folder'], $user['dropped'] ?? [])
        ) {
            // Считаем количество совпадающих жанров
            $matching_genres = array_intersect($user_genres, $anime['genres']);
            $score = count($matching_genres);

            // Если есть совпадения, добавляем аниме в рекомендации
            if ($score > 0) {
                $anime['score'] = $score; // Сохраняем "вес" рекомендации
                $recommended_anime[] = $anime;
            }
        }
    }

    // Сортируем рекомендации по количеству совпадающих жанров (от большего к меньшему)
    usort($recommended_anime, function($a, $b) {
        return $b['score'] - $a['score'];
    });

    // Ограничиваем количество рекомендаций (например, 10)
    $recommended_anime = array_slice($recommended_anime, 0, 10);
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Личный кабинет - <?= htmlspecialchars($username) ?></title>
    <link rel="stylesheet" href="css/style.css">
    <style>
        /* Стили для раскрывающихся разделов */
        .section {
            margin-bottom: 10px;
            border: 1px solid #444;
            border-radius: 10px;
            overflow: hidden;
            background-color: #333;
            transition: background-color 0.3s ease;
        }

        .section-header {
            padding: 15px;
            margin: 0;
            cursor: pointer;
            background-color: #444;
            color: #fff;
            font-size: 1.2em;
            transition: background-color 0.3s ease;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .section-header:hover {
            background-color: #555;
        }

        .section-content {
            max-height: 0;
            overflow: hidden;
            padding: 0 15px;
            transition: max-height 0.5s ease, padding 0.5s ease;
        }

        .section.open .section-content {
            max-height: fit-content; /* Максимальная высота контента */
            padding: 15px;
        }

        .section.open {
            background-color: #444;
        }

        .arrow {
            transition: transform 0.3s ease;
        }

        .section.open .arrow {
            transform: rotate(180deg);
        }
    </style>
</head>
<body>
    <div class="wrapper">
        <h1>Личный кабинет - <?= htmlspecialchars($username) ?></h1>

        <!-- Ваш топ -->
        <div class="section">
            <h2 class="section-header" onclick="toggleSection('top')">
                Ваш топ
                <span class="arrow">▼</span>
            </h2>
            <div class="section-content" id="top">
                <?php if (empty($rated_anime)): ?>
                    <p>Вы еще не оценили ни одного аниме.</p>
                <?php else: ?>
                    <div class="anime-list">
                        <?php foreach ($rated_anime as $anime): ?>
                            <div class="anime-box">
                                <?php if (file_exists("anime/{$anime['folder']}/main.jpg")): ?>
                                    <img src="anime/<?= htmlspecialchars($anime['folder']) ?>/main.jpg" alt="<?= htmlspecialchars($anime['title']) ?>">
                                <?php else: ?>
                                    <img src="images/default.jpg" alt="Изображение отсутствует">
                                <?php endif; ?>
                                <h3><?= htmlspecialchars($anime['title']) ?></h3>
                                <p>Ваша оценка: <?= htmlspecialchars($anime['user_rating']) ?></p>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Запланированное -->
        <div class="section">
            <h2 class="section-header" onclick="toggleSection('planned')">
                Запланированное
                <span class="arrow">▼</span>
            </h2>
            <div class="section-content" id="planned">
                <?php if (empty($planned_anime)): ?>
                    <p>Список запланированных аниме пуст.</p>
                <?php else: ?>
                    <div class="anime-list">
                        <?php foreach ($planned_anime as $anime): ?>
                            <div class="anime-box">
                                <?php if (file_exists("anime/{$anime['folder']}/main.jpg")): ?>
                                    <img src="anime/<?= htmlspecialchars($anime['folder']) ?>/main.jpg" alt="<?= htmlspecialchars($anime['title']) ?>">
                                <?php else: ?>
                                    <img src="images/default.jpg" alt="Изображение отсутствует">
                                <?php endif; ?>
                                <h3><?= htmlspecialchars($anime['title']) ?></h3>
                                <!-- Кнопка перехода на страницу аниме (series.php) -->
                                <a href="series.php?folder=<?= htmlspecialchars($anime['folder']) ?>" class="watch-button">Перейти к аниме</a>
                                <!-- Кнопка удаления из запланированного -->
                                <a href="remove_from_list.php?anime=<?= htmlspecialchars($anime['folder']) ?>&list=planned" class="remove-button">Удалить</a>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Избранное -->
        <div class="section">
            <h2 class="section-header" onclick="toggleSection('favorites')">
                Избранное
                <span class="arrow">▼</span>
            </h2>
            <div class="section-content" id="favorites">
                <?php if (empty($favorite_anime)): ?>
                    <p>Список избранного пуст.</p>
                <?php else: ?>
                    <div class="anime-list">
                        <?php foreach ($favorite_anime as $anime): ?>
                            <div class="anime-box">
                                <?php if (file_exists("anime/{$anime['folder']}/main.jpg")): ?>
                                    <img src="anime/<?= htmlspecialchars($anime['folder']) ?>/main.jpg" alt="<?= htmlspecialchars($anime['title']) ?>">
                                <?php else: ?>
                                    <img src="images/default.jpg" alt="Изображение отсутствует">
                                <?php endif; ?>
                                <h3><?= htmlspecialchars($anime['title']) ?></h3>
                                <!-- Кнопка перехода на страницу аниме (series.php) -->
                                <a href="series.php?folder=<?= urlencode($anime['folder']) ?>" class="watch-button">Перейти к аниме</a>
                                <!-- Кнопка удаления из избранного -->
                                <a href="remove_from_list.php?anime=<?= htmlspecialchars($anime['folder']) ?>&list=favorites" class="remove-button">Удалить</a>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- История просмотра -->
        <div class="section">
            <h2 class="section-header" onclick="toggleSection('history')">
                История просмотра
                <span class="arrow">▼</span>
            </h2>
            <div class="section-content" id="history">
                <?php if (empty($history_anime)): ?>
                    <p>История просмотров пуста.</p>
                <?php else: ?>
                    <div class="anime-list">
                        <?php
                        // Ограничиваем историю до последних 5 аниме
                        $recent_history = array_slice($history_anime, 0, 5);
                        foreach ($recent_history as $anime_folder => $anime): ?>
                            <div class="anime-box">
                                <?php if (file_exists("anime/{$anime['folder']}/main.jpg")): ?>
                                    <img src="anime/<?= htmlspecialchars($anime['folder']) ?>/main.jpg" alt="<?= htmlspecialchars($anime['title']) ?>">
                                <?php else: ?>
                                    <img src="images/default.jpg" alt="Изображение отсутствует">
                                <?php endif; ?>
                                <h3><?= htmlspecialchars($anime['title']) ?></h3>
                                <p>Последняя серия: <?= htmlspecialchars($anime['last_episode']) ?></p>
                                <p>Дата просмотра: <?= htmlspecialchars($anime['last_watched_date']) ?></p>
                                <!-- Кнопка перехода к последней просмотренной серии -->
                                <a href="episode.php?folder=<?= htmlspecialchars($anime['folder']) ?>&season=<?= htmlspecialchars($anime['last_season']) ?>&episode=<?= htmlspecialchars($anime['last_episode_number']) ?>" class="watch-button" style="background-color: green;">Продолжить просмотр</a>
                                <!-- Кнопка удаления из истории -->
                                <a href="remove_from_list.php?anime=<?= htmlspecialchars($anime['folder']) ?>&list=history" class="remove-button">Удалить</a>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Заброшенное -->
        <div class="section">
            <h2 class="section-header" onclick="toggleSection('dropped')">
                Заброшенное
                <span class="arrow">▼</span>
            </h2>
            <div class="section-content" id="dropped">
                <?php if (empty($dropped_anime)): ?>
                    <p>Список заброшенных аниме пуст.</p>
                <?php else: ?>
                    <div class="anime-list">
                        <?php foreach ($dropped_anime as $anime): ?>
                            <div class="anime-box">
                                <?php if (file_exists("anime/{$anime['folder']}/main.jpg")): ?>
                                    <img src="anime/<?= htmlspecialchars($anime['folder']) ?>/main.jpg" alt="<?= htmlspecialchars($anime['title']) ?>">
                                <?php else: ?>
                                    <img src="images/default.jpg" alt="Изображение отсутствует">
                                <?php endif; ?>
                                <h3><?= htmlspecialchars($anime['title']) ?></h3>
                                <!-- Кнопка перехода на страницу аниме (series.php) -->
                                <a href="series.php?folder=<?= htmlspecialchars($anime['folder']) ?>" class="watch-button">Перейти к аниме</a>
                                <!-- Кнопка удаления из заброшенного -->
                                <a href="remove_from_list.php?anime=<?= htmlspecialchars($anime['folder']) ?>&list=dropped" class="remove-button">Удалить</a>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Рекомендуемое -->
        <div class="section">
            <h2 class="section-header" onclick="toggleSection('recommended')">
                Рекомендуемое
                <span class="arrow">▼</span>
            </h2>
            <div class="section-content" id="recommended">
                <?php if (empty($recommended_anime)): ?>
                    <p>Нет рекомендаций. Оцените больше аниме, чтобы получить рекомендации.</p>
                <?php else: ?>
                    <div class="anime-list">
                        <?php foreach ($recommended_anime as $anime): ?>
                            <div class="anime-box">
                                <?php if (file_exists("anime/{$anime['folder']}/main.jpg")): ?>
                                    <img src="anime/<?= htmlspecialchars($anime['folder']) ?>/main.jpg" alt="<?= htmlspecialchars($anime['title']) ?>">
                                <?php else: ?>
                                    <img src="images/default.jpg" alt="Изображение отсутствует">
                                <?php endif; ?>
                                <h3><?= htmlspecialchars($anime['title']) ?></h3>
                                <p>Жанры: <?= htmlspecialchars(implode(', ', $anime['genres'])) ?></p>
                                <p>Совпадение: <?= htmlspecialchars($anime['score']) ?> жанров</p>
                                <!-- Кнопка "Смотреть" -->
                                <a href="series.php?folder=<?= htmlspecialchars($anime['folder']) ?>" class="watch-button">Смотреть</a>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
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
            // Функция для раскрытия/закрытия разделов
            function toggleSection(sectionId) {
                const section = document.getElementById(sectionId).parentElement;
                section.classList.toggle('open');

                // Сохраняем состояние в localStorage
                const isOpen = section.classList.contains('open');
                localStorage.setItem(sectionId, isOpen);
            }

            // Восстанавливаем состояние при загрузке страницы
            document.addEventListener('DOMContentLoaded', function () {
                document.querySelectorAll('.section').forEach(section => {
                    const sectionId = section.querySelector('.section-content').id;
                    const isOpen = localStorage.getItem(sectionId) === 'true';
                    if (isOpen) {
                        section.classList.add('open');
                    }
                });
            });
        </script>
    </div>
</body>
</html>