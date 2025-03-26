<?php
include_once 'headeradmin.php';
session_start();

// Включение отображения ошибок
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Проверка, авторизован ли пользователь и является ли он администратором
if (!isset($_SESSION['username']) || !$_SESSION['is_admin']) {
    header("Location: index.php");
    exit();
}

$error = '';
$anime_list = [];

// Сканируем папку с аниме
$anime_folders = glob('anime/*', GLOB_ONLYDIR);
foreach ($anime_folders as $folder) {
    $data_file = $folder . '/data.json';
    if (file_exists($data_file)) {
        $anime_data = json_decode(file_get_contents($data_file), true);
        if ($anime_data) {
            $anime_data['folder'] = basename($folder); // Добавляем название папки
            $anime_data['ongoing'] = $anime_data['ongoing'] ?? false; // Добавляем ключ 'ongoing', если его нет
            $anime_list[] = $anime_data;
        }
    }
}

// Обработка добавления нового аниме
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['title'])) {
    $title = trim($_POST['title']);
    $original_title = trim($_POST['original_title']); // Оригинальное название
    $description = trim($_POST['description']);
    $genres = trim($_POST['genres']);
    $folder_name = trim($_POST['folder_name']); // Название папки, указанное администратором
    $date_of_release = trim($_POST['date_of_release']); // Дата выхода
    $date_added = date("Y-m-d"); // Дата добавления (текущая дата)

    // Если название папки не указано, генерируем его на основе названия аниме
    if (empty($folder_name)) {
        $folder_name = strtolower(str_replace(' ', '_', $title)); // Название папки для аниме
    }

    // Проверка на корректность названия папки
    if (!preg_match('/^[a-zA-Z0-9_\-]+$/', $folder_name)) {
        $error = "Название папки может содержать только латинские буквы, цифры, '_' и '-'!";
    } else {
        // Проверка, существует ли папка с таким именем
        if (file_exists("anime/$folder_name")) {
            $error = "Папка с названием '$folder_name' уже существует!";
        } else {
            // Создание папки для аниме
            if (!mkdir("anime/$folder_name", 0777, true)) {
                $error = "Не удалось создать папку для аниме!";
            } else {
                mkdir("anime/$folder_name/season1", 0777, true); // Создаем папку для первого сезона

                // Загрузка изображений
                $upload_dir = "anime/$folder_name/";
                $images = ['main_image' => 'main.jpg', 'banner_image' => 'banner.jpg', 'poster_image' => 'poster.jpg'];
                $upload_success = true;

                foreach ($images as $file_key => $file_name) {
                    if ($_FILES[$file_key]['error'] === UPLOAD_ERR_OK) {
                        $tmp_name = $_FILES[$file_key]['tmp_name'];
                        $type = mime_content_type($tmp_name);
                        if (strpos($type, 'image/jpeg') === false) {
                            $error = "Файл $file_name должен быть в формате JPEG!";
                            $upload_success = false;
                            break;
                        }
                        move_uploaded_file($tmp_name, $upload_dir . $file_name);
                    } else {
                        $error = "Ошибка загрузки файла $file_name!";
                        $upload_success = false;
                        break;
                    }
                }

                if ($upload_success) {
                    // Создание data.json
                    $data = [
                        'title' => $title,
                        'original_title' => $original_title, // Добавляем оригинальное название
                        'description' => $description,
                        'genres' => array_map('trim', explode(',', $genres)),
                        'rating' => 0,
                        'ongoing' => true, // По умолчанию статус "Онгоинг"
                        'date_of_release' => $date_of_release, // Дата выхода
                        'date_added' => $date_added, // Дата добавления
                        'seasons' => [
                            [
                                'number' => 1,
                                'title' => 'Сезон 1',
                                'episodes' => []
                            ]
                        ]
                    ];

                    // Сохраняем данные с форматированием (в Unicode)
                    if (file_put_contents($upload_dir . 'data.json', json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT))) {
                        $error = "Аниме '$title' успешно добавлено!";
                    } else {
                        $error = "Ошибка при сохранении данных!";
                    }
                }
            }
        }
    }
}

// Обработка удаления аниме
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_folder'])) {
    $delete_folder = trim($_POST['delete_folder']);
    $password = trim($_POST['password']);

    if ($password === '110041') {
        if (file_exists("anime/$delete_folder")) {
            // Удаляем папку с аниме
            $files = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator("anime/$delete_folder", FilesystemIterator::SKIP_DOTS),
                RecursiveIteratorIterator::CHILD_FIRST
            );
            foreach ($files as $file) {
                $file->isDir() ? rmdir($file) : unlink($file);
            }
            rmdir("anime/$delete_folder");
            $error = "Аниме '$delete_folder' успешно удалено!";
        } else {
            $error = "Папка '$delete_folder' не найдена!";
        }
    } else {
        $error = "Неверный пароль для удаления!";
    }
}

// Обработка редактирования JSON
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_json'])) {
    $edit_folder = trim($_POST['edit_folder']);
    $json_data = trim($_POST['json_data']);

    $data_file = "anime/$edit_folder/data.json";
    if (file_exists($data_file)) {
        // Сохраняем отредактированные данные (в Unicode)
        if (file_put_contents($data_file, $json_data)) {
            $error = "Данные аниме '$edit_folder' успешно обновлены!";
        } else {
            $error = "Ошибка при сохранении данных!";
        }
    } else {
        $error = "Файл данных не найден!";
    }
}

// Обработка обновления статуса онгоинга
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_ongoing_folder'])) {
    $update_folder = trim($_POST['update_ongoing_folder']);
    $ongoing = isset($_POST['ongoing']) && $_POST['ongoing'] === '1' ? true : false;

    $data_file = "anime/$update_folder/data.json";
    if (file_exists($data_file)) {
        $anime_data = json_decode(file_get_contents($data_file), true);
        if ($anime_data) {
            $anime_data['ongoing'] = $ongoing;

            // Сохраняем обновленные данные
            if (file_put_contents($data_file, json_encode($anime_data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT))) {
                $error = "Статус онгоинга для аниме '{$anime_data['title']}' успешно обновлен!";
            } else {
                $error = "Ошибка при сохранении данных!";
            }
        } else {
            $error = "Ошибка при чтении файла данных!";
        }
    } else {
        $error = "Файл данных не найден!";
    }
}

// Обработка редактирования аниме
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_anime'])) {
    $edit_folder = trim($_POST['edit_folder']);
    $title = trim($_POST['title']);
    $original_title = trim($_POST['original_title']);
    $description = trim($_POST['description']);
    $genres = trim($_POST['genres']);
    $date_of_release = trim($_POST['date_of_release']);
    $date_added = trim($_POST['date_added']);

    $data_file = "anime/$edit_folder/data.json";
    if (file_exists($data_file)) {
        $anime_data = json_decode(file_get_contents($data_file), true);
        if ($anime_data) {
            // Обновляем данные
            $anime_data['title'] = $title;
            $anime_data['original_title'] = $original_title;
            $anime_data['description'] = $description;
            $anime_data['genres'] = array_map('trim', explode(',', $genres));
            $anime_data['date_of_release'] = $date_of_release;
            $anime_data['date_added'] = $date_added;

            // Сохраняем обновленные данные
            if (file_put_contents($data_file, json_encode($anime_data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT))) {
                $error = "Данные аниме '$title' успешно обновлены!";
            } else {
                $error = "Ошибка при сохранении данных!";
            }
        } else {
            $error = "Ошибка при чтении файла данных!";
        }
    } else {
        $error = "Файл данных не найден!";
    }
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Админ-панель</title>
    <link rel="stylesheet" href="css/admin.css">
    <style>
        /* Основные стили */
        body {
            font-family: 'Arial', sans-serif;
            background-color: #f4f4f9;
            color: #333;
            margin: 0;
            padding: 0;
        }

        .admin-panel {
            max-width: 1200px;
            margin: 20px auto;
            padding: 20px;
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        }

        h1, h2 {
            color: #2c3e50;
            margin-bottom: 20px;
        }

        p {
            margin: 10px 0;
        }

        /* Формы */
        form {
            margin-bottom: 20px;
        }

        input[type="text"], textarea, input[type="password"], input[type="file"], input[type="date"] {
            width: 100%;
            padding: 10px;
            margin: 10px 0;
            border: 1px solid #ddd;
            border-radius: 8px;
            font-size: 16px;
            transition: border-color 0.3s ease;
        }

        input[type="text"]:focus, textarea:focus, input[type="password"]:focus, input[type="file"]:focus, input[type="date"]:focus {
            border-color: #007bff;
            outline: none;
        }

        textarea {
            resize: vertical;
            min-height: 150px;
        }

        /* Кнопки */
        .button-link {
            display: block;
            padding: 12px 24px;
            background-color: #007bff;
            color: white;
            text-decoration: none;
            border-radius: 8px;
            text-align: center;
            cursor: pointer;
            border: none;
            margin: 10px 0;
            transition: background-color 0.3s ease, transform 0.2s ease;
        }

        .button-link:hover {
            background-color: #0056b3;
            transform: translateY(-2px);
        }

        .delete-button {
            background-color: #dc3545;
        }

        .delete-button:hover {
            background-color: #c82333;
        }

        .edit-button {
            background-color: #28a745;
        }

        .edit-button:hover {
            background-color: #218838;
        }

        /* Список аниме */
        .anime-list {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 20px;
        }

        .anime-item {
            background: #fff;
            padding: 20px;
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }

        .anime-item:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 16px rgba(0, 0, 0, 0.2);
        }

        /* Тёмная тема */
        @media (prefers-color-scheme: dark) {
            body {
                background-color: #121212;
                color: #e0e0e0;
            }

            .admin-panel, .anime-item {
                background: #1e1e1e;
                color: #e0e0e0;
            }

            input[type="text"], textarea, input[type="password"], input[type="file"], input[type="date"] {
                background: #333;
                color: #e0e0e0;
                border-color: #444;
            }

            input[type="text"]:focus, textarea:focus, input[type="password"]:focus, input[type="file"]:focus, input[type="date"]:focus {
                border-color: #007bff;
            }

            .button-link {
                background-color: #007bff;
            }

            .button-link:hover {
                background-color: #0056b3;
            }

            .delete-button {
                background-color: #dc3545;
            }

            .delete-button:hover {
                background-color: #c82333;
            }

            .edit-button {
                background-color: #28a745;
            }

            .edit-button:hover {
                background-color: #218838;
            }
        }
    </style>
</head>
<body>
    <div class="admin-panel">
        <h1>Админ-панель</h1>
        <?php if ($error): ?>
            <p class="error"><?= $error ?></p>
        <?php endif; ?>
        
        <!-- Форма добавления аниме -->
        <h2>Добавить аниме</h2>
        <form method="POST" enctype="multipart/form-data">
            <input type="text" name="title" placeholder="Название аниме" required>
            <input type="text" name="original_title" placeholder="Оригинальное название" required>
            <textarea name="description" placeholder="Описание аниме" required></textarea>
            <input type="text" name="genres" placeholder="Жанры (через запятую)" required>
            <label>Название папки (латиница, цифры, '_', '-'):</label>
            <input type="text" name="folder_name" placeholder="Название папки (необязательно)">
            <label>Дата выхода:</label>
            <input type="date" name="date_of_release" required>
            <label>Главное изображение (main.jpg) 500x281px:</label>
            <input type="file" name="main_image" accept="image/jpeg" required>
            <label>Баннер (banner.jpg):</label>
            <input type="file" name="banner_image" accept="image/jpeg" required>
            <label>Постер (poster.jpg):</label>
            <input type="file" name="poster_image" accept="image/jpeg" required>
            <button type="submit">Добавить аниме</button>
        </form>

        <!-- Поиск по аниме -->
        <form method="GET" action="admin.php">
            <input type="text" name="search" placeholder="Поиск по названию аниме">
            <button type="submit">Искать</button>
        </form>

        <!-- Список аниме -->
        <h2>Список аниме</h2>
        <h2>В основном работаешь через управление сезонами, остальное лучше согласовать с Horonagi</h2>
        <div class="anime-list">
            <?php
            $search = isset($_GET['search']) ? trim($_GET['search']) : '';
            foreach ($anime_list as $anime):
                if (empty($search) || stripos($anime['title'], $search) !== false):
                    $data_file = "anime/{$anime['folder']}/data.json";
                    $json_data = file_exists($data_file) ? json_encode(json_decode(file_get_contents($data_file)), JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) : '{}';
            ?>
                <div class="anime-item">
                    <h2><?= $anime['title'] ?></h2>
                    <p><strong>Оригинальное название:</strong> <?= $anime['original_title'] ?></p>
                    <p><?= $anime['description'] ?></p>
                    <p>Жанры: <?= implode(', ', $anime['genres']) ?></p>
                    <p><strong>Дата выхода:</strong> <?= $anime['date_of_release'] ?? 'Не указана' ?></p>
                    <p><strong>Дата добавления:</strong> <?= $anime['date_added'] ?? 'Не указана' ?></p>
                    <a href="admin_seasons.php?folder=<?= urlencode($anime['folder']) ?>" class="button-link">Управление сезонами</a>
                    <form method="POST">
                        <input type="hidden" name="update_ongoing_folder" value="<?= $anime['folder'] ?>">
                        <label>
                            <input type="radio" name="ongoing" value="1" <?= isset($anime['ongoing']) && $anime['ongoing'] ? 'checked' : '' ?>> Онгоинг
                        </label>
                        <label>
                            <input type="radio" name="ongoing" value="0" <?= !isset($anime['ongoing']) || !$anime['ongoing'] ? 'checked' : '' ?>> Завершено
                        </label>
                        <button type="submit" class="button-link">Обновить статус</button>
                    </form>
                    <form method="POST" style="display:block;">
                        <h2>Аккуратно с этим файлом, сначала обратиться к Horonagi</h2>
                        <input type="hidden" name="delete_folder" value="<?= $anime['folder'] ?>">
                        <input type="password" name="password" placeholder="Пароль для удаления" required>
                        <button type="submit" class="button-link delete-button">Удалить</button>
                        <p>Удаляет безвозвратно, не восстановить. Серии тоже будут удалены.</p>
                    </form>
                    <button onclick="toggleEditForm('<?= $anime['folder'] ?>')" class="button-link edit-button">Редактировать аниме</button>
                    <form method="POST" id="edit-form-<?= $anime['folder'] ?>" style="display:none;">
                        <input type="hidden" name="edit_folder" value="<?= $anime['folder'] ?>">
                        <label>Название аниме:</label>
                        <input type="text" name="title" value="<?= htmlspecialchars($anime['title']) ?>" required>
                        <label>Оригинальное название:</label>
                        <input type="text" name="original_title" value="<?= htmlspecialchars($anime['original_title']) ?>" required>
                        <label>Описание аниме:</label>
                        <textarea name="description" required><?= htmlspecialchars($anime['description']) ?></textarea>
                        <label>Жанры (через запятую):</label>
                        <input type="text" name="genres" value="<?= htmlspecialchars(implode(', ', $anime['genres'])) ?>" required>
<!-- Дата выхода -->
<label>Дата выхода:</label>
<input type="date" name="date_of_release" value="<?= htmlspecialchars($anime['date_of_release'] ?? '') ?>" required>

<!-- Дата добавления -->
<label>Дата добавления:</label>
<input type="date" name="date_added" value="<?= htmlspecialchars($anime['date_added'] ?? '') ?>" required>
                        <button type="submit" name="edit_anime" class="button-link">Сохранить изменения</button>
                    </form>
                </div>
            <?php
                endif;
            endforeach;
            ?>
        </div>
    </div>

    <script>
        function toggleEditForm(folder) {
            const form = document.getElementById(`edit-form-${folder}`);
            form.style.display = form.style.display === 'none' ? 'block' : 'none';
        }
    </script>
</body>
</html>