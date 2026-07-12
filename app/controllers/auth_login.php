<?php
// Редірект на Google OAuth consent screen.
$cfg = auth_config()['google_oauth'];

if (empty($cfg['client_id'])) {
    // Google ще не налаштований. На localhost показуємо заглушку для
    // тестування адмінки — на проді показуємо чесну помилку.
    if (IS_LOCAL) {
        $pageTitle = 'Вхід (тест) — Bee Genius';
        require __DIR__ . '/../views/layout/header.php';
        ?>
        <section class="section" style="max-width:480px;margin:0 auto;text-align:center">
          <h2 class="serif">Тестовий вхід</h2>
          <p style="margin-bottom:24px">
            Google OAuth ще не налаштований (немає client_id/client_secret у <code>config.php</code>).
            Це тимчасова заглушка тільки для localhost, щоб можна було протестувати адмінку.
            Вона не працює на проді.
          </p>
          <a class="btn btn-primary" href="<?= BASE_PATH ?>/dev-login">Увійти як тестовий адміністратор</a>
        </section>
        <?php
        require __DIR__ . '/../views/layout/footer.php';
        exit;
    }

    http_response_code(500);
    echo 'Google OAuth ще не налаштований. Додайте client_id/client_secret у config/config.php.';
    exit;
}

header('Location: ' . google_login_url());
exit;
