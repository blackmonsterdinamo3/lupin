<?php
session_start();

if (!isset($_GET['folder'])) {
    header("Location: index.php");
    exit();
}

$folder = $_GET['folder'];
$anime_data = json_decode(file_get_contents("anime/$folder/data.json"), true);

if (!$anime_data) {
    die("Аниме не найдено.");
}

// Загружаем данные о рекламных блоках
$ads_data = json_decode(file_get_contents("ads/ads.json"), true);

include_once 'header.php';
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($anime_data['title']) ?> - Lupin.su</title>
    <link rel="stylesheet" href="css/series.css">
    <link rel="stylesheet" href="css/adblock.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script>window.yaContextCb = window.yaContextCb || [];</script>
    <script src="https://yandex.ru/ads/system/context.js" async></script>
    <script src="https://sdk.adlook.tech/inventory/core.js" async type="text/javascript"></script>
</head>
<body>
    <div id="yandex-rtb-ad" style="margin: 20px auto; text-align: center;"></div>
    <div id="ut-flying"></div>
<div class="m-container">
            <div id="reklama">
    <main class="series-container">
   <div class="banner">
    <img src="anime/<?= htmlspecialchars($folder) ?>/banner.jpg" alt="<?= htmlspecialchars($anime_data['title']) ?>">
    <div class="banner-overlay">
        <h1><?= htmlspecialchars($anime_data['title']) ?></h1>
        <p><?= htmlspecialchars($anime_data['description']) ?></p>
    </div>
    <div class="black-highlight"></div>
</div>
            <div class="black-highlight"></div>
        </section>

        <section class="original-title-box">
            <h2>Оригинальное название</h2>
            <p><?= htmlspecialchars($anime_data['original_title']) ?></p>
        </section>

        <section class="anime-info">
            <div class="info-box">
                <h2>Жанры</h2>
                <p><?= htmlspecialchars(implode(', ', $anime_data['genres'])) ?></p>
            </div>
            <div class="info-box">
                <h2>Возрастное ограничение</h2>
                <p><?= htmlspecialchars($anime_data['PG'] ?? 'Не указано') ?></p>
            </div>
            <div class="info-box">
                <h2>Рейтинг</h2>
                <p><?= htmlspecialchars($anime_data['rating']) ?></p>
            </div>
            <div class="info-box">
                <h2>Дата выхода</h2>
                <p><?= htmlspecialchars($anime_data['date_of_release'] ?? 'Не указана') ?></p>
            </div>
        </section>

        <section class="actions">
            <a href="add_to_list.php?anime=<?= htmlspecialchars($folder) ?>&list=favorites" class="button">
                <i class="fas fa-star"></i> Добавить в Избранное
            </a>
            <a href="add_to_list.php?anime=<?= htmlspecialchars($folder) ?>&list=planned" class="button">
                <i class="fas fa-calendar-plus"></i> Добавить в Запланированное
            </a>
            <a href="add_to_list.php?anime=<?= htmlspecialchars($folder) ?>&list=dropped" class="button">
                <i class="fas fa-times-circle"></i> Добавить в Заброшенное
            </a>
        </section>

        <section class="seasons">
            <?php foreach ($anime_data['seasons'] as $season): ?>
                <div class="season">
                    <h2><?= htmlspecialchars($season['title']) ?></h2>
                    <div class="episodes">
                        <?php foreach ($season['episodes'] as $episode): ?>
                            <a href="episode.php?folder=<?= htmlspecialchars($folder) ?>&season=<?= htmlspecialchars($season['number']) ?>&episode=<?= htmlspecialchars($episode['number']) ?>" class="episode-link">
                                Серия <?= htmlspecialchars($episode['number']) ?>
                            </a>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </section>
    </main>
             </div>
    </div>


    <footer class="site-footer">
        <div class="footer-content">
            <p>Вся информация, файлы и аудиовизуальные произведения предоставлены на сайте только для ознакомительного просмотра. Возрастное ограничение 18+</p>
            <p>&copy; Lupin.su 2024</p>
        </div>
    </footer>

    <script>
        window.addEventListener('load', () => {
            window.yaContextCb.push(() => {
                Ya.Context.AdvManager.render({
                    "blockId": "<?= $ads_data['yandex_rtb']['blockId'] ?>",
                    "type": "<?= $ads_data['yandex_rtb']['type'] ?>",
                    "platform": "<?= $ads_data['yandex_rtb']['platform'] ?>"
                });
            });

            (function UTCoreInitialization() {
                if (window.UTInventoryCore) {
                    new window.UTInventoryCore({
                        type: "flying",
                        host: <?= $ads_data['adlook']['host'] ?>,
                        content: false,
                        container: "ut-flying",
                        width: <?= $ads_data['adlook']['width'] ?>,
                        height: <?= $ads_data['adlook']['height'] ?>,
                        playMode: "<?= $ads_data['adlook']['playMode'] ?>",
                        align: "<?= $ads_data['adlook']['align'] ?>",
                        verticalAlign: "<?= $ads_data['adlook']['verticalAlign'] ?>",
                        collapse: "<?= $ads_data['adlook']['collapse'] ?>",
                        mobile: {
                            align: "<?= $ads_data['adlook']['mobile']['align'] ?>",
                            verticalAlign: "<?= $ads_data['adlook']['mobile']['verticalAlign'] ?>"
                        }
                    });
                    return;
                }
                setTimeout(UTCoreInitialization, 100);
            })();
        });

        async function checkAdBlock() {
            return new Promise((resolve) => {
                const ad = document.createElement('div');
                ad.className = 'ad-class';
                document.body.appendChild(ad);
                setTimeout(() => {
                    const isBlocked = window.getComputedStyle(ad).display === 'none';
                    document.body.removeChild(ad);
                    resolve(isBlocked);
                }, 100);
            });
        }

        async function handleAdBlockDetection() {
            const isBlocked = await checkAdBlock();
            const mContainer = document.querySelector('.m-container');
            if (isBlocked) {
                mContainer.classList.add('adblock-disabled');
            } else {
                mContainer.classList.remove('adblock-disabled');
            }
        }

        window.addEventListener('load', handleAdBlockDetection);
    </script>
</body>
</html>