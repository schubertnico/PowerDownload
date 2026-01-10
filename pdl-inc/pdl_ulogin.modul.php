<?php
/**
 * PowerDownload - User Login Module
 * @license MIT
 */

$script_file = htmlspecialchars($settings['script_file'] ?? '');
echo "
<form name=\"login\" method=\"post\" action=\"" . $script_file . "login=1\">
" . replace($template['ulogin_form'] ?? '', []) . "
</form>";
