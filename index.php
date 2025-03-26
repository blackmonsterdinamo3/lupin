<?php
// Подключаем библиотеку Google Translate
require 'vendor/autoload.php';
include 'ads.php';
use Stichoza\GoogleTranslate\GoogleTranslate;

$logged_in = isset($_SESSION['username']);
$anime_list = [];
include_once 'header.php';

// Сканируем папку с аниме
$anime_folders = array_filter(scandir('anime'), function($item) {
    return is_dir("anime/$item") && !in_array($item, ['.', '..']);
});

foreach ($anime_folders as $folder) {
    $data_file = "anime/$folder/data.json";
    if (file_exists($data_file)) {
        $anime_data = json_decode(file_get_contents($data_file), true);
        if ($anime_data) {
            $anime_data['folder'] = $folder; // Добавляем название папки

            // Проверка, голосовал ли пользователь за это аниме
            if ($logged_in) {
                $users = json_decode(file_get_contents('users.json'), true);
                $username = $_SESSION['username'];
                $anime_data['voted'] = isset($users[$username]['rated'][$anime_data['folder']]);
            }

            $anime_list[] = $anime_data;
        } 
    }
}

// Функция для нормализации строки (транслит, раскладка, регистр, спецсимволы)
function normalizeString($str) {
    // Приводим к нижнему регистру
    $str = mb_strtolower($str, 'UTF-8');

    // Заменяем русские буквы на английские (транслит)
    $ruToEn = [
        'а' => 'a', 'б' => 'b', 'в' => 'v', 'г' => 'g', 'д' => 'd',
        'е' => 'e', 'ё' => 'e', 'ж' => 'zh', 'з' => 'z', 'и' => 'i',
        'й' => 'y', 'к' => 'k', 'л' => 'l', 'м' => 'm', 'н' => 'n',
        'о' => 'o', 'п' => 'p', 'р' => 'r', 'с' => 's', 'т' => 't',
        'у' => 'u', 'ф' => 'f', 'х' => 'h', 'ц' => 'ts', 'ч' => 'ch',
        'ш' => 'sh', 'щ' => 'sch', 'ъ' => '', 'ы' => 'y', 'ь' => '',
        'э' => 'e', 'ю' => 'yu', 'я' => 'ya'
    ];

    // Заменяем английские буквы на русские (ошибки в раскладке)
    $enToRu = [
        'a' => 'а', 'b' => 'б', 'c' => 'с', 'd' => 'д', 'e' => 'е',
        'f' => 'ф', 'g' => 'г', 'h' => 'х', 'i' => 'и', 'j' => 'й',
        'k' => 'к', 'l' => 'л', 'm' => 'м', 'n' => 'н', 'o' => 'о',
        'p' => 'п', 'q' => 'й', 'r' => 'р', 's' => 'с', 't' => 'т',
        'u' => 'у', 'v' => 'в', 'w' => 'в', 'x' => 'х', 'y' => 'ы',
        'z' => 'з'
    ];

    // Транслитерация русских букв в английские
    $str = strtr($str, $ruToEn);

    // Обратная транслитерация (английские буквы в русские)
    $str = strtr($str, $enToRu);

    // Убираем спецсимволы (оставляем только буквы и цифры)
    $str = preg_replace('/[^a-zа-я0-9]/u', '', $str);

    return $str;
}

// Функция для вычисления Левенштейнского расстояния
function levenshteinDistance($s1, $s2) {
    $s1 = normalizeString($s1);
    $s2 = normalizeString($s2);
    return levenshtein($s1, $s2);
}

// Фильтрация аниме
$filtered_anime = $anime_list;
if (isset($_GET['search']) && !empty($_GET['search'])) {
    $searchTerm = $_GET['search'];

    // Проверяем, является ли запрос на английском языке
    if (preg_match('/^[a-zA-Z\s]+$/', $searchTerm)) {
        // Переводим запрос с английского на русский
        $tr = new GoogleTranslate(); // Создаем объект переводчика
        $tr->setSource('en'); // Указываем исходный язык (английский)
        $tr->setTarget('ru'); // Указываем язык перевода (русский)

        try {
            $translatedSearchTerm = $tr->translate($searchTerm);
            echo "Оригинальный запрос: $searchTerm<br>";
            echo "Переведенный запрос: $translatedSearchTerm<br>";

            // Нормализуем переведенный запрос
            $searchTerm = normalizeString($translatedSearchTerm);
        } catch (Exception $e) {
            echo "Ошибка перевода: " . $e->getMessage() . "<br>";
            $searchTerm = normalizeString($searchTerm); // Если перевод не удался, используем оригинальный запрос
        }
    } else {
        // Если запрос не на английском, просто нормализуем его
        $searchTerm = normalizeString($searchTerm);
    }

    $filtered_anime = array_filter($filtered_anime, function($anime) use ($searchTerm, $synonyms) {
        // Нормализуем название аниме и оригинальное название
        $normalizedTitle = normalizeString($anime['title']);
        $normalizedOriginalTitle = isset($anime['original_title']) ? normalizeString($anime['original_title']) : '';

        // Поиск по синонимам
        $synonymMatches = false;
        if (isset($synonyms[$anime['title']])) {
            foreach ($synonyms[$anime['title']] as $synonym) {
                $normalizedSynonym = normalizeString($synonym);
                if (strpos($normalizedSynonym, $searchTerm) !== false) {
                    $synonymMatches = true;
                    break;
                }
            }
        }

        // Поиск по Левенштейнскому расстоянию (допустимая погрешность: 2 символа)
        $levenshteinDistanceTitle = levenshteinDistance($anime['title'], $searchTerm);
        $levenshteinDistanceOriginalTitle = isset($anime['original_title']) ? levenshteinDistance($anime['original_title'], $searchTerm) : PHP_INT_MAX;
        $levenshteinMatches = ($levenshteinDistanceTitle <= 2) || ($levenshteinDistanceOriginalTitle <= 2);

        // Ищем совпадения в названии, оригинальном названии и синонимах
        $matches = strpos($normalizedTitle, $searchTerm) !== false ||
               strpos($normalizedOriginalTitle, $searchTerm) !== false ||
               $synonymMatches ||
               $levenshteinMatches;

        if ($matches) {
            echo "Совпадение найдено: {$anime['title']}<br>"; // Отладочное сообщение
        }

        return $matches;
    });
}

// Фильтрация по жанру (исправленная версия)
if (!empty($_GET['genre'])) {
    $selected_genre = htmlspecialchars($_GET['genre']);
    $filtered_anime = array_filter($filtered_anime, function($anime) use ($selected_genre) {
        return in_array($selected_genre, $anime['genres']);
    });
}

// Сортировка аниме
if (isset($_GET['sort']) && !empty($_GET['sort'])) {
    switch ($_GET['sort']) {
        case 'rating_asc':
            usort($filtered_anime, function($a, $b) {
                return $a['rating'] <=> $b['rating'];
            });
            break;
        case 'rating_desc':
            usort($filtered_anime, function($a, $b) {
                return $b['rating'] <=> $a['rating'];
            });
            break;
        case 'release_asc':
            usort($filtered_anime, function($a, $b) {
                return strtotime($a['date_of_release']) <=> strtotime($b['date_of_release']);
            });
            break;
        case 'release_desc':
            usort($filtered_anime, function($a, $b) {
                return strtotime($b['date_of_release']) <=> strtotime($a['date_of_release']);
            });
            break;
        case 'added_asc':
            usort($filtered_anime, function($a, $b) {
                return strtotime($a['date_added']) <=> strtotime($b['date_added']);
            });
            break;
        case 'added_desc':
            usort($filtered_anime, function($a, $b) {
                return strtotime($b['date_added']) <=> strtotime($a['date_added']);
            });
            break;
    }
}
// Получаем последние добавленные серии
$latest_episodes = [];
foreach ($anime_list as $anime) {
    if (isset($anime['seasons'])) {
        foreach ($anime['seasons'] as $season) {
            if (isset($season['episodes'])) {
                foreach ($season['episodes'] as $episode) {
                    // Проверяем, есть ли поле date_added
                    if (isset($episode['date_added'])) {
                        $latest_episodes[] = [
                            'anime_title' => $anime['title'],
                            'anime_folder' => $anime['folder'],
                            'season_number' => $season['number'],
                            'episode_number' => $episode['number'],
                            'image' => "anime/{$anime['folder']}/banner.jpg", // Используем banner.jpg
                            'date_added' => $episode['date_added'] // Используем date_added
                        ];
                    }
                }
            }
        }
    }
}

// Сортируем по дате добавления (последние добавленные серии будут первыми)
usort($latest_episodes, function($a, $b) {
    return strtotime($b['date_added']) - strtotime($a['date_added']);
});

// Берем последние 10 серий
$latest_episodes = array_slice($latest_episodes, 0, 10);
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lupin.su - Главная</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/slider.css">
	<link rel="stylesheet" href="css/adblock.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    
    <!-- Step 1 - Insert a script call inside the <head> tag -->
    <script src="https://sdk.adlook.tech/inventory/core.js" async type="text/javascript"></script>
    
 <!-- Yandex.RTB загрузчик -->
    <script>window.yaContextCb = window.yaContextCb || [];</script>
    <script src="https://yandex.ru/ads/system/context.js" async></script>
</head>
<body>
    <!-- Рекламный блок Yandex.RTB (десктоп) -->
    <div id="yandex-rtb-ad" style="margin: 20px auto; text-align: center;">
        <!-- Yandex.RTB реклама будет вставлена сюда -->
    </div>

    <!-- Рекламный блок Yandex.RTB (мобильные) -->
    <div id="yandex-rtb-mobile-ad" style="margin: 20px auto; text-align: center;">
        <!-- Yandex.RTB мобильная реклама будет вставлена сюда -->
    </div>


    <!-- Yandex.RTB R-A-11991079-3 -->
    <script>
    window.yaContextCb.push(() => {
        Ya.Context.AdvManager.render({
            "blockId": "R-A-11991079-3",
            "type": "floorAd",
            "platform": "desktop"
        })
    })
    </script>

<!-- Кастомный слайдер -->
<div class="alternative-slider">
    <div class="slider-wrapper">
        <?php foreach ($latest_episodes as $index => $episode): ?>
            <div class="slide" data-index="<?= $index ?>">
                <div class="slide-content">
                    <img src="<?= $episode['image'] ?>" alt="<?= $episode['anime_title'] ?>">
                    <div class="slide-overlay"></div>
                    <div class="slide-info">
                        <h3><?= $episode['anime_title'] ?></h3>
                        <p>Сезон <?= $episode['season_number'] ?>, Серия <?= $episode['episode_number'] ?></p>
                        <p class="date-added">Добавлено: <?= date("d.m.Y H:i", strtotime($episode['date_added'])) ?></p>
                        <a href="series.php?folder=<?= urlencode($episode['anime_folder']) ?>" class="watch-button">Смотреть</a>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
    <!-- Кнопки навигации -->
    <button class="slider-nav prev-nav">❰</button>
    <button class="slider-nav next-nav">❱</button>
    <!-- Индикаторы -->
    <div class="slider-dots">
        <?php foreach ($latest_episodes as $index => $episode): ?>
            <span class="dot <?= $index === 0 ? 'active' : '' ?>" data-index="<?= $index ?>"></span>
        <?php endforeach; ?>
    </div>
</div>
 <div class="wrapper">
<div class="m-container">
            <div id="reklama">
        <!-- Форма поиска -->
        <form method="GET" action="index.php" class="search-container">
            <input type="text" name="search" placeholder="Поиск по названию аниме..." class="search-input" value="<?= htmlspecialchars($_GET['search'] ?? '') ?>">
            <select name="genre" class="search-select">
                <option value="">Все жанры</option>
                <?php
                // Получаем уникальные жанры из всех аниме
                $genres = [];
                foreach ($anime_list as $anime) {
                    $genres = array_merge($genres, $anime['genres']);
                }
                $genres = array_unique($genres);
                foreach ($genres as $genre): ?>
                    <option value="<?= htmlspecialchars($genre) ?>" <?= isset($_GET['genre']) && $_GET['genre'] === $genre ? 'selected' : '' ?>>
                        <?= htmlspecialchars($genre) ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <select name="sort" class="search-select">
                <option value="">Сортировать по...</option>
                <option value="rating_desc" <?= isset($_GET['sort']) && $_GET['sort'] === 'rating_desc' ? 'selected' : '' ?>>По рейтингу (убывание)</option>
                <option value="rating_asc" <?= isset($_GET['sort']) && $_GET['sort'] === 'rating_asc' ? 'selected' : '' ?>>По рейтингу (возрастание)</option>
                <option value="release_desc" <?= isset($_GET['sort']) && $_GET['sort'] === 'release_desc' ? 'selected' : '' ?>>По дате выхода (новые)</option>
                <option value="release_asc" <?= isset($_GET['sort']) && $_GET['sort'] === 'release_asc' ? 'selected' : '' ?>>По дате выхода (старые)</option>
                <option value="added_desc" <?= isset($_GET['sort']) && $_GET['sort'] === 'added_desc' ? 'selected' : '' ?>>По дате добавления (новые)</option>
                <option value="added_asc" <?= isset($_GET['sort']) && $_GET['sort'] === 'added_asc' ? 'selected' : '' ?>>По дате добавления (старые)</option>
            </select>
            <button type="submit" class="search-button">Найти</button>
        </form>

        <!-- Отображение отфильтрованных аниме -->
<div class="anime-list">
    <?php foreach ($filtered_anime as $anime): ?>
        <div class="anime-box">
            <img src="anime/<?= htmlspecialchars($anime['folder']) ?>/main.jpg" alt="<?= htmlspecialchars($anime['title']) ?>">
            <h3><?= htmlspecialchars($anime['title']) ?></h3>
            <p><?= htmlspecialchars(implode(', ', $anime['genres'])) ?></p>
            <p class="status">
                <?php if ($anime['ongoing']): ?>
                    <span class="ongoing">Статус: Онгоинг</span>
                <?php else: ?>
                    <span class="completed">Статус: Завершено</span>
                <?php endif; ?>
            </p>
            <div class="rating">
                <?php if ($logged_in): ?>
                    <div class="stars <?= $anime['voted'] ? 'hidden' : '' ?>" data-anime-folder="<?= $anime['folder'] ?>">
                        <?php for ($i = 1; $i <= 10; $i++): ?>
                            <span class="star" data-value="<?= $i ?>">&#9733;</span>
                        <?php endfor; ?>
                    </div>
                <?php else: ?>
                    <p>Зарегистрируйтесь для оценки</p>
                <?php endif; ?>
                <p>Средний балл: <span class="average-rating"><?= $anime['rating'] ?></span></p>
            </div>
            <a href="series.php?folder=<?= urlencode($anime['folder']) ?>" class="watch-button">Смотреть</a>
        </div>
    <?php endforeach; ?>
</div>
            </div>
    </div>

    <!-- Кнопка "Вверх" -->
    <button class="scroll-top" onclick="scrollToTop()">
        <i class="fas fa-arrow-up"></i>
    </button>

    <!-- Футер -->
    <footer class="site-footer">
        <div class="footer-content">
            <p>Вся информация, файлы и аудиовизуальные произведения предоставлены на сайте только для ознакомительного просмотра. Возрастное ограничение 18+</p>
            <p>&copy; Lupin.su 2024</p>
        </div>
    </footer>


<!-- Скрипты -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
    // Инициализация Yandex.RTB рекламы
    window.yaContextCb.push(() => {
        Ya.Context.AdvManager.render({
            renderTo: 'yandex-rtb-ad',
            blockId: 'R-A-11991079-3', // Замените на ваш blockId
            type: 'floorAd', // Тип рекламы
            platform: 'desktop' // Платформа (desktop, touch)
        });
    });

    // Слайдер
    let currentSlide = 0;
    const slides = document.querySelectorAll('.slide');
    const dots = document.querySelectorAll('.dot');
    const totalSlides = slides.length;
    let startX = 0;
    let endX = 0;

    function showSlide(index) {
        slides.forEach((slide, i) => {
            slide.classList.remove('active', 'prev', 'next');
        });

        slides[index].classList.add('active');
        if (index > 0) {
            slides[index - 1].classList.add('prev');
        } else {
            slides[totalSlides - 1].classList.add('prev');
        }
        if (index < totalSlides - 1) {
            slides[index + 1].classList.add('next');
        } else {
            slides[0].classList.add('next');
        }

        dots.forEach((dot, i) => {
            dot.classList.toggle('active', i === index);
        });
    }

    function nextSlide() {
        currentSlide = (currentSlide + 1) % totalSlides;
        showSlide(currentSlide);
    }

    function prevSlide() {
        currentSlide = (currentSlide - 1 + totalSlides) % totalSlides;
        showSlide(currentSlide);
    }

    document.querySelector('.next-nav')?.addEventListener('click', nextSlide);
    document.querySelector('.prev-nav')?.addEventListener('click', prevSlide);

    dots.forEach((dot, index) => {
        dot.addEventListener('click', () => {
            currentSlide = index;
            showSlide(currentSlide);
        });
    });

    let intervalId = setInterval(nextSlide, 5000);

    document.querySelector('.alternative-slider').addEventListener('mouseenter', () => {
        clearInterval(intervalId);
    });

    document.querySelector('.alternative-slider').addEventListener('mouseleave', () => {
        intervalId = setInterval(nextSlide, 5000);
    });

    const slider = document.querySelector('.slider-wrapper');

    slider.addEventListener('touchstart', (e) => {
        startX = e.touches[0].clientX;
    });

    slider.addEventListener('touchmove', (e) => {
        endX = e.touches[0].clientX;
    });

    slider.addEventListener('touchend', () => {
        const deltaX = endX - startX;
        const swipeThreshold = 50;

        if (deltaX > swipeThreshold) {
            prevSlide();
        } else if (deltaX < -swipeThreshold) {
            nextSlide();
        }
    });

    showSlide(currentSlide);
</script>

<script src="js/scripts.js"></script>
</body>
</html>