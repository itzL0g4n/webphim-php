<?php
require __DIR__.'/bootstrap.php';
$page=max(1,(int)($_GET['page']??1));
$type=trim($_GET['type']??'');$cat=trim($_GET['cat']??'');$country=trim($_GET['country']??'');$year=(int)($_GET['year']??0);$sort=trim($_GET['sort']??'');
$kind='all';$filters=[];$title='Tất cả phim';
if($cat!==''){$kind='category';$filters=['slug'=>$cat];$title='Thể loại: '.$cat;}
elseif($country!==''){$kind='country';$filters=['slug'=>$country];$title='Quốc gia: '.$country;}
elseif($year>0){$kind='year';$filters=['year'=>$year];$title='Phim năm '.$year;}
elseif($type!==''){$kind='type';$map=['Phim lẻ'=>'phim-le','Phim bộ'=>'phim-bo','Hoạt hình'=>'hoat-hinh','Shows'=>'tv-shows','Phim chiếu rạp'=>'phim-chieu-rap'];$filters=['slug'=>$map[$type]??$type];$title=$type;}
elseif($sort==='views'){$kind='all';$filters=['sort_field'=>'view','sort_type'=>'desc'];$title='Xem nhiều';}
else{$kind='all';$title='Phim mới';}
$data=sql_catalog($sort==='views'?'views':$kind,$page,$filters);
if($data===null){$data=fetch_catalog($kind,$page,$filters);}
$movies=$data['items'];$meta=$data['meta'];$pathImage=$data['pathImage'];
// Add locally managed/manual movies as a small supplemental block on page 1 for the home catalog.
if($page===1&&in_array($kind,['all','type'],true)){
 $locals=db()->query("SELECT * FROM movies WHERE is_hidden=0 AND source_type='manual' ORDER BY updated_at DESC LIMIT 12")->fetchAll();
 $existing=[];foreach($movies as $m){if(isset($m['slug']))$existing[$m['slug']]=1;}
 $localNorm=[];foreach($locals as $m)if(!isset($existing[$m['slug']]))$localNorm[]=$m;
 if($localNorm)$movies=array_merge($localNorm,$movies);
}
$pageTitle=$title.' · '.setv('site_name','FilmNova');
if(theme_has_view('browse.php') && theme_has_view('header.php') && theme_has_view('footer.php')){
  theme_render_public('browse.php',['movies'=>$movies,'meta'=>$meta,'page'=>$page,'title'=>$title,'kind'=>$kind,'pathImage'=>$pathImage]);
  exit;
}
require __DIR__.'/partials/header.php';
?>
<div class="container" style="padding-top:10px"><div class="fm-section"><div class="fm-bar"><h2><?=e($title)?></h2><span>API: <?=number_format((int)$meta['total'])?> phim · Trang <?=e((string)$meta['page'])?> / <?=e((string)$meta['pages'])?></span></div>
<div class="fm-filter">
<a class="<?=!$type&&!$cat&&!$country&&!$year&&!$sort?'active':''?>" href="<?=e(site('browse.php'))?>">Tất cả</a>
<a href="<?=e(site('browse.php?type=Phim%20l%E1%BA%BB'))?>">Phim lẻ</a><a href="<?=e(site('browse.php?type=Phim%20b%E1%BB%99'))?>">Phim bộ</a><a href="<?=e(site('browse.php?type=Ho%E1%BA%A1t%20h%C3%ACnh'))?>">Hoạt hình</a><a href="<?=e(site('browse.php?sort=views'))?>">Xem nhiều</a>
</div>
<?php if($movies): ?><div class="fm-grid"><?php foreach($movies as $m): $poster=(string)($m['poster_url']??$m['thumb_url']??''); ?><article class="fm-card"><a href="<?=e(site('watch.php?slug='.urlencode((string)$m['slug'])))?>"><div class="fm-poster"><img loading="lazy" src="<?=e(img($poster,$pathImage))?>" alt="<?=e($m['name']??'')?>" onerror="this.onerror=null;this.src='<?=e(site('assets/placeholder.svg'))?>';"><span class="fm-quality"><?=e($m['quality']??'HD')?></span><?php $ep=(int)($m['episode_total']??$m['episodes_count']??0);if($ep): ?><span class="fm-ep"><?=e((string)$ep)?> tập</span><?php endif;?></div><div class="fm-title"><?=e($m['name']??'')?></div><div class="fm-origin"><?=e($m['origin_name']??'')?></div></a></article><?php endforeach;?></div>
<div class="fm-pagination"><?php if($page>1): ?><a href="<?=e(site('browse.php?'.http_build_query(array_merge($_GET,['page'=>$page-1]))))?>">←</a><?php endif; ?><?php $from=max(1,$page-2);$to=min((int)$meta['pages'],$page+2);for($i=$from;$i<=$to;$i++): ?><a class="<?=$i===$page?'active':''?>" href="<?=e(site('browse.php?'.http_build_query(array_merge($_GET,['page'=>$i]))))?>"><?=$i?></a><?php endfor;?><?php if($page<(int)$meta['pages']): ?><a href="<?=e(site('browse.php?'.http_build_query(array_merge($_GET,['page'=>$page+1]))))?>">→</a><?php endif;?></div></div></div>
<?php else: ?><div class="empty">API chưa trả về phim cho mục này. Hãy thử lại sau ít phút.</div><?php endif;?></div>
<?php require __DIR__.'/partials/footer.php'; ?>
