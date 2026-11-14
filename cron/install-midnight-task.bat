@echo off
setlocal EnableExtensions
set "ROOT=%~dp0.."
set "BAT=%~dp0run-midnight.bat"
set "TASK=FilmNova Auto Sync 00:00"

if not exist "C:\xampp\php\php.exe" (
  echo [LOI] Khong tim thay C:\xampp\php\php.exe
  echo Hay sua file run-midnight.bat neu XAMPP cua ban nam o noi khac.
  pause
  exit /b 1
)

schtasks /Create /TN "%TASK%" /TR "\"%BAT%\"" /SC DAILY /ST 00:00 /RL HIGHEST /F
if errorlevel 1 (
  echo [LOI] Khong tao duoc Scheduled Task. Hay chay file nay bang Run as administrator.
  pause
  exit /b 1
)

echo.
echo [OK] Da cai FilmNova tu dong cap nhat moi ngay luc 00:00.
echo Nguon: KKPhim + VSMOV
schtasks /Query /TN "%TASK%" /FO LIST /V | findstr /I "TaskName Next Run Time Status"
echo.
pause
