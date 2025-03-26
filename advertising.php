<?php
/**
 * Файл с рекламными блоками для подключения через include
 * Конфигурация загружается из отдельного JSON-файла
 */

$configFile = __DIR__ . '/ad_config.json';
$adConfig = json_decode(file_get_contents($configFile), true);

if (json_last_error() !== JSON_ERROR_NONE) {
    // Обработка ошибки чтения конфига
    die('Error loading ad configuration');
}
?>
<!-- Yandex.RTB Загрузчик -->
<script>window.yaContextCb=window.yaContextCb||[]</script>
<script src="https://yandex.ru/ads/system/context.js" async></script>

<!-- Yandex.RTB Блок R-A-11991079-3 -->
<script>
window.yaContextCb.push(() => {
    Ya.Context.AdvManager.render({
        "blockId": "R-A-11991079-3",
        "type": "floorAd",
        "platform": "desktop"
    })
})
</script>

<!-- Adlook SDK -->
<script src="https://sdk.adlook.tech/inventory/core.js" async type="text/javascript"></script>

<!-- Adlook Sticky Banner -->
<script>
(function UTCoreInitialization() {
  if (window.UTInventoryCore) {
    new window.UTInventoryCore({
      type: "sticky",
      host: 1198,
      content: false,
      width: 560,
      height: 315,
      playMode: "autoplay",
      align: "left",
      verticalAlign: "center",
      openTo: "open-creativeView",
      mobile: {
        align: "left",
        verticalAlign: "bottom",
      },
    });
    return;
  }
  setTimeout(UTCoreInitialization, 100);
})();
</script>
<!-- Далее тот же HTML/JS код, что и в первом варианте -->