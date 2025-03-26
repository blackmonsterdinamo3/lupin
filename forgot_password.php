<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'vendor/autoload.php'; // Подключение PHPMailer
include_once 'header.php';

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);

    // Чтение данных из users.json
    $users = json_decode(file_get_contents('users.json'), true);

    // Поиск пользователя по email
    $user_found = false;
    foreach ($users as $username => $user) {
        if ($user['email'] === $email) {
            $user_found = true;

            // Генерация токена для восстановления пароля
            $token = md5($username . $email . time());
            $reset_link = "https://lupin.su/project/reset_password.php?token=$token";

            // Сохранение токена в users.json
            $users[$username]['reset_token'] = $token;
            file_put_contents('users.json', json_encode($users, JSON_PRETTY_PRINT));

            // Отправка письма через Яндекс SMTP
            $mail = new PHPMailer(true);
            try {
                // Настройки SMTP
                $mail->isSMTP();
                $mail->Host = 'smtp.yandex.ru';
                $mail->SMTPAuth = true;
                $mail->Username = 'lupin.su@yandex.ru'; // Ваш email на Яндексе
                $mail->Password = 'mhttmoldlvzficmk'; // Пароль от почты
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS; // Используйте PHPMailer::ENCRYPTION_STARTTLS для порта 587
                $mail->Port = 465; // Порт 465 для SSL, 587 для TLS
                $mail->CharSet = 'UTF-8'; // Указываем кодировку UTF-8

                // Отправитель и получатель
                $mail->setFrom('lupin.su@yandex.ru', 'Lupin.su');
                $mail->addAddress($email); // Email пользователя

                // Содержание письма
                $mail->isHTML(true);
                $mail->Subject = 'Восстановление пароля на Lupin.su';
                $mail->Body = "Для восстановления пароля перейдите по ссылке: <a href='$reset_link'>$reset_link</a>";

                $mail->send();
                $error = "Ссылка для восстановления пароля отправлена на ваш email.";
            } catch (Exception $e) {
                $error = "Ошибка при отправке письма: {$mail->ErrorInfo}";
            }
            break;
        }
    }

    if (!$user_found) {
        $error = "Пользователь с таким email не найден.";
    }
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0"> <!-- Важно для адаптивности -->
    <title>Восстановление пароля - Lupin.su</title>
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

        .form-container input[type="email"] {
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

        .form-container input[type="email"]:focus {
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

            .form-container input[type="email"] {
                font-size: 0.9em; /* Уменьшаем размер шрифта */
                padding: 8px; /* Уменьшаем padding */
            }

            .form-container button[type="submit"] {
                font-size: 1em; /* Уменьшаем размер шрифта */
                padding: 8px; /* Уменьшаем padding */
            }

            .error {
                font-size: 0.8em; /* Уменьшаем размер шрифта для ошибок */
            }
        }
    </style>
</head>
<body>
    <div class="form-container">
        <h1>Восстановление пароля</h1>
        <?php if ($error): ?>
            <p class="error"><?= $error ?></p>
        <?php endif; ?>
        <form method="POST">
            <input type="email" name="email" placeholder="Ваш email" required>
            <button type="submit">Отправить</button>
        </form>
    </div>
</body>
</html>