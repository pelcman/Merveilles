@echo off
chcp 65001 >nul
php -r "file_put_contents('phpinfo.txt', 'ini: '.php_ini_loaded_file().chr(10).'ext_dir: '.ini_get('extension_dir').chr(10).'binary: '.PHP_BINARY.chr(10));"
type phpinfo.txt
echo.
echo Loaded extensions:
php -m
pause
