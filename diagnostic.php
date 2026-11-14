<?php
require __DIR__.'/bootstrap.php';
header('Content-Type: text/plain; charset=utf-8');
echo "FilmNova diagnostic\n===================\n";
echo "PHP: ".PHP_VERSION."\n";
echo "PDO MySQL: ".(extension_loaded('pdo_mysql')?'OK':'MISSING')."\n";
echo "cURL: ".(extension_loaded('curl')?'OK':'MISSING')."\n";
try {
  $pdo=db(); echo "MySQL: OK\n";
  foreach(['users','settings','movies','episodes','banners','sync_logs'] as $t){
    $ok=(bool)$pdo->query("SHOW TABLES LIKE ". $pdo->quote($t))->fetchColumn(); echo "Table {$t}: ".($ok?'OK':'MISSING')."\n";
  }
  foreach(['view_count','episodes_count','is_featured','is_hidden','source_type'] as $c) echo "movies.{$c}: ".(column_exists($pdo,'movies',$c)?'OK':'MISSING')."\n";
} catch(Throwable $e) { echo "MySQL: FAIL\n".$e->getMessage()."\n"; }
?>
