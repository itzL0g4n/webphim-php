<?php
declare(strict_types=1);
require __DIR__.'/../bootstrap.php';

$movieId=(int)($_GET['movie']??0);
$episodeId=(int)($_GET['episode']??0);
$sig=(string)($_GET['sig']??'');
function player_not_found_page(): void {
    http_response_code(404);
    header('Content-Type: text/html; charset=utf-8');
    header('Cache-Control: no-store, private');
    echo '<!doctype html><html lang="vi"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><meta name="robots" content="noindex,nofollow"><title>404 — Không tìm thấy trang</title><style>*{box-sizing:border-box}html,body{margin:0;min-height:100%;font-family:Inter,system-ui,-apple-system,BlinkMacSystemFont,"Segoe UI",sans-serif;background:#0b1020;color:#e8ecf6}body{display:flex;align-items:center;justify-content:center;padding:28px;background:radial-gradient(circle at 50% 10%,rgba(76,110,245,.18),transparent 38%),linear-gradient(180deg,#0b1020,#060914)}.nf{width:min(720px,100%);text-align:center;padding:48px 28px}.code{font-size:clamp(92px,20vw,180px);font-weight:900;line-height:.82;letter-spacing:-.08em;background:linear-gradient(135deg,#fff,#7c8cff 55%,#31d0aa);-webkit-background-clip:text;background-clip:text;color:transparent;text-shadow:0 10px 45px rgba(76,110,245,.18)}.badge{display:inline-flex;align-items:center;gap:8px;margin:22px 0 14px;padding:8px 13px;border:1px solid rgba(255,255,255,.1);border-radius:999px;background:rgba(255,255,255,.05);color:#aeb8d6;font-size:13px}.dot{width:7px;height:7px;border-radius:50%;background:#31d0aa;box-shadow:0 0 12px #31d0aa}.title{font-size:clamp(27px,5vw,42px);margin:0 0 12px;font-weight:800}.desc{max-width:560px;margin:0 auto;color:#98a3c2;line-height:1.7;font-size:15px}.actions{display:flex;justify-content:center;gap:12px;flex-wrap:wrap;margin-top:28px}.btn{display:inline-flex;align-items:center;justify-content:center;min-width:150px;padding:12px 18px;border-radius:12px;text-decoration:none;font-weight:700;font-size:14px;border:1px solid rgba(255,255,255,.1);transition:.18s ease}.btn:hover{transform:translateY(-1px)}@media(max-width:520px){.nf{padding:30px 16px}.code{font-size:104px}}</style></head><body><main class="nf"><div class="code">404</div><div class="badge"><span class="dot"></span>Trang không khả dụng</div><h1 class="title">Không tìm thấy trang này</h1></main></body></html>';
    exit;
}
if(!player_request_is_authorized()) player_not_found_page();
if($movieId<=0||$episodeId<=0||!player_signature_valid($movieId,$episodeId,$sig)) player_not_found_page();

$st=db()->prepare('SELECT e.*,m.name AS movie_name FROM episodes e INNER JOIN movies m ON m.id=e.movie_id WHERE e.id=? AND e.movie_id=? AND m.is_hidden=0 LIMIT 1');
$st->execute([$episodeId,$movieId]);
$ep=$st->fetch();
if(!$ep) player_not_found_page();

$src=trim((string)($ep['embed_url']?:$ep['stream_url']?:''));
if($src==='' || !preg_match('~^https://~i',$src)) player_not_found_page();

// The page itself remains same-origin. The actual third-party player URL is only
// reconstructed inside this child frame at runtime, so it is not present in the
// parent watch.php HTML or iframe src.
$srcB64=base64_encode($src);
$origin='https://'.($_SERVER['HTTP_HOST']??'localhost');
header('Content-Type: text/html; charset=utf-8');
header('X-Frame-Options: SAMEORIGIN');
header('Referrer-Policy: strict-origin-when-cross-origin');
header("Content-Security-Policy: default-src 'self' 'unsafe-inline' https: data: blob:; frame-src https: http:; img-src 'self' https: data: blob:; media-src https: http: data: blob:; connect-src https: http: data: blob:; style-src 'self' 'unsafe-inline' https:; script-src 'self' 'unsafe-inline' https:;");
?><!doctype html>
<html lang="vi"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title><?=e((string)$ep['movie_name'])?></title>
<style>html,body{margin:0;width:100%;height:100%;background:#000;overflow:hidden}#p{position:fixed;inset:0;display:flex;align-items:center;justify-content:center;background:#000}iframe{width:100%;height:100%;border:0;background:#000}#msg{position:absolute;inset:0;display:flex;align-items:center;justify-content:center;color:#cbd5e1;font:14px system-ui,sans-serif;background:#000;z-index:2}#msg.hide{display:none}</style></head>
<body><div id="p"><div id="msg">Đang tải trình phát…</div><iframe id="player" allow="autoplay; fullscreen; picture-in-picture; encrypted-media" allowfullscreen referrerpolicy="strict-origin-when-cross-origin"></iframe></div>
<script>
(function(){
  const encoded=<?=json_encode($srcB64,JSON_UNESCAPED_SLASHES)?>;
  const frame=document.getElementById('player');
  const msg=document.getElementById('msg');
  try{
    const url=atob(encoded);
    if(!/^https:\/\//i.test(url)) throw new Error('Nguồn không hợp lệ');
    frame.onload=function(){msg.classList.add('hide');};
    frame.src=url;
    setTimeout(()=>msg.classList.add('hide'),5000);
  }catch(e){msg.textContent='Không thể tải trình phát.';}
  document.addEventListener('contextmenu',e=>e.preventDefault());
})();
</script></body></html>
