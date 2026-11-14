@echo off
setlocal
set "TASK=FilmNova Auto Sync 00:00"
schtasks /Delete /TN "%TASK%" /F
if errorlevel 1 echo Khong tim thay task hoac can quyen Administrator.
pause
