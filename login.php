<?php
session_start();
include_once 'header.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);

    // Чтение данных из users.json
    $users = json_decode(file_get_contents('users.json'), true);

    // Проверка, существует ли пользователь
    if (isset($users[$username])) {
        // Проверка пароля
        if (password_verify($password, $users[$username]['password'])) {
            // Авторизация пользователя
            $_SESSION['username'] = $username;
            $_SESSION['is_admin'] = $users[$username]['is_admin']; // Сохраняем статус администратора

            // Перенаправление в зависимости от роли
            if ($_SESSION['is_admin']) {
                header("Location: index.php"); // Переход на главную
            } else {
                header("Location: index.php"); // Переход на главную
            }
            exit();
        } else {
            $error = 'Неверный пароль!';
        }
    } else {
        $error = 'Пользователь не найден!';
    }
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0"> <!-- Важно для адаптивности -->
    <title>Вход</title>
    <style>
        /* Основные стили */
        body {
            font-family: 'Roboto', sans-serif;
            margin: 0;
            padding: 0;
            background-color: #1a1a1a;
            color: #fff;
            line-height: 1.6;
            display: flex;
            flex-direction: column;
            align-items: center;
            min-height: 100vh;
            text-align: center;
            overflow-x: hidden; /* Запрет горизонтальной прокрутки */
        }
/* Логотип */
.logo {
    max-width: 100%; /* Логотип не выходит за пределы контейнера */
    height: auto; /* Автоматическая высота для сохранения пропорций */
    display: flex;
    justify-content: center;
    align-items: center;
}

.logo img {
    max-width: 150px; /* Максимальная ширина логотипа */
    height: auto; /* Сохраняем пропорции */
    transition: transform 0.3s;
}

.logo img:hover {
    transform: scale(1.1); /* Эффект увеличения при наведении */
}

        .form-container {
            background-color: #333;
            padding: 20px;
            border-radius: 20px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.3);
            max-width: 400px;
            width: 90%;
            margin: 10% auto 0; /* Отступ сверху для мобильных устройств */
            text-align: center;
            box-sizing: border-box; /* Убедимся, что padding не увеличивает размер контейнера */
        }

        .form-container h1 {
            margin-bottom: 20px;
            font-size: 2em;
            color: #ffcc00;
        }

        .form-container input[type="text"],
        .form-container input[type="password"] {
            width: calc(100% - 20px); /* Учитываем padding */
            padding: 10px;
            margin: 10px 0;
            border: none;
            border-radius: 20px;
            background-color: #444;
            color: #fff;
            font-size: 1em;
            transition: background-color 0.3s, box-shadow 0.3s;
            box-sizing: border-box; /* Убедимся, что padding не увеличивает ширину */
        }

        .form-container input[type="text"]:focus,
        .form-container input[type="password"]:focus {
            background-color: #555;
            box-shadow: 0 0 10px rgba(255, 204, 0, 0.5);
            outline: none;
        }

        .form-container button[type="submit"] {
            width: 100%;
            padding: 10px;
            background-color: #ffcc00;
            border: none;
            border-radius: 20px;
            color: #000;
            font-size: 1.2em;
            font-weight: bold;
            cursor: pointer;
            transition: background-color 0.3s, transform 0.3s;
            box-sizing: border-box; /* Убедимся, что padding не увеличивает ширину */
        }

        .form-container button[type="submit"]:hover {
            background-color: #e6b800;
            transform: translateY(-2px);
        }

        .forgot-password {
            display: block;
            margin-top: 10px;
            color: #ffcc00;
            text-decoration: none;
            font-size: 0.9em;
            transition: color 0.3s;
        }

        .forgot-password:hover {
            color: #e6b800;
        }

        .error {
            color: #ff4444;
            font-size: 0.9em;
            margin-bottom: 10px;
        }

        /* Адаптивность для формы */
        @media (max-width: 768px) {
            .form-container {
                margin-top: 20%; /* Увеличенный отступ сверху для планшетов */
            }
        .logo img {
        max-width: 120px; /* Уменьшаем логотип для планшетов */
    }
        }

        @media (max-width: 480px) {
            .form-container {
                padding: 15px;
                margin-top: 25%; /* Увеличенный отступ сверху для мобильных устройств */
            }
         .logo img {
        max-width: 80px; /* Ещё меньше логотип для очень маленьких экранов */
    }

            .form-container h1 {
                font-size: 1.5em; /* Уменьшаем размер заголовка */
            }

            .form-container input[type="text"],
            .form-container input[type="password"] {
                font-size: 0.9em; /* Уменьшаем размер шрифта */
                padding: 8px; /* Уменьшаем padding */
            }

            .form-container button[type="submit"] {
                font-size: 1em; /* Уменьшаем размер шрифта */
                padding: 8px; /* Уменьшаем padding */
            }

            .forgot-password {
                font-size: 0.8em; /* Уменьшаем размер шрифта для ссылки */
            }

            .error {
                font-size: 0.8em; /* Уменьшаем размер шрифта для ошибок */
            }
        }
    </style>
</head>
<body>
    <div class="form-container">
        <h1>Вход</h1>
        <?php if (isset($error)): ?>
            <p class="error"><?= $error ?></p>
        <?php endif; ?>
        <form method="POST">
            <input type="text" name="username" placeholder="Имя пользователя" required>
            <input type="password" name="password" placeholder="Пароль" required>
            <button type="submit">Войти</button>
            <a href="forgot_password.php" class="forgot-password">Забыли пароль?</a>
        </form>
    </div>
</body>
</html>