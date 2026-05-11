<?php
// Debug Infos
/** @psalm-suppress TypeDoesNotContainType */
if(!empty($settings['debug']))
 {
  $rendertime2=microtime();
  $rendertimetemp=explode(" ",$rendertime2);
  $rendertime2=(float)$rendertimetemp[0]+(float)$rendertimetemp[1];
  $rendertime=$rendertime2-$rendertime1;
  $rendertime=round($rendertime,3);
  echo '<div class="alert alert-secondary text-center small mt-3 mb-0">Renderzeit: '
      . htmlspecialchars((string)$rendertime, ENT_QUOTES, 'UTF-8') . 's &middot; '
      . htmlspecialchars((string)$db_handler->querys, ENT_QUOTES, 'UTF-8') . ' SQL-Anfragen</div>';
 } ?>
    </div>
</main>
<footer class="pdl-admin-footer text-center small py-3">
    <div class="container-fluid">
        &copy; <a href="https://www.powerscripts.org" target="_blank" rel="noopener" class="link-light">https://www.powerscripts.org</a> &middot;
        <a href="../<?php echo htmlspecialchars((string)($settings['script_file'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>" class="link-light">Zur Übersicht</a>
    </div>
</footer>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
(function () {
    var sidebar = document.querySelector('.pdl-admin-sidebar');
    if (!sidebar) return;
    var storageKey = 'pdlAdminSidebarScroll';
    var saved = sessionStorage.getItem(storageKey);
    if (saved !== null) {
        sidebar.scrollTop = parseInt(saved, 10) || 0;
    }
    sidebar.addEventListener('click', function (e) {
        if (e.target.closest('a')) {
            sessionStorage.setItem(storageKey, String(sidebar.scrollTop));
        }
    });
    var current = document.querySelector('.pdl-admin-nav .pdl-menu-link.active, .pdl-admin-nav a[aria-current="page"]');
    if (current && saved === null) {
        var rect = current.getBoundingClientRect();
        var sbRect = sidebar.getBoundingClientRect();
        if (rect.top < sbRect.top || rect.bottom > sbRect.bottom) {
            current.scrollIntoView({ block: 'center' });
        }
    }
})();
</script>
</body>
</html>
