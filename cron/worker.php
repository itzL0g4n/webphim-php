<?php
declare(strict_types=1);
/*
 * CLI worker for scheduled sync. It repeatedly calls sync.php in small chunks,
 * so one 00:xx cron run can finish without PHP/Apache request timeouts.
 */
require dirname(__DIR__).'/bootstrap.php';
$token=$argv[1]??setv('cron_token','changeme');
$batch=max(1,min(5,(int)($argv[2]??3)));
$state='';
$script=__DIR__.'/sync.php';
for($guard=0;$guard<2000;$guard++){
  $cmd=PHP_BINARY.' '.escapeshellarg($script).' '.escapeshellarg((string)$token).' '.escapeshellarg($state).' '.escapeshellarg((string)$batch);
  $out=[];$code=0;
  exec($cmd,$out,$code);
  $text=implode("\n",$out);
  if(preg_match('/state=([a-f0-9]{16})/i',$text,$m)){$state=$m[1];continue;}
  echo $text."\n";
  if($code!==0) exit($code);
  break;
}
?>
