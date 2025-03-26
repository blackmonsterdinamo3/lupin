<?php
// Настройки
$telegramBotToken = '6662675336:AAEOFmo6pYZQDhxDXmssm4V5GDhwyTVVbJo';
$telegramChatId = '-1001775722627';
$usedNumbersFile = 'used_numbers.txt';

// Инициализация файла для хранения использованных номеров
if (!file_exists($usedNumbersFile)) {
    file_put_contents($usedNumbersFile, '');
}

// Генерация уникального 4-значного числа
function generateUniqueNumber($filename) {
    $usedNumbers = file($filename, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    $usedNumbers = $usedNumbers ? array_map('trim', $usedNumbers) : [];
    
    do {
        $number = str_pad(mt_rand(0, 9999), 4, '0', STR_PAD_LEFT);
    } while (in_array($number, $usedNumbers));
    
    file_put_contents($filename, $number.PHP_EOL, FILE_APPEND);
    return $number;
}

// Обработка формы
$message = '';
$participantNumber = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    
    if (!empty($username)) {
        $participantNumber = generateUniqueNumber($usedNumbersFile);
        
        $telegramMessage = "Новый участник розыгрыша!\n";
        $telegramMessage .= "Идентификатор: " . htmlspecialchars($username) . "\n";
        $telegramMessage .= "Номер: " . $participantNumber . "\n";
        $telegramMessage .= "Дата: " . date('Y-m-d H:i:s');
        
        $telegramUrl = "https://api.telegram.org/bot{$telegramBotToken}/sendMessage";
        $telegramData = [
            'chat_id' => $telegramChatId,
            'text' => $telegramMessage,
            'parse_mode' => 'HTML'
        ];
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $telegramUrl);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $telegramData);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $result = curl_exec($ch);
        curl_close($ch);
        
        $message = "Спасибо за участие! Ваш номер: <strong>{$participantNumber}</strong><br> Поделитесь номером с другом! 😊 После того, как ваш друг подпишется на нашу группу в ВК или ТГ, он пришлет нам код, и мы увеличим ваши шансы на выигрыш! 🎉💰";
    } else {
        $message = "Пожалуйста, введите ваш username или VK ID";
    }
}
include_once 'header.php';
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Розыгрыш - Участвуйте и выигрывайте!</title>
    
    <!-- Yandex.RTB загрузчик -->
    <script>window.yaContextCb = window.yaContextCb || [];</script>
    
    <style>
        /* Основные стили */
        html, body {
            overflow-x: hidden;
            width: 100%;
            max-width: 100%;
        }

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
        }

        .wrapper {
            width: 100%;
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
            box-sizing: border-box;
        }

        /* Контейнер формы */
        .form-container {
            display: flex;
            flex-direction: column;
            justify-content: center;
            gap: 20px;
            padding: 30px;
            background-color: #222;
            margin: 40px auto;
            max-width: 600px;
            width: 90%;
            border-radius: 20px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.3);
        }

        .form-container h1 {
            margin: 0 0 20px;
            font-size: 2em;
            color: #ffcc00;
        }

        .form-container p {
            margin: 0 0 20px;
            color: #ccc;
            font-size: 1.1em;
        }

        /* Поле ввода */
        .form-input {
            padding: 15px;
            border: none;
            border-radius: 20px;
            width: 100%;
            background-color: #333;
            color: #fff;
            font-size: 1em;
            box-sizing: border-box;
        }

        .form-input:focus {
            outline: 2px solid #ffcc00;
        }

        /* Кнопка */
        .form-button {
            padding: 15px 30px;
            background-color: #ffcc00;
            border: none;
            border-radius: 20px;
            cursor: pointer;
            font-weight: bold;
            font-size: 1.1em;
            color: #000;
            transition: background-color 0.3s, transform 0.3s;
            width: 100%;
            box-sizing: border-box;
        }

        .form-button:hover {
            background-color: #e6b800;
            transform: translateY(-2px);
        }

        /* Сообщения */
        .message {
            margin: 20px 0;
            padding: 15px;
            border-radius: 10px;
            font-size: 1.1em;
        }

        .success {
            background-color: rgba(40, 167, 69, 0.2);
            border: 1px solid #28a745;
            color: #28a745;
        }

        .error {
            background-color: rgba(220, 53, 69, 0.2);
            border: 1px solid #dc3545;
            color: #dc3545;
        }

        /* Номер участника */
        .participant-number {
            font-size: 2.5em;
            font-weight: bold;
            color: #ffcc00;
            margin: 20px 0;
            padding: 15px;
            background-color: rgba(255, 204, 0, 0.1);
            border-radius: 10px;
            border: 1px dashed #ffcc00;
        }

        /* Рекламные контейнеры */
        .ad-container {
            margin: 30px auto;
            padding: 20px;
            background-color: #2a2a2a;
            border-radius: 8px;
            text-align: center;
            min-height: 250px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .ad-placeholder {
            color: #666;
        }

        /* Адаптивность */
        @media (max-width: 768px) {
            .form-container {
                padding: 20px;
                margin: 20px auto;
            }

            .form-container h1 {
                font-size: 1.5em;
            }
        }

        @media (max-width: 480px) {
            .form-container {
                padding: 15px;
            }

            .form-input, .form-button {
                padding: 12px;
            }

            .participant-number {
                font-size: 2em;
            }
        }
    </style>
</head>
<body>
    <div class="wrapper">
        <!-- Верхний рекламный блок -->
        <div class="ad-container">
            <div id="yandex-ad-container" class="ad-placeholder">Загрузка рекламы...</div>
            <script>
            window.yaContextCb.push(() => {
                Ya.Context.AdvManager.render({
                    "blockId": "R-A-11991079-3",
                    "type": "floorAd",
                    "platform": "desktop",
                    "renderTo": "yandex-ad-container"
                })
            })
            </script>
        </div>

        <div class="form-container">
            <h1>Участвуйте в розыгрыше!</h1>
            <p>Введите ваш Telegram username или VK ID для участия</p>
            
            <?php if (!empty($message)): ?>
                <div class="message <?php echo strpos($message, 'Спасибо') !== false ? 'success' : 'error'; ?>">
                    <?php echo $message; ?>
                </div>
            <?php endif; ?>
            
            <?php if (!empty($participantNumber)): ?>
                <div class="participant-number"><?php echo $participantNumber; ?></div>
            <?php else: ?>
                <form method="POST">
                    <input type="text" name="username" class="form-input" placeholder="@username TG или vk.com/id123" required>
                    <button type="submit" class="form-button">Участвовать</button>
                </form>
            <?php endif; ?>
        </div>

        <!-- Нижний рекламный блок -->
        <div class="ad-container">
            <div id="yandex-ad-container-2" class="ad-placeholder">Загрузка рекламы...</div>
            <script>
            window.yaContextCb.push(() => {
                Ya.Context.AdvManager.render({
                    "blockId": "R-A-11991079-3",
                    "type": "floorAd",
                    "platform": "desktop",
                    "renderTo": "yandex-ad-container-2"
                })
            })
            </script>
        </div>
    </div>

</body>
</html>