<?php
$logged_in = isset($_SESSION['username']);
?>
 <link rel="stylesheet" href="css/header.css">
    <header>
        <img src="logo.png" alt="Логотип" class="logo">
        <nav>
            <a href="index.php" class="nav-button">Главная</a>
            <a href="admin_users.php" class="nav-button">Управление пользователями</a>
			<a href="admin_news.php" class="nav-button">Управление новостями</a>
            <?php if ($logged_in): ?>
                <a href="profile.php" class="nav-button">Профиль</a>
                <span class="welcome-message">Красавчик Админ, <?= $_SESSION['username'] ?>!</span>
            <?php else: ?>
                <a href="forgot_password.php" class="nav-button">Забыли пароль?</a>
            	<a href="logout.phpforgot_password.php" class="nav-button">Выйти</a>
            <?php endif; ?>
        </nav>
    </header>