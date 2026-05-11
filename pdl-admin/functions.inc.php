<?php
function menu_topic(bool $rechte, string $titel): void
 {
  global $master_if,$template;
  if($rechte)
   {
    $master_if = true;
    echo '<h2 class="pdl-menu-topic h6">' . htmlspecialchars($titel) . '</h2>';
   }
 }

function menu_link(bool $rechte, string $titel, string $link): void
 {
  global $master_if;
  if($rechte && $master_if === true)
   {
    echo '<a class="pdl-menu-link" href="' . htmlspecialchars($link) . '">'
        . htmlspecialchars($titel) . '</a>';
   }
 }

function menu_close(): void
 {
  global $master_if;
  // Bei der Bootstrap-Sidebar trennen die einzelnen Menü-Links sich selbst
  // ueber CSS, daher ist hier kein zusaetzliches Trennelement noetig.
  $master_if = false;
 }

function pdlif(bool $bedingung, string $true, string $false): string
 {
  if($bedingung) return $true;
  else return $false;
 }

function makedialog(string $titel, string $text, string $button, string $action): string
 {
  global $template;
  return '
<form action="' . htmlspecialchars($action) . '?submit=1" method="post" class="pdl-confirm-form">
<div class="card pdl-card mx-auto pdl-danger-action" style="max-width: 720px;">
    <header class="card-header bg-danger text-white">
        <h2 class="h5 mb-0">' . htmlspecialchars($titel) . '</h2>
    </header>
    <div class="card-body">
        ' . $text . '
    </div>
    <div class="card-footer text-end">
        <button type="submit" class="btn btn-danger">' . htmlspecialchars($button) . '</button>
    </div>
</div>
</form>
  ';
 }

// treeview_select() is now defined in pdl_functions.inc.php

function delrelease(int $id): void
 {
  global $sql_table,$db_handler;
  $id_escaped = $db_handler->sql_escape_int($id);
  $delscreens_res = $db_handler->sql_query("SELECT * FROM " . $sql_table['screens'] . " WHERE release_id='$id_escaped'");
  while($delscreens_row = $db_handler->sql_fetch_array($delscreens_res))
   {
    unlink("../pdl-gfx/screens/release".$id."screen".$delscreens_row['screen_id']."g.jpg");
    unlink("../pdl-gfx/screens/release".$id."screen".$delscreens_row['screen_id']."k.jpg");
   }
  $db_handler->sql_query("DELETE FROM " . $sql_table['screens'] . " WHERE release_id='$id_escaped'");
  $db_handler->sql_query("DELETE FROM " . $sql_table['comments'] . " WHERE release_id='$id_escaped'");
  $db_handler->sql_query("DELETE FROM " . $sql_table['files'] . " WHERE release_id='$id_escaped'");
  $db_handler->sql_query("DELETE FROM " . $sql_table['release'] . " WHERE release_id='$id_escaped'");
 }

function check_gd(): void
 {
  global $settings;
  $settings['gdversion'] = 0;
  if(!extension_loaded("gd")) $settings['gdversion'] = 0;
  elseif(function_exists("gd_info"))
   {
    $gd_info = gd_info();
    if(strstr($gd_info['GD Version'],"2.")) $settings['gdversion'] = 2;
    elseif(strstr($gd_info['GD Version'],"1.")) $settings['gdversion'] = 1;
   }
  else
   {
    ob_start();
    phpinfo(INFO_MODULES);
    $phpinfo = strip_tags((string) ob_get_contents());
    ob_end_clean();
    preg_match("/gd version\s*(.*)/i",$phpinfo,$version);
    if(strstr($version[1],"2.")) $settings['gdversion'] = 2;
    elseif(strstr($version[1],"1.")) $settings['gdversion'] = 1;
   }
 }

/**
 * Rendert eine Bootstrap-Breadcrumb für den Admin-Bereich.
 *
 * @param array<int, array{title: string, href?: string}> $items
 */
function pdl_admin_breadcrumb(array $items): void
{
    echo '<nav aria-label="Navigationspfad"><ol class="breadcrumb">';
    $count = count($items);
    foreach ($items as $i => $item) {
        $title = htmlspecialchars($item['title'] ?? '');
        if ($i === $count - 1 || empty($item['href'])) {
            echo '<li class="breadcrumb-item active" aria-current="page">' . $title . '</li>';
        } else {
            echo '<li class="breadcrumb-item"><a href="' . htmlspecialchars($item['href']) . '">' . $title . '</a></li>';
        }
    }
    echo '</ol></nav>';
}

/**
 * Rendert ein Bootstrap-Alert im Admin-Bereich.
 */
function pdl_admin_alert(string $type, string $message): string
{
    $allowed = ['success', 'danger', 'warning', 'info', 'primary', 'secondary'];
    if (!in_array($type, $allowed, true)) {
        $type = 'info';
    }
    return '<div class="alert alert-' . htmlspecialchars($type) . '" role="alert">' . $message . '</div>';
}
?>
