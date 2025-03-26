<?php
session_start();
include_once 'headeradmin.php';

// Проверка, авторизован ли пользователь и является ли он администратором
if (!isset($_SESSION['username']) || !$_SESSION['is_admin']) {
    header("Location: index.php");
    exit();
}

$users = json_decode(file_get_contents('users.json'), true);
$error = '';
$success = '';

// Обработка удаления пользователя
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_user'])) {
    $username = trim($_POST['username']);

    if (isset($users[$username])) {
        unset($users[$username]);
        file_put_contents('users.json', json_encode($users, JSON_PRETTY_PRINT));
        $success = "Пользователь '$username' успешно удален!";
    } else {
        $error = "Пользователь не найден!";
    }
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Управление пользователями</title>
    <link rel="stylesheet" href="css/admin.css">
 <link rel="stylesheet" href="css/style.css">
</head>
<body>
        <!-- Основная область -->
        <main class="content">
            <h1>Управление пользователями</h1>

            <!-- Список пользователей -->
            <table class="users-table">
                <thead>
                    <tr>
                        <th>Имя пользователя</th>
                        <th>Email</th>
                        <th>Действия</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($users as $username => $user): ?>
                        <tr>
                            <td><?= $username ?></td>
                            <td><?= $user['email'] ?></td>
                            <td>
                                <form method="POST" style="display:inline;">
                                    <input type="hidden" name="username" value="<?= $username ?>">
                                    <button type="submit" name="delete_user" class="delete-button">Удалить</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <?php if ($error): ?>
                <p class="error"><?= $error ?></p>
            <?php endif; ?>
            <?php if ($success): ?>
                <p class="success"><?= $success ?></p>
            <?php endif; ?>
        </main>
    </div>
</body>
</html>