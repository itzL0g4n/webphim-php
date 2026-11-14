<?php
require __DIR__.'/bootstrap.php';
if(!app_feature_enabled()){ header('Location:'.site()); exit; }

$pageTitle='Apple View • '.setv('site_name','FilmNova');
$error='';
$verified=app_ios_verified();
$returnSlug=trim((string)($_GET['slug']??$_POST['slug']??''));

if($_SERVER['REQUEST_METHOD']==='POST'){
  csrf_check();
  if(!request_is_apple()){
    $error='Thiết bị của bạn không phải iPhone, iPad hoặc Mac.';
    $verified=false;
  } else {
    $pin=trim((string)($_POST['pin']??''));
    if(app_verify_ios_pin($pin)){ $verified=true; }
    else { $error='Mã PIN không hợp lệ.'; $verified=false; }
  }
}

$siteName=setv('site_name','FilmNova');
$help=setv('app_ios_help','Mở bằng iPhone, iPad hoặc Mac rồi nhập mã PIN để tiếp tục.');
$target=$returnSlug!=='' ? site('watch.php?slug='.rawurlencode($returnSlug)) : site('browse.php');
?>
<!doctype html>
<html lang="vi">
<head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<meta name="theme-color" content="#070b14"><meta name="robots" content="noindex,nofollow">
<title><?=e($pageTitle)?></title>
<style>
:root{color-scheme:dark}*{box-sizing:border-box}html,body{min-height:100%;margin:0}body{font-family:Inter,system-ui,-apple-system,BlinkMacSystemFont,"Segoe UI",sans-serif;background:radial-gradient(900px 500px at 50% -10%,rgba(99,102,241,.18),transparent 58%),radial-gradient(700px 500px at 100% 100%,rgba(16,185,129,.08),transparent 55%),#070b14;color:#f8fafc;display:flex;align-items:center;justify-content:center;padding:24px}.wrap{width:min(620px,100%)}.brand{text-align:center;font-weight:800;font-size:28px;letter-spacing:-.4px;margin-bottom:18px}.brand span{display:inline-grid;place-items:center;width:42px;height:42px;margin-right:9px;border-radius:13px;background:linear-gradient(135deg,#fff,#dbeafe);color:#111827;vertical-align:-9px;box-shadow:0 10px 30px rgba(255,255,255,.08)}.card{background:linear-gradient(180deg,rgba(17,24,39,.97),rgba(10,15,27,.97));border:1px solid rgba(148,163,184,.16);border-radius:26px;padding:34px;box-shadow:0 30px 90px rgba(0,0,0,.45);text-align:center}.eyebrow{font-size:12px;letter-spacing:.12em;color:#94a3b8;font-weight:700}.title{font-size:clamp(28px,5vw,40px);margin:10px 0 10px;line-height:1.12}.desc{margin:0 auto 24px;color:#aab5c7;line-height:1.7;max-width:520px}.notice{border:1px solid rgba(248,113,113,.2);background:rgba(127,29,29,.18);color:#fecaca;border-radius:15px;padding:15px;text-align:left;margin:18px 0}.ok{border:1px solid rgba(52,211,153,.2);background:rgba(6,78,59,.18);color:#bbf7d0;border-radius:15px;padding:15px;text-align:left;margin:18px 0}.row{display:flex;gap:12px;align-items:center;justify-content:center;flex-wrap:wrap;margin-top:18px}.btn{display:inline-flex;align-items:center;justify-content:center;border:0;border-radius:12px;padding:12px 18px;font-weight:800;text-decoration:none;cursor:pointer}.primary{background:#fff;color:#101828}.input{width:100%;padding:14px 15px;border-radius:13px;border:1px solid rgba(148,163,184,.2);background:#0b1220;color:#fff;outline:none;font-size:18px;text-align:center;letter-spacing:.18em}.input:focus{border-color:#60a5fa}.form{margin-top:18px;text-align:left}.form label{display:block;color:#cbd5e1;font-size:13px;margin-bottom:9px}.muted{font-size:12px;color:#718096;margin-top:18px}
</style></head>
<body><main class="wrap"><div class="brand"><span></span><?=e($siteName)?></div><section class="card">
<div class="eyebrow">APPLE VIEW</div>
<h1 class="title">Xem phim trên Apple</h1>
<?php if(!request_is_apple()): ?>
  <p class="desc">Tùy chọn này chỉ hoạt động trên iPhone, iPad hoặc Mac.</p>
  <div class="notice"><b>Thiết bị không được hỗ trợ</b><br>Thiết bị hiện tại không phải hệ điều hành Apple nên không thể tiếp tục bằng tùy chọn này.</div>
<?php elseif(!$verified): ?>
  <p class="desc"><?=e($help)?></p>
  <?php if($error): ?><div class="notice"><b><?=e($error)?></b></div><?php endif; ?>
  <form class="form" method="post">
    <input type="hidden" name="csrf" value="<?=e(csrf())?>">
    <input type="hidden" name="slug" value="<?=e($returnSlug)?>">
    <label for="pin">Mã PIN</label>
    <input class="input" id="pin" name="pin" inputmode="numeric" autocomplete="one-time-code" maxlength="20" required autofocus>
    <div class="row"><button class="btn primary" type="submit">Tiếp tục</button></div>
  </form>
<?php else: ?>
  <div class="ok"><b>✓ Thiết bị Apple đã được xác nhận</b><br>Bạn có thể tiếp tục xem phim trên web.</div>
  <div class="row"><a class="btn primary" href="<?=e($target)?>">Tiếp tục xem phim</a></div>
<?php endif; ?>
<div class="muted">FilmNova • Apple Web View</div>
</section></main></body></html>
