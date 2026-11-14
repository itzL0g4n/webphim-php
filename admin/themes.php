<?php
require __DIR__.'/_header.php';
$adminTitle='Giao diện'; $msg=''; $err=''; $previewSlug='';

function theme_upload_base():string{ $p=__DIR__.'/../themes/.staging'; if(!is_dir($p))mkdir($p,0755,true); return $p; }
function theme_is_valid_asset(string $rel):bool{
  $rel=str_replace('\\','/',$rel); if($rel===''||str_contains($rel,'..')||str_starts_with($rel,'/'))return false;
  $ext=strtolower(pathinfo($rel,PATHINFO_EXTENSION));
  return in_array($ext,['css','js','json','html','htm','svg','png','jpg','jpeg','webp','gif','ico','woff','woff2','ttf','otf'],true);
}
function theme_copy_tree_safe(string $src,string $dst):void{
  if(!is_dir($dst))mkdir($dst,0755,true);
  $it=new RecursiveIteratorIterator(new RecursiveDirectoryIterator($src,FilesystemIterator::SKIP_DOTS),RecursiveIteratorIterator::SELF_FIRST);
  foreach($it as $f){
    $rel=str_replace('\\','/',substr($f->getPathname(),strlen($src)+1));
    $dest=$dst.'/'.$rel;
    if($f->isDir()){ if(str_contains($rel,'..')||str_starts_with($rel,'/'))continue; if(!is_dir($dest))mkdir($dest,0755,true); continue; }
    if(!theme_is_valid_asset($rel))continue;
    $parent=dirname($dest);if(!is_dir($parent))mkdir($parent,0755,true);
    copy($f->getPathname(),$dest);
  }
}
function theme_scope_css(string $css,string $slug):string{
  $css=preg_replace('~/\*.*?\*/~s','',$css)??$css;
  $css=preg_replace('/@import\s+[^;]+;/i','',$css)??$css;
  $css=preg_replace('/url\(\s*["\']?\s*(?:javascript:|data:text\/html)[^)]*\)/i','none',$css)??$css;
  // Scope the complete theme to its public FilmNova body. This lets a theme override
  // presentation without leaking into the admin pages or other themes.
  $css=preg_replace('/(^|[,{]\s*)body\b/i','$1:scope',$css)??$css;
  $css=preg_replace('/(^|[,{]\s*)html\b/i','$1:scope',$css)??$css;
  $css=preg_replace('/(^|[,{]\s*):root\b/i','$1:scope',$css)??$css;
  return "/* FilmNova Universal Theme - scoped */\n@scope (body.theme-".$slug.") {\n".$css."\n}\n";
}
function theme_find_manifest(string $tmp):?string{
  foreach(new RecursiveIteratorIterator(new RecursiveDirectoryIterator($tmp,FilesystemIterator::SKIP_DOTS)) as $f){if(strtolower($f->getFilename())==='theme.json')return $f->getPathname();}
  return null;
}
function theme_find_entry(string $base,string $entry):?string{
  $entry=ltrim(str_replace('..','',$entry),'/'); $p=$base.'/'.$entry;
  if(is_file($p)&&strtolower(pathinfo($p,PATHINFO_EXTENSION))==='css')return $p;
  foreach(['style.css','theme.css','assets/style.css','css/style.css'] as $x){if(is_file($base.'/'.$x))return $base.'/'.$x;}
  return null;
}
function theme_validate_package(string $src,string $originalName):array{
  $ext=strtolower(pathinfo($originalName,PATHINFO_EXTENSION));
  if(!in_array($ext,['zip','fntheme'],true))return ['ok'=>false,'error'=>'Chỉ nhận Theme ZIP hoặc .fntheme.'];
  $tmp=theme_upload_base().'/check-'.bin2hex(random_bytes(5));mkdir($tmp,0755,true);
  $z=new ZipArchive(); if($z->open($src)!==true)return ['ok'=>false,'error'=>'Không mở được gói theme.'];
  for($i=0;$i<$z->numFiles;$i++){ $n=$z->getNameIndex($i); if($n===false||str_contains(str_replace('\\','/',$n),'../')||str_starts_with($n,'/')){$z->close();return ['ok'=>false,'error'=>'Gói theme chứa đường dẫn không an toàn.'];} }
  if(!$z->extractTo($tmp)){ $z->close();return ['ok'=>false,'error'=>'Không giải nén được gói theme.']; } $z->close();
  $manifestPath=theme_find_manifest($tmp); $base=$manifestPath?dirname($manifestPath):$tmp;
  $manifest=$manifestPath?json_decode((string)file_get_contents($manifestPath),true):null;
  if(!is_array($manifest)){
    $css=theme_find_entry($base,'style.css');
    if(!$css)return ['ok'=>false,'error'=>'Không tìm thấy theme.json hoặc CSS chính (style.css/theme.css).'];
    $slug='theme-'.substr(sha1($originalName.microtime(true)),0,10);
    $manifest=['name'=>pathinfo($originalName,PATHINFO_FILENAME),'slug'=>$slug,'version'=>'1.0.0','description'=>'Theme FilmNova tự nhận diện từ CSS.','author'=>'Imported','entry'=>'style.css','engine'=>'filmnova-universal-v1','features'=>['header','nav','search','hero','catalog','sidebar','ads','player','comments','footer']];
    file_put_contents($base.'/theme.json',json_encode($manifest,JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT));
  }
  $slug=(string)($manifest['slug']??'');
  if(!preg_match('/^[a-z0-9][a-z0-9_-]{1,50}$/i',$slug))return ['ok'=>false,'error'=>'theme.json có slug không hợp lệ.'];
  $entry=(string)($manifest['entry']??'style.css'); $css=theme_find_entry($base,$entry);
  if(!$css)return ['ok'=>false,'error'=>'Theme thiếu file CSS chính. Hãy có style.css hoặc theme.json → entry.'];
  foreach(new RecursiveIteratorIterator(new RecursiveDirectoryIterator($base,FilesystemIterator::SKIP_DOTS)) as $f){
    $rel=str_replace('\\','/',substr($f->getPathname(),strlen($base)+1)); if(!$f->isDir()&&!theme_is_valid_asset($rel))return ['ok'=>false,'error'=>'File không được phép: '.$rel];
  }
  $themeName=(string)($manifest['name']??$slug);
  $manifest['slug']=$slug;$manifest['name']=$themeName;$manifest['entry']='style.css';$manifest['engine']='filmnova-universal-v1';
  $manifest['features']=array_values(array_unique(array_merge(['header','nav','search','hero','catalog','sidebar','ads','player','comments','footer'],is_array($manifest['features']??null)?$manifest['features']:[])));
  file_put_contents($base.'/theme.json',json_encode($manifest,JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT));
  file_put_contents($base.'/style.css',theme_scope_css((string)file_get_contents($css),$slug));
  return ['ok'=>true,'tmp'=>$tmp,'base'=>$base,'manifest'=>$manifest,'css'=>$base.'/style.css'];
}
function theme_cleanup(string $dir):void{if(!is_dir($dir))return;$it=new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir,FilesystemIterator::SKIP_DOTS),RecursiveIteratorIterator::CHILD_FIRST);foreach($it as $f){$f->isDir()?@rmdir($f->getPathname()):@unlink($f->getPathname());}@rmdir($dir);}

if($_SERVER['REQUEST_METHOD']==='POST'){
  csrf_check(); $action=$_POST['action']??'';
  if($action==='activate'){
    $slug=trim($_POST['slug']??''); if(theme_info($slug)){setv_save('active_theme',$slug);$msg='Đã kích hoạt theme: '.$slug;} else $err='Theme không hợp lệ.';
  }
  if($action==='install_staged'){
    $token=preg_replace('/[^a-z0-9_-]/i','',$_POST['token']??'');$stage=theme_upload_base().'/'.$token;
    $manifestPath=$stage.'/theme.json';
    if(!is_dir($stage)||!is_file($manifestPath))$err='Bản theme xem trước không còn tồn tại. Hãy upload lại.';
    else{
      $manifest=json_decode((string)file_get_contents($manifestPath),true)?:[];$slug=(string)($manifest['slug']??'');
      if(!preg_match('/^[a-z0-9][a-z0-9_-]{1,50}$/i',$slug))$err='Theme slug không hợp lệ.';
      else{
        $target=__DIR__.'/../themes/'.$slug;if(is_dir($target)){rename($target,__DIR__.'/../themes/.backup-'.$slug.'-'.date('YmdHis'));}
        if(!rename($stage,$target)){theme_copy_tree_safe($stage,$target);theme_cleanup($stage);}
        setv_save('active_theme',$slug);$msg='Đã cài và kích hoạt theme '.$manifest['name'].'; toàn bộ Core FilmNova được giữ nguyên.';$previewSlug=$slug;
      }
    }
  }
  if($action==='upload'){
    if(empty($_FILES['theme']['tmp_name'])||!is_uploaded_file($_FILES['theme']['tmp_name']))$err='Chưa chọn file theme.';
    else{
      $name=(string)$_FILES['theme']['name'];$ext=strtolower(pathinfo($name,PATHINFO_EXTENSION));
      if(!in_array($ext,['zip','fntheme'],true))$err='Sai định dạng. Dùng .zip hoặc .fntheme.';
      else{
        $res=theme_validate_package($_FILES['theme']['tmp_name'],$name);
        if(!$res['ok']){$err=$res['error'];}
        else{
          $token='stage-'.bin2hex(random_bytes(6));$stage=theme_upload_base().'/'.$token;rename($res['base'],$stage);theme_cleanup($res['tmp']);
          // Reconfirm expected files after staging.
          if(!is_file($stage.'/theme.json')||!is_file($stage.'/style.css')){theme_cleanup($stage);$err='Theme chưa đạt chuẩn sau khi kiểm tra.';}
          else{$previewSlug=(string)$res['manifest']['slug'];$msg='✓ Theme hợp lệ: '.$res['manifest']['name'].' · v'.$res['manifest']['version'].'. Đã sẵn sàng để xem thử và cài đặt.';
            setv_save('theme_stage_'.$token,json_encode(['slug'=>$res['manifest']['slug'],'name'=>$res['manifest']['name'],'created'=>time()],JSON_UNESCAPED_UNICODE));
          }
        }
      }
    }
  }
}
$themes=theme_dirs();$active=active_theme();
$stages=[];$stageRoot=theme_upload_base();foreach(scandir($stageRoot)?:[] as $d){if(str_starts_with($d,'stage-')&&is_file($stageRoot.'/'.$d.'/theme.json')){$mi=json_decode((string)file_get_contents($stageRoot.'/'.$d.'/theme.json'),true);if(is_array($mi))$stages[]=['token'=>$d,'name'=>$mi['name']??$d,'version'=>$mi['version']??'1.0.0','slug'=>$mi['slug']??''];}}
?>
<div class="admin-head"><div><small>THEME CENTER · UNIVERSAL ENGINE</small><h1>Giao diện</h1><p>Upload theme → kiểm tra cấu trúc → xem thử → cài đặt. Theme chỉ thay phần trình bày; API, dữ liệu phim, player, bình luận, rating, lượt xem và Admin luôn thuộc Core FilmNova.</p></div></div>
<?php if($msg):?><div class="sync-flash"><?=e($msg)?></div><?php endif;?>
<?php if($err):?><div class="error-box">✕ <?=e($err)?></div><?php endif;?>
<section class="admin-card"><h2>Upload theme</h2><form class="admin-form" method="post" enctype="multipart/form-data"><input type="hidden" name="csrf" value="<?=e(csrf())?>"><input type="hidden" name="action" value="upload"><label>Gói giao diện (.ZIP hoặc .FNTHEME)<input type="file" name="theme" accept=".zip,.fntheme" required></label><p style="font-size:11px;color:#9ca8b8">Theme chuẩn có thể chứa theme.json, CSS, JS, ảnh, font, HTML tĩnh và thư mục views/. Không cho phép PHP trong theme. Không hợp lệ thì hệ thống từ chối trước khi cài.</p><button class="btn primary">⬆ Kiểm tra & tải lên</button></form></section>
<?php if($stages):?><section class="admin-card"><h2>Theme đã kiểm tra — chờ cài</h2><?php foreach($stages as $st):?><div class="theme-card"><div class="theme-body"><h3><?=e($st['name'])?></h3><p>v<?=e($st['version'])?> · Đã qua kiểm tra cấu trúc</p><div class="theme-actions"><a class="btn" target="_blank" href="<?=e(site('admin/theme_preview.php?token='.rawurlencode($st['token'])))?>">👁 Xem thử</a><form method="post" style="display:inline"><input type="hidden" name="csrf" value="<?=e(csrf())?>"><input type="hidden" name="action" value="install_staged"><input type="hidden" name="token" value="<?=e($st['token'])?>"><button class="primary">Cài đặt & kích hoạt</button></form></div></div></div><?php endforeach;?></section><?php endif;?>
<section class="admin-card"><h2>Theme đã cài</h2><div class="theme-list"><?php foreach($themes as $t):?><div class="theme-card"><div class="theme-body"><h3><?=e($t['name']??$t['slug'])?></h3><p>v<?=e($t['version']??'1.0.0')?> · <?=e($t['description']??'')?></p><div class="theme-actions"><a class="btn" target="_blank" href="<?=e(site('admin/theme_preview.php?slug='.rawurlencode($t['slug'])))?>">👁 Xem thử</a><form method="post" style="display:inline"><input type="hidden" name="csrf" value="<?=e(csrf())?>"><input type="hidden" name="action" value="activate"><input type="hidden" name="slug" value="<?=e($t['slug'])?>"><button class="<?=$active===$t['slug']?'active':''?>"><?=$active===$t['slug']?'✓ Đang dùng':'Cài đặt / kích hoạt'?></button></form></div></div></div><?php endforeach;?></div></section>
<?php require __DIR__.'/_footer.php'; ?>
