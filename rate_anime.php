<?php
session_start();

if (!isset($_SESSION['username'])) {
    echo json_encode(['success' => false, 'message' => 'Необходимо авторизоваться']);
    exit();
}

$input = json_decode(file_get_contents('php://input'), true);
$anime_folder = $input['anime'];
$rating = $input['rating'];

$users = json_decode(file_get_contents('users.json'), true);
$username = $_SESSION['username'];

// Проверка, голосовал ли пользователь уже за это аниме
if (isset($users[$username]['rated'][$anime_folder])) {
    echo json_encode(['success' => false, 'message' => 'Вы уже голосовали за это аниме', 'voted' => true]);
    exit();
}

// Сохраняем оценку пользователя
$users[$username]['rated'][$anime_folder] = $rating;
file_put_contents('users.json', json_encode($users, JSON_PRETTY_PRINT));

// Обновляем средний рейтинг аниме
$anime_data = json_decode(file_get_contents("anime/$anime_folder/data.json"), true);
$ratings = array_column($users, 'rated');
$anime_ratings = array_filter(array_column($ratings, $anime_folder));
$average_rating = array_sum($anime_ratings) / count($anime_ratings);

$anime_data['rating'] = round($average_rating, 1);
file_put_contents("anime/$anime_folder/data.json", json_encode($anime_data, JSON_PRETTY_PRINT));

echo json_encode(['success' => true, 'new_rating' => $anime_data['rating'], 'voted' => true]);

?>