@echo off
echo ====================================
echo Deploy, Commit, and Push Script
echo ====================================

REM Use the first argument as commit message, or default to "Auto-commit update"
set MSG=%~1
if "%MSG%"=="" set MSG=Auto-commit update

echo.
echo [1/3] Adding changes...
git add .

echo.
echo [2/3] Committing changes (Message: "%MSG%")...
git commit -m "%MSG%"

echo.
echo [3/3] Pushing to remote...
git push

echo.
echo Done!
