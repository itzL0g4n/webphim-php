<?php
$adminTitle='Nhập API thủ công';
require __DIR__.'/_header.php';
$message=''; $error='';
if($_SERVER['REQUEST_METHOD']==='POST'){
    try{
        csrf_check();
        $url=trim((string)($_POST['api_url']??''));
        $okKk= preg_match('~^https://(?:www\.)?phimapi\.com/v1/api/phim/([a-z0-9][a-z0-9_-]*)/?$~i',$url,$mm);
        $okVs= preg_match('~^https://(?:www\.)?vsmov\.com/api/phim/([a-z0-9][a-z0-9_-]*)/?$~i',$url,$mm2);
        if(!$okKk && !$okVs){
            throw new RuntimeException('Chỉ cho phép URL chi tiết HTTPS của phimapi.com hoặc vsmov.com.');
        }
        $slug=$okKk ? $mm[1] : $mm2[1];
        $source=$okKk ? 'phimapi' : 'vsmov';
        global $config;
        $json=api_get($url,(int)($config['api']['timeout']??8));
        if(!$json) throw new RuntimeException('API không trả dữ liệu hợp lệ hoặc không thể kết nối.');
        $raw=pick($json,['data.item','data.movie','movie','data'],$json);
        if(!is_array($raw)) $raw=$json;
        $m=normmovie($raw);
        if($m['slug']==='') $m['slug']=$slug;
        if($m['name']==='Untitled') $m['slug']=$slug;
        $q=db()->prepare('SELECT id FROM movies WHERE slug=? LIMIT 1');
        $q->execute([$m['slug']]);
        $existing=(int)($q->fetchColumn()?:0);
        $id=save_movie($m,$existing,'api_manual_'.$source);
        try{save_movie_images($id,$m,(string)pick($json,['pathImage','data.pathImage','data.item.pathImage'],''),$okKk?'https://phimapi.com':'https://vsmov.com');}catch(Throwable $e){}
        $eps=api_normalize_episodes(api_detail_payload($json));
        $pdo=db();
        if($eps){
            $sourceType='api_manual_'.$source;
            $pdo->prepare('DELETE FROM episodes WHERE movie_id=? AND source_type=?')->execute([$id,$sourceType]);
            $ins=$pdo->prepare('INSERT INTO episodes(movie_id,server_name,season,episode_number,name,stream_url,embed_url,raw_json,source_type) VALUES(?,?,?,?,?,?,?,?,?)');
            foreach($eps as $e)$ins->execute([$id,$e['server'],$e['season'],$e['num'],$e['name'],$e['stream'],$e['embed'],$e['raw'],$sourceType]);
            $pdo->prepare('UPDATE movies SET episodes_count=(SELECT COUNT(*) FROM episodes WHERE movie_id=?) WHERE id=?')->execute([$id,$id]);
        }
        $message=($existing?'Đã cập nhật':'Đã nhập mới').' phim “'.($m['name']?:$m['slug']).'”'.($eps?' • '.count($eps).' dữ liệu tập/server':' • API không trả tập/server');
    }catch(Throwable $e){$error=$e->getMessage();}
}
?>
<div class="admin-head"><div><small>API IMPORT</small><h1>Nhập phim bằng API</h1><p>Dán URL API chi tiết của KKPhim hoặc VSMOV để nhập/cập nhật thủ công.</p></div></div>
<?php if($message):?><div class="sync-flash">✓ <?=e($message)?></div><?php endif;?>
<?php if($error):?><div class="admin-card" style="border-color:#7a2e38;color:#ffb4bd"><b>Lỗi:</b> <?=e($error)?></div><?php endif;?>
<section class="admin-card"><form method="post" class="admin-form"><input type="hidden" name="csrf" value="<?=e(csrf())?>"><label>URL API chi tiết phim<input name="api_url" placeholder="https://vsmov.com/api/phim/cuoc-song-thuong-luu" required value="<?=e((string)($_POST['api_url']??''))?>"></label><div class="actions"><button class="btn primary">Nhập / cập nhật phim</button><a class="btn" href="api.php">← API Center</a></div></form></section>
<section class="admin-card"><h2>Lưu ý</h2><p>Chức năng này dành cho một phim cụ thể. Nó không thay thế nút cập nhật hàng loạt KKPhim + VSMOV.</p><p>Hệ thống hỗ trợ URL HTTPS của <code>phimapi.com/v1/api/phim/{slug}</code> và <code>vsmov.com/api/phim/{slug}</code>.</p></section>
<?php require __DIR__.'/_footer.php';?>
