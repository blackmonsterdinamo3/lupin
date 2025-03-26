<?php
session_start();
$logged_in = isset($_SESSION['username']);
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $page_title ?? 'Lupin.su' ?></title>
    <link rel="stylesheet" href="/css/style.css">
    <link rel="stylesheet" href="/css/header.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    
    <!-- Yandex.RTB загрузчик -->
    <script>window.yaContextCb = window.yaContextCb || [];</script>
    <script src="https://yandex.ru/ads/system/context.js" async></script>
</head>
<body>
    <div class="wrapper">
        <img src="/logo.png" alt="Логотип" class="logo">
    </div>

    <header class="rounded-header">
        <nav>
            <a href="/index.php" class="nav-button">Главная</a>
            <a href="/news.php" class="nav-button">Новости</a>
            <?php if ($logged_in && ($_SESSION['is_admin'] ?? false)): ?>
                <a style="background-color: purple;" href="/admin.php" class="nav-button">Панель управления</a>
            <?php endif; ?>
            <?php if ($logged_in): ?>
                <a href="/profile.php" class="nav-button">Профиль</a>
                <a href="/logout.php" class="nav-button">Выйти</a>
            <?php else: ?>
                <a href="/login.php" class="nav-button">Войти</a>
                <a href="/register.php" class="nav-button">Регистрация</a>
            <?php endif; ?>
        </nav>
    </header>

    <?php if ($logged_in): ?>
        <div class="welcome-panel">
            <span class="welcome-message">Рады вас видеть, <?= htmlspecialchars($_SESSION['username']) ?>!</span>
        </div>
    <?php endif; ?>