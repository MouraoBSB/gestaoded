@echo off
REM Autor: Thiago Mourao
REM Instagram: https://www.instagram.com/mouraoeguerin/
REM Data: 2026-02-11 17:30:00

echo ============================================
echo SERVIDOR DE DESENVOLVIMENTO LOCAL
echo Sistema de Chamada de Cursos
echo ============================================
echo.

REM Verificar se PHP está instalado
where php >nul 2>nul
if %ERRORLEVEL% NEQ 0 (
    echo [ERRO] PHP nao encontrado!
    echo.
    echo Por favor, instale o PHP primeiro:
    echo 1. Baixe em: https://windows.php.net/download/
    echo 2. Extraia para C:\php
    echo 3. Adicione C:\php ao PATH do Windows
    echo.
    echo Ou instale XAMPP/Laragon que ja incluem PHP.
    echo.
    pause
    exit /b 1
)

echo [OK] PHP encontrado!
echo.
echo Iniciando servidor em http://localhost:8000
echo.
echo Pressione Ctrl+C para parar o servidor
echo ============================================
echo.

REM Iniciar servidor PHP
php -S localhost:8000

pause
