#Region ;**** Directives created by AutoIt3Wrapper_GUI ****
#AutoIt3Wrapper_UseX64=y
#AutoIt3Wrapper_Res_Description=FilmNova Windows App
#AutoIt3Wrapper_Res_Language=1066
#AutoIt3Wrapper_Res_Fileversion=1.0.0.0
#EndRegion

#include <GUIConstantsEx.au3>
#include <WindowsConstants.au3>
#include <EditConstants.au3>
#include <ListBoxConstants.au3>
#include <StaticConstants.au3>
#include <ButtonConstants.au3>
#include <Array.au3>
#include <File.au3>

Global Const $APP_TITLE = "FilmNova"
Global Const $MB_ICONERROR = 16
Global Const $MB_ICONINFORMATION = 64
Global Const $CONFIG_FILE = @ScriptDir & "\FilmNova.ini"
Global $gApiBase = ""
Global $gApiKey = ""
Global $gMovies[1][5]
Global $gMovieCount = 0

_LoadConfig()
If $gApiBase = "" Or $gApiKey = "" Then
    _ShowSetup()
EndIf

Local $hGUI = GUICreate($APP_TITLE, 900, 600, -1, -1, BitOR($WS_MINIMIZEBOX, $WS_SYSMENU))
GUISetFont(10, 400, 0, "Segoe UI")
GUICtrlCreateLabel("FilmNova", 20, 18, 180, 32, $SS_CENTERIMAGE)
GUICtrlSetFont(-1, 18, 700)
Local $idSearch = GUICtrlCreateInput("", 210, 18, 430, 32)
GUICtrlSetTip($idSearch, "Nhập tên phim để tìm")
Local $idSearchBtn = GUICtrlCreateButton("Tìm", 650, 18, 90, 32)
Local $idRefresh = GUICtrlCreateButton("Tải lại", 750, 18, 110, 32)
Local $idList = GUICtrlCreateList("", 20, 70, 840, 390, BitOR($LBS_NOTIFY, $WS_VSCROLL))
Local $idInfo = GUICtrlCreateLabel("Chọn một phim để xem thông tin.", 20, 475, 840, 45)
GUICtrlSetState($idInfo, $GUI_DISABLE)
Local $idWatch = GUICtrlCreateButton("▶ Xem phim", 20, 530, 180, 40)
Local $idEpisode = GUICtrlCreateButton("Chọn tập / mở trang xem", 215, 530, 240, 40)
Local $idSettings = GUICtrlCreateButton("Cấu hình API", 700, 530, 160, 40)

GUISetState(@SW_SHOW, $hGUI)
_LoadMovies($idList)

While 1
    Switch GUIGetMsg()
        Case $GUI_EVENT_CLOSE
            ExitLoop
        Case $idRefresh
            _LoadMovies($idList)
        Case $idSearchBtn
            _LoadMovies($idList, GUICtrlRead($idSearch))
        Case $idSettings
            _ShowSetup()
            _LoadMovies($idList)
        Case $idWatch
            _OpenSelectedWatch($idList)
        Case $idEpisode
            _OpenSelectedWatch($idList)
    EndSwitch
    Local $sel = GUICtrlRead($idList)
    If $sel <> "" Then
        Local $idx = StringRegExpReplace($sel, "^#(\d+)\s.*$", "$1")
        If StringRegExp($sel, "^#\d+\s") Then
            Local $movie = _FindMovieById(Number($idx))
            If IsArray($movie) Then GUICtrlSetData($idInfo, $movie[1] & "\r\n" & $movie[3] & " · " & $movie[4] & " tập")
        EndIf
    EndIf
WEnd

GUIDelete($hGUI)
Exit

Func _ShowSetup()
    Local $h = GUICreate("Cấu hình FilmNova API", 700, 280, -1, -1, BitOR($WS_SYSMENU, $WS_CAPTION))
    GUISetFont(10, 400, 0, "Segoe UI")
    GUICtrlCreateLabel("API URL", 20, 25, 90, 25)
    Local $u = GUICtrlCreateInput($gApiBase, 120, 20, 550, 30)
    GUICtrlSetTip($u, "Ví dụ: http://127.0.0.1/api/app.php hoặc https://tenmien.com/api/app.php")
    GUICtrlCreateLabel("API Key", 20, 75, 90, 25)
    Local $k = GUICtrlCreateInput($gApiKey, 120, 70, 550, 30, $ES_PASSWORD)
    Local $save = GUICtrlCreateButton("Lưu", 120, 130, 150, 36)
    Local $test = GUICtrlCreateButton("Kiểm tra kết nối", 285, 130, 170, 36)
    Local $close = GUICtrlCreateButton("Đóng", 470, 130, 140, 36)
    GUICtrlCreateLabel("API phải trỏ tới đúng FilmNova trên máy hoặc host của bạn.", 120, 180, 520, 25)
    GUISetState(@SW_SHOW, $h)
    While 1
        Local $m = GUIGetMsg()
        Switch $m
            Case $GUI_EVENT_CLOSE, $close
                GUIDelete($h)
                Return
            Case $save
                $gApiBase = StringStripWS(GUICtrlRead($u), 3)
                $gApiKey = StringStripWS(GUICtrlRead($k), 3)
                IniWrite($CONFIG_FILE, "FilmNova", "ApiBase", $gApiBase)
                IniWrite($CONFIG_FILE, "FilmNova", "ApiKey", $gApiKey)
                MsgBox($MB_ICONINFORMATION, $APP_TITLE, "Đã lưu cấu hình.")
            Case $test
                $gApiBase = StringStripWS(GUICtrlRead($u), 3)
                $gApiKey = StringStripWS(GUICtrlRead($k), 3)
                Local $res = _ApiGet("?action=health")
                If @error Then
                    MsgBox($MB_ICONERROR, $APP_TITLE, "Không kết nối được API.")
                Else
                    MsgBox($MB_ICONINFORMATION, $APP_TITLE, "API hoạt động: " & $res)
                EndIf
        EndSwitch
    WEnd
EndFunc

Func _LoadConfig()
    $gApiBase = IniRead($CONFIG_FILE, "FilmNova", "ApiBase", "")
    $gApiKey = IniRead($CONFIG_FILE, "FilmNova", "ApiKey", "")
EndFunc

Func _ApiGet($query)
    If StringLeft($gApiBase, 4) <> "http" Then Return SetError(1, 0, "")
    Local $url = $gApiBase & $query
    Local $http = ObjCreate("WinHttp.WinHttpRequest.5.1")
    If Not IsObj($http) Then Return SetError(2, 0, "")
    $http.Open("GET", $url, False)
    $http.SetRequestHeader("X-FilmNova-Key", $gApiKey)
    $http.SetRequestHeader("User-Agent", "FilmNova-Windows/1.0")
    $http.Send()
    If $http.Status <> 200 Then Return SetError(3, $http.Status, $http.ResponseText)
    Return $http.ResponseText
EndFunc

Func _LoadMovies($idList, $q = "")
    GUICtrlSetData($idList, "")
    Local $query = "?action=list&page=1&limit=50"
    If $q <> "" Then $query &= "&q=" & _UrlEncode($q)
    Local $json = _ApiGet($query)
    If @error Then
        GUICtrlSetData($idList, "Không kết nối được API")
        Return
    EndIf
    Local $matches = StringRegExp($json, '"id"\s*:\s*(\d+).*?"name"\s*:\s*"((?:\\.|[^"\\])*)".*?"slug"\s*:\s*"((?:\\.|[^"\\])*)"', 3)
    If @error Or Not IsArray($matches) Then
        GUICtrlSetData($idList, "Không đọc được danh sách phim")
        Return
    EndIf
    $gMovieCount = 0
    ReDim $gMovies[1][5]
    Local $count = Int(UBound($matches) / 3)
    If $count > 0 Then ReDim $gMovies[$count][5]
    $gMovieCount = $count
    For $i = 0 To $count - 1
        Local $base = $i * 3
        Local $id = Number($matches[$base])
        Local $name = _JsonUnescape($matches[$base + 1])
        Local $slug = _JsonUnescape($matches[$base + 2])
        $gMovies[$i][0] = $id
        $gMovies[$i][1] = $name
        $gMovies[$i][2] = $slug
        $gMovies[$i][3] = "Phim"
        $gMovies[$i][4] = 0
        GUICtrlSetData($idList, "#" & $id & "  " & $name, 1)
    Next
EndFunc

Func _FindMovieById($id)
    For $i = 0 To $gMovieCount - 1
        If $gMovies[$i][0] = $id Then Return $gMovies[$i]
    Next
    Return SetError(1, 0, 0)
EndFunc

Func _OpenSelectedWatch($idList)
    Local $sel = GUICtrlRead($idList)
    If Not StringRegExp($sel, "^#\d+\s") Then
        MsgBox($MB_ICONINFORMATION, $APP_TITLE, "Hãy chọn một phim trước.")
        Return
    EndIf
    Local $id = Number(StringRegExpReplace($sel, "^#(\d+)\s.*$", "$1"))
    Local $movie = _FindMovieById($id)
    If Not IsArray($movie) Then Return
    Local $json = _ApiGet("?action=movie&slug=" & _UrlEncode($movie[2]))
    If @error Then
        MsgBox($MB_ICONERROR, $APP_TITLE, "Không lấy được thông tin phim.")
        Return
    EndIf
    Local $watch = StringRegExp($json, '"watch_url"\s*:\s*"((?:\\.|[^"\\])*)"', 1)
    If @error Or Not IsArray($watch) Then
        MsgBox($MB_ICONERROR, $APP_TITLE, "API không trả về link xem phim.")
        Return
    EndIf
    Local $url = _JsonUnescape($watch[0])
    If StringLeft($url, 4) <> "http" Then Return
    ; Edge app mode gives a dedicated app-like window while using the same FilmNova web/API.
    If FileExists(@ProgramFilesDir & "\Microsoft\Edge\Application\msedge.exe") Then
        Run('"' & @ProgramFilesDir & '\Microsoft\Edge\Application\msedge.exe" --app="' & $url & '"', "", @SW_SHOWNORMAL)
    ElseIf FileExists(EnvGet("LOCALAPPDATA") & "\Microsoft\Edge\Application\msedge.exe") Then
        Run('"' & EnvGet("LOCALAPPDATA") & '\Microsoft\Edge\Application\msedge.exe" --app="' & $url & '"', "", @SW_SHOWNORMAL)
    Else
        ShellExecute($url)
    EndIf
EndFunc

Func _JsonUnescape($s)
    $s = StringReplace($s, "\r", @CR)
    $s = StringReplace($s, "\n", @LF)
    $s = StringReplace($s, "\t", @TAB)
    $s = StringReplace($s, Chr(92) & Chr(34), Chr(34))
    $s = StringReplace($s, Chr(92) & Chr(92), Chr(92))
    Return $s
EndFunc

Func _UrlEncode($s)
    Local $out = ""
    Local $i, $c, $o
    For $i = 1 To StringLen($s)
        $c = StringMid($s, $i, 1)
        $o = AscW($c)
        If (($o >= 48 And $o <= 57) Or ($o >= 65 And $o <= 90) Or ($o >= 97 And $o <= 122) Or StringInStr("-_.~", $c)) Then
            $out &= $c
        ElseIf $o <= 255 Then
            $out &= "%" & Hex($o, 2)
        Else
            ; Minimal UTF-8 percent encoding for BMP characters.
            $out &= "%" & Hex(192 + Int($o / 64), 2)
            $out &= "%" & Hex(128 + Mod($o, 64), 2)
        EndIf
    Next
    Return $out
EndFunc
