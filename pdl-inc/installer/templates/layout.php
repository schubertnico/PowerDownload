<?php

/**
 * PowerDownload - Seitenlayout von Installer und update.php
 *
 * Liefert eine Funktion; direkt aufgerufen gibt die Datei nichts aus. Kein
 * JavaScript nötig. Bootstrap kommt wie im Adminbereich vom CDN; ohne
 * Internet bleibt die Seite ungestylt, aber bedienbar.
 *
 * @package    PowerDownload
 * @license    MIT License
 */

declare(strict_types=1);

use PowerDownload\Installer\Html;
use PowerDownload\Installer\Wizard;

/**
 * @param int $step aktueller Schritt (0 = ohne Schrittanzeige)
 * @param int $completed höchster vollständig erledigter Schritt
 * @param callable(): void $content
 */
return static function (string $title, string $badge, int $step, int $completed, callable $content): void {
    $total = count(Wizard::STEPS);
    $progress = $step > 0 ? (int) round(($step - 1) / ($total - 1) * 100) : 100;
    ?>
<!DOCTYPE html>
<html lang="de">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex, nofollow">
<title><?php echo Html::escape($title); ?> · PowerDownload-<?php echo Html::escape($badge); ?></title>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
</head>
<body class="bg-body-tertiary d-flex flex-column min-vh-100">
<nav class="navbar navbar-dark bg-dark" aria-label="<?php echo Html::escape($badge); ?>">
  <div class="container-lg">
    <span class="navbar-brand fw-semibold" id="pdl_install_brand">
      PowerDownload <span class="badge text-bg-warning ms-1 align-middle"><?php echo Html::escape($badge); ?></span>
    </span>
  </div>
</nav>

<main class="container-lg py-4 flex-grow-1" id="pdl_install_main">
  <div class="row justify-content-center">
    <div class="col-lg-10 col-xl-8">
      <h1 class="h3 mb-3" id="pdl_install_page_title"><?php echo Html::escape($title); ?></h1>
<?php if ($step > 0) { ?>
      <nav class="mb-4" aria-label="Installationsschritte">
        <div class="progress mb-2" role="progressbar" aria-label="Fortschritt" aria-valuenow="<?php echo $progress; ?>" aria-valuemin="0" aria-valuemax="100" style="height: 6px;">
          <div class="progress-bar" style="width: <?php echo $progress; ?>%"></div>
        </div>
        <ol class="list-unstyled d-flex flex-wrap gap-2 mb-0" id="pdl_install_steps">
<?php
        foreach (Wizard::STEPS as $number => $label) {
            $isCurrent = $number === $step;
            $isDone = $number <= $completed && !$isCurrent;
            $class = $isCurrent
                ? 'text-bg-primary'
                : ($number <= $completed ? 'text-bg-success' : 'text-bg-light border text-body-secondary');
            $text = $number . '. ' . $label . ($isDone ? ' ✓' : '');

            if (!$isCurrent && $number <= $completed + 1) {
                ?>
          <li><a class="badge rounded-pill text-decoration-none <?php echo $class; ?>" id="pdl_install_step_link_<?php echo $number; ?>" href="install.php?step=<?php echo $number; ?>"><?php echo Html::escape($text); ?></a></li>
<?php
            } else {
                ?>
          <li><span class="badge rounded-pill <?php echo $class; ?>" id="pdl_install_step_link_<?php echo $number; ?>"<?php echo $isCurrent ? ' aria-current="step"' : ''; ?>><?php echo Html::escape($text); ?></span></li>
<?php
            }
        }
    ?>
        </ol>
        <p class="small text-body-secondary mt-2 mb-0" id="pdl_install_step_counter">Schritt <?php echo $step; ?> von <?php echo $total; ?></p>
      </nav>
<?php } ?>

<?php $content(); ?>
    </div>
  </div>
</main>

<footer class="bg-dark text-light py-3 mt-auto">
  <div class="container-lg text-center">
    <small>PowerDownload &copy; <a class="link-light" href="https://www.powerscripts.org" target="_blank" rel="noopener noreferrer">PowerScripts</a></small>
  </div>
</footer>
</body>
</html>
<?php
};
