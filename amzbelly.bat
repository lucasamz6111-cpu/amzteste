@echo off
cd /d "%~dp0"
set "cmd=%~1"
if "%cmd%"=="" goto help
if /I "%cmd%"=="help" goto help
if /I "%cmd%"=="serve" goto serve
if /I "%cmd%"=="start" goto serve
if /I "%cmd%"=="update" goto update
if /I "%cmd%"=="git" goto update
if /I "%cmd%"=="phpserve" goto phpserve
echo Comando invalido: %cmd%
echo.
goto help

:serve
echo Iniciando servidor Node.js...
if exist "%~dp0server.js" (
    start "AMZBELLY Server" cmd /k "cd /d "%~dp0" && node server.js"
) else (
    echo Arquivo server.js nao encontrado.
)
goto end

:update
echo Atualizando o sistema com git pull...
if exist "%~dp0.git" (
    git -C "%~dp0" pull
) else (
    echo Esta pasta nao e um repositorio git. Clone o projeto do GitHub primeiro.
)
goto end

:phpserve
echo Iniciando servidor PHP embutido em http://localhost:8000
php -S localhost:8000
goto end

:help
echo.
echo Uso: amzbelly ^<comando^>
echo.
echo Comandos disponiveis:
echo   amzbelly help      - Exibe esta ajuda
echo   amzbelly serve     - Inicia server.js no servidor Node.js
echo   amzbelly update    - Atualiza o repositorio local via git pull
echo   amzbelly phpserve  - Inicia PHP embutido em localhost:8000
echo.
echo Observacao:
echo   - Execute este comando na pasta do projeto.
echo   - Se quiser usar de qualquer lugar, adicione esta pasta ao PATH.
echo.
goto end

:end