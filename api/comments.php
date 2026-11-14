<?php
require dirname(__DIR__).'/bootstrap.php';
ensure_comment_columns();
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');
function out(array $d,int $s=200):void{http_response_code($s);echo json_encode($d,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);exit;}
function input_json():array{$raw=file_get_contents('php://input');$j=json_decode($raw?:'',true);return is_array($j)?$j:$_POST;}
function require_comment_account():array{$a=comment_account();if(!$a)out(['ok'=>false,'code'=>'AUTH_REQUIRED','message'=>'Vui lòng đăng nhập bằng Email + PIN trước.'],401);return$a;}
$action=$_GET['action']??'';$method=$_SERVER['REQUEST_METHOD'];$data=input_json();
if($action==='session'){ $a=comment_account(); out(['ok'=>true,'authenticated'=>!!$a,'account'=>$a?['display_name'=>$a['display_name'],'email'=>$a['email']]:null]); }
if($action==='register_or_login'&&$method==='POST'){
 $name=trim((string)($data['display_name']??''));$email=strtolower(trim((string)($data['email']??'')));$pin=trim((string)($data['pin']??''));
 if(!filter_var($email,FILTER_VALIDATE_EMAIL))out(['ok'=>false,'message'=>'Email không hợp lệ.'],422);
 if(!preg_match('/^\d{4,8}$/',$pin))out(['ok'=>false,'message'=>'PIN phải gồm 4–8 chữ số.'],422);
 $ban=comment_ban($email);if($ban)out(['ok'=>false,'message'=>'Email này đang bị hạn chế bình luận.'.($ban['ends_at']?' Đến '.$ban['ends_at'].'.':'')],403);
 $s=db()->prepare('SELECT * FROM comment_accounts WHERE email=? LIMIT 1');$s->execute([$email]);$acc=$s->fetch();
 if($acc){
   if(!password_verify($pin,$acc['pin_hash']))out(['ok'=>false,'message'=>'PIN không đúng.'],403);
   issue_comment_token((int)$acc['id']);
   out(['ok'=>true,'mode'=>'login','message'=>'Đăng nhập thành công.','account'=>['display_name'=>$acc['display_name'],'email'=>$acc['email']]]);
 }
 if($name===''||mb_strlen($name)>120)out(['ok'=>false,'code'=>'NAME_REQUIRED','message'=>'Đây là tài khoản mới. Vui lòng nhập tên hiển thị để tạo tài khoản.'],422);
 $st=db()->prepare('INSERT INTO comment_accounts(display_name,email,pin_hash) VALUES(?,?,?)');$st->execute([$name,$email,password_hash($pin,PASSWORD_DEFAULT)]);$id=(int)db()->lastInsertId();$s=db()->prepare('SELECT * FROM comment_accounts WHERE id=?');$s->execute([$id]);$acc=$s->fetch();
 issue_comment_token((int)$acc['id']); out(['ok'=>true,'mode'=>'register','message'=>'Tạo tài khoản và đăng nhập thành công.','account'=>['display_name'=>$acc['display_name'],'email'=>$acc['email']]]);
}
if($action==='forgot_pin'&&$method==='POST'){
 $email=strtolower(trim((string)($data['email']??'')));$new=trim((string)($data['new_pin']??''));
 if(!filter_var($email,FILTER_VALIDATE_EMAIL))out(['ok'=>false,'message'=>'Email không hợp lệ.'],422);
 if(!preg_match('/^\d{4,8}$/',$new))out(['ok'=>false,'message'=>'PIN mới phải gồm 4–8 chữ số.'],422);
 $s=db()->prepare('SELECT id FROM comment_accounts WHERE email=? LIMIT 1');$s->execute([$email]);$id=$s->fetchColumn();
 if($id===false)out(['ok'=>false,'message'=>'Không tìm thấy tài khoản với email này.'],404);
 if(comment_ban($email))out(['ok'=>false,'message'=>'Email này đang bị hạn chế bình luận.'],403);
 db()->prepare('UPDATE comment_accounts SET pin_hash=? WHERE id=?')->execute([password_hash($new,PASSWORD_DEFAULT),$id]);
 issue_comment_token((int)$id);
 $s=db()->prepare('SELECT * FROM comment_accounts WHERE id=?');$s->execute([$id]);$acc=$s->fetch();
 out(['ok'=>true,'message'=>'Đặt lại mã PIN thành công và đã đăng nhập.','account'=>['display_name'=>$acc['display_name'],'email'=>$acc['email']]]);
}
if($action==='change_pin'&&$method==='POST'){
 $acc=require_comment_account();$old=trim((string)($data['old_pin']??''));$new=trim((string)($data['new_pin']??''));
 if(!preg_match('/^\d{4,8}$/',$old)||!preg_match('/^\d{4,8}$/',$new))out(['ok'=>false,'message'=>'Mã PIN phải gồm 4–8 chữ số.'],422);
 if(!password_verify($old,$acc['pin_hash']))out(['ok'=>false,'message'=>'PIN hiện tại không đúng.'],403);
 if($old===$new)out(['ok'=>false,'message'=>'PIN mới phải khác PIN hiện tại.'],422);
 db()->prepare('UPDATE comment_accounts SET pin_hash=? WHERE id=?')->execute([password_hash($new,PASSWORD_DEFAULT),$acc['id']]);
 issue_comment_token((int)$acc['id']);
 out(['ok'=>true,'message'=>'Đổi mã PIN thành công.']);
}
if($action==='logout'&&$method==='POST'){unset($_SESSION['comment_token']);setcookie('film_comment_token','',time()-3600,'/','',false,true);out(['ok'=>true]);}
if($action==='react'&&$method==='POST'){
 $acc=require_comment_account();$commentId=(int)($data['comment_id']??0);$type=trim((string)($data['reaction_type']??''));
 $allowed=['like','dislike','heart','laugh','wow','sad','fire'];if(!in_array($type,$allowed,true))out(['ok'=>false,'message'=>'Reaction không hợp lệ.'],422);
 $s=db()->prepare("SELECT id FROM comments WHERE id=? AND moderation_status='published' LIMIT 1");$s->execute([$commentId]);if(!$s->fetchColumn())out(['ok'=>false,'message'=>'Bình luận không tồn tại.'],404);
 if(in_array($type,['like','dislike'],true))db()->prepare("DELETE FROM comment_reactions WHERE comment_id=? AND account_id=? AND reaction_type IN ('like','dislike') AND reaction_type<>?")->execute([$commentId,$acc['id'],$type]);
 $s=db()->prepare('SELECT id FROM comment_reactions WHERE comment_id=? AND account_id=? AND reaction_type=? LIMIT 1');$s->execute([$commentId,$acc['id'],$type]);$exists=$s->fetchColumn();
 if($exists){db()->prepare('DELETE FROM comment_reactions WHERE id=?')->execute([$exists]);$active=false;}else{db()->prepare('INSERT INTO comment_reactions(comment_id,account_id,reaction_type) VALUES(?,?,?)')->execute([$commentId,$acc['id'],$type]);$active=true;}
 $q=db()->prepare('SELECT reaction_type,COUNT(*) cnt FROM comment_reactions WHERE comment_id=? GROUP BY reaction_type');$q->execute([$commentId]);$counts=[];foreach($q as $r)$counts[$r['reaction_type']]=(int)$r['cnt'];
 out(['ok'=>true,'active'=>$active,'reaction_type'=>$type,'counts'=>$counts]);
}
if($action==='list'){
 $movieId=(int)($_GET['movie_id']??0);$page=max(1,(int)($_GET['page']??1));$per=10;$offset=($page-1)*$per;
 // Pagination is based on top-level comments, like YouTube/FB. Replies stay inside their parent thread.
 $s=db()->prepare("SELECT COUNT(*) FROM comments WHERE movie_id=? AND moderation_status='published' AND parent_id IS NULL");$s->execute([$movieId]);$total=(int)$s->fetchColumn();
 $s=db()->prepare("SELECT c.id,c.parent_id,c.display_name,c.body,c.reaction_icon,c.reaction_icon_animated,c.created_at,c.account_id FROM comments c WHERE c.movie_id=? AND c.moderation_status='published' AND c.parent_id IS NULL ORDER BY c.created_at DESC,c.id DESC LIMIT $per OFFSET $offset");$s->execute([$movieId]);
 $parents=$s->fetchAll();$items=$parents;$parentIds=array_map(fn($x)=>(int)$x['id'],$parents);
 if($parentIds){
   $in=implode(',',array_fill(0,count($parentIds),'?'));
   $q=db()->prepare("SELECT c.id,c.parent_id,c.display_name,c.body,c.reaction_icon,c.reaction_icon_animated,c.created_at,c.account_id,pc.display_name parent_display_name FROM comments c LEFT JOIN comments pc ON pc.id=c.parent_id WHERE c.moderation_status='published' AND c.parent_id IN ($in) ORDER BY c.parent_id ASC,c.created_at ASC,c.id ASC");
   $q->execute($parentIds);$replyMap=[];foreach($q as $r){$replyMap[(int)$r['parent_id']][]=$r;}
   foreach($items as &$it){$it['replies']=$replyMap[(int)$it['id']]??[];$it['reply_count']=count($it['replies']);}unset($it);
   $flat=[];foreach($items as $it){$flat[]=$it;foreach($it['replies'] as $r)$flat[]=$r;}
 }else{$flat=[];foreach($items as $it)$flat[]=$it;}
 $ids=array_map(fn($x)=>(int)$x['id'],$flat);$reactionMap=[];
 if($ids){$in=implode(',',array_fill(0,count($ids),'?'));$q=db()->prepare("SELECT comment_id,reaction_type,COUNT(*) cnt FROM comment_reactions WHERE comment_id IN ($in) GROUP BY comment_id,reaction_type");$q->execute($ids);foreach($q as $r){$cid=(int)$r['comment_id'];$reactionMap[$cid][$r['reaction_type']]=(int)$r['cnt'];}}
 $auth=comment_account();$mine=[];if($auth&&$ids){$in=implode(',',array_fill(0,count($ids),'?'));$q=db()->prepare("SELECT comment_id,reaction_type FROM comment_reactions WHERE account_id=? AND comment_id IN ($in)");$q->execute(array_merge([(int)$auth['id']],$ids));foreach($q as $r)$mine[(int)$r['comment_id']][]=$r['reaction_type'];}
 $decorate=function(&$it)use($reactionMap,$mine,$auth){$cid=(int)$it['id'];$it['reactions']=$reactionMap[$cid]??[];$it['my_reactions']=$mine[$cid]??[];$it['is_mine']=($auth&&((int)$it['account_id']===(int)$auth['id']));$it['can_delete']=($it['is_mine']&&is_comment_delete_window_open((string)$it['created_at']));unset($it['account_id']);};
 foreach($items as &$it){$decorate($it);foreach($it['replies'] as &$r)$decorate($r);unset($r);}unset($it);
 out(['ok'=>true,'items'=>$items,'total'=>$total,'page'=>$page,'pages'=>max(1,(int)ceil($total/$per))]);
}
if($action==='post'&&$method==='POST'){
 $acc=require_comment_account();$movieId=(int)($data['movie_id']??0);$body=trim((string)($data['body']??''));$icon=trim((string)($data['icon']??''));$iconAnimated=!empty($data['icon_animated'])?1:0;$parentId=(int)($data['parent_id']??0);
 if($movieId<1||!movie_by_id($movieId))out(['ok'=>false,'message'=>'Phim không tồn tại.'],404);
 if($parentId>0){$q=db()->prepare("SELECT id FROM comments WHERE id=? AND movie_id=? AND moderation_status='published' LIMIT 1");$q->execute([$parentId,$movieId]);if(!$q->fetchColumn())out(['ok'=>false,'message'=>'Bình luận được trả lời không còn tồn tại.'],404);}
 if($body===''||mb_strlen($body)>1000)out(['ok'=>false,'message'=>'Nội dung bình luận từ 1 đến 1000 ký tự.'],422);
 $allowedIcons=comment_icon_options();if($icon!==''&&!in_array($icon,$allowedIcons,true))$icon='';
 $ban=comment_ban($acc['email']);if($ban)out(['ok'=>false,'message'=>'Email này đang bị hạn chế bình luận.'.($ban['ends_at']?' Đến '.$ban['ends_at'].'.':'')],403);
 $s=db()->prepare("SELECT COUNT(*) FROM comments WHERE email=? AND created_at>=DATE_SUB(NOW(),INTERVAL 1 MINUTE)");$s->execute([$acc['email']]);if((int)$s->fetchColumn()>=3)out(['ok'=>false,'message'=>'Bạn bình luận quá nhanh. Hãy thử lại sau ít phút.'],429);
 $mod=moderate_text($body);$status=$mod['score']>=6?'hidden':'published';$reason=$mod['hits']?'Phát hiện: '.implode(', ',$mod['hits']):null;
 $st=db()->prepare('INSERT INTO comments(movie_id,account_id,parent_id,display_name,email,body,original_body,reaction_icon,reaction_icon_animated,moderation_score,moderation_status,flag_reason) VALUES(?,?,?,?,?,?,?,?,?,?,?,?)');$st->execute([$movieId,$acc['id'],$parentId?:null,$acc['display_name'],$acc['email'],$mod['text'],$body,$icon,$iconAnimated,$mod['score'],$status,$reason]);$cid=(int)db()->lastInsertId();
 foreach($mod['hits'] as $hit){learn_moderation_term((string)$hit,max(2,(int)$mod['score']));}
 if($parentId>0 && $mod['score']>=2){db()->prepare("INSERT INTO moderation_logs(comment_id,email,action,reason,score) VALUES(?,?,?,?,?)")->execute([$cid,$acc['email'],'hostility_reply','Phản hồi có dấu hiệu công kích cá nhân',$mod['score']]);}
 if($mod['score']>0)db()->prepare('INSERT INTO moderation_logs(comment_id,email,action,reason,score) VALUES(?,?,?,?,?)')->execute([$cid,$acc['email'],'flagged',$reason,$mod['score']]);
 $v=comment_violation_count($acc['email']);$auto=maybe_auto_mute($acc['email'],$v);if($auto)db()->prepare('INSERT INTO moderation_logs(comment_id,email,action,reason,score) VALUES(?,?,?,?,?)')->execute([$cid,$acc['email'],'auto_mute','Bot tự động hạn chế do nhiều vi phạm',$mod['score']]);
 out(['ok'=>true,'published'=>$status==='published','message'=>$status==='published'?'Bình luận đã được đăng.':'Bình luận đã được bot che và đưa vào kiểm duyệt.','masked'=>$mod['text'],'auto_mute'=>(bool)$auto]);
}
if($action==='delete'&&$method==='POST'){
 $acc=require_comment_account();$commentId=(int)($data['comment_id']??0);$s=db()->prepare('SELECT * FROM comments WHERE id=? AND account_id=? LIMIT 1');$s->execute([$commentId,$acc['id']]);$c=$s->fetch();if(!$c)out(['ok'=>false,'message'=>'Bạn không có quyền xóa bình luận này.'],403);if(!is_comment_delete_window_open((string)$c['created_at']))out(['ok'=>false,'message'=>'Bình luận đã quá 30 phút nên không thể tự xóa.'],403);db()->prepare("UPDATE comments SET moderation_status='deleted',deleted_at=NOW(),body='Bình luận đã được xóa bởi người đăng' WHERE id=?")->execute([$commentId]);out(['ok'=>true,'message'=>'Đã xóa bình luận.']);
}
out(['ok'=>false,'message'=>'Action không hợp lệ.'],400);
