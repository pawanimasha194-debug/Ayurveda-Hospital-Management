' ============================================================
' Ayurveda Hospital Management System - Silent Launcher
' Double-click this file to start the system.
' No console windows, no "press any key" prompts.
' ============================================================

Set WshShell = CreateObject("WScript.Shell")
Set fso = CreateObject("Scripting.FileSystemObject")

' --- Start Apache (hidden, window style 0) ---
WshShell.Run """C:\xampp\apache\bin\httpd.exe""", 0, False

' --- Start MySQL (hidden, window style 0) ---
WshShell.Run """C:\xampp\mysql\bin\mysqld.exe"" --defaults-file=""C:\xampp\mysql\bin\my.ini""", 0, False

' Give both servers a few seconds to fully start
WScript.Sleep 4000

' --- Open the system in app mode (no browser address bar) ---
chromePath = "C:\Program Files\Google\Chrome\Application\chrome.exe"
chromePathX86 = "C:\Program Files (x86)\Google\Chrome\Application\chrome.exe"
edgePath = "C:\Program Files (x86)\Microsoft\Edge\Application\msedge.exe"

url = "http://localhost/ayurveda_system/login.php"
appArgs = " --app=" & url & " --window-size=1280,800"

If fso.FileExists(chromePath) Then
    WshShell.Run """" & chromePath & """" & appArgs, 1, False
ElseIf fso.FileExists(chromePathX86) Then
    WshShell.Run """" & chromePathX86 & """" & appArgs, 1, False
ElseIf fso.FileExists(edgePath) Then
    WshShell.Run """" & edgePath & """" & appArgs, 1, False
Else
    MsgBox "Chrome or Edge was not found in the default location." & vbCrLf & _
           "Please open your browser manually and go to:" & vbCrLf & url, 48, "Ayurveda Hospital System"
End If
