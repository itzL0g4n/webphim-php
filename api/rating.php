<?php
require dirname(__DIR__).'/bootstrap.php';
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');
function jout(array $d,int $s=200):void{http_response_code($s);echo json_encode($d,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);exit;}
$action=$_GET['action']??'get';
$movieId=(int)($_GET['movie_id']??0);
if($movieId<1||!movie_by_id($movieId))jout(['ok'=>false,'message'=>'Phim không tồn tại.'],404);
if($action==='get'){jout(['ok'=>true,'rating'=>movie_rating($movieId)]);}
if($action==='vote'&&$_SERVER['REQUEST_METHOD']==='POST'){
 $data=json_decode(file_get_contents('php://input')?:'',true); if(!is_array($data))$data=$_POST;
 $rating=(int)($data['rating']??0); if($rating<1||$rating>5)jout(['ok'=>false,'message'=>'Vui lòng chọn từ 1 đến 5 sao.'],422);
 $key=client_view_key();
 $s=db()->prepare('SELECT created_at FROM movie_ratings WHERE movie_id=? AND visitor_key=? LIMIT 1');$s->execute([$movieId,$key]);$old=$s->fetchColumn();
 try{
   if($old!==false){$u=db()->prepare('UPDATE movie_ratings SET rating=?,updated_at=NOW() WHERE movie_id=? AND visitor_key=?');$u->execute([$rating,$movieId,$key]);$msg='Đã cập nhật đánh giá của bạn.';}
   else{$u=db()->prepare('INSERT INTO movie_ratings(movie_id,visitor_key,rating) VALUES(?,?,?)');$u->execute([$movieId,$key,$rating]);$msg='Đã ghi nhận đánh giá của bạn.';}
 }catch(PDOException $e){jout(['ok'=>false,'message'=>'Không thể lưu đánh giá lúc này.'],500);}
 jout(['ok'=>true,'message'=>$msg,'rating'=>movie_rating($movieId)]);
}
jout(['ok'=>false,'message'=>'Action không hợp lệ.'],400);
