<?php $adminTitle='API Center'; require __DIR__.'/_header.php';
$apis=[
 ['KKPhim','GET','/v1/api/danh-sach?page={page}','Phim mới cập nhật'],
 ['KKPhim','GET','/v1/api/phim/{slug}','Chi tiết phim + episodes'],
 ['KKPhim','GET','/the-loai','Danh sách thể loại'],
 ['KKPhim','GET','/quoc-gia','Danh sách quốc gia'],
 ['KKPhim','GET','/nam-phat-hanh','Danh sách năm'],
 ['VSMOV','GET','/api/danh-sach/phim-moi-cap-nhat?page={page}','Phim mới cập nhật'],
 ['VSMOV','GET','/api/danh-sach/subteam','Danh sách subteam'],
 ['VSMOV','GET','/api/tim-kiem?keyword={keyword}&limit=20&page={page}','Tìm kiếm phim'],
 ['VSMOV','GET','/api/the-loai','Danh sách thể loại'],
 ['VSMOV','GET','/api/the-loai/{slug}?limit=20&page={page}&year={year}&country={country}&type={type}&status={status}','Phim theo thể loại + bộ lọc'],
 ['VSMOV','GET','/api/nam','Danh sách năm'],
 ['VSMOV','GET','/api/nam/{year}?limit=20&page={page}&type={type}&status={status}','Phim theo năm + bộ lọc'],
];
?>
<div class="admin-head"><div><small>API CENTER</small><h1>API KKPhim + VSMOV</h1><p>Hai nguồn được gom vào cùng hệ thống đồng bộ. Nút cập nhật cho phép chọn riêng từng nguồn hoặc cả hai.</p></div><div class="actions"><a class="btn" href="api_import.php">＋ Nhập 1 phim bằng API</a></div><div class="sync-picker"><button type="button" class="btn primary" id="syncBtn">⟳ Cập nhật từ API ▾</button><div class="sync-menu" id="syncMenu" hidden><a href="../cron/sync.php?token=<?=urlencode(setv('cron_token','changeme'))?>&source=kkphim&ui=1">KKPhim</a><a href="../cron/sync.php?token=<?=urlencode(setv('cron_token','changeme'))?>&source=vsmov&ui=1">VSMOV</a><a href="../cron/sync.php?token=<?=urlencode(setv('cron_token','changeme'))?>&source=all&ui=1">Cả hai</a></div></div><script>(function(){var b=document.getElementById('syncBtn'),m=document.getElementById('syncMenu');if(!b||!m)return;b.addEventListener('click',function(e){e.stopPropagation();m.hidden=!m.hidden;});document.addEventListener('click',function(){m.hidden=true;});})();</script></div>
<section class="admin-card"><div style="overflow:auto"><table class="admin-table"><tr><th>#</th><th>Nguồn</th><th>Method</th><th>Endpoint</th><th>Mục đích</th></tr><?php foreach($apis as $i=>$a):?><tr><td><?=$i+1?></td><td><b><?=e($a[0])?></b></td><td><?=e($a[1])?></td><td><code><?=e($a[2])?></code></td><td><?=e($a[3])?></td></tr><?php endforeach;?></table></div></section>
<?php require __DIR__.'/_footer.php';?>