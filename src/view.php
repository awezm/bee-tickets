<?php
require_once __DIR__ . '/config.php';
function e($s){ return htmlspecialchars($s ?? '', ENT_QUOTES, 'UTF-8'); }
function tpl_header($title=''){ $t=$title? "$title · ".APP_NAME:APP_NAME;
  echo "<!doctype html><meta charset=utf-8><meta name=viewport content='width=device-width,initial-scale=1'><title>$t</title><link rel=stylesheet href=/public/style.css><body>";
  echo "<header><div class=wrap><div class=actions><strong>".APP_NAME."</strong> <a href='/?route=home'>Submit</a> <a href='/?route=portal'>Portal</a> <a href='/?route=admin/login'>Admin</a></div></div></header><main class=wrap>";
}
function tpl_footer(){ echo "</main><footer class=wrap muted>".date('Y')."</footer></body>"; }
