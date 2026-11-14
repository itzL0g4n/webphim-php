<?php
require_once dirname(__DIR__).'/bootstrap.php';
require_admin();
$token=preg_replace('/[^a-z0-9_-]/i','',$_GET['token']??'');
$file=str_replace('\\','/',trim((string)($_GET['file']??''),'/'));
if($token===''||$file===''||str_contains($file,'..')||str_starts_with($file,'/')||preg_match('/(^|\/)\.\.?\//',$file)){http_response_code(404);exit;}
$path=__DIR__.'/../themes/.staging/'.$token.'/'.$file;
if(!is_file($path)){ $slug=preg_replace('/[^a-z0-9_-]/i','',$token); $path=__DIR__.'/../themes/'.$slug.'/'.$file; }
if(!is_file($path)||strtolower(pathinfo($path,PATHINFO_EXTENSION))==='php'){http_response_code(404);exit;}
$mime=function($ext){return match(strtolower($ext)){ 'css'=>'text/css; charset=UTF-8','js'=>'application/javascript; charset=UTF-8','json'=>'application/json; charset=UTF-8','svg'=>'image/svg+xml','png'=>'image/png','jpg','jpeg'=>'image/jpeg','webp'=>'image/webp','gif'=>'image/gif','ico'=>'image/x-icon','woff'=>'font/woff','woff2'=>'font/woff2','ttf'=>'font/ttf','otf'=>'font/otf','html','htm'=>'text/html; charset=UTF-8',default=>'application/octet-stream'};};
header('Content-Type: '.$mime(pathinfo($path,PATHINFO_EXTENSION)));header('Cache-Control: private, max-age=300');readfile($path);
