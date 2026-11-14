<?php
declare(strict_types=1);

// AJAX sync must never emit PHP HTML errors before its JSON response.
$isAjaxRequest = PHP_SAPI !== 'cli' && isset($_GET['ajax']);
if ($isAjaxRequest) {
    @ini_set('display_errors','0');
    @ini_set('log_errors','1');
    set_error_handler(function($severity,$message,$file,$line){
        if (!(error_reporting() & $severity)) return false;
        error_log("FilmNova sync PHP error: {$message} in {$file}:{$line}");
        return true;
    });
    register_shutdown_function(function(){
        $e = error_get_last();
        if ($e && in_array($e['type'], [E_ERROR,E_PARSE,E_CORE_ERROR,E_COMPILE_ERROR], true)) {
            while (ob_get_level()) @ob_end_clean();
            http_response_code(500);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode([
                'ok'=>false,'busy'=>false,'done'=>false,'summary'=>'',
                'error'=>'PHP lỗi trong chunk đồng bộ: '.$e['message'].' (dòng '.$e['line'].')'
            ], JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
        }
    });
}
require dirname(__DIR__).'/bootstrap.php';

$workerMode = PHP_SAPI === 'cli' && (($argv[1] ?? '') === '--worker');
$token = PHP_SAPI === 'cli' ? ($workerMode ? '' : ($argv[1] ?? '')) : (string)($_GET['token'] ?? '');
$expected = (string)setv('cron_token','changeme');
if (PHP_SAPI !== 'cli' && !hash_equals($expected, $token)) { http_response_code(403); exit('Forbidden'); }
@set_time_limit(45); @ini_set('max_execution_time','45');

$source=(string)($_GET['source']??($argv[2]??'all'));
if(!in_array($source,['kkphim','vsmov','all'],true)) $source='all';

$stateFile=__DIR__.'/sync_state.json';
$lockFile=__DIR__.'/sync.lock';

function st_read(string $f):array{if(!is_file($f))return[];$j=json_decode((string)file_get_contents($f),true);return is_array($j)?$j:[];}
function st_write(string $f,array $s):void{$tmp=$f.'.tmp';file_put_contents($tmp,json_encode($s,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES|JSON_PRETTY_PRINT),LOCK_EX);@rename($tmp,$f);}
function state_base(string $source):array{return ['source_choice'=>$source,'started_at'=>date('Y-m-d H:i:s'),'source_i'=>0,'page'=>1,'item'=>0,'items'=>[],'sources'=>[],'progress'=>['percent'=>0,'label'=>'Đang cập nhật phim bộ & phim lẻ • tập mới','mode'=>'manual'], 'stats'=>['sources'=>0,'pages'=>0,'api_items'=>0,'unique_movies'=>0,'new_movies'=>0,'updated_movies'=>0,'new_episodes'=>0,'errors'=>0,'requests'=>0,'loaded'=>['kkphim'=>0,'vsmov'=>0],'discovered'=>['kkphim'=>null,'vsmov'=>null]],'done'=>false,'logs'=>[]];}
function source_defs(string $choice):array{global $config;$out=[];if(in_array($choice,['kkphim','all'],true))$out[]=['key'=>'kkphim','label'=>'KKPhim','base'=>$config['api']['base_url'],'endpoint'=>$config['api']['latest_v1'],'pages'=>5000,'detail'=>$config['api']['detail']];if(in_array($choice,['vsmov','all'],true))$out[]=['key'=>'vsmov','label'=>'VSMOV','base'=>$config['vsmov']['base_url'],'endpoint'=>$config['vsmov']['latest'],'pages'=>5000,'detail'=>$config['vsmov']['detail']];return$out;}
function source_item(array $raw):array{return normmovie($raw);}

$state=st_read($stateFile);
if(!$state || ($state['done']??false) || ($state['source_choice']??'')!==$source){
  // A new run resets the state only when no state exists, or the previous run is complete.
  $state=state_base($source);$state['sources']=source_defs($source);$state['stats']['sources']=count($state['sources']);st_write($stateFile,$state);
}

function sync_progress(array &$state):void{
  $sources=$state['sources']??[]; $total=max(1,count($sources)); $si=(int)($state['source_i']??0); $src=$sources[$si]??null;
  if(!$src){$state['progress']['percent']=100;return;}
  $pages=max(1,(int)($src['pages']??1)); $page=max(1,(int)($state['page']??1)); $items=$state['items']??[]; $item=max(0,(int)($state['item']??0)); $itemTotal=max(1,count(is_array($items)?$items:[]));
  $sourceBase=$si/$total; $sourceFrac=min(1, (($page-1)+($itemTotal>0?$item/$itemTotal:0))/$pages);
  $pct=(int)floor((($sourceBase+($sourceFrac/$total))*100));
  $state['progress']['percent']=max(0,min(99,$pct));
  $state['progress']['label']='Đang cập nhật phim bộ & phim lẻ • tập mới';
}

function process_chunk(string $stateFile,array $state):array{
  $lockFp=fopen(__DIR__.'/sync.lock','c+');
  if(!$lockFp || !flock($lockFp,LOCK_EX|LOCK_NB)) return ['ok'=>false,'busy'=>true,'state'=>$state];
  try{
    $sources=$state['sources'];$budget=4;$made=0;
    while($made<$budget&&!$state['done']){
      $si=(int)$state['source_i'];
      if($si>=count($sources)){$state['done']=true;$state['progress']['percent']=100;break;}
      $src=$sources[$si];$page=(int)$state['page'];
      if($page>(int)$src['pages']){$state['source_i']=$si+1;$state['page']=1;$state['item']=0;$state['items']=[];continue;}
      if(empty($state['items'])){
        $url=api_url_base($src['base'],$src['endpoint'],['page'=>$page]);
        $json=api_get($url,8);$made++;$state['stats']['requests']++;
        if(!$json){$state['stats']['errors']++;$state['logs'][]=$src['label'].' page '.$page.': API không phản hồi';$state['page']++;$state['stats']['pages']++;continue;}
        $state['items']=api_extract_items_generic($json);
        $meta=api_extract_meta_generic($json);
        if(is_int($meta['total'])) $state['stats']['discovered'][$src['key']]=max((int)($state['stats']['discovered'][$src['key']]??0),$meta['total']);$mp=(int)($meta['total_pages']??0); if($mp>0) $state['sources'][$si]['pages']=min(5000,max((int)$state['sources'][$si]['pages'],$mp));
        $state['item']=0;$state['stats']['pages']++;
        if(!$state['items']){$state['source_i']=$si+1;$state['page']=1;$state['item']=0;$state['items']=[];continue;}
      }
      if((int)$state['item']>=count($state['items'])){$state['stats']['loaded'][$src['key']]=(int)($state['stats']['loaded'][$src['key']]??0)+count($state['items']);$state['items']=[];$state['item']=0;$state['page']++;continue;}
      $raw=$state['items'][$state['item']];$state['item']++;
      $m=source_item($raw);$slug=trim((string)$m['slug']);if($slug==='')continue;
      $q=db()->prepare('SELECT id FROM movies WHERE slug=? LIMIT 1');$q->execute([$slug]);$existing=(int)($q->fetchColumn()?:0);
      $id=save_movie($m,$existing,'api_'.$src['key']);
      if($existing)$state['stats']['updated_movies']++;else$state['stats']['new_movies']++;
      $state['stats']['api_items']++;$state['stats']['unique_movies']++;
      if($made<$budget){
        $dj=api_get(api_url_base($src['base'],$src['detail'],['slug'=>$slug]),8);$made++;$state['stats']['requests']++;
        if($dj){
          $d=api_detail_payload($dj);
          if(is_array($d)){
            $detailMovie=normmovie($d);
            $detailMovie['slug']=$slug;
            if($detailMovie['name']!=='Untitled'){$id=save_movie($detailMovie,$id,'api_'.$src['key']);}
            $path=(string)pick($dj,['pathImage','data.pathImage','data.item.pathImage'],'');
            try{save_movie_images($id,$detailMovie,$path,$src['base']);}catch(Throwable $e){$state['stats']['errors']++;}
          }
          if(is_array($d)&&api_find_episode_arrays($d)){
            try{$state['stats']['new_episodes']+=api_save_episodes($id,$d,'api_'.$src['key']);}catch(Throwable$e){$state['stats']['errors']++;}
          }
        }
      }
    }
    sync_progress($state);
    st_write($stateFile,$state);
    return ['ok'=>true,'busy'=>false,'state'=>$state,'chunk'=>$made];
  }finally{flock($lockFp,LOCK_UN);fclose($lockFp);}
}

function summary_for(array $state):string{
  $st=$state['stats'];$source=$state['source_choice'];
  $summary='Hoàn tất '.$source.' | đã load '.$st['api_items'].' phim | mới '.$st['new_movies'].' | cập nhật '.$st['updated_movies'].' | tập '.$st['new_episodes'].' | lỗi '.$st['errors'].' | request '.$st['requests'];
  foreach(['kkphim','vsmov'] as $k){$d=$st['discovered'][$k];$summary.=' | '.$k.': tìm thấy '.($d===null?'không rõ':number_format((int)$d)).', load '.number_format((int)$st['loaded'][$k]);}
  return $summary;
}

// JSON chunk endpoint: no HTTP redirects, so browsers cannot get stuck in a redirect loop.
if(PHP_SAPI!=='cli' && isset($_GET['ajax'])){
  try {
    $result=process_chunk($stateFile,$state);
    if(($result['state']['done']??false)){
      $st=$result['state']['stats'];
      $summary=summary_for($result['state']);
      setv_save('last_sync_at',date('Y-m-d H:i:s'));
      logsync('summary',$summary,(int)$st['unique_movies']);
      @unlink($stateFile);
      $result['summary']=$summary;
      $result['done']=true;
    } else {
      $result['done']=false;
    }
    $payload=[
      'ok'=>(bool)($result['ok']??false),
      'busy'=>(bool)($result['busy']??false),
      'done'=>(bool)($result['done']??false),
      'summary'=>(string)($result['summary']??''),
      'stats'=>$result['state']['stats']??[],
      'error'=>(string)($result['error']??'')
    ];
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($payload,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES|JSON_INVALID_UTF8_SUBSTITUTE);
  } catch (Throwable $e) {
    error_log('FilmNova sync exception: '.$e->getMessage().' @ '.$e->getFile().':'.$e->getLine());
    http_response_code(500);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode([
      'ok'=>false,'busy'=>false,'done'=>false,'summary'=>'',
      'stats'=>$state['stats']??[],
      'error'=>'Lỗi đồng bộ: '.$e->getMessage()
    ],JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES|JSON_INVALID_UTF8_SUBSTITUTE);
  }
  exit;
}

// CLI mode. --worker runs all chunks until finished, intended for Windows Task Scheduler / cron.
if(PHP_SAPI==='cli'){
  if($workerMode){
    @set_time_limit(0); @ini_set('max_execution_time','0');
    $started=microtime(true); $guard=0;
    while($guard++<100000){
      $current=st_read($stateFile);
      if(!$current || ($current['source_choice']??'')!==$source || ($current['done']??false)){
        $current=state_base($source);$current['progress']['mode']='auto_00';$current['sources']=source_defs($source);$current['stats']['sources']=count($current['sources']);st_write($stateFile,$current);
      }
      $result=process_chunk($stateFile,$current);
      if(!empty($result['busy'])){sleep(2);continue;}
      if(($result['state']['done']??false)){
        $summary=summary_for($result['state']);
        setv_save('last_sync_at',date('Y-m-d H:i:s'));
        setv_save('last_sync_mode','auto_00');
        logsync('summary',$summary,(int)$result['state']['stats']['unique_movies']);
        @unlink($stateFile);
        file_put_contents(__DIR__.'/auto_sync.log',date('Y-m-d H:i:s').' | '.$summary.PHP_EOL,FILE_APPEND|LOCK_EX);
        echo "SYNC OK\n$summary\nElapsed: ".round(microtime(true)-$started,1)."s\n";
        exit(0);
      }
    }
    file_put_contents(__DIR__.'/auto_sync.log',date('Y-m-d H:i:s').' | STOP: vượt giới hạn vòng xử lý'.PHP_EOL,FILE_APPEND|LOCK_EX);
    fwrite(STDERR,"SYNC STOP\n"); exit(2);
  }
  $result=process_chunk($stateFile,$state);
  if(($result['state']['done']??false)){$summary=summary_for($result['state']);setv_save('last_sync_at',date('Y-m-d H:i:s'));logsync('summary',$summary,(int)$result['state']['stats']['unique_movies']);@unlink($stateFile);echo "SYNC OK\n$summary\n";}else echo "CHUNK\n";exit;
}

// Browser runner page. It uses fetch() to execute chunks, not Location redirects.
$sourceLabel=['kkphim'=>'KKPhim','vsmov'=>'VSMOV','all'=>'KKPhim + VSMOV'][$source]??$source;
$ajaxUrl=site('cron/sync.php').'?token='.rawurlencode($token).'&source='.rawurlencode($source).'&ajax=1';
?><!doctype html><html lang="vi"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Đang cập nhật API</title><style>body{margin:0;background:#0b1018;color:#eef3f8;font:15px Arial,sans-serif}.box{max-width:720px;margin:60px auto;padding:28px;background:#151d29;border:1px solid #273244;border-radius:18px}.bar{height:12px;background:#0c1119;border-radius:99px;overflow:hidden;margin:18px 0}.fill{height:100%;width:5%;background:#36d47a;transition:width .25s}.muted{color:#9aa8b8}.stats{display:grid;grid-template-columns:repeat(3,1fr);gap:10px;margin-top:18px}.stat{background:#101721;border-radius:12px;padding:13px}.stat b{display:block;font-size:20px}.done{padding:14px;border-radius:12px;background:#10271b;color:#7bf0a5;display:none}.err{padding:14px;border-radius:12px;background:#32171b;color:#ff9ca7;display:none}</style></head><body><div class="box"><div class="muted">FILMNOVA • API SYNC</div><h1>Đang cập nhật <?=$sourceLabel?></h1><p id="status">Đang tải dữ liệu...</p><div class="bar"><div id="fill" class="fill"></div></div><div class="stats"><div class="stat"><b id="loaded">0</b><span class="muted">Phim đã load</span></div><div class="stat"><b id="found">0</b><span class="muted">API công bố</span></div><div class="stat"><b id="req">0</b><span class="muted">Request</span></div></div><div id="done" class="done"></div><div id="err" class="err"></div></div><script>
const url=<?=json_encode($ajaxUrl,JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE)?>;
const home=<?=json_encode(site('admin/index.php?sync=1'),JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE)?>;
const statusEl=document.getElementById('status'),fill=document.getElementById('fill'),loaded=document.getElementById('loaded'),found=document.getElementById('found'),req=document.getElementById('req'),done=document.getElementById('done'),err=document.getElementById('err');
let rounds=0;
function tick(){rounds++;fetch(url+'&r='+Date.now(),{cache:'no-store'}).then(async r=>{const text=await r.text();let d;try{d=JSON.parse(text);}catch(e){throw new Error('Server không trả JSON. Phản hồi: '+text.slice(0,220));}if(!r.ok||d.ok===false&&!d.busy){throw new Error(d.error||'Không thể chạy một đợt đồng bộ');}return d;}).then(d=>{if(d.busy){statusEl.textContent='Đang có phiên cập nhật khác...';setTimeout(tick,1500);return;}if(!d.ok){throw new Error(d.error||'Không thể chạy một đợt đồng bộ');}const s=d.stats||{};loaded.textContent=Number(s.api_items||0).toLocaleString('vi-VN');req.textContent=Number(s.requests||0).toLocaleString('vi-VN');const ds=s.discovered||{};let f=0;Object.keys(ds).forEach(k=>{if(ds[k])f+=Number(ds[k]||0)});found.textContent=f.toLocaleString('vi-VN');const pct=Math.min(95,5+rounds*2);fill.style.width=pct+'%';if(d.done){fill.style.width='100%';statusEl.textContent='Đã hoàn tất.';done.style.display='block';done.textContent=d.summary||'Đồng bộ hoàn tất.';setTimeout(()=>location.href=home,1800);}else{statusEl.textContent='Đang xử lý từng đợt nhỏ để tránh quá thời gian PHP...';setTimeout(tick,250);}}).catch(e=>{err.style.display='block';err.textContent=e.message||'Có lỗi khi cập nhật API.';statusEl.textContent='Đã dừng.';});}
tick();
</script></body></html>
