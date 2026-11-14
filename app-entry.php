<?php
require __DIR__.'/bootstrap.php';
$exp=(int)($_GET['exp']??0);
$sig=(string)($_GET['sig']??'');
if($exp<time() || $exp>time()+900 || $sig==='' || !hash_equals(app_launch_signature($exp),$sig)){
    http_response_code(404);
    header('Content-Type:text/html; charset=utf-8');
    echo '<!doctype html><html lang="vi"><head><meta name="viewport" content="width=device-width,initial-scale=1"><title>404</title><style>body{margin:0;background:#0b1220;color:#eef2f7;font-family:system-ui,-apple-system,Segoe UI,sans-serif;display:grid;place-items:center;min-height:100vh}.box{text-align:center;padding:34px}.n{font-size:100px;font-weight:900;letter-spacing:-6px}.t{font-size:22px}</style></head><body><div class="box"><div class="n">404</div><div class="t">Trang không khả dụng</div></div></body></html>';
    exit;
}
$_SESSION['fm_app_client_until']=time()+1800;
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Location: '.site('index.php'));
exit;
