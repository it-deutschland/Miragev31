<?php
declare(strict_types=1);

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/functions.php';
$title = 'Mirage Projekt';
require __DIR__ . '/includes/header.php';
?>
<section class="app-card hero-banner reveal-up fx-tilt" data-tilt-card>
    <div class="hero-media">
        <img src="<?= e($mirageHeaderImage) ?>" alt="Mirage VIP Header">
    </div>
    <div class="hero-overlay p-4 p-lg-5">
        <div class="hero-copy">
            <span class="cyber-chip">Mirage VIP Lounge</span>
            <h1 class="hero-title mt-3 mb-3">Dein exklusiver Zugang in eine Welt für Erwachsene</h1>
            <p class="mb-0">Willkommen im Mirage VIP Bereich – einem exklusiven Kosmos für volljährige Gäste. Sichere dir deinen privaten Zugang, betrete deine persönliche Lounge und erlebe eine außergewöhnliche FSK18-Atmosphäre mit modernem Cyber-Luxury-Design.</p>
            <div class="hero-meta">
                <span class="cyber-chip">FSK18</span>
                <span class="cyber-chip">Private VIP Area</span>
                <span class="cyber-chip">Exklusiver Zugang</span>
            </div>
            <div class="d-flex flex-wrap gap-2 mt-4">
                <a class="btn btn-primary" href="<?= e(appUrl('/login/telegram')) ?>">VIP Zugang</a>
                <a class="btn btn-outline-light" href="<?= e(appUrl('/admin/login')) ?>">Admin Login</a>
            </div>
        </div>
    </div>
</section>

<div class="row g-4 mt-1">
    <div class="col-lg-4 reveal-up">
        <div class="app-card surface-panel p-4 h-100">
            <span class="cyber-chip">Private Access</span>
            <h2 class="h4 mt-3">Exklusiv für Volljährige</h2>
            <p class="mb-0">Der Zugang zum Mirage VIP Bereich ist ausschließlich für Erwachsene bestimmt. Über Telegram gelangst du schnell und unkompliziert in deine private Lounge.</p>
        </div>
    </div>


<div class="col-lg-4 reveal-up">
    <div class="app-card surface-panel p-4 h-100">
        <span class="cyber-chip">VIP Experience</span>
        <h2 class="h4 mt-3">Dein Zugang. Deine Welt.</h2>
        <p class="mb-0">Aktiviere deinen Voucher und entdecke einen exklusiven FSK18-Bereich mit einer besonderen Atmosphäre, stilvollem Design und einem privaten Erlebnis abseits des Gewöhnlichen.</p>
    </div>
</div>

<div class="col-lg-4 reveal-up">
    <div class="app-card surface-panel p-4 h-100">
        <span class="cyber-chip">Secure Control</span>
        <h2 class="h4 mt-3">Privat & diskret</h2>
        <p class="mb-0">Der Mirage VIP Bereich verbindet exklusiven Content mit einem klaren, diskreten Zugangssystem – für eine private Umgebung, die ganz auf volljährige Nutzer ausgerichtet ist.</p>
    </div>
</div>


</div>

<?php require __DIR__ . '/includes/footer.php'; ?>
