<?php
/**
 * PowerDownload - Newsletter verfassen, Vorschau, Testmail und Versand
 *
 * Ablauf: Zeitraum und Empfänger wählen → Text prüfen → „Vorschau anzeigen“
 * → Testmail oder Versand. Empfänger sind nur Konten der gewählten Gruppen,
 * die den Newsletter bestellt haben (get_letter = 'Y'). Versteckte Releases
 * erscheinen nicht. Links sind absolut (Einstellung „site_url“, sonst die
 * Adresse der aktuellen Anfrage). Vor der ersten Mail werden „lastletter“
 * (der nächste Newsletter beginnt dort) und der Versandstand „letter_job“
 * gespeichert, nach jeder Mail die zuletzt bediente Benutzer-ID. Ein zweites
 * „Senden“ verschickt denselben Newsletter nicht noch einmal; nach einem
 * Abbruch setzt es den Versand beim nächsten Empfänger fort.
 */
include("header.inc.php");
include_once("system_helpers.inc.php");
include_once($incdir . "pdl-inc/pdl_mail.inc.php");

if (!pdl_sys_can($user_rights, 'edituser')) {
    echo pdl_admin_alert('warning', pdl_sys_denied_text('edituser') . ' Der Newsletter geht an die E-Mail-Adressen der Benutzer.');
    include("footer.inc.php");
    return;
}

$release_t = pdl_sys_ident($sql_table['release']);
$now = time();
$guest_group_id = pdl_sys_guest_group_id($settings);
$lastletter = (int) ($settings['lastletter'] ?? 0);
$sitename = pdl_mail_sitename($settings);
$url = static fn (string $query): string => pdl_script_url($settings, $query);
$is_post = ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST';
$step = $is_post ? pdl_sys_post('step', 'edit') : 'edit';

$groups = [];
foreach (pdl_sys_groups($db_handler, $sql_table) as $group) {
    if ($group['ugroup_id'] !== $guest_group_id) {
        $groups[$group['ugroup_id']] = $group;
    }
}

/**
 * Releases des Zeitraums (nur sichtbare).
 *
 * @return list<array{release_id: int, name: string, text: string, time: int}>
 */
$load_releases = static function (int $since) use ($db_handler, $release_t, $now): array {
    $list = [];
    $res = $db_handler->sql_query('SELECT release_id, name, text, time FROM ' . $release_t
        . " WHERE released = 'Y' AND time > " . $since . ' AND time <= ' . $now . ' ORDER BY time ASC, release_id ASC');
    while ($row = $db_handler->sql_fetch_array($res)) {
        $list[] = [
            'release_id' => (int) $row['release_id'],
            'name' => (string) $row['name'],
            'text' => (string) $row['text'],
            'time' => (int) $row['time'],
        ];
    }
    return $list;
};
// Die Trennmarke wird immer aus dem Text genommen; geschnitten wird an ihr
// nur, wenn die Vorschau nach Marke getrennt wird.
$marker = (string) ($settings['trenn_string'] ?? '');
$marker_cut = ($settings['trenn_durch'] ?? '') === 'string';

// Formularzustand
$mode = $is_post ? pdl_sys_post('zeitraum') : pdl_sys_get('zeitraum');
$date = $is_post ? pdl_sys_post('ab') : pdl_sys_get('ab');
$period = pdl_sys_letter_period($mode, $date, $lastletter, $now);
$selected_groups = [];
foreach (is_array($_POST['ugroup_ids'] ?? null) ? $_POST['ugroup_ids'] : [] as $gid) {
    if (is_scalar($gid) && isset($groups[(int) $gid])) {
        $selected_groups[] = (int) $gid;
    }
}
if (!$is_post && isset($groups[1])) {
    $selected_groups = [1];
}
$subject = $is_post ? trim(pdl_sys_post('subject')) : 'Neue Downloads bei ' . $sitename;
$text = $is_post ? str_replace("\r\n", "\n", pdl_sys_post('text')) : '';
$test_to = $is_post ? trim(pdl_sys_post('test_to')) : (string) ($user_details['email'] ?? '');
$errors = [];

if (!$is_post || $step === 'fill') {
    $releases = $load_releases($period['since']);
    $text = pdl_sys_letter_text($releases, $period, $sitename, $now, $url, $marker, $marker_cut);
    if ($step === 'fill') {
        $step = 'edit';
    }
}

pdl_admin_breadcrumb([
    ['title' => 'Adminbereich', 'href' => 'index.php'],
    ['title' => 'Newsletter'],
    ['title' => 'Newsletter schreiben'],
]);
echo '<h1 class="h3 pdl-page-title">Newsletter schreiben</h1>';

// Prüfen (Vorschau, Test, Versand)
if (in_array($step, ['preview', 'test', 'send'], true)) {
    if (!pdl_sys_csrf_ok()) {
        $errors[] = pdl_sys_csrf_error_text();
    }
    if ($subject === '' || strlen($subject) > 200) {
        $errors[] = 'Bitte geben Sie einen Betreff ein (höchstens 200 Zeichen).';
    }
    if (trim($text) === '') {
        $errors[] = 'Der Newsletter-Text ist leer.';
    }
    if ($step !== 'test' && $selected_groups === []) {
        $errors[] = 'Bitte wählen Sie mindestens eine Empfängergruppe.';
    }
    if ($step === 'test' && filter_var($test_to, FILTER_VALIDATE_EMAIL) === false) {
        $errors[] = 'Bitte geben Sie für die Testmail eine gültige E-Mail-Adresse ein.';
    }
    if ($errors !== []) {
        echo pdl_admin_alert('danger', '<strong>Bitte prüfen Sie Ihre Angaben.</strong><br>' . implode('<br>', array_map('htmlspecialchars', $errors)));
        $step = $step === 'test' ? 'preview' : 'edit';
        if ($step === 'preview' && $selected_groups === []) {
            $step = 'edit';
        }
    }
}
$recipients = pdl_sys_letter_recipients($db_handler, $sql_table, $selected_groups, $guest_group_id);
$headers = ['List-Unsubscribe' => '<' . $url('usercenter=profil') . '>'];

if ($step === 'test' && $errors === []) {
    if (pdl_send_mail($test_to, '[Test] ' . $subject, $text, $settings)) {
        echo pdl_admin_alert('success', '<strong>Die Testmail an ' . htmlspecialchars($test_to) . ' wurde verschickt.</strong> Prüfen Sie Darstellung und Links, bevor Sie den Newsletter senden.');
    } else {
        echo pdl_admin_alert('danger', '<strong>Die Testmail wurde nicht verschickt.</strong> Bitte prüfen Sie den Mailversand des Servers und die Absenderadresse unter Einstellungen → E-Mail.');
    }
    $step = 'preview';
}

if ($step === 'send' && $errors === []) {
    // Doppelten Versand verhindern: Neuladen der Seite, zweiter Klick auf
    // „Senden“, Abbruch mitten im Versand. Der Versandstand steht in der
    // Datenbank (Einstellung „letter_job“), nicht nur in der Sitzung.
    $fingerprint = sha1($subject . "\n" . $text . "\n" . implode(',', $selected_groups));
    $job = pdl_sys_letter_job_load($db_handler, $sql_table);
    $job_state = pdl_sys_letter_job_state($job, $fingerprint, $now);
    $sent_before = (int) ($_SESSION['pdl_letter_sent'][$fingerprint] ?? 0);
    if ($job_state === 'done' || $sent_before > $now - 3600) {
        $sent_at = $job_state === 'done' && $job !== null ? $job['finished'] : $sent_before;
        $sent_when = date('d.m.Y', $sent_at) === date('d.m.Y', $now) ? 'um ' . date('H:i', $sent_at) : 'am ' . date('d.m.Y', $sent_at) . ' um ' . date('H:i', $sent_at);
        echo pdl_admin_alert('warning', '<strong>Dieser Newsletter wurde bereits ' . $sent_when . ' Uhr verschickt.</strong> Er wird nicht noch einmal gesendet.');
        echo '<a class="btn btn-primary" href="makeletter.php" id="pdlLetterNew">Neuen Newsletter schreiben</a>';
        include("footer.inc.php");
        return;
    }
    if ($job_state === 'running' && $job !== null) {
        echo pdl_admin_alert('warning', '<strong>Dieser Newsletter wird gerade verschickt</strong> (begonnen um ' . date('H:i', $job['started']) . ' Uhr, bisher '
            . pdl_sys_num($job['sent']) . ' Empfänger). Er wird nicht ein zweites Mal gesendet. Laden Sie diese Seite in ein paar Minuten neu: Ist der Versand dann abgeschlossen, sehen Sie das hier; wurde er unterbrochen, setzt PowerDownload ihn fort.');
        echo '<a class="btn btn-primary" href="makeletter.php" id="pdlLetterNew">Neuen Newsletter schreiben</a>';
        include("footer.inc.php");
        return;
    }
    $resume_job = $job_state === 'resume' ? $job : null;
    $resume = $resume_job !== null;
    $last_user_id = $resume_job !== null ? $resume_job['last_user_id'] : 0;
    $todo = array_values(array_filter(
        pdl_sys_letter_recipient_rows($db_handler, $sql_table, $selected_groups, $guest_group_id),
        static fn (array $r): bool => $r['user_id'] > $last_user_id
    ));
    if ($recipients === [] || ($resume && $todo === [])) {
        echo pdl_admin_alert('warning', $recipients === []
            ? 'In den gewählten Gruppen hat niemand den Newsletter bestellt. Es wurde nichts verschickt.'
            : 'Alle Empfänger dieses Newsletters haben ihn bereits erhalten. Es wurde nichts verschickt.');
        if ($resume_job !== null) {
            $resume_job['finished'] = $now;
            pdl_sys_letter_job_save($db_handler, $sql_table, $resume_job);
        }
        $step = 'edit';
    } else {
        ignore_user_abort(true);
        @set_time_limit(300);
        // Versandstand vor der ersten Mail sichern: Bricht der Versand ab,
        // setzt ein erneutes „Senden“ fort, statt von vorn zu beginnen.
        $job = $resume_job ?? [
            'fingerprint' => $fingerprint, 'started' => $now, 'heartbeat' => $now,
            'last_user_id' => 0, 'sent' => 0, 'failed' => 0, 'finished' => 0,
        ];
        $job['heartbeat'] = time();
        $job_saved = pdl_sys_letter_job_save($db_handler, $sql_table, $job);
        $saved = $resume || pdl_sys_setting_store($db_handler, $sql_table, 'lastletter', (string) $now);
        if ($resume) {
            echo pdl_admin_alert('info', '<strong>Der letzte Versand dieses Newsletters wurde unterbrochen.</strong> Er wird jetzt fortgesetzt; wer ihn schon erhalten hat ('
                . pdl_sys_num($job['sent']) . ' Empfänger), bekommt ihn nicht noch einmal.');
        }
        $ok = 0;
        $failed = [];
        foreach ($todo as $i => $recipient) {
            if ($i > 0 && $i % 10 === 0) {
                sleep(1); // höchstens zehn Mails pro Sekunde
            }
            @set_time_limit(60);
            if (pdl_send_mail($recipient['email'], $subject, $text, $settings, $headers)) {
                $ok++;
                $job['sent']++;
            } else {
                $failed[] = $recipient['email'];
                $job['failed']++;
            }
            $job['last_user_id'] = $recipient['user_id'];
            $job['heartbeat'] = time();
            if ($job_saved) {
                pdl_sys_letter_job_save($db_handler, $sql_table, $job);
            }
        }
        if ($job['sent'] === 0) {
            // Keine einzige Mail ging hinaus: Zustand wie vorher, damit ein
            // neuer Versuch nach der Reparatur des Mailversands möglich ist.
            pdl_sys_setting_store($db_handler, $sql_table, 'letter_job', '');
            if (!$resume) {
                pdl_sys_setting_store($db_handler, $sql_table, 'lastletter', (string) $lastletter);
            }
        } else {
            $job['finished'] = time();
            pdl_sys_letter_job_save($db_handler, $sql_table, $job);
        }
        if ($ok > 0) {
            $_SESSION['pdl_letter_sent'][$fingerprint] = $now;
            pdl_audit_log($db_handler, $sql_table, $user_details, 'send', 'newsletter', $ok);
            echo pdl_admin_alert('success', '<strong>Der Newsletter wurde an ' . ($ok === 1 ? 'einen Empfänger' : pdl_sys_num($ok) . ' Empfänger') . ' verschickt.</strong>'
                . ($saved ? ' Der nächste Newsletter beginnt mit den Releases ab heute (' . date('d.m.Y H:i', $resume && $job['started'] > 0 ? $job['started'] : $now) . ' Uhr).' : ' Das Versanddatum konnte nicht gespeichert werden.'));
        }
        if ($failed !== []) {
            echo pdl_admin_alert('danger', '<strong>An ' . count($failed) . ' Adresse(n) konnte nicht gesendet werden:</strong> ' . htmlspecialchars(implode(', ', array_slice($failed, 0, 20)))
                . (count($failed) > 20 ? ' …' : '') . '. Bitte prüfen Sie den Mailversand des Servers.');
        }
        echo '<a class="btn btn-primary" href="makeletter.php" id="pdlLetterNew">Neuen Newsletter schreiben</a>';
        include("footer.inc.php");
        return;
    }
}

$hidden_common = static function () use ($period, $date, $selected_groups, $subject, $text): string {
    $html = csrf_input()
        . '<input type="hidden" name="zeitraum" value="' . htmlspecialchars($period['mode']) . '">'
        . '<input type="hidden" name="ab" value="' . htmlspecialchars($date) . '">'
        . '<input type="hidden" name="subject" value="' . htmlspecialchars($subject) . '">'
        . '<textarea name="text" hidden>' . htmlspecialchars($text) . '</textarea>';
    foreach ($selected_groups as $gid) {
        $html .= '<input type="hidden" name="ugroup_ids[]" value="' . $gid . '">';
    }
    return $html;
};

// ---------------------------------------------------------------------------
// Vorschau
// ---------------------------------------------------------------------------
if ($step === 'preview') {
    $from = trim((string) ($settings['mail_fromname'] ?? '')) . ' <' . trim((string) ($settings['mail_fromaddr'] ?? '')) . '>';
    $group_names = [];
    foreach ($selected_groups as $gid) {
        $group_names[] = $groups[$gid]['name'] . ' (' . pdl_sys_num($groups[$gid]['letter']) . ')';
    }
    ?>
<section class="card pdl-card mb-4" id="pdlLetterPreviewCard">
    <header class="card-header"><h2 class="h5 mb-0">Vorschau: So kommt der Newsletter an</h2></header>
    <div class="card-body">
        <dl class="row mb-3">
            <dt class="col-sm-3">Absender</dt><dd class="col-sm-9" id="pdlLetterPreviewFrom"><?php echo htmlspecialchars($from); ?></dd>
            <dt class="col-sm-3">Betreff</dt><dd class="col-sm-9" id="pdlLetterPreviewSubject"><?php echo htmlspecialchars($subject); ?></dd>
            <dt class="col-sm-3">Empfänger</dt>
            <dd class="col-sm-9" id="pdlLetterPreviewCount">
                <strong><?php echo count($recipients) === 1 ? '1 Empfänger' : pdl_sys_num(count($recipients)) . ' Empfänger'; ?></strong>
                aus <?php echo htmlspecialchars(implode(', ', $group_names)); ?> – nur Konten, die den Newsletter bestellt haben.
                <?php if ($recipients !== []) { ?>
                <details class="mt-1"><summary class="small">Adressen anzeigen</summary><div class="small text-muted"><?php echo htmlspecialchars(implode(', ', $recipients)); ?></div></details>
                <?php } ?>
            </dd>
        </dl>
        <pre class="border rounded p-3 mb-0 text-body" id="pdlLetterPreviewText" style="white-space: pre-wrap; max-height: 24rem; overflow-y: auto; font-size: .95rem"><?php echo htmlspecialchars($text); ?></pre>
    </div>
</section>
<?php if ($recipients === []) { echo pdl_admin_alert('warning', 'In den gewählten Gruppen hat niemand den Newsletter bestellt. Wählen Sie andere Gruppen oder verschicken Sie nur eine Testmail.'); } ?>
<div class="row g-3">
    <div class="col-12 col-lg-6">
        <form action="makeletter.php" method="post" class="card pdl-card h-100" id="pdlLetterTestForm">
            <?php echo $hidden_common(); ?>
            <input type="hidden" name="step" value="test">
            <div class="card-body">
                <label for="pdlLetterTest" class="form-label fw-bold">Testmail an</label>
                <input type="email" class="form-control mb-2" id="pdlLetterTest" name="test_to" value="<?php echo htmlspecialchars($test_to); ?>" style="max-width: 28rem">
                <p class="form-text">Schickt den Newsletter einmal an diese Adresse, mit „[Test]“ im Betreff. Der Zeitpunkt des letzten Newsletters ändert sich dadurch nicht.</p>
                <button type="submit" class="btn btn-outline-light" id="pdlLetterSendTest">Testmail senden</button>
            </div>
        </form>
    </div>
    <div class="col-12 col-lg-6">
        <form action="makeletter.php" method="post" class="card pdl-card h-100" id="pdlLetterSendForm">
            <?php echo $hidden_common(); ?>
            <input type="hidden" name="step" value="send">
            <input type="hidden" name="test_to" value="<?php echo htmlspecialchars($test_to); ?>">
            <div class="card-body">
                <p class="fw-bold mb-2">Newsletter verschicken</p>
                <p class="form-text">Jeder Empfänger erhält eine eigene Mail; andere Adressen sieht er nicht. Danach beginnt der nächste Newsletter bei den Releases ab heute.</p>
                <button type="submit" class="btn btn-primary" id="pdlLetterSend"<?php echo $recipients === [] ? ' disabled' : ''; ?>>Newsletter an <?php echo count($recipients) === 1 ? '1 Empfänger' : pdl_sys_num(count($recipients)) . ' Empfänger'; ?> senden</button>
            </div>
        </form>
    </div>
</div>
<form action="makeletter.php" method="post" class="mt-3" id="pdlLetterBackForm">
    <?php echo $hidden_common(); ?>
    <input type="hidden" name="step" value="edit">
    <input type="hidden" name="test_to" value="<?php echo htmlspecialchars($test_to); ?>">
    <button type="submit" class="btn btn-outline-light" id="pdlLetterBack">Zurück zum Bearbeiten</button>
</form>
    <?php
    include("footer.inc.php");
    return;
}

// ---------------------------------------------------------------------------
// Bearbeiten
// ---------------------------------------------------------------------------
$period_label = $lastletter > 0
    ? 'Der letzte Newsletter ging am ' . date('d.m.Y', $lastletter) . ' um ' . date('H:i', $lastletter) . ' Uhr hinaus.'
    : 'Bisher wurde noch kein Newsletter verschickt.';
?>
<form action="makeletter.php" method="post" id="pdlLetterForm" novalidate>
    <?php echo csrf_input(); ?>
    <button type="submit" name="step" value="preview" tabindex="-1" aria-hidden="true" style="position:absolute;left:-9999px;width:1px;height:1px;overflow:hidden">Vorschau</button>
    <input type="hidden" name="test_to" value="<?php echo htmlspecialchars($test_to); ?>">
    <section class="card pdl-card mb-4">
        <header class="card-header"><h2 class="h5 mb-0">1. Zeitraum</h2></header>
        <div class="card-body">
            <p class="form-text mt-0"><?php echo htmlspecialchars($period_label); ?> Der Text listet alle sichtbaren Releases des gewählten Zeitraums auf; versteckte Releases erscheinen nicht.</p>
            <div class="row g-2 align-items-end">
                <div class="col-12 col-md-5">
                    <label for="pdlLetterZeitraum" class="form-label">Releases</label>
                    <select class="form-select" id="pdlLetterZeitraum" name="zeitraum">
                        <?php if ($lastletter > 0) { ?>
                        <option value="seit_letztem"<?php echo $period['mode'] === 'seit_letztem' ? ' selected' : ''; ?>>seit dem letzten Newsletter (<?php echo date('d.m.Y', $lastletter); ?>)</option>
                        <?php } ?>
                        <option value="30tage"<?php echo $period['mode'] === '30tage' ? ' selected' : ''; ?>>der letzten 30 Tage</option>
                        <option value="datum"<?php echo $period['mode'] === 'datum' ? ' selected' : ''; ?>>ab einem bestimmten Datum</option>
                    </select>
                </div>
                <div class="col-12 col-md-3">
                    <label for="pdlLetterAb" class="form-label">ab Datum</label>
                    <input type="date" class="form-control" id="pdlLetterAb" name="ab" value="<?php echo htmlspecialchars($period['mode'] === 'datum' ? date('Y-m-d', $period['since']) : $date); ?>">
                </div>
                <div class="col-12 col-md-4">
                    <button type="submit" name="step" value="fill" class="btn btn-outline-light w-100" id="pdlLetterFill">Text neu erzeugen</button>
                </div>
            </div>
            <div class="form-text">„Text neu erzeugen“ ersetzt den Text unten durch eine neue Liste für den gewählten Zeitraum. Betreff und Empfänger bleiben erhalten.</div>
        </div>
    </section>

    <section class="card pdl-card mb-4">
        <header class="card-header"><h2 class="h5 mb-0">2. Inhalt</h2></header>
        <div class="card-body">
            <div class="mb-3">
                <label for="pdlLetterSubject" class="form-label">Betreff</label>
                <input type="text" class="form-control" id="pdlLetterSubject" name="subject" maxlength="200" value="<?php echo htmlspecialchars($subject); ?>">
            </div>
            <label for="pdlLetterText" class="form-label">Text</label>
            <textarea id="pdlLetterText" name="text" class="form-control font-monospace" rows="18" aria-describedby="pdlLetterTextHelp"><?php echo htmlspecialchars($text); ?></textarea>
            <div class="form-text" id="pdlLetterTextHelp">Reiner Text ohne HTML. Die Links sind vollständige Adressen (<?php echo htmlspecialchars(pdl_site_base_url($settings)); ?>…) und funktionieren in jedem Mailprogramm.</div>
        </div>
    </section>

    <section class="card pdl-card mb-4">
        <header class="card-header"><h2 class="h5 mb-0">3. Empfänger</h2></header>
        <div class="card-body">
            <p class="form-text mt-0">Der Newsletter geht nur an Benutzer der gewählten Gruppen, die ihn in ihrem Profil bestellt haben.</p>
            <?php foreach ($groups as $gid => $group) { ?>
            <div class="form-check">
                <input class="form-check-input" type="checkbox" name="ugroup_ids[]" value="<?php echo $gid; ?>" id="pdlLetterGroup_<?php echo $gid; ?>"<?php echo in_array($gid, $selected_groups, true) ? ' checked' : ''; ?>>
                <label class="form-check-label" for="pdlLetterGroup_<?php echo $gid; ?>"><?php echo htmlspecialchars($group['name']); ?>
                    <span class="text-muted">– <?php echo $group['letter'] === 1 ? '1 Empfänger' : pdl_sys_num($group['letter']) . ' Empfänger'; ?> von <?php echo pdl_sys_num($group['members']); ?> Benutzern</span></label>
            </div>
            <?php } ?>
        </div>
    </section>

    <div class="d-grid d-md-flex gap-2 justify-content-md-end">
        <a href="makeletter.php" class="btn btn-outline-light" id="pdlLetterReset">Neu beginnen</a>
        <button type="submit" name="step" value="preview" class="btn btn-primary" id="pdlLetterPreview">Vorschau anzeigen</button>
    </div>
</form>
<?php
include("footer.inc.php");
