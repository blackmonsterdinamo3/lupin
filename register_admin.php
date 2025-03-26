<?php
session_start();
require 'config.php'; // Подключаем файл с админским паролем

// Проверка, авторизован ли пользователь и является ли он администратором
if (!isset($_SESSION['username']) || !$_SESSION['is_admin']) {
    header("Location: index.php");
    exit();
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);
    $email = trim($_POST['email']);

    // Валидация данных
    if (empty($username) || empty($password) || empty($email)) {
        $error = 'Все поля обязательны для заполнения!';
    } else {
        // Чтение данных из users.json
        $users = json_decode(file_get_contents('users.json'), true);

        // Проверка, существует ли пользователь
        if (isset($users[$username])) {
            $error = 'Пользователь с таким именем уже существует!';
        } else {
            // Хеширование пароля
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);

            // Добавление нового администратора
            $users[$username] = [
                'password' => $hashed_password,
                'email' => $email,
                'is_admin' => true // Новый пользователь — администратор
            ];

            // Сохранение данных в users.json
            file_put_contents('users.json', json_encode($users, JSON_PRETTY_PRINT));

            $error = "Администратор $username успешно зарегистрирован!";
        }
    }
}
   // Сохранение данных с использованием JSON_UNESCAPED_UNICODE
    file_put_contents("../anime/$anime_folder/data.json", json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

    echo "Серия '$episode_title' успешно добавлена!";
} else {
    echo "Ошибка при загрузке видео!";
}
?>
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Регистрация администратора</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <header>
        <img src="logo.jpg" alt="Логотип" class="logo">
        <nav>
            <a href="index.php">Главная</a>
            <a href="logout.php">Выйти</a>
        </nav>
    </header>

    <div class="form-container">
        <h1>Регистрация администратора</h1>
        <?php if ($error): ?>
            <p class="error"><?= $error ?></p>
        <?php endif; ?>
        <form method="POST">
            <input type="text" name="username" placeholder="Имя пользователя" required>
            <input type="email" name="email" placeholder="Email" required>
            <input type="password" name="password" placeholder="Пароль" required>
            <button type="submit">Зарегистрировать администратора</button>
        </form>
    </div>
</body>
</html>