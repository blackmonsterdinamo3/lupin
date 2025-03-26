<?php
session_start();
require 'config.php'; // Подключаем файл с админским паролем
include_once 'header.php';

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        die('CSRF token validation failed');
    }

    $username = trim($_POST['username']);
    $password = trim($_POST['password']);
    $email = trim($_POST['email']);
    $admin_secret = trim($_POST['admin_secret']);

    // Проверка на специальные символы в логине и пароле
    if (!preg_match('/^[a-zA-Z0-9]+$/', $username)) {
        $error = 'Логин может содержать только буквы и цифры!';
    } elseif (!preg_match('/^[a-zA-Z0-9]+$/', $password)) {
        $error = 'Пароль может содержать только буквы и цифры!';
    } else {
        $is_admin = ($admin_secret === $admin_password);

        if (empty($username) || empty($password) || empty($email)) {
            $error = 'Все поля обязательны для заполнения!';
        } else {
            $users = json_decode(file_get_contents('users.json'), true);

            if (isset($users[$username])) {
                $error = 'Пользователь с таким именем уже существует!';
            } else {
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);

                $users[$username] = [
                    'password' => $hashed_password,
                    'email' => $email,
                    'is_admin' => $is_admin
                ];

                file_put_contents('users.json', json_encode($users, JSON_PRETTY_PRINT));

                $_SESSION['username'] = $username;
                $_SESSION['is_admin'] = $is_admin;
                header("Location: profile.php");
                exit();
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Регистрация</title>
    <style>
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
            overflow-x: hidden;
        }

        .logo {
            max-width: 100%;
            height: auto;
            display: flex;
            justify-content: center;
            align-items: center;
        }

        .logo img {
            max-width: 150px;
            height: auto;
            transition: transform 0.3s;
        }

        .logo img:hover {
            transform: scale(1.1);
        }

        .form-container {
            background-color: #333;
            padding: 20px;
            border-radius: 20px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.3);
            max-width: 400px;
            width: 90%;
            margin: 10% auto 0;
            text-align: center;
            box-sizing: border-box;
        }

        .form-container h1 {
            margin-bottom: 20px;
            font-size: 2em;
            color: #ffcc00;
        }

        .form-container input[type="text"],
        .form-container input[type="email"],
        .form-container input[type="password"] {
            width: calc(100% - 20px);
            padding: 10px;
            margin: 10px 0;
            border: none;
            border-radius: 20px;
            background-color: #444;
            color: #fff;
            font-size: 1em;
            transition: background-color 0.3s, box-shadow 0.3s;
            box-sizing: border-box;
        }

        .form-container input[type="text"]:focus,
        .form-container input[type="email"]:focus,
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
            box-sizing: border-box;
        }

        .form-container button[type="submit"]:hover {
            background-color: #e6b800;
            transform: translateY(-2px);
        }

        .error {
            color: #ff4444;
            font-size: 0.9em;
            margin-bottom: 10px;
        }

        @media (max-width: 768px) {
            .form-container {
                margin-top: 20%;
            }

            .logo img {
                max-width: 120px;
            }
        }

        @media (max-width: 480px) {
            .form-container {
                padding: 15px;
                margin-top: 25%;
            }

            .logo img {
                max-width: 80px;
            }

            .form-container h1 {
                font-size: 1.5em;
            }

            .form-container input[type="text"],
            .form-container input[type="email"],
            .form-container input[type="password"] {
                font-size: 0.9em;
                padding: 8px;
            }

            .form-container button[type="submit"] {
                font-size: 1em;
                padding: 8px;
            }

            .error {
                font-size: 0.8em;
            }
        }
    </style>
</head>
<body>
    <div class="form-container">
        <h1>Регистрация</h1>
        <?php if ($error): ?>
            <p class="error"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></p>
        <?php endif; ?>
        <form method="POST">
            <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
            <input type="text" name="username" placeholder="Имя пользователя" maxlength="50" required>
            <input type="email" name="email" placeholder="Email" maxlength="100" required>
            <input type="password" name="password" placeholder="Пароль" maxlength="255" required>
            <input type="password" name="admin_secret" placeholder="Админский пароль (если есть)" maxlength="255">
            <button type="submit">Зарегистрироваться</button>
        </form>
    </div>
</body>
</html>