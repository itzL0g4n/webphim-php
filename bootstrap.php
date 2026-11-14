<?php
declare(strict_types=1);

$config = require __DIR__ . '/config.php';
date_default_timezone_set($config['site']['timezone'] ?? 'Asia/Ho_Chi_Minh');
if (session_status() !== PHP_SESSION_ACTIVE) session_start();

function db(): PDO {
    static $pdo = null;
    static $schemaReady = false;
    global $config;
    if ($pdo instanceof PDO) {
        if (!$schemaReady) { ensure_schema($pdo); $schemaReady = true; }
        return $pdo;
    }
    try {
        $pdo = new PDO("mysql:host={$config['db']['host']};dbname={$config['db']['name']};charset={$config['db']['charset']}", $config['db']['user'], $config['db']['pass'], [
            PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES=>false,
        ]);
    } catch (PDOException $e) {
        if ((int)($e->errorInfo[1] ?? 0)!==1049) throw $e;
        $pdo=new PDO("mysql:host={$config['db']['host']};charset={$config['db']['charset']}",$config['db']['user'],$config['db']['pass'],[
            PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES=>false,
        ]);
        $safe=str_replace('`','``',$config['db']['name']);
        $pdo->exec("CREATE DATABASE IF NOT EXISTS `{$safe}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
        $pdo->exec("USE `{$safe}`");
    }
    ensure_schema($pdo); $schemaReady=true; return $pdo;
}
function table_exists(PDO $pdo,string $table):bool{ $s=$pdo->prepare("SELECT COUNT(*) FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME=?");$s->execute([$table]);return(int)$s->fetchColumn()>0; }
function column_exists(PDO $pdo,string $table,string $column):bool{ $s=$pdo->prepare("SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME=? AND COLUMN_NAME=?");$s->execute([$table,$column]);return(int)$s->fetchColumn()>0; }
function add_column_if_missing(PDO $pdo,string $table,string $column,string $definition):void{if(!column_exists($pdo,$table,$column)){try{$pdo->exec("ALTER TABLE `{$table}` ADD COLUMN `{$column}` {$definition}");}catch(Throwable $e){}}}
function ensure_schema(PDO $pdo):void{
    $pdo->exec("CREATE TABLE IF NOT EXISTS settings(id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,`key` VARCHAR(100) UNIQUE NOT NULL,value TEXT NULL) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    $pdo->exec("CREATE TABLE IF NOT EXISTS users(id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,username VARCHAR(100) UNIQUE NOT NULL,password_hash VARCHAR(255) NOT NULL,role VARCHAR(30) NOT NULL DEFAULT 'user',display_name VARCHAR(150) NULL,created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    $pdo->exec("CREATE TABLE IF NOT EXISTS movies(id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,name VARCHAR(255) NOT NULL,slug VARCHAR(255) UNIQUE NOT NULL,origin_name VARCHAR(255) NULL,thumb_url TEXT NULL,poster_url TEXT NULL,type VARCHAR(100) NULL,status VARCHAR(100) NULL,year SMALLINT NULL,time VARCHAR(100) NULL,quality VARCHAR(100) NULL,lang VARCHAR(255) NULL,categories TEXT NULL,countries TEXT NULL,description MEDIUMTEXT NULL,director TEXT NULL,actors TEXT NULL,trailer_url TEXT NULL,view_count BIGINT UNSIGNED NOT NULL DEFAULT 0,episodes_count INT UNSIGNED NOT NULL DEFAULT 0,is_featured TINYINT(1) NOT NULL DEFAULT 0,is_hidden TINYINT(1) NOT NULL DEFAULT 0,source_type VARCHAR(30) NOT NULL DEFAULT 'api',source_json LONGTEXT NULL,updated_at DATETIME NOT NULL,created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    $pdo->exec("CREATE TABLE IF NOT EXISTS episodes(id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,movie_id INT UNSIGNED NOT NULL,server_name VARCHAR(255) DEFAULT 'Server 1',season INT NOT NULL DEFAULT 1,episode_number INT NOT NULL DEFAULT 0,name VARCHAR(255) NULL,stream_url TEXT NULL,embed_url TEXT NULL,raw_json LONGTEXT NULL,created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,INDEX idx_episode(movie_id,server_name,season,episode_number),FOREIGN KEY(movie_id) REFERENCES movies(id) ON DELETE CASCADE) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    $pdo->exec("CREATE TABLE IF NOT EXISTS banners(id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,title VARCHAR(255) NULL,subtitle VARCHAR(255) NULL,image_url TEXT NULL,link_url TEXT NULL,position VARCHAR(40) DEFAULT 'home',button_text VARCHAR(80) NULL,active TINYINT(1) DEFAULT 1,sort_order INT DEFAULT 0,created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    $pdo->exec("CREATE TABLE IF NOT EXISTS sync_logs(id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,sync_type VARCHAR(50),message TEXT,items INT DEFAULT 0,created_at DATETIME DEFAULT CURRENT_TIMESTAMP) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    $pdo->exec("CREATE TABLE IF NOT EXISTS movie_ratings(id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,movie_id INT UNSIGNED NOT NULL,visitor_key CHAR(64) NOT NULL,rating TINYINT UNSIGNED NOT NULL,created_at DATETIME DEFAULT CURRENT_TIMESTAMP,updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,UNIQUE KEY uq_rating_visitor(movie_id,visitor_key),INDEX idx_rating_movie(movie_id),FOREIGN KEY(movie_id) REFERENCES movies(id) ON DELETE CASCADE) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    $pdo->exec("CREATE TABLE IF NOT EXISTS view_events(id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,movie_id INT UNSIGNED NOT NULL,visitor_key CHAR(64) NOT NULL,view_day DATE NOT NULL,created_at DATETIME DEFAULT CURRENT_TIMESTAMP,INDEX idx_view_movie_day(movie_id,view_day),UNIQUE KEY uq_view_once(movie_id,visitor_key,view_day),FOREIGN KEY(movie_id) REFERENCES movies(id) ON DELETE CASCADE) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    // Comment system
    $pdo->exec("CREATE TABLE IF NOT EXISTS comment_accounts(id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,display_name VARCHAR(120) NOT NULL,email VARCHAR(190) NOT NULL UNIQUE,pin_hash VARCHAR(255) NOT NULL,auth_token_hash VARCHAR(255) NULL,last_login_at DATETIME NULL,created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,INDEX idx_token(auth_token_hash(32))) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    $pdo->exec("CREATE TABLE IF NOT EXISTS comments(id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,movie_id INT UNSIGNED NOT NULL,account_id BIGINT UNSIGNED NOT NULL,display_name VARCHAR(120) NOT NULL,email VARCHAR(190) NOT NULL,body TEXT NOT NULL,original_body TEXT NULL,moderation_score INT NOT NULL DEFAULT 0,moderation_status ENUM('published','hidden','deleted') NOT NULL DEFAULT 'published',flag_reason VARCHAR(255) NULL,created_at DATETIME DEFAULT CURRENT_TIMESTAMP,updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,FOREIGN KEY(movie_id) REFERENCES movies(id) ON DELETE CASCADE,FOREIGN KEY(account_id) REFERENCES comment_accounts(id) ON DELETE CASCADE,INDEX idx_comments_movie(movie_id,moderation_status,created_at),INDEX idx_comments_email(email)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    $pdo->exec("CREATE TABLE IF NOT EXISTS comment_reactions(id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,comment_id BIGINT UNSIGNED NOT NULL,account_id BIGINT UNSIGNED NOT NULL,reaction_type VARCHAR(30) NOT NULL,created_at DATETIME DEFAULT CURRENT_TIMESTAMP,FOREIGN KEY(comment_id) REFERENCES comments(id) ON DELETE CASCADE,FOREIGN KEY(account_id) REFERENCES comment_accounts(id) ON DELETE CASCADE,UNIQUE KEY uq_comment_reaction(comment_id,account_id,reaction_type),INDEX idx_reaction_comment(comment_id,reaction_type)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    $pdo->exec("CREATE TABLE IF NOT EXISTS comment_bans(id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,email VARCHAR(190) NOT NULL,reason VARCHAR(255) NULL,starts_at DATETIME DEFAULT CURRENT_TIMESTAMP,ends_at DATETIME NULL,permanent TINYINT(1) NOT NULL DEFAULT 0,created_at DATETIME DEFAULT CURRENT_TIMESTAMP,created_by VARCHAR(100) NULL,INDEX idx_ban_email(email),INDEX idx_ban_active(email,permanent,ends_at)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    $pdo->exec("CREATE TABLE IF NOT EXISTS moderation_terms(id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,term VARCHAR(120) NOT NULL UNIQUE,category VARCHAR(50) DEFAULT 'negative',weight INT NOT NULL DEFAULT 1,source ENUM('default','learned','admin') NOT NULL DEFAULT 'default',active TINYINT(1) NOT NULL DEFAULT 1,created_at DATETIME DEFAULT CURRENT_TIMESTAMP,INDEX idx_term_active(active)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    $pdo->exec("CREATE TABLE IF NOT EXISTS moderation_logs(id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,comment_id BIGINT UNSIGNED NULL,email VARCHAR(190) NULL,action VARCHAR(50) NOT NULL,reason VARCHAR(255) NULL,score INT NOT NULL DEFAULT 0,created_at DATETIME DEFAULT CURRENT_TIMESTAMP,INDEX idx_mod_email(email),INDEX idx_mod_created(created_at)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    try{$pdo->exec("ALTER TABLE moderation_terms MODIFY source VARCHAR(30) NOT NULL DEFAULT 'default'");}catch(Throwable $e){}
    foreach([['comments','reaction_icon','VARCHAR(16) NULL'],['comments','reaction_icon_animated','TINYINT(1) NOT NULL DEFAULT 0'],['users','display_name','VARCHAR(150) NULL'],['movies','is_featured','TINYINT(1) NOT NULL DEFAULT 0'],['movies','is_hidden','TINYINT(1) NOT NULL DEFAULT 0'],['movies','source_type',"VARCHAR(30) NOT NULL DEFAULT 'api'"],['movies','view_count','BIGINT UNSIGNED NOT NULL DEFAULT 0'],['movies','episodes_count','INT UNSIGNED NOT NULL DEFAULT 0'],['movies','local_media_version','VARCHAR(40) NULL'],['movies','api_image_source','VARCHAR(255) NULL'],['episodes','server_name',"VARCHAR(255) DEFAULT 'Server 1'"],['episodes','season','INT NOT NULL DEFAULT 1'],['episodes','episode_number','INT NOT NULL DEFAULT 0'],['episodes','stream_url','TEXT NULL'],['episodes','embed_url','TEXT NULL'],['episodes','raw_json','LONGTEXT NULL'],['episodes','source_type',"VARCHAR(30) NOT NULL DEFAULT 'api'"],['banners','subtitle','VARCHAR(255) NULL'],['banners','button_text','VARCHAR(80) NULL'],['banners','active','TINYINT(1) DEFAULT 1'],['banners','sort_order','INT DEFAULT 0']] as $x) add_column_if_missing($pdo,$x[0],$x[1],$x[2]);
    try{$pdo->exec("UPDATE movies SET updated_at=COALESCE(updated_at,created_at,NOW()) WHERE updated_at IS NULL");}catch(Throwable $e){}
    try{$pdo->exec("ALTER TABLE movies MODIFY updated_at DATETIME NOT NULL");}catch(Throwable $e){}

    $hash='$2y$12$nKXSgqCEc3Ck.f5mYgQcUur5/X33Wpi02visq7OOuCCCZV62I7zKW';
    $pdo->prepare("INSERT INTO users(username,password_hash,role,display_name) VALUES('admin',?,?,?) ON DUPLICATE KEY UPDATE role='admin',display_name=COALESCE(display_name,VALUES(display_name))")->execute([$hash,'admin','Quản trị viên']);
    $defaults=['site_name'=>'FilmNova','site_tagline'=>'Xem phim mới cập nhật mỗi ngày','hero_text'=>'Thư viện phim hiện đại, cập nhật tự động và cho phép quản trị thủ công.','site_logo'=>'','cron_token'=>'filmnova-change-token','last_sync_at'=>'','active_theme'=>'movie-premium','contact_facebook'=>'','contact_telegram'=>'','contact_zalo'=>'','contact_tiktok'=>'','comment_auto_mute_threshold'=>'3','comment_auto_mute_hours'=>'24','view_hash_salt'=>'','player_allowed_domains'=>'','app_api_key'=>''];
    $ins=$pdo->prepare("INSERT INTO settings(`key`,value) VALUES(?,?) ON DUPLICATE KEY UPDATE `key`=`key`");foreach($defaults as $k=>$v)$ins->execute([$k,$v]);

    $defaultsTerms=['địt','đụ','dm','đmm','dmm','đéo','đéo có','ngu','óc chó','chó chết','cặn bã','khốn nạn','thằng điên','con điên','vãi lồn','vl','fuck','shit','bitch','asshole','stupid'];
    foreach($defaultsTerms as $term){$st=$pdo->prepare("INSERT INTO moderation_terms(term,source,weight,active) VALUES(?,?,?,1) ON DUPLICATE KEY UPDATE term=term");$st->execute([$term,'default',2]);}
}
function e(?string $s):string{return htmlspecialchars($s??'',ENT_QUOTES,'UTF-8');}
function comment_icon_options():array{return ['❤️','👍','😂','😮','😢','🔥','👏','😍','🎉','🤔','😎','💯'];}

function app_base():string{static $base=null;if($base!==null)return$base;if(isset($_SERVER['DOCUMENT_ROOT'],$_SERVER['SCRIPT_FILENAME'])&&is_dir($_SERVER['DOCUMENT_ROOT'])){$root=realpath($_SERVER['DOCUMENT_ROOT']);$app=realpath(__DIR__);if($root&&$app&&str_starts_with(str_replace('\\','/',$app),str_replace('\\','/',$root))){$rel=substr(str_replace('\\','/',$app),strlen(str_replace('\\','/',$root)));return$base=rtrim($rel,'/');}}global $config;return$base=rtrim((string)($config['site']['base_path']??''),'/');}
function site(string $p=''):string{$b=app_base();return($b?:'').'/'.ltrim($p,'/');}
function site_origin():string{
    $https=!empty($_SERVER['HTTPS'])&&$_SERVER['HTTPS']!=='off';
    if(!$https && !empty($_SERVER['HTTP_X_FORWARDED_PROTO'])) $https=strtolower((string)$_SERVER['HTTP_X_FORWARDED_PROTO'])==='https';
    $host=(string)($_SERVER['HTTP_HOST']??'localhost');
    return ($https?'https://':'http://').$host;
}
function absolute_site_url(string $p=''):string{
    $u=trim($p);
    if($u==='')return site_origin().site();
    if(preg_match('~^https?://~i',$u)){
        $oldHost=(string)(parse_url($u,PHP_URL_HOST)??'');
        $currentHost=(string)(parse_url(site_origin(),PHP_URL_HOST)??'');
        if($oldHost!==''&&$currentHost!==''&&strcasecmp($oldHost,$currentHost)!==0&&preg_match('~^(localhost|127\.0\.0\.1|0\.0\.0\.0)$~i',$oldHost)){
            $path=(string)(parse_url($u,PHP_URL_PATH)??'/');$query=(string)(parse_url($u,PHP_URL_QUERY)??'');
            $u=site_origin().site(ltrim($path,'/')).($query!==''?'?'.$query:'');
        }
        return $u;
    }
    return site_origin().site(ltrim($u,'/'));
}
function normalize_public_url(string $u):string{return absolute_site_url($u);}
function img(?string $u,?string $pathImage=null):string{
  if(!$u)return site('assets/placeholder.svg');
  $u=trim($u);if($u==='')return site('assets/placeholder.svg');
  if(str_starts_with($u,'//'))return'https:'.$u;
  if(preg_match('~^https?://~i',$u))return$u;
  if(str_starts_with($u,'/'))return site(ltrim($u,'/'));
  if(str_starts_with($u,'uploads/')||str_starts_with($u,'assets/'))return site($u);
  $base=$pathImage?:'https://phimimg.com/';if(!str_ends_with($base,'/'))$base.='/';return$base.ltrim($u,'/');
}
function media_dir(string $kind='posters'):string{$d=__DIR__.'/uploads/'.$kind;if(!is_dir($d))@mkdir($d,0775,true);return$d;}
function media_public_path(string $kind,string $file):string{return 'uploads/'.trim($kind,'/').'/'.ltrim($file,'/');}
function absolute_remote_image(string $url,string $pathImage='',string $sourceBase=''):string{
  $url=trim($url);if($url==='')return'';
  if(str_starts_with($url,'//'))return'https:'.$url;
  if(preg_match('~^https?://~i',$url))return$url;
  $base=trim($pathImage);
  if($base!==''){if(str_starts_with($base,'//'))$base='https:'.$base;if(!preg_match('~^https?://~i',$base))$base=rtrim($sourceBase,'/').'/'.ltrim($base,'/');}
  if($base==='')$base=rtrim($sourceBase,'/').'/';
  return rtrim($base,'/').'/'.ltrim($url,'/');
}
function remote_image_filename(string $url,int $movieId,string $role):string{return $movieId.'-'.substr(sha1($role.'|'.$url),0,20).'.webp';}
function compress_image_bytes(string $data,string $role):?string{
  if(!function_exists('getimagesizefromstring')||!function_exists('imagecreatefromstring')) return null;
  $info=@getimagesizefromstring($data);
  if(!$info||empty($info[0])||empty($info[1])) return null;
  $src=@imagecreatefromstring($data); if(!$src) return null;
  $w=(int)$info[0];$h=(int)$info[1];
  $maxW=$role==='poster'?900:480;$maxH=$role==='poster'?1350:720;
  $scale=min(1,$maxW/$w,$maxH/$h);$nw=max(1,(int)round($w*$scale));$nh=max(1,(int)round($h*$scale));
  $dst=imagecreatetruecolor($nw,$nh);
  $hasAlpha=($info[2]??0)===IMAGETYPE_PNG||($info[2]??0)===IMAGETYPE_WEBP||($info[2]??0)===IMAGETYPE_GIF;
  if($hasAlpha){imagealphablending($dst,false);imagesavealpha($dst,true);$transparent=imagecolorallocatealpha($dst,0,0,0,127);imagefilledrectangle($dst,0,0,$nw,$nh,$transparent);}
  else {$bg=imagecolorallocate($dst,255,255,255);imagefilledrectangle($dst,0,0,$nw,$nh,$bg);}
  imagecopyresampled($dst,$src,0,0,0,0,$nw,$nh,$w,$h);
  ob_start();
  $ok=false;
  if(function_exists('imagewebp')) $ok=imagewebp($dst,null,72);
  elseif(function_exists('imagejpeg')) $ok=imagejpeg($dst,null,74);
  $out=ob_get_clean();
  imagedestroy($dst);imagedestroy($src);
  return $ok&&strlen($out)>200?$out:null;
}
function optimize_local_image(string $path,string $role):bool{
  if(!is_file($path)||filesize($path)<200)return false;
  $raw=@file_get_contents($path);if($raw===false)return false;
  $out=compress_image_bytes($raw,$role);if($out===null)return false;
  return @file_put_contents($path,$out,LOCK_EX)!==false;
}
function download_movie_image(string $url,int $movieId,string $role,string $pathImage='',string $sourceBase=''):string{
  $remote=absolute_remote_image($url,$pathImage,$sourceBase);if($remote==='')return'';
  $kind=$role==='poster'?'posters':'thumbs';$file=remote_image_filename($remote,$movieId,$role);$dir=media_dir($kind);$dest=$dir.DIRECTORY_SEPARATOR.$file;
  if(is_file($dest)&&filesize($dest)>200)return media_public_path($kind,$file);
  $ch=curl_init($remote);if(!$ch)return'';curl_setopt_array($ch,[CURLOPT_RETURNTRANSFER=>true,CURLOPT_FOLLOWLOCATION=>true,CURLOPT_TIMEOUT=>18,CURLOPT_CONNECTTIMEOUT=>7,CURLOPT_USERAGENT=>'FilmNova/2.0',CURLOPT_HTTPHEADER=>['Accept:image/avif,image/webp,image/apng,image/*,*/*;q=0.8']]);$data=curl_exec($ch);$status=(int)curl_getinfo($ch,CURLINFO_HTTP_CODE);$ctype=(string)curl_getinfo($ch,CURLINFO_CONTENT_TYPE);curl_close($ch);if($data===false||$status<200||$status>=300||strlen($data)<200)return'';
  if($ctype!==''&&!preg_match('~^image/|octet-stream~i',$ctype))return'';if(strlen($data)>12*1024*1024)return'';
  $optimized=compress_image_bytes($data,$role);
  if($optimized!==null)$data=$optimized;
  if(@file_put_contents($dest,$data,LOCK_EX)===false)return'';return media_public_path($kind,$file);
}
function local_media_file_valid(?string $path):bool{
  $path=trim((string)$path);
  if($path==='')return false;
  if(!preg_match('~^uploads/(posters|thumbs)/([^/?#]+)$~i',$path,$m))return false;
  $file=__DIR__.'/uploads/'.$m[1].'/'.$m[2];
  return is_file($file) && filesize($file)>200;
}
function save_movie_images(int $movieId,array $m,string $pathImage='',string $sourceBase=''):array{
  $pdo=db();
  $q=$pdo->prepare('SELECT thumb_url,poster_url,local_media_version FROM movies WHERE id=? LIMIT 1');$q->execute([$movieId]);$old=$q->fetch()?:[];
  $out=['thumb'=>'','poster'=>''];
  $thumbExisting=local_media_file_valid((string)($old['thumb_url']??''));
  $posterExisting=local_media_file_valid((string)($old['poster_url']??''));
  // Existing local artwork is authoritative: do not download it again during episode/data updates.
  if($thumbExisting)$out['thumb']=(string)$old['thumb_url'];
  else {
    $out['thumb']=download_movie_image((string)($m['thumb_url']??''),$movieId,'thumb',$pathImage,$sourceBase);
  }
  if($posterExisting)$out['poster']=(string)$old['poster_url'];
  else {
    $out['poster']=download_movie_image((string)($m['poster_url']??$m['thumb_url']??''),$movieId,'poster',$pathImage,$sourceBase);
  }
  $sets=[];$vals=[];
  if($out['thumb']){$sets[]='thumb_url=?';$vals[]=$out['thumb'];}
  else{$sets[]='thumb_url=?';$vals[]=site('assets/placeholder.svg');}
  if($out['poster']){$sets[]='poster_url=?';$vals[]=$out['poster'];}
  else{$sets[]='poster_url=?';$vals[]=site('assets/placeholder.svg');}
  $sets[]='local_media_version=?';$vals[]=(local_media_file_valid($out['thumb'])||local_media_file_valid($out['poster']))?'1':'';
  $sets[]='api_image_source=?';$vals[]=$sourceBase;
  $vals[]=$movieId;
  $pdo->prepare('UPDATE movies SET '.implode(',',$sets).' WHERE id=?')->execute($vals);
  return$out;
}
function sanitize_source_json(array $m):string{
  unset($m['source_json']);
  // Source JSON stores metadata only; movie artwork is downloaded locally.
  $m['thumb_url']='';$m['poster_url']='';
  return json_encode($m,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
}


function api_catalog_items(array $json): array {
    $paths=[['items'],['data','items'],['data','items','data'],['data','list'],['data','data'],['list']];
    foreach($paths as $path){
        $cur=$json; $ok=true;
        foreach($path as $k){
            if(is_array($cur)&&array_key_exists($k,$cur)) $cur=$cur[$k];
            else {$ok=false; break;}
        }
        if($ok&&is_array($cur)) return array_values(array_filter($cur,'is_array'));
    }
    return [];
}
function api_catalog_meta(array $json): array {
    $p=pick($json,['pagination','data.pagination','data.params.pagination','params.pagination'],[]);
    if(!is_array($p)) $p=[];
    $total=(int)pick($p,['totalItems','total'],0);
    $per=(int)pick($p,['totalItemsPerPage','perPage','limit'],24);
    $current=(int)pick($p,['currentPage','page'],1);
    $pages=(int)pick($p,['totalPages','pageRanges','pages'],0);
    if($pages<=0&&$per>0&&$total>0) $pages=(int)ceil($total/$per);
    return ['total'=>$total,'per_page'=>$per?:24,'page'=>$current?:1,'pages'=>$pages?:1];
}
function sql_catalog(string $kind='all',int $page=1,array $filters=[]):?array{
  $page=max(1,$page);$per=24;$where=['is_hidden=0'];$args=[];
  if($kind==='year'){ $where[]='year=?';$args[]=(int)($filters['year']??0);}
  if($kind==='views'){ $order='view_count DESC,updated_at DESC,id DESC'; }
  elseif($kind==='search'){ $q=trim((string)($filters['q']??''));if($q==='')return ['items'=>[],'meta'=>['total'=>0,'per_page'=>$per,'page'=>$page,'pages'=>1],'pathImage'=>''];$where[]='(name LIKE ? OR origin_name LIKE ? OR actors LIKE ? OR categories LIKE ? OR countries LIKE ? OR description LIKE ?)';$like='%'.$q.'%';$args=[$like,$like,$like,$like,$like,$like];$order='updated_at DESC,id DESC';}
  elseif($kind==='all'){ $order='updated_at DESC,id DESC'; }
  else{return null;}
  $pdo=db();$w=implode(' AND ',$where);$count=$pdo->prepare("SELECT COUNT(*) FROM movies WHERE $w");$count->execute($args);$total=(int)$count->fetchColumn();$pages=max(1,(int)ceil($total/$per));$page=min($page,$pages);$offset=($page-1)*$per;$sql="SELECT * FROM movies WHERE $w ORDER BY $order LIMIT $per OFFSET $offset";$st=$pdo->prepare($sql);$st->execute($args);return ['items'=>$st->fetchAll(),'meta'=>['total'=>$total,'per_page'=>$per,'page'=>$page,'pages'=>$pages],'pathImage'=>''];
}
function fetch_catalog(string $kind='all', int $page=1, array $filters=[]): array {
    global $config;
    $page=max(1,$page);
    $slug=rawurlencode((string)($filters['slug']??''));
    if($kind==='category') $endpoint='/v1/api/the-loai/'.$slug.'?page='.$page;
    elseif($kind==='country') $endpoint='/v1/api/quoc-gia/'.$slug.'?page='.$page;
    elseif($kind==='type') $endpoint='/v1/api/danh-sach/'.rawurlencode((string)($filters['slug']??'phim-moi')).'?page='.$page;
    elseif($kind==='year') $endpoint='/v1/api/nam/'.rawurlencode((string)($filters['year']??date('Y'))).'?page='.$page;
    elseif($kind==='search') $endpoint='/v1/tim-kiem?keyword='.rawurlencode((string)($filters['q']??'')).'&page='.$page;
    else $endpoint='/v1/api/danh-sach?page='.$page;
    foreach(['country','category','year_filter','sort_field','sort_type'] as $f){
        if(isset($filters[$f])&&$filters[$f]!=='') $endpoint.='&'.rawurlencode($f).'='.rawurlencode((string)$filters[$f]);
    }
    $json=api_get(api_url($endpoint),(int)$config['api']['timeout']);
    if(!$json) return ['items'=>[],'meta'=>['total'=>0,'per_page'=>24,'page'=>$page,'pages'=>1],'pathImage'=>''];
    return ['items'=>api_catalog_items($json),'meta'=>api_catalog_meta($json),'pathImage'=>(string)pick($json,['pathImage','data.pathImage'],'https://phimimg.com/')];
}
function cached_api_list(string $endpointKey, int $ttl=21600): array {
    $key='api_cache_'.$endpointKey;
    $raw=setv($key,'');
    $pack=json_decode($raw,true);
    if(is_array($pack)&&isset($pack['at'],$pack['data'])&&(time()-(int)$pack['at']<$ttl)&&is_array($pack['data'])) return $pack['data'];
    global $config;
    $endpoint=(string)($config['api'][$endpointKey]??'');
    if($endpoint==='') return [];
    $json=api_get(api_url($endpoint),(int)$config['api']['timeout']);
    $data=[];
    if($json){
        $data=pick($json,['data.items','items','data'],[]);
        if(!is_array($data)) $data=[];
    }
    setv_save($key,json_encode(['at'=>time(),'data'=>$data],JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES));
    return $data;
}
function setv(string $k,string $d=''):string{$s=db()->prepare('SELECT value FROM settings WHERE `key`=?');$s->execute([$k]);$v=$s->fetchColumn();return$v===false?$d:(string)$v;}
function setv_save(string $k,string $v):void{db()->prepare('INSERT INTO settings(`key`,value) VALUES(?,?) ON DUPLICATE KEY UPDATE value=VALUES(value)')->execute([$k,$v]);}

function request_is_apple(): bool {
    $ua=(string)($_SERVER['HTTP_USER_AGENT']??'');
    return (bool)preg_match('~iPhone|iPad|iPod|Macintosh~i',$ua);
}
function request_platform(): string {
    $ua=(string)($_SERVER['HTTP_USER_AGENT']??'');
    if(preg_match('~iPhone|iPad|iPod~i',$ua)) return 'ios';
    if(preg_match('~Macintosh~i',$ua)) return 'mac';
    if(preg_match('~Android~i',$ua)) return 'android';
    if(preg_match('~Windows~i',$ua)) return 'windows';
    if(preg_match('~Linux~i',$ua)) return 'linux';
    return 'other';
}
function app_feature_enabled(): bool { return setv('app_gate_enabled','0')==='1'; }
function app_ios_verified(): bool {
    return !empty($_SESSION['fm_ios_verified_until']) && (int)$_SESSION['fm_ios_verified_until']>time() && request_is_apple();
}
function app_verify_ios_pin(string $pin): bool {
    $hash=setv('app_ios_pin_hash','');
    if($hash===''||!password_verify($pin,$hash)) return false;
    $_SESSION['fm_ios_verified_until']=time()+3600;
    return true;
}
function app_download_url(string $platform): string { return normalize_public_url(setv('app_'.$platform.'_url','')); }
function app_api_key(): string {
    $k=trim(setv('app_api_key',''));
    if($k===''){ $k='fn_'.bin2hex(random_bytes(24)); setv_save('app_api_key',$k); }
    return $k;
}
function app_api_authorized(): bool {
    $expected=app_api_key();
    $given=(string)($_SERVER['HTTP_X_FILMNOVA_APP_KEY']??($_GET['key']??''));
    return $given!==''&&hash_equals($expected,$given);
}
function app_home_notice(string $default=''): string { return setv('app_notice_text',$default); }
function render_app_gate(): void {
    if(!app_feature_enabled()) return;
    $win=app_download_url('windows'); $and=app_download_url('android');
    $ios=normalize_public_url(setv('app_ios_url','ios.php'));
    $currentSlug=trim((string)($_GET['slug']??''));
    if($currentSlug!==''){ $sep=strpos($ios,'?')===false?'?':'&'; $ios.=$sep.'slug='.rawurlencode($currentSlug); }
    echo '<section class="fm-app-gate" aria-label="Ứng dụng xem phim"><div class="fm-app-gate-head"><div class="fm-app-gate-title">📱 Muốn xem phim?</div><p>'.e(app_home_notice('Muốn xem phim, vui lòng chọn cách xem phù hợp với thiết bị của bạn.')).'</p></div><div class="fm-app-choices">';
    echo $win?'<a class="fm-app-choice" href="'.e($win).'" target="_blank" rel="noopener"><b>▣ Windows</b><span>Tải ứng dụng EXE</span></a>':'<div class="fm-app-choice disabled"><b>▣ Windows</b><span>Chưa cấu hình ứng dụng</span></div>';
    echo $and?'<a class="fm-app-choice" href="'.e($and).'" target="_blank" rel="noopener"><b>🤖 Android</b><span>Tải ứng dụng APK</span></a>':'<div class="fm-app-choice disabled"><b>🤖 Android</b><span>Chưa cấu hình ứng dụng</span></div>';
    echo '<a class="fm-app-choice" href="'.e($ios).'" data-apple-view><b> Apple</b><span>iPhone · iPad · Mac</span></a></div><div class="fm-app-gate-msg" id="fmAppleGateMsg" hidden></div></section>';
}
function csrf():string{if(empty($_SESSION['csrf']))$_SESSION['csrf']=bin2hex(random_bytes(24));return$_SESSION['csrf'];}
function csrf_check():void{if(!hash_equals($_SESSION['csrf']??'',$_POST['csrf']??'')){http_response_code(419);exit('CSRF token không hợp lệ');}}
function user():?array{if(empty($_SESSION['uid']))return null;$s=db()->prepare('SELECT * FROM users WHERE id=?');$s->execute([(int)$_SESSION['uid']]);return$s->fetch()?:null;}
function admin():bool{$u=user();return$u&&$u['role']==='admin';}
function require_admin():void{if(!admin()){header('Location: login.php');exit;}}
function client_view_key(): string {
    $cookie = $_COOKIE['fm_vid'] ?? '';
    if (!is_string($cookie) || !preg_match('/^[a-f0-9]{32,128}$/',$cookie)) {
        $cookie = bin2hex(random_bytes(16));
        setcookie('fm_vid',$cookie,['expires'=>time()+31536000,'path'=>'/','samesite'=>'Lax','httponly'=>true, 'secure'=>(!empty($_SERVER['HTTPS'])&&$_SERVER['HTTPS']!=='off')]);
        $_COOKIE['fm_vid']=$cookie;
    }
    $salt = setv('view_hash_salt','');
    if ($salt==='') {
        $salt = bin2hex(random_bytes(24));
        setv_save('view_hash_salt',$salt);
    }
    $ip = (string)($_SERVER['REMOTE_ADDR'] ?? '0.0.0.0');
    $ua = substr((string)($_SERVER['HTTP_USER_AGENT'] ?? ''),0,512);
    return hash_hmac('sha256',$cookie.'|'.$ip.'|'.$ua,$salt);
}
function register_view(int $movieId): bool {
    if ($movieId<=0) return false;
    $key=client_view_key(); $day=date('Y-m-d');
    try {
        $st=db()->prepare('INSERT INTO view_events(movie_id,visitor_key,view_day) VALUES(?,?,?)');
        $st->execute([$movieId,$key,$day]);
        db()->prepare('UPDATE movies SET view_count=view_count+1 WHERE id=?')->execute([$movieId]);
        return true;
    } catch (PDOException $e) {
        if ((int)($e->errorInfo[1]??0)===1062) return false;
        throw $e;
    }
}
function movie_rating(int $movieId):array {
    if($movieId<=0) return ['average'=>0,'count'=>0,'mine'=>0];
    $s=db()->prepare('SELECT AVG(rating) a, COUNT(*) c FROM movie_ratings WHERE movie_id=?');$s->execute([$movieId]);$r=$s->fetch()?:[];
    $mine=0;
    try{$s=db()->prepare('SELECT rating FROM movie_ratings WHERE movie_id=? AND visitor_key=? LIMIT 1');$s->execute([$movieId,client_view_key()]);$mine=(int)($s->fetchColumn()?:0);}catch(Throwable $e){}
    return ['average'=>round((float)($r['a']??0),1),'count'=>(int)($r['c']??0),'mine'=>$mine];
}
function top_rated_movies(int $limit=8):array {
    $limit=max(1,min(20,$limit));
    $sql="SELECT m.id,m.name,m.slug,m.thumb_url,m.poster_url,m.year,ROUND(AVG(r.rating),1) rating,COUNT(r.id) rating_count FROM movies m JOIN movie_ratings r ON r.movie_id=m.id WHERE m.is_hidden=0 GROUP BY m.id ORDER BY rating DESC,rating_count DESC,m.updated_at DESC LIMIT {$limit}";
    return db()->query($sql)->fetchAll();
}
function latest_movies(int $limit=8):array {
    $limit=max(1,min(20,$limit));
    return db()->query("SELECT id,name,slug,thumb_url,poster_url,year,episodes_count,updated_at FROM movies WHERE is_hidden=0 ORDER BY updated_at DESC,id DESC LIMIT {$limit}")->fetchAll();
}
function api_get(string $url,int $timeout=20):?array{$c=curl_init($url);curl_setopt_array($c,[CURLOPT_RETURNTRANSFER=>true,CURLOPT_FOLLOWLOCATION=>true,CURLOPT_TIMEOUT=>$timeout,CURLOPT_CONNECTTIMEOUT=>8,CURLOPT_USERAGENT=>'FilmNova/1.0',CURLOPT_HTTPHEADER=>['Accept: application/json']]);$r=curl_exec($c);$st=(int)curl_getinfo($c,CURLINFO_HTTP_CODE);curl_close($c);if(!$r||$st<200||$st>=300)return null;$j=json_decode($r,true);return is_array($j)?$j:null;}
function api_url(string $ep,array $vars=[]):string{global $config;$u=rtrim($config['api']['base_url'],'/').'/'.ltrim($ep,'/');foreach($vars as $k=>$v)$u=str_replace('{'.$k.'}',rawurlencode((string)$v),$u);return$u;}
function api_url_base(string $base,string $ep,array $vars=[]):string{$u=rtrim($base,'/').'/'.ltrim($ep,'/');foreach($vars as $k=>$v)$u=str_replace('{'.$k.'}',rawurlencode((string)$v),$u);$u=preg_replace('/\{[a-z_]+\}/i','',$u)??$u;$u=preg_replace('/[?&][a-z_]+=&/i','&',$u)??$u;$u=rtrim($u,'?&');return$u;}
function api_get_any(string $base,string $endpoint,array $vars=[],int $timeout=8):?array{ $url=api_url_base($base,$endpoint,$vars); return api_get($url,$timeout); }
function api_extract_items_generic(array $json):array{ $candidates=[]; $walk=function($v,$path='')use(&$walk,&$candidates){if(!is_array($v))return; if(array_is_list_compat($v)){ $score=0; foreach(array_slice($v,0,8) as $x){if(is_array($x)&& (isset($x['slug'])||isset($x['name'])||isset($x['title'])||isset($x['movie_name'])))$score++;} if($score>0)$candidates[]=[$score,count($v),$v,$path]; } else {foreach($v as $k=>$x){if(is_array($x))$walk($x,$path.'.'.$k);}}}; $walk($json); usort($candidates,fn($a,$b)=>($b[0]<=>$a[0])?:($b[1]<=>$a[1])); return $candidates[0][2]??[];}
function array_is_list_compat(array $a):bool{ if(function_exists('array_is_list')) return array_is_list($a); $i=0; foreach($a as $k=>$_){if($k!==$i++)return false;} return true;}
function array_is_list_safe(array $a):bool{if(function_exists('array_is_list'))return array_is_list($a);$i=0;foreach($a as $k=>$_){if($k!==$i++)return false;}return true;}
function api_is_assoc(array $a):bool{return $a!==[]&&!array_is_list_safe($a);}
function api_find_episode_arrays(array $root):array{
  $found=[];$walk=function($v)use(&$walk,&$found){if(!is_array($v))return;if(api_is_assoc($v)){foreach(['episodes','episode_list','episodeList','episodes_data','server_data','items'] as $k){if(isset($v[$k])&&is_array($v[$k]))$found[]=$v[$k];}foreach($v as $x)if(is_array($x))$walk($x);}else{$score=0;foreach(array_slice($v,0,10) as $x){if(is_array($x)&&(isset($x['link_m3u8'])||isset($x['link_embed'])||isset($x['m3u8'])||isset($x['embed_url'])||isset($x['stream_url'])||isset($x['url'])||isset($x['file'])))$score++;}if($score>0)$found[]=$v;foreach($v as $x)if(is_array($x))$walk($x);}};$walk($root);$unique=[];$out=[];foreach($found as $a){$h=md5(json_encode($a,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES));if(!isset($unique[$h])){$unique[$h]=1;$out[]=$a;}}return$out;}
function api_detail_payload(array $j):array{$c=[];foreach(['data.item','data.movie','movie','data'] as $k){$v=pick($j,[$k],null);if(is_array($v))$c[]=$v;}$c[]=$j;foreach($c as $v)if(api_find_episode_arrays($v))return$v;return$c[0]??$j;}
function api_normalize_episodes(array $detail):array{
  $norm=[];$seen=[];foreach(api_find_episode_arrays($detail) as $arr){if(api_is_assoc($arr))$arr=[$arr];foreach($arr as $container){if(!is_array($container))continue;$server=(string)pick($container,['server_name','server_name_new','server','group_name'],'');$list=pick($container,['server_data','episodes','items','data'],null);if($list===null&&!api_is_assoc($container))$list=$container;if(!is_array($list))continue;if(api_is_assoc($list)&&(isset($list['link_m3u8'])||isset($list['link_embed'])||isset($list['m3u8'])||isset($list['embed_url'])||isset($list['stream_url'])||isset($list['url'])||isset($list['file'])))$list=[$list];foreach($list as $ix=>$ep){if(!is_array($ep))continue;$srv=$server!==''?$server:(string)pick($ep,['server_name','server','group_name','server_name_new'],'Server 1');$name=(string)pick($ep,['name','episode_name','episode','title','slug'],'Tập '.((int)$ix+1));$num=(int)pick($ep,['episode_number','episode_num','episodeNumber','ep','number'],0);if($num<=0&&preg_match('/(\d+)/u',$name,$mm))$num=(int)$mm[1];if($num<=0)$num=(int)$ix+1;$season=(int)pick($ep,['season','season_number'],1);$stream=(string)pick($ep,['link_m3u8','m3u8','stream_url','link_m3u8_vip','hls','file'],'');$embed=(string)pick($ep,['link_embed','embed','embed_url','url','iframe'],'');if($stream===''&&$embed==='')continue;$key=mb_strtolower(trim($srv).'|'.$season.'|'.$num,'UTF-8');if(isset($seen[$key]))continue;$seen[$key]=1;$norm[]=['server'=>$srv?:'Server 1','season'=>$season,'num'=>$num,'name'=>$name,'stream'=>$stream,'embed'=>$embed,'raw'=>json_encode($ep,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES)];}}}return$norm;}
function api_save_episodes(int $movieId,array $detail,string $sourceType):int{$norm=api_normalize_episodes($detail);if(!$norm)return 0;$pdo=db();$pdo->beginTransaction();try{$pdo->prepare('DELETE FROM episodes WHERE movie_id=? AND source_type=?')->execute([$movieId,$sourceType]);$ins=$pdo->prepare('INSERT INTO episodes(movie_id,server_name,season,episode_number,name,stream_url,embed_url,raw_json,source_type) VALUES(?,?,?,?,?,?,?,?,?)');foreach($norm as $e)$ins->execute([$movieId,$e['server'],$e['season'],$e['num'],$e['name'],$e['stream'],$e['embed'],$e['raw'],$sourceType]);$pdo->prepare('UPDATE movies SET episodes_count=(SELECT COUNT(*) FROM episodes WHERE movie_id=?) WHERE id=?')->execute([$movieId,$movieId]);$pdo->commit();return count($norm);}catch(Throwable $e){if($pdo->inTransaction())$pdo->rollBack();throw$e;}}
function api_extract_meta_generic(array $json):array{ $keys=['total','total_items','totalItems','count','totalResults','total_movies','movie_total','pagination.total','data.total','data.totalItems','data.pagination.total','data.meta.total','data.pagination.totalPages','pagination.totalPages','totalPages']; $out=['total'=>null,'total_pages'=>null,'page'=>null,'limit'=>null]; foreach($keys as $k){$v=pick($json,[$k],null); if($v===null)continue; if(str_ends_with_compat($k,'totalPages'))$out['total_pages']=(int)$v; elseif(str_contains_compat($k,'total')||$k==='count')$out['total']=is_numeric($v)?(int)$v:null;} $out['page']=(int)pick($json,['page','data.page','pagination.currentPage','data.pagination.currentPage'],0); $out['limit']=(int)pick($json,['limit','page_size','data.limit','pagination.limit','data.pagination.limit'],0); return $out;}
function str_ends_with_compat(string $hay,string $needle):bool{return $needle===''||substr($hay,-strlen($needle))===$needle;}
function str_contains_compat(string $hay,string $needle):bool{return $needle===''||strpos($hay,$needle)!==false;}
function pick($d,array $ks,$def=null){foreach($ks as $k){$c=$d;$ok=true;foreach(explode('.',$k) as $p){if(is_array($c)&&array_key_exists($p,$c))$c=$c[$p];else{$ok=false;break;}}if($ok&&$c!==null&&$c!=='')return$c;}return$def;}
function normlist($x):string{if(!is_array($x))return(string)$x;$o=[];foreach($x as $v){if(is_string($v))$o[]=$v;elseif(is_array($v))$o[]=(string)pick($v,['name','title'],'');}return implode(', ',array_filter($o));}
function normmovie(array $r):array{
  $n=(string)pick($r,['name','title','movie.name','movie.title','data.item.name','data.movie.name','data.name'],'Untitled');
  $ascii=iconv('UTF-8','ASCII//TRANSLIT//IGNORE',$n); $fallback=preg_replace('/[^a-z0-9]+/','-',strtolower($ascii?:$n));
  $slug=(string)pick($r,['slug','movie.slug','data.item.slug','data.movie.slug','data.slug'],$fallback);
  $thumb=(string)pick($r,['thumb_url','thumb','thumbnail','poster_url','poster','image','movie.thumb_url','movie.poster_url','data.item.thumb_url','data.item.poster_url','data.movie.thumb_url','data.movie.poster_url'],'');
  $poster=(string)pick($r,['poster_url','poster','thumb_url','thumb','thumbnail','image','movie.poster_url','movie.thumb_url','data.item.poster_url','data.item.thumb_url','data.movie.poster_url','data.movie.thumb_url'],$thumb);
  return['name'=>$n,'slug'=>trim($slug,'-'),'origin_name'=>(string)pick($r,['origin_name','original_name','original_title','movie.origin_name','movie.original_name','data.item.origin_name','data.movie.origin_name'],''),'thumb_url'=>$thumb,'poster_url'=>$poster,'type'=>(string)pick($r,['type','movie.type','data.item.type','data.movie.type'],''),'status'=>(string)pick($r,['status','movie.status','data.item.status','data.movie.status'],''),'year'=>(int)pick($r,['year','release_year','movie.year','data.item.year','data.movie.year'],0),'time'=>(string)pick($r,['time','duration','movie.time','data.item.time','data.movie.time'],''),'quality'=>(string)pick($r,['quality','movie.quality','data.item.quality','data.movie.quality'],''),'lang'=>(string)pick($r,['lang','language','language_name','movie.lang','data.item.lang','data.movie.lang'],''),'categories'=>normlist(pick($r,['category','categories','genres','movie.category','movie.categories','data.item.category','data.item.categories','data.movie.category','data.movie.categories'],[])),'countries'=>normlist(pick($r,['country','countries','nation','movie.country','movie.countries','data.item.country','data.item.countries','data.movie.country','data.movie.countries'],[])),'description'=>(string)pick($r,['content','description','desc','overview','movie.content','movie.description','data.item.content','data.item.description','data.movie.content','data.movie.description'],''),'director'=>normlist(pick($r,['director','directors','movie.director','data.item.director','data.movie.director'],[])),'actors'=>normlist(pick($r,['actor','actors','cast','movie.actor','movie.actors','data.item.actor','data.item.actors','data.movie.actor','data.movie.actors'],[])),'trailer_url'=>(string)pick($r,['trailer_url','trailer','youtube_trailer','movie.trailer_url','data.item.trailer_url','data.movie.trailer_url'],''),'source_json'=>json_encode($r,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES)];}
function save_movie(array $m,int $id=0,string $sourceType='api'):int{
  $pdo=db();$m['source_type']=$sourceType;
  if(!$id&&!empty($m['slug'])){$q=$pdo->prepare('SELECT id FROM movies WHERE slug=? LIMIT 1');$q->execute([$m['slug']]);$e=$q->fetchColumn();if($e!==false)$id=(int)$e;}
  $m['source_json']=sanitize_source_json($m);
  $fields='name=:name,slug=:slug,origin_name=:origin_name,thumb_url=:thumb_url,poster_url=:poster_url,type=:type,status=:status,year=:year,time=:time,quality=:quality,lang=:lang,categories=:categories,countries=:countries,description=:description,director=:director,actors=:actors,trailer_url=:trailer_url,source_json=:source_json,source_type=:source_type,updated_at=NOW()';
  if($id){$old=$pdo->prepare('SELECT thumb_url,poster_url FROM movies WHERE id=?');$old->execute([$id]);$oldRow=$old->fetch()?:[];if(local_media_file_valid((string)($oldRow['thumb_url']??'')))$m['thumb_url']=(string)$oldRow['thumb_url'];if(local_media_file_valid((string)($oldRow['poster_url']??'')))$m['poster_url']=(string)$oldRow['poster_url'];$m['id']=$id;$pdo->prepare('UPDATE movies SET '.$fields.' WHERE id=:id')->execute($m);return$id;}
  try{$pdo->prepare('INSERT INTO movies(name,slug,origin_name,thumb_url,poster_url,type,status,year,time,quality,lang,categories,countries,description,director,actors,trailer_url,source_json,source_type,updated_at) VALUES(:name,:slug,:origin_name,:thumb_url,:poster_url,:type,:status,:year,:time,:quality,:lang,:categories,:countries,:description,:director,:actors,:trailer_url,:source_json,:source_type,NOW())')->execute($m);return(int)$pdo->lastInsertId();}
  catch(PDOException $e){if((int)($e->errorInfo[1]??0)!==1062)throw$e;$q=$pdo->prepare('SELECT id FROM movies WHERE slug=? LIMIT 1');$q->execute([$m['slug']]);$ex=$q->fetchColumn();if($ex===false)throw$e;$m['id']=(int)$ex;$pdo->prepare('UPDATE movies SET '.$fields.' WHERE id=:id')->execute($m);return(int)$ex;}
}


function hydrate_movie_by_slug(string $slug):?array{
  $slug=trim($slug);if($slug==='')return null;
  $local=db()->prepare('SELECT * FROM movies WHERE slug=? AND is_hidden=0 LIMIT 1');$local->execute([$slug]);$m=$local->fetch();if($m)return$m;
  global $config;
  $sources=[['base'=>$config['api']['base_url'],'detail'=>$config['api']['detail'],'type'=>'api_phimapi'],['base'=>$config['vsmov']['base_url'],'detail'=>$config['vsmov']['detail'],'type'=>'api_vsmov']];
  foreach($sources as $src){
    $j=api_get(api_url_base($src['base'],$src['detail'],['slug'=>$slug]),8);if(!$j)continue;
    $d=api_detail_payload($j);$rawMovie=is_array($d)&&normmovie($d)['name']!=='Untitled'?$d:$j;$m=normmovie($rawMovie);if($m['slug']==='')$m['slug']=$slug;
    $id=save_movie($m,0,$src['type']);$m['id']=$id;$path=(string)pick($j,['pathImage','data.pathImage','data.item.pathImage'],'');save_movie_images($id,$m,$path,$src['base']);
    if(is_array($d)&&api_find_episode_arrays($d)){@api_save_episodes($id,$d,$src['type']);}
    $local=db()->prepare('SELECT * FROM movies WHERE id=? LIMIT 1');$local->execute([$id]);return$local->fetch()?:null;
  }return null;
}
function theme_valid_slug(string $slug):bool{return(bool)preg_match('/^[a-z0-9][a-z0-9_-]{1,50}$/i',$slug);}
function active_theme():string{ $t=setv('active_theme','movie-premium'); return theme_valid_slug($t)?$t:'movie-premium'; }
function theme_info(string $slug):?array{
  if(!theme_valid_slug($slug)) return null;
  $f=__DIR__.'/themes/'.$slug.'/theme.json'; if(!is_file($f))return null;
  $j=json_decode((string)file_get_contents($f),true); if(!is_array($j)) return null;
  $j['slug']=$j['slug']??$slug; $j['name']=$j['name']??$slug; $j['version']=$j['version']??'1.0.0';
  $j['entry']=$j['entry']??'style.css'; $j['features']=is_array($j['features']??null)?$j['features']:[];
  return $j;
}
function theme_asset(string $file):string{
  $safe=ltrim(str_replace('..','',$file),'/');
  if(!empty($GLOBALS['FILMNOVA_THEME_PREVIEW_TOKEN'])){
    return site('admin/theme_asset.php?token='.rawurlencode((string)$GLOBALS['FILMNOVA_THEME_PREVIEW_TOKEN']).'&file='.rawurlencode($safe));
  }
  $slug=active_theme();
  $path=__DIR__.'/themes/'.$slug.'/'.$safe;
  $v=is_file($path)?filemtime($path):1;
  return site('themes/'.rawurlencode($slug).'/'.str_replace('\\','/', $safe)).'?v='.$v;
}
function theme_view_path(string $view):?string{
  $slug=active_theme(); if(!theme_valid_slug($slug))return null;
  $view=ltrim(str_replace('..','',$view),'/');
  $path=__DIR__.'/themes/'.$slug.'/views/'.$view;
  return is_file($path)?$path:null;
}
function render_theme_view(string $view,array $data=[],?string $fallback=null):bool{
  $path=theme_view_path($view);
  if($path===null)return false;
  extract($data,EXTR_SKIP);
  require $path;
  return true;
}
function theme_has_view(string $view):bool{return theme_view_path($view)!==null;}
function theme_contract_version():string{ $i=theme_info(active_theme()); return (string)($i['contract']??'filmnova-theme-v2'); }
function theme_entry_js():?string{ $i=theme_info(active_theme()); $j=ltrim((string)($i['script']??'script.js'),'/'); if($j==='')return null; $p=__DIR__.'/themes/'.active_theme().'/'.$j; return is_file($p)?theme_asset($j):null; }
function theme_render_public(string $view,array $data=[]):bool{
  $p=theme_view_path($view); if(!$p)return false;
  extract($data,EXTR_SKIP);
  $header=theme_view_path('header.php'); $footer=theme_view_path('footer.php');
  if($header)require $header;
  if($view==='home.php') render_app_gate();
  require $p; if($footer)require $footer; return true;
}
function theme_features():array{static $f=null;if($f!==null)return$f;$i=theme_info(active_theme());$f=is_array($i['features']??null)?$i['features']:[];return$f;}
function theme_has_feature(string $name):bool{return in_array($name,theme_features(),true);}
function theme_dirs():array{
  $out=[];$base=__DIR__.'/themes'; if(!is_dir($base))return$out;
  foreach(scandir($base)?:[] as $d){if($d==='.'||$d==='..')continue;$info=theme_info($d);if($info)$out[]=$info;}
  usort($out,fn($a,$b)=>strcmp((string)($a['name']??''),(string)($b['name']??'')));
  return$out;
}
function logsync(string $type,string $msg,int $items=0):void{db()->prepare('INSERT INTO sync_logs(sync_type,message,items) VALUES(?,?,?)')->execute([$type,$msg,$items]);}
function movie_by_id(int $id):?array{$s=db()->prepare('SELECT * FROM movies WHERE id=?');$s->execute([$id]);return$s->fetch()?:null;}
function redirect(string $path):void{header('Location: '.site($path));exit;}
function player_secret():string{
  global $config;
  $secret=(string)($config['site']['app_secret']??'');
  return $secret!==''?$secret:hash('sha256',__FILE__.'|filmnova-player');
}
function player_signature(int $movieId,int $episodeId,int $ttl=1800):string{
  $exp=time()+max(60,$ttl);
  return $exp.'.'.hash_hmac('sha256',$movieId.'|'.$episodeId.'|'.$exp,player_secret());
}
function player_allowed_domains():array{
  $raw=setv('player_allowed_domains','');
  $items=preg_split('/[\r\n,;\s]+/',(string)$raw,-1,PREG_SPLIT_NO_EMPTY)?:[];
  $out=[];
  foreach($items as $host){
    $host=strtolower(trim($host));
    $host=preg_replace('~^https?://~i','',$host)??$host;
    $host=preg_replace('~/.*$~','',$host)??$host;
    $host=explode(':',$host,2)[0];
    $host=trim($host,'.');
    if($host!=='' && preg_match('/^[a-z0-9.-]+$/',$host))$out[$host]=true;
  }
  if(!$out){
    $h=strtolower((string)($_SERVER['HTTP_HOST']??''));
    $h=preg_replace('/:\d+$/','',$h)??$h;
    if($h!=='')$out[$h]=true;
  }
  return array_keys($out);
}
function player_host_matches(string $host,array $allowed):bool{
  $host=strtolower($host);$host=explode(':',$host,2)[0];
  foreach($allowed as $d){
    $d=strtolower($d);
    if($host===$d) return true;
    if(str_ends_with($host,'.'.$d)) return true;
  }
  return false;
}
function player_request_is_authorized():bool{
  $allowed=player_allowed_domains();
  $referer=(string)($_SERVER['HTTP_REFERER']??'');
  if($referer!==''){
    $rh=(string)(parse_url($referer,PHP_URL_HOST)??'');
    if($rh!=='' && player_host_matches($rh,$allowed)) return true;
    return false;
  }
  $origin=(string)($_SERVER['HTTP_ORIGIN']??'');
  if($origin!==''){
    $oh=(string)(parse_url($origin,PHP_URL_HOST)??'');
    if($oh!=='' && player_host_matches($oh,$allowed)) return true;
    return false;
  }
  $sec=(string)($_SERVER['HTTP_SEC_FETCH_SITE']??'');
  return in_array(strtolower($sec),['same-origin','same-site'],true);
}

function player_signature_valid(int $movieId,int $episodeId,string $sig):bool{
  $parts=explode('.',$sig,2);
  if(count($parts)!==2)return false;
  $exp=(int)$parts[0]; $mac=$parts[1];
  if($exp<time()||$exp>(time()+86400))return false;
  $expected=hash_hmac('sha256',$movieId.'|'.$episodeId.'|'.$exp,player_secret());
  return hash_equals($expected,$mac);
}
function player_frame_url(int $movieId,int $episodeId):string{
  return site('api/player.php?movie='.rawurlencode((string)$movieId).'&episode='.rawurlencode((string)$episodeId).'&sig='.rawurlencode(player_signature($movieId,$episodeId)));
}

function ensure_comment_columns():void{
    static $done=false;if($done)return;$done=true;
    try{ $pdo=db(); $cols=$pdo->query("SHOW COLUMNS FROM comments")->fetchAll(PDO::FETCH_COLUMN);
      if(!in_array('parent_id',$cols,true)) $pdo->exec("ALTER TABLE comments ADD COLUMN parent_id BIGINT UNSIGNED NULL");
      if(!in_array('deleted_at',$cols,true)) $pdo->exec("ALTER TABLE comments ADD COLUMN deleted_at DATETIME NULL");
    }catch(Throwable $e){}
}
function is_comment_delete_window_open(string $createdAt):bool{return strtotime($createdAt)!==false && (time()-strtotime($createdAt))<=1800;}
// ----- Comment moderation -----
function comment_account():?array{
    $token=$_SESSION['comment_token']??($_COOKIE['film_comment_token']??'');
    if(!$token)return null;
    $rows=db()->query("SELECT * FROM comment_accounts WHERE auth_token_hash IS NOT NULL ORDER BY id DESC")->fetchAll();
    foreach($rows as $r){ if(password_verify($token,$r['auth_token_hash'])) return $r; }
    return null;
}
function comment_ban(string $email):?array{
    $s=db()->prepare("SELECT * FROM comment_bans WHERE LOWER(email)=LOWER(?) AND (permanent=1 OR ends_at IS NULL OR ends_at>NOW()) ORDER BY permanent DESC, ends_at DESC, id DESC LIMIT 1");$s->execute([$email]);return$s->fetch()?:null;
}
function moderation_terms():array{ return db()->query("SELECT term,weight FROM moderation_terms WHERE active=1 ORDER BY CHAR_LENGTH(term) DESC")->fetchAll(); }
function moderate_text(string $text):array{
    $clean=$text;$score=0;$hits=[];
    foreach(moderation_terms() as $t){$term=(string)$t['term'];if($term==='')continue;$pattern='/'.preg_quote($term,'/').'/iu';if(preg_match($pattern,$clean)){$score+=(int)$t['weight'];$hits[]=$term;$clean=preg_replace($pattern,'***',$clean)??$clean;}}
    // Basic hostility detector for direct personal attacks. When a term from this detector
    // is seen repeatedly, it can be promoted to moderation_terms by learn_moderation_term().
    $heuristics=['ngu'=>2,'đần'=>2,'vô dụng'=>2,'óc chó'=>3,'cút'=>2,'khốn'=>2];
    foreach($heuristics as $term=>$weight){if(in_array($term,$hits,true))continue;$pattern='/'.preg_quote($term,'/').'/iu';if(preg_match($pattern,$clean)){$score+=$weight;$hits[]=$term;$clean=preg_replace($pattern,'***',$clean)??$clean;}}
    $score=min(100,$score);return ['text'=>$clean,'score'=>$score,'hits'=>array_values(array_unique($hits))];
}
function learn_moderation_term(string $term,int $weight=2):void{
    $term=trim(mb_strtolower($term));if($term===''||mb_strlen($term)>120)return;
    $s=db()->prepare('INSERT INTO moderation_terms(term,category,weight,source,active) VALUES(?,?,?,?,1) ON DUPLICATE KEY UPDATE weight=GREATEST(weight,VALUES(weight)),active=1');$s->execute([$term,'learned',$weight,'learned']);
}

function comment_violation_count(string $email,int $hours=24):int{$s=db()->prepare("SELECT COUNT(*) FROM moderation_logs WHERE LOWER(email)=LOWER(?) AND action IN ('flagged','auto_mute','banned') AND created_at>=DATE_SUB(NOW(),INTERVAL ? HOUR)");$s->execute([$email,$hours]);return(int)$s->fetchColumn();}
function maybe_auto_mute(string $email,int $count):?array{
    $threshold=max(1,(int)setv('comment_auto_mute_threshold','3'));$hours=max(1,(int)setv('comment_auto_mute_hours','24'));if($count<$threshold)return null;
    $ban=comment_ban($email);if($ban)return$ban;
    db()->prepare("INSERT INTO comment_bans(email,reason,starts_at,ends_at,permanent,created_by) VALUES(?,?,?,?,?,?)")->execute([$email,'Bot moderation: quá nhiều bình luận vi phạm',date('Y-m-d H:i:s'),date('Y-m-d H:i:s',time()+$hours*3600),0,'AI Bot']);
    return comment_ban($email);
}
function issue_comment_token(int $accountId):string{$token=bin2hex(random_bytes(24));$hash=password_hash($token,PASSWORD_DEFAULT);db()->prepare('UPDATE comment_accounts SET auth_token_hash=?,last_login_at=NOW() WHERE id=?')->execute([$hash,$accountId]);$_SESSION['comment_token']=$token;setcookie('film_comment_token',$token,['expires'=>time()+31536000,'path'=>'/','httponly'=>true,'samesite'=>'Lax']);return$token;}
?>
