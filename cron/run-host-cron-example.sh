#!/bin/sh
# Ví dụ cho Linux hosting. Sửa đường dẫn USERNAME/public_html.
cd /home/USERNAME/public_html || exit 1
/usr/bin/php cron/sync.php --worker all >> cron/auto_sync.log 2>&1
