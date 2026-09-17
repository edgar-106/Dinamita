@echo off
title Subir cambios a GitHub
set "PATH=C:\Program Files\Microsoft Visual Studio\18\Community\Common7\IDE\CommonExtensions\Microsoft\TeamFoundation\Team Explorer\Git\cmd;C:\Program Files\Microsoft Visual Studio\18\Community\Common7\IDE\CommonExtensions\Microsoft\TeamFoundation\Team Explorer\Git\mingw64\bin;%PATH%"
cd /d C:\xampp\htdocs\infonatec

echo ========================================================
echo   Subiendo cambios al repositorio remoto en GitHub
echo ========================================================
echo.
git push origin main
echo.
if %ERRORLEVEL% equ 0 (
    echo [EXITO] Los cambios se han subido correctamente a GitHub.
) else (
    echo [AVISO] Si te solicita iniciar sesion, completa el inicio en el navegador.
)
echo.
pause
