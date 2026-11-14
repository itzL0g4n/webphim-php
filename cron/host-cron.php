<?php
/**
 * FilmNova host cron wrapper.
 * Recommended command on Linux/cPanel:
 *   /usr/bin/php /home/USERNAME/public_html/cron/host-cron.php
 * It delegates to the chunked worker and keeps the same all-source behavior.
 */
declare(strict_types=1);
chdir(dirname(__DIR__));
$php = PHP_BINARY ?: 'php';
$script = __DIR__ . '/sync.php';
$log = __DIR__ . '/auto_sync.log';
$cmd = escapeshellarg($php).' '.escapeshellarg($script).' --worker all';
$out = [];
$code = 0;
exec($cmd.' 2>&1', $out, $code);
file_put_contents($log, date('Y-m-d H:i:s').' | host-cron exit='.$code.PHP_EOL.implode(PHP_EOL,$out).PHP_EOL, FILE_APPEND|LOCK_EX);
exit($code);
