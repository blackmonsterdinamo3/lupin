<?php
session_start();

if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit();
}

$anime_folder = $_GET['anime']; // Название папки аниме
$list = $_GET['list']; // Раздел, из которого нужно удалить (history, planned, favorites, dropped)

$users = json_decode(file_get_contents('users.json'), true);
$username = $_SESSION['username'];

if (isset($users[$username])) {
    switch ($list) {
        case 'history':
            // Удаляем из истории
            if (isset($users[$username]['history'])) {
                foreach ($users[$username]['history'] as $key => $item) {
                    if (is_array($item) && isset($item['folder']) && $item['folder'] === $anime_folder) {
                        // Удаляем элемент, если это объект с полем 'folder'
                        unset($users[$username]['history'][$key]);
                    } elseif ($key === $anime_folder) {
                        // Удаляем элемент, если ключ совпадает с названием аниме
                        unset($users[$username]['history'][$key]);
                    }
                }
            }
            break;

        case 'planned':
            // Удаляем из запланированного
            if (($key = array_search($anime_folder, $users[$username]['planned'])) !== false) {
                unset($users[$username]['planned'][$key]);
            }
            break;

        case 'favorites':
            // Удаляем из избранного
            if (($key = array_search($anime_folder, $users[$username]['favorites'])) !== false) {
                unset($users[$username]['favorites'][$key]);
            }
            break;

        case 'dropped':
            // Удаляем из заброшенного
            if (($key = array_search($anime_folder, $users[$username]['dropped'])) !== false) {
                unset($users[$username]['dropped'][$key]);
            }
            break;

        default:
            // Неизвестный раздел
            break;
    }

    // Сохраняем обновленные данные
    file_put_contents('users.json', json_encode($users, JSON_PRETTY_PRINT));
}

header("Location: profile.php");
exit();
?>