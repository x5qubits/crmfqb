@echo off
setlocal

REM Check Git is installed
where git >nul 2>&1
if errorlevel 1 (
  echo Git is not installed or not in PATH.
  exit /b 1
)

REM Use first argument as commit message, or fallback
if "%~1"=="" (
  set "MSG=Auto update on %date% %time%"
) else (
  set "MSG=%~1"
)

echo Staging all changes...
git add -A

echo Committing with message: "%MSG%"
git commit -m "%MSG%" || echo Nothing to commit.

echo Pulling latest changes...
git pull --rebase origin HEAD

echo Pushing to origin...
git push origin HEAD

echo Done.
pause
endlocal
