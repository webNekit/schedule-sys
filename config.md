@echo off
chcp 65001 >nul
:: Проверка на права администратора
net session >nul 2>&1
if %errorlevel% neq 0 (
echo Запустите этот файл от имени администратора!
pause
exit /b 1
)

echo === Создание учетной записи "студент" ===
net user студент "" /add /comment:"Учетная запись студента" /passwordreq:no
net localgroup Users студент /add
:: НЕ добавляем в Administrators — это уже блокирует установку большинства программ (UAC не даст подтвердить)

echo === Загрузка Default-профиля для применения политик всем будущим пользователям ===
reg load HKU\DefTemp C:\Users\Default\NTUSER.DAT

:: --- 2. Запрет смены обоев и персонализации ---
reg add "HKU\DefTemp\Software\Microsoft\Windows\CurrentVersion\Policies\ActiveDesktop" /v NoChangingWallpaper /t REG_DWORD /d 1 /f
reg add "HKU\DefTemp\Software\Microsoft\Windows\CurrentVersion\Policies\System" /v NoDispBackgroundPage /t REG_DWORD /d 1 /f
reg add "HKU\DefTemp\Software\Microsoft\Windows\CurrentVersion\Policies\System" /v NoDispAppearancePage /t REG_DWORD /d 1 /f
reg add "HKU\DefTemp\Software\Microsoft\Windows\CurrentVersion\Policies\System" /v NoDispScrSavPage /t REG_DWORD /d 1 /f
reg add "HKU\DefTemp\Software\Microsoft\Windows\CurrentVersion\Policies\System" /v NoDispSettingsPage /t REG_DWORD /d 1 /f
reg add "HKU\DefTemp\Software\Microsoft\Windows\CurrentVersion\Policies\System" /v NoDispCPL /t REG_DWORD /d 1 /f
reg add "HKU\DefTemp\Software\Microsoft\Windows\CurrentVersion\Policies\Explorer" /v NoThemesTab /t REG_DWORD /d 1 /f

:: --- 3. Запрет создания ярлыков/папок и изменения рабочего стола (без блокировки правого клика полностью) ---
reg add "HKU\DefTemp\Software\Microsoft\Windows\CurrentVersion\Policies\ActiveDesktop" /v NoAddingComponents /t REG_DWORD /d 1 /f
reg add "HKU\DefTemp\Software\Microsoft\Windows\CurrentVersion\Policies\ActiveDesktop" /v NoDeletingComponents /t REG_DWORD /d 1 /f
reg add "HKU\DefTemp\Software\Microsoft\Windows\CurrentVersion\Policies\ActiveDesktop" /v NoEditingComponents /t REG_DWORD /d 1 /f
reg add "HKU\DefTemp\Software\Microsoft\Windows\CurrentVersion\Policies\Explorer" /v NoNewAppAlert /t REG_DWORD /d 1 /f
:: Полный запрет пункта "Создать" в контекстном меню рабочего стола/проводника:
reg add "HKU\DefTemp\Software\Microsoft\Windows\CurrentVersion\Policies\Explorer" /v NoViewContextMenu /t REG_DWORD /d 0 /f

:: --- 4. Запрет установки программ (доп. к тому, что аккаунт не админ) ---
reg add "HKU\DefTemp\Software\Policies\Microsoft\Windows\Installer" /v DisableMSI /t REG_DWORD /d 1 /f
reg add "HKU\DefTemp\Software\Microsoft\Windows\CurrentVersion\Policies\Explorer" /v NoDevMgrUpdate /t REG_DWORD /d 1 /f

:: --- 5. Запрет доступа к Параметрам Windows и Панели управления ---
reg add "HKU\DefTemp\Software\Microsoft\Windows\CurrentVersion\Policies\Explorer" /v NoControlPanel /t REG_DWORD /d 1 /f
reg add "HKU\DefTemp\Software\Microsoft\Windows\CurrentVersion\Policies\Explorer" /v SettingsPageVisibility /t REG_SZ /d "showonly:" /f

echo === Выгрузка куста реестра ===
reg unload HKU\DefTemp

echo.
echo Готово! Учетная запись "студент" создана.
echo Ограничения применятся автоматически при ПЕРВОМ входе студента в систему.
pause
