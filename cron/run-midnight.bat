@echo off
setlocal
set "ROOT=%~dp0.."
set "PHP=C:\xampp\php\php.exe"
if not exist "%PHP%" (
  echo Khong tim thay PHP tai %PHP%
  echo Hay sua dong PHP= trong file nay cho dung duong dan XAMPP.
  exit /b 1
)
cd /d "%ROOT%"
"%PHP%" "%ROOT%\cron\sync.php" --worker all >> "%ROOT%\cron\auto_sync.log" 2>&1
exit /b %errorlevel%
