<?php

/**
 * PowerDownload - update.php ohne Berechtigung (HTTP 403)
 *
 * @package    PowerDownload
 * @license    MIT License
 */

declare(strict_types=1);

return static function (bool $loggedIn): void {
    ?>
<div class="alert alert-warning" role="alert" id="pdl_update_denied" data-logged-in="<?php echo $loggedIn ? '1' : '0'; ?>">
  <strong>Nur für Administratoren.</strong><br>
<?php if ($loggedIn) { ?>
  Ihrem Konto fehlt das Recht, die Einstellungen zu verwalten. Bitte melden Sie sich mit einem Administrator-Konto an.
<?php } else { ?>
  Bitte melden Sie sich zuerst im Adminbereich an und rufen Sie <code>update.php</code> danach erneut auf.
<?php } ?>
</div>
<p><a class="btn btn-primary" href="pdl-admin/" id="pdl_update_link_login">Zum Adminbereich</a></p>
<?php
};
