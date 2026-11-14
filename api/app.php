<?php
declare(strict_types=1);
require dirname(__DIR__).'/bootstrap.php';
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: X-FilmNova-App-Key, Content-Type');
header('Access-Control-Allow-Methods: GET, OPTIONS');
if(($_SERVER['REQUEST_METHOD']??'GET')==='OPTIONS'){http_response_code(204);exit;}
$out=['ok'=>true,'site'=>site_origin()];
try{
  if(!app_api_authorized()) throw new RuntimeException('App API key không hợp lệ.');
  $action=(string)($_GET['action']??'config');
  if($action==='config'){
    $out['data']=[
      'version'=>'2.0',
      'site_name'=>setv('site_name','FilmNova'),
      'site_url'=>site_origin().site(),
      'api_url'=>absolute_site_url('api/app.php'),
      'enabled'=>app_feature_enabled(),
      'notice'=>app_home_notice('Muốn xem phim, vui lòng chọn cách xem phù hợp với thiết bị của bạn.'),
      'windows_url'=>app_download_url('windows'),
      'android_url'=>app_download_url('android'),
      'ios_url'=>absolute_site_url('ios.php'),
      'platforms'=>['windows','android','ios','ipados','macos'],
    ];
  } elseif($action==='list'){
    $page=max(1,(int)($_GET['page']??1));$limit=max(1,min(50,(int)($_GET['limit']??24)));$q=trim((string)($_GET['q']??''));$sort=(string)($_GET['sort']??'latest');
    $where=['is_hidden=0'];$args=[];
    if($q!==''){ $like='%'.$q.'%';$where[]='(name LIKE ? OR origin_name LIKE ? OR actors LIKE ? OR categories LIKE ? OR countries LIKE ? OR description LIKE ?)';$args=[$like,$like,$like,$like,$like,$like]; }
    $order=$sort==='views'?'view_count DESC,updated_at DESC,id DESC':'updated_at DESC,id DESC';
    $w=implode(' AND ',$where);$pdo=db();$st=$pdo->prepare("SELECT COUNT(*) FROM movies WHERE $w");$st->execute($args);$total=(int)$st->fetchColumn();$pages=max(1,(int)ceil($total/$limit));$page=min($page,$pages);$offset=($page-1)*$limit;
    $st=$pdo->prepare("SELECT id,name,slug,origin_name,year,type,status,quality,lang,episodes_count,view_count,thumb_url,poster_url,updated_at FROM movies WHERE $w ORDER BY $order LIMIT $limit OFFSET $offset");$st->execute($args);
    $out['data']=['items'=>$st->fetchAll(),'meta'=>['total'=>$total,'page'=>$page,'pages'=>$pages,'limit'=>$limit]];
  } elseif($action==='movie'){
    $slug=trim((string)($_GET['slug']??''));$id=(int)($_GET['id']??0);
    if($slug===' '||($slug===''&&$id<=0)) throw new RuntimeException('Thiếu slug hoặc id.');
    if($id>0){$st=db()->prepare('SELECT * FROM movies WHERE id=? AND is_hidden=0 LIMIT 1');$st->execute([$id]);}
    else {$st=db()->prepare('SELECT * FROM movies WHERE slug=? AND is_hidden=0 LIMIT 1');$st->execute([$slug]);}
    $m=$st->fetch();if(!$m)throw new RuntimeException('Không tìm thấy phim.');
    $e=db()->prepare('SELECT id,server_name,season,episode_number,name FROM episodes WHERE movie_id=? ORDER BY server_name,season,episode_number,id');$e->execute([(int)$m['id']]);$eps=$e->fetchAll();
    foreach($eps as &$ep){$ep['play_url']=absolute_site_url('api/player.php?movie='.(int)$m['id'].'&episode='.(int)$ep['id'].'&sig='.rawurlencode(player_signature((int)$m['id'],(int)$ep['id'])));$ep['watch_url']=absolute_site_url('watch.php?slug='.rawurlencode((string)$m['slug']));}unset($ep);
    $m['thumb_url']=normalize_public_url((string)$m['thumb_url']);$m['poster_url']=normalize_public_url((string)$m['poster_url']);$m['watch_url']=absolute_site_url('watch.php?slug='.rawurlencode((string)$m['slug']));$m['episodes']=$eps;$out['data']=$m;
  } elseif($action==='episode'){
    $episode=(int)($_GET['episode']??0);$movie=(int)($_GET['movie']??0);if($episode<=0||$movie<=0)throw new RuntimeException('Thiếu movie/episode.');
    $st=db()->prepare('SELECT e.id,e.movie_id,e.server_name,e.season,e.episode_number,e.name FROM episodes e INNER JOIN movies m ON m.id=e.movie_id WHERE e.id=? AND e.movie_id=? AND m.is_hidden=0 LIMIT 1');$st->execute([$episode,$movie]);$e=$st->fetch();if(!$e)throw new RuntimeException('Không tìm thấy tập.');
    $e['play_url']=absolute_site_url('api/player.php?movie='.$movie.'&episode='.$episode.'&sig='.rawurlencode(player_signature($movie,$episode)));$out['data']=$e;
  } elseif($action==='health'){
    $out['data']=['status'=>'ok','time'=>date('c'),'site_url'=>site_origin().site()];
  } else throw new RuntimeException('Action không hợp lệ.');
}catch(Throwable $e){http_response_code(400);$out=['ok'=>false,'error'=>$e->getMessage()];}
echo json_encode($out,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES|JSON_INVALID_UTF8_SUBSTITUTE);
