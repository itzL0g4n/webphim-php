<?php
declare(strict_types=1);
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
$stateFile=__DIR__.'/sync_state.json';
if(!is_file($stateFile)){echo json_encode(['active'=>false,'percent'=>0],JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);exit;}
$j=json_decode((string)file_get_contents($stateFile),true);
if(!is_array($j)||($j['done']??false)){echo json_encode(['active'=>false,'percent'=>0],JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);exit;}
$p=$j['progress']??[];
echo json_encode(['active'=>true,'percent'=>max(0,min(100,(int)($p['percent']??0))),'label'=>(string)($p['label']??'Đang cập nhật dữ liệu...'),'mode'=>(string)($p['mode']??'manual')],JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
