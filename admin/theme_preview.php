<?php
require_once dirname(__DIR__).'/bootstrap.php';
require_admin();
$token=preg_replace('/[^a-z0-9_-]/i','',$_GET['token']??'');
$slug=preg_replace('/[^a-z0-9_-]/i','',$_GET['slug']??'');
$root=$token ? __DIR__.'/../themes/.staging/'.$token : __DIR__.'/../themes/'.$slug;
if(!$root||!is_dir($root)||!is_file($root.'/theme.json')){http_response_code(404);exit('Không tìm thấy theme preview.');}
$meta=json_decode((string)file_get_contents($root.'/theme.json'),true)?:[];
foreach(['views/header.php','views/home.php','views/footer.php','style.css'] as $r){if(!is_file($root.'/'.$r)){http_response_code(422);exit('Theme preview thiếu '.$r);}}
$GLOBALS['FILMNOVA_THEME_PREVIEW_TOKEN']=$token ?: $slug;
$themePreviewSlug=(string)($meta['slug']??$slug);
$hero=[['name'=>'Đấu Phá Thương Khung - Phần 5','origin_name'=>'Fights Break Sphere','slug'=>'demo-du-pha','year'=>2022,'quality'=>'HD','lang'=>'Vietsub','description'=>'Preview giao diện FilmNova.','poster_url'=>'','thumb_url'=>'','episodes_count'=>104,'view_count'=>12580],['name'=>'Cuộc Sống Thượng Lưu','origin_name'=>'The High Life','slug'=>'demo-thuong-luu','year'=>2024,'quality'=>'FHD','lang'=>'Vietsub','description'=>'Demo theme mới.','poster_url'=>'','thumb_url'=>'','episodes_count'=>24,'view_count'=>8200]];
$latest=$hero;$popular=$hero;$banners=[];$apiPath='';$pageTitle='Preview · '.($meta['name']??'Theme');$siteName=setv('site_name','FilmNova');
?><style>body{margin:0} .preview-note{position:fixed;z-index:9999;top:10px;right:10px;background:#0b1220;color:#fff;padding:8px 12px;border-radius:8px;font-size:12px;box-shadow:0 4px 15px #0008}.preview-note b{color:#8fd000}</style><div class="preview-note">PREVIEW · <b><?=e($meta['name']??'Theme')?></b></div><?php require $root.'/views/header.php';require $root.'/views/home.php';require $root.'/views/footer.php';?>
