<?php
$adminTitle='Quản lý phim';
require __DIR__.'/_header.php';
if(isset($_GET['toggle'])){db()->prepare('UPDATE movies SET is_hidden=1-is_hidden WHERE id=?')->execute([(int)$_GET['toggle']]);header('Location:movies.php');exit;}
if(isset($_GET['feature'])){db()->prepare('UPDATE movies SET is_featured=1-is_featured WHERE id=?')->execute([(int)$_GET['feature']]);header('Location:movies.php');exit;}
if(isset($_GET['delete'])){db()->prepare('DELETE FROM movies WHERE id=?')->execute([(int)$_GET['delete']]);header('Location:movies.php');exit;}
$q=trim($_GET['q']??'');
$perPage=30;
$page=max(1,(int)($_GET['page']??1));
$l="%$q%";
$countStmt=db()->prepare('SELECT COUNT(*) FROM movies WHERE name LIKE ? OR origin_name LIKE ?');
$countStmt->execute([$l,$l]);
$total=(int)$countStmt->fetchColumn();
$totalPages=max(1,(int)ceil($total/$perPage));
$page=min($page,$totalPages);
$offset=($page-1)*$perPage;
$s=db()->prepare("SELECT * FROM movies WHERE name LIKE ? OR origin_name LIKE ? ORDER BY updated_at DESC,id DESC LIMIT $perPage OFFSET $offset");
$s->execute([$l,$l]);$ms=$s->fetchAll();
?><div class="admin-head"><div><small>CATALOG</small><h1>Quản lý phim</h1></div><div class="actions"><a class="btn primary" href="../cron/sync.php?token=<?=urlencode(setv('cron_token','changeme'))?>&redirect=1">⟳ Cập nhật API</a><a class="btn primary" href="movie_edit.php">＋ Thêm phim</a></div></div><section class="admin-card"><form class="actions" method="get"><input style="flex:1;min-width:200px;background:#0d1219;border:1px solid var(--line);color:#fff;border-radius:9px;padding:10px" name="q" value="<?=e($q)?>" placeholder="Tìm phim..."><button class="btn">Tìm</button></form><div style="overflow:auto"><table class="admin-table"><tr><th>Ảnh</th><th>Tên</th><th>Nguồn</th><th>Tập</th><th>Featured</th><th>Hiện</th><th>Hành động</th></tr><?php foreach($ms as $m):?><tr><td><img loading="lazy" src="<?=e(img($m['poster_url']?:$m['thumb_url']))?>" onerror="this.onerror=null;this.src='../assets/placeholder.svg';"></td><td><b><?=e($m['name'])?></b><br><small><?=e($m['origin_name'])?></small></td><td><?=e($m['source_type'])?></td><td><?=e((string)$m['episodes_count'])?></td><td><?=$m['is_featured']?'Có':'Không'?></td><td><?=$m['is_hidden']?'Ẩn':'Hiện'?></td><td><div class="actions"><a class="btn" href="movie_edit.php?id=<?=$m['id']?>">Sửa</a><a class="btn" href="episodes.php?id=<?=$m['id']?>">Tập</a><a class="btn" href="?feature=<?=$m['id']?>">Ghim</a><a class="btn" href="?toggle=<?=$m['id']?>">Ẩn/Hiện</a><a class="btn red" onclick="return confirm('Xóa phim?')" href="?delete=<?=$m['id']?>">Xóa</a></div></td></tr><?php endforeach;?></table></div></section>
<?php if($totalPages>1): ?>
<div class="admin-pagination">
  <?php for($i=max(1,$page-2);$i<=min($totalPages,$page+2);$i++): ?>
    <a class="<?= $i===$page?'active':'' ?>" href="?<?=http_build_query(array_filter(['q'=>$q,'page'=>$i],fn($v)=>$v!==''))?>"><?=$i?></a>
  <?php endfor; ?>
  <span>Trang <?=$page?> / <?=$totalPages?> · <?=$total?> phim</span>
</div>
<?php endif; ?>
<?php require __DIR__.'/_footer.php';?>
