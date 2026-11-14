FILMNOVA - TU DONG CAP NHAT API LUC 00:00

Cach dung tren XAMPP Windows:
1. Giai nen FilmNova.
2. Kiem tra run-midnight.bat dang dung C:\xampp\php\php.exe.
3. Chuot phai install-midnight-task.bat -> Run as administrator.
4. Windows Task Scheduler se tao task: FilmNova Auto Sync 00:00.
5. Moi ngay 00:00 worker se cap nhat CẢ KKPhim + VSMOV.

Worker chạy CLI nên khong phu thuoc trang web dang mo va khong bi gioi han PHP 120 giay cua browser.
Moi dot API van duoc chia chunk va co lock chong chay trung.
Ket qua ghi vao cron/auto_sync.log va settings last_sync_at.

Luu y:
- May tinh/XAMPP phai dang bat tai thoi diem 00:00.
- Apache khong bat buoc cho worker CLI, nhung PHP trong C:\xampp\php\php.exe phai ton tai.
- Neu doi thu muc XAMPP, sua bien PHP trong run-midnight.bat.
