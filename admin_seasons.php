<?php
session_start();
include_once 'headeradmin.php';

// Проверка, авторизован ли пользователь и является ли он администратором
if (!isset($_SESSION['username']) || !$_SESSION['is_admin']) {
    header("Location: index.php");
    exit();
}

// Проверка наличия параметра folder
if (!isset($_GET['folder'])) {
    header("Location: admin.php");
    exit();
}

$folder = basename($_GET['folder']); // Защита от path traversal
$data_file = "anime/$folder/data.json";

// Проверка существования файла данных
if (!file_exists($data_file)) {
    header("Location: admin.php");
    exit();
}

// Загрузка данных аниме
$anime_data = json_decode(file_get_contents($data_file), true);
if (!$anime_data) {
    die("Ошибка при чтении файла данных.");
}

$seasons = $anime_data['seasons'];
$error = '';

// Обработка добавления нового сезона
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $season_title = trim($_POST['season_title']);
    if (empty($season_title)) {
        $error = "Название сезона не может быть пустым!";
    } else {
        $season_number = count($seasons) + 1;
        $season_dir = "anime/$folder/season$season_number";

        // Создание папки для нового сезона
        if (!mkdir($season_dir, 0777, true)) {
            $error = "Не удалось создать папку для сезона!";
        } else {
            // Добавление сезона в data.json
            $anime_data['seasons'][] = [
                'number' => $season_number,
                'title' => $season_title,
                'episodes' => []
            ];

            // Сохраняем данные с отключением экранирования Unicode и слэшей
            if (file_put_contents($data_file, json_encode($anime_data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT))) {
                $error = "Сезон '$season_title' успешно добавлен!";
            } else {
                $error = "Ошибка при сохранении данных!";
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Управление сезонами - <?= htmlspecialchars($anime_data['title']) ?></title>
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

        input[type="text"], textarea, input[type="password"], input[type="file"] {
            width: 100%;
            padding: 10px;
            margin: 10px 0;
            border: 1px solid #ddd;
            border-radius: 8px;
            font-size: 16px;
            transition: border-color 0.3s ease;
        }

        input[type="text"]:focus, textarea:focus, input[type="password"]:focus, input[type="file"]:focus {
            border-color: #007bff;
            outline: none;
        }

        textarea {
            resize: vertical;
            min-height: 150px;
        }

        /* Кнопки */
        .button-link {
            display: inline-block;
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

        /* Список сезонов */
        .season-list {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 20px;
        }

        .season-item {
            background: #fff;
            padding: 20px;
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }

        .season-item:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 16px rgba(0, 0, 0, 0.2);
        }

        /* Тёмная тема */
        @media (prefers-color-scheme: dark) {
            body {
                background-color: #121212;
                color: #e0e0e0;
            }

            .admin-panel, .season-item {
                background: #1e1e1e;
                color: #e0e0e0;
            }

            input[type="text"], textarea, input[type="password"], input[type="file"] {
                background: #333;
                color: #e0e0e0;
                border-color: #444;
            }

            input[type="text"]:focus, textarea:focus, input[type="password"]:focus, input[type="file"]:focus {
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
        <h1>Управление сезонами - <?= htmlspecialchars($anime_data['title']) ?></h1>
        <?php if ($error): ?>
            <p class="error"><?= htmlspecialchars($error) ?></p>
        <?php endif; ?>

        <!-- Список сезонов -->
        <h2>Сезоны</h2>
        <div class="season-list">
            <?php foreach ($seasons as $season): ?>
                <div class="season-item">
                    <h3><?= htmlspecialchars($season['title']) ?></h3>
                    <a href="admin_episodes.php?folder=<?= urlencode($folder) ?>&season=<?= htmlspecialchars($season['number']) ?>" class="button-link">Управление сериями</a>
                </div>
            <?php endforeach; ?>
        </div>

        <!-- Форма добавления сезона -->
        <h2>Добавить сезон</h2>
        <form method="POST">
            <input type="text" name="season_title" placeholder="Название сезона" required>
            <p>Можно указать как вариант Сезон 1, название сезона "Сезон 2: Новый рубеж".</p>
            <button type="submit" class="button-link">Добавить сезон</button>
        </form>
    </div>
</body>
</html>