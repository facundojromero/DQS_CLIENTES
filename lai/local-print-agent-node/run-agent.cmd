@echo off
setlocal
set "BASE_DIR=%~dp0"
set "NODE_EXE=%BASE_DIR%runtime\node.exe"

pushd "%BASE_DIR%"
if exist "%NODE_EXE%" (
  "%NODE_EXE%" "%BASE_DIR%agent.js"
) else (
  node "%BASE_DIR%agent.js"
)
set "EXIT_CODE=%ERRORLEVEL%"
popd
exit /b %EXIT_CODE%
