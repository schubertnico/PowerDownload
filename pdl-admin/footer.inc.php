<?php
// Debug Infos
/** @psalm-suppress TypeDoesNotContainType */
if(!empty($settings['debug']))
 {
  $rendertime = round(microtime(true) - (float) $rendertime1, 3);
  echo '<div class="alert alert-secondary text-center small mt-3 mb-0">Renderzeit: '
      . htmlspecialchars((string)$rendertime, ENT_QUOTES, 'UTF-8') . 's &middot; '
      . htmlspecialchars((string)$db_handler->querys, ENT_QUOTES, 'UTF-8') . ' SQL-Anfragen</div>';
 } ?>
    </div>
</main>
<footer class="pdl-admin-footer text-center small py-3">
    <div class="container-fluid">
        &copy; <a href="https://www.powerscripts.org" target="_blank" rel="noopener" class="link-light">https://www.powerscripts.org</a> &middot;
        <a href="../<?php echo htmlspecialchars((string)($settings['script_file'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>" class="link-light">Zur Webseite</a>
    </div>
</footer>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
(function () {
    var sidebar = document.querySelector('.pdl-admin-sidebar');
    if (!sidebar) return;
    var storageKey = 'pdlAdminSidebarScroll';
    var saved = null;
    try { saved = sessionStorage.getItem(storageKey); } catch (e) { saved = null; }
    if (saved !== null) {
        sidebar.scrollTop = parseInt(saved, 10) || 0;
    }
    sidebar.addEventListener('click', function (e) {
        if (e.target.closest('a')) {
            try { sessionStorage.setItem(storageKey, String(sidebar.scrollTop)); } catch (err) { /* ohne Speicher weiter */ }
        }
    });
    // Den aktiven Menüpunkt immer sichtbar halten (nur die Seitenleiste scrollen).
    // Gerechnet wird mit offsetTop/clientHeight, die auch bei CSS-Zoom in
    // derselben Einheit wie scrollTop vorliegen.
    // Erneut prüfen, wenn sich die Höhe der Leiste ändert (z. B. nachträglich
    // gesetzter Seitenzoom oder Fenstergröße).
    var current = sidebar.querySelector('.pdl-menu-link.active');
    function zeigeAktiven() {
        if (!current) return;
        var top = 0;
        for (var el = current; el && el !== sidebar; el = el.offsetParent) {
            top += el.offsetTop;
        }
        var bottom = top + current.offsetHeight;
        if (top < sidebar.scrollTop || bottom > sidebar.scrollTop + sidebar.clientHeight) {
            sidebar.scrollTop = Math.max(0, top - (sidebar.clientHeight - current.offsetHeight) / 2);
        }
    }
    zeigeAktiven();
    window.addEventListener('load', zeigeAktiven);
    if (window.ResizeObserver) {
        new ResizeObserver(zeigeAktiven).observe(sidebar);
    }
})();
</script>
</body>
</html>
