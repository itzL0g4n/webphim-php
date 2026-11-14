<?php
$siteName=setv('site_name','FilmNova');
$logo=setv('site_logo','');
$pageTitle=$pageTitle??$siteName;
$cats=cached_api_list('categories'); $countries=cached_api_list('countries');
$cats=array_values(array_filter($cats,'is_array')); $countries=array_values(array_filter($countries,'is_array'));
?><!doctype html>
<html lang="vi"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<meta name="description" content="<?=e(setv('site_tagline','Xem phim mới cập nhật mỗi ngày'))?>"><title><?=e($pageTitle)?></title>
<link rel="stylesheet" href="<?=e(theme_asset((string)(theme_info(active_theme())['entry']??'style.css')))?>">
</head><body class="theme-<?=e(active_theme())?>" data-theme="<?=e(active_theme())?>">
<header class="fm-head"><div class="container fm-head-top">
<a class="fm-logo" href="<?=e(site())?>"><?php if($logo): ?><img src="<?=e(img($logo))?>" alt="<?=e($siteName)?>"><?php else: ?><span class="fm-logo-badge">F</span><?php endif; ?><span class="fm-logo-name"><?=e($siteName)?></span></a>
<form class="fm-search" action="<?=e(site('search.php'))?>"><input name="q" value="<?=e($_GET['q']??'')?>" placeholder="Tìm phim, diễn viên..."><button>⌕</button></form>
<div class="fm-tools"><a href="<?=e(site('browse.php?sort=views'))?>">🔥 Xem nhiều</a><?php if(admin()): ?><a href="<?=e(site('admin/'))?>">ADMIN</a><?php endif; ?></div>
</div><div class="fm-menu-bar"><div class="container"><button class="fm-menu-mobile" id="mobileMenuBtn" aria-label="Mở menu">☰</button><nav class="fm-menu" id="mainMenu">
<a class="fm-menu-home" href="<?=e(site())?>">⌂</a><a href="<?=e(site('browse.php'))?>">Phim mới</a><a href="<?=e(site('browse.php?type=Phim%20l%E1%BA%BB'))?>">Phim lẻ</a><a href="<?=e(site('browse.php?type=Phim%20b%E1%BB%99'))?>">Phim bộ</a><a href="<?=e(site('browse.php?type=Ho%E1%BA%A1t%20h%C3%ACnh'))?>">Hoạt hình</a>
<div class="fm-menu-drop"><button type="button" data-menu-drop>Thể loại ▾</button><div class="fm-dropdown"><?php foreach(array_slice($cats,0,60) as $c): $n=(string)($c['name']??'');$s=(string)($c['slug']??'');if(!$n||$n==='Phim 18+')continue;?><a href="<?=e(site('browse.php?cat='.urlencode($s)))?>"><?=e($n)?></a><?php endforeach;?></div></div>
<div class="fm-menu-drop"><button type="button" data-menu-drop>Quốc gia ▾</button><div class="fm-dropdown country"><?php foreach(array_slice($countries,0,60) as $c): $n=(string)($c['name']??'');$s=(string)($c['slug']??'');if(!$n)continue;?><a href="<?=e(site('browse.php?country='.urlencode($s)))?>"><?=e($n)?></a><?php endforeach;?></div></div>
</nav></div></div></header><div class="fm-shell"><div class="fm-main">
