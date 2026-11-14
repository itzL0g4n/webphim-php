<?php
require dirname(__DIR__).'/bootstrap.php'; require_admin();
$adminTitle='Ứng dụng';
function app_make_key(): string { return bin2hex(random_bytes(24)); }
if($_SERVER['REQUEST_METHOD']==='POST'){
    csrf_check();
    $key=trim((string)($_POST['app_api_key']??''));
    if(isset($_POST['regenerate'])) $key=app_make_key();
    if($key==='') $key=app_make_key();
    setv_save('app_api_key',$key);
    setv_save('app_player_mode',trim((string)($_POST['app_player_mode']??'web'))==='web'?'web':'web');
    header('Location: app.php?saved=1');exit;
}
require __DIR__.'/_header.php';
$apiKey=setv('app_api_key',''); if($apiKey===''){ $apiKey=app_make_key(); setv_save('app_api_key',$apiKey); }
$base=site('api/app.php');
?>
<section class="admin-card"><div class="admin-head"><div><small>APP API</small><h1>Ứng dụng FilmNova</h1></div></div>
<?php if(isset($_GET['saved'])): ?><div class="sync-status" style="margin-bottom:16px">✓ Đã lưu cấu hình.</div><?php endif; ?>
<form class="admin-form" method="post"><input type="hidden" name="csrf" value="<?=e(csrf())?>">
<label>API Key cho ứng dụng<input name="app_api_key" value="<?=e($apiKey)?>" autocomplete="off"><small>Không chia sẻ khóa này công khai. App Windows/Android dùng khóa này để gọi API.</small></label>
<div style="display:flex;gap:10px;flex-wrap:wrap"><button class="btn primary" name="save" value="1">Lưu API Key</button><button class="btn" name="regenerate" value="1">Tạo API Key mới</button></div>
</form>
<div class="sync-status" style="margin-top:18px"><b>API URL</b><div style="margin-top:7px"><code><?=e($base)?></code></div><p style="margin:.55rem 0 0">Ví dụ: <code><?=e($base.'?action=list&page=1')?></code></p></div>
<div class="sync-status" style="margin-top:12px"><b>Source AutoIt</b><p style="margin:.55rem 0 0">Mã nguồn mẫu nằm trong thư mục <code>apps/FilmNovaPlayer.au3</code>.</p></div>
</section>
<?php require __DIR__.'/_footer.php'; ?>
