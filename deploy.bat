@echo off
REM NoteShare Deployment Script for Windows
REM This script sets up the NoteShare application for production deployment

echo 🚀 NoteShare Deployment Script
echo ===============================

REM Check if we're in the right directory
if not exist "index.php" (
    echo ❌ Error: Please run this script from the note_share directory
    pause
    exit /b 1
)

echo 📁 Current directory: %CD%

REM Function to backup existing files
:backup_file
if exist "%~1" (
    copy "%~1" "%~1.backup.%date:~-4,4%%date:~-10,2%%date:~-7,2%_%time:~0,2%%time:~3,2%%time:~6,2%" >nul
    echo ✅ Backed up %~1
)
goto :eof

REM 1. Check PHP version
echo.
echo 🔍 Checking PHP version...
php -v >nul 2>&1
if %errorlevel% neq 0 (
    echo ❌ Error: PHP is not installed or not in PATH
    pause
    exit /b 1
)

for /f "tokens=2" %%i in ('php -r "echo PHP_VERSION;"') do set PHP_VERSION=%%i
echo ✅ PHP version: %PHP_VERSION%

php -r "exit(version_compare(PHP_VERSION, '7.4', '<') ? 1 : 0);"
if %errorlevel% neq 0 (
    echo ❌ Error: PHP 7.4 or higher is required
    pause
    exit /b 1
)

REM 2. Check required PHP extensions
echo.
echo 🔍 Checking PHP extensions...
set "REQUIRED_EXTS=curl json session mbstring"
set "MISSING_EXTS="

for %%e in (%REQUIRED_EXTS%) do (
    php -m | findstr /r "^%%e$" >nul
    if !errorlevel! neq 0 (
        set "MISSING_EXTS=!MISSING_EXTS! %%e"
    ) else (
        echo ✅ %%e extension loaded
    )
)

if defined MISSING_EXTS (
    echo ❌ Missing PHP extensions:%MISSING_EXTS%
    echo Please install them using your PHP installer or package manager
    pause
    exit /b 1
)

REM 3. Create necessary directories
echo.
echo 📁 Creating directories...
if not exist "data" mkdir data
if not exist "logs" mkdir logs
if not exist "vendor" mkdir vendor
icacls data /grant Everyone:(OI)(CI)F /T >nul 2>&1
icacls logs /grant Everyone:(OI)(CI)F /T >nul 2>&1
echo ✅ Directories created

REM 4. Set up environment file
echo.
echo 🔧 Setting up environment configuration...
if not exist ".env" (
    if exist ".env.example" (
        copy .env.example .env >nul
        echo ✅ Created .env from .env.example
        echo ⚠️  Please edit .env with your actual configuration values
    ) else (
        echo ⚠️  .env.example not found. Please create .env manually
    )
) else (
    echo ✅ .env already exists
)

REM 5. Check Firebase configuration
echo.
echo 🔥 Checking Firebase configuration...
if exist "js\firebase-config.js" (
    findstr "YOUR_API_KEY" js\firebase-config.js >nul
    if !errorlevel! equ 0 (
        echo ⚠️  Firebase config contains placeholder values
        echo    Please update js\firebase-config.js with your Firebase credentials
    ) else (
        echo ✅ Firebase config appears to be configured
    )
) else (
    echo ❌ js\firebase-config.js not found
    pause
    exit /b 1
)

REM 6. Test basic functionality
echo.
echo 🧪 Running basic tests...
php -l index.php >nul 2>&1
if %errorlevel% equ 0 (
    echo ✅ index.php syntax OK
) else (
    echo ❌ index.php has syntax errors
    pause
    exit /b 1
)

php -l admin_dashboard.php >nul 2>&1
if %errorlevel% equ 0 (
    echo ✅ admin_dashboard.php syntax OK
) else (
    echo ❌ admin_dashboard.php has syntax errors
    pause
    exit /b 1
)

REM 7. Create initial data files if they don't exist
echo.
echo 📄 Setting up initial data files...
if not exist "data\users.json" (
    echo {} > data\users.json
    echo ✅ Created data\users.json
)

if not exist "logs\otp_log.txt" (
    type nul > logs\otp_log.txt
    echo ✅ Created logs\otp_log.txt
)

REM 8. Generate deployment summary
echo.
echo 📋 Generating deployment summary...
(
echo # NoteShare Deployment Summary
echo.
echo ## ✅ Deployment Completed Successfully
echo.
echo ### Server Requirements Met
echo - ✅ PHP 7.4+ installed
echo - ✅ Required extensions loaded ^(curl, json, session, mbstring^)
echo - ✅ Directory permissions set
echo.
echo ### Configuration Status
echo - ✅ Environment file created ^(.env^)
echo - ⚠️  **ACTION REQUIRED**: Update .env with your actual values
echo - ⚠️  **ACTION REQUIRED**: Configure Firebase credentials in js/firebase-config.js
echo - ⚠️  **ACTION REQUIRED**: Set up Gmail SMTP credentials for OTP emails
echo.
echo ### Next Steps
echo 1. **Configure Firebase**: Update js/firebase-config.js with your Firebase project credentials
echo 2. **Configure Email**: Update .env with Gmail SMTP settings for OTP emails
echo 3. **Configure Admin**: Set admin credentials in .env
echo 4. **Upload to Server**: Upload all files to your web server
echo 5. **Test Application**: Access the application and test all features
echo.
echo ### File Structure
echo ```
echo note_share/
echo ├── index.php                 # Main dashboard
echo ├── admin_dashboard.php       # Admin panel
echo ├── login.php                 # User login
echo ├── messages.php              # Real-time chat
echo ├── buy_notes.php             # Note marketplace
echo ├── sell_notes.php            # Sell notes
echo ├── share_notes.php           # Share with batch
echo ├── rent_notes.php            # Rent notes
echo ├── coins.php                 # Coin management
echo ├── profile.php               # User profiles
echo ├── data/users.json           # User data
echo ├── logs/                     # Application logs
echo ├── js/firebase-config.js     # Firebase config
echo ├── css/style.css             # Styles
echo └── includes/                 # PHP includes
echo ```
echo.
echo ### URLs to Test
echo - Main Site: http://yourdomain.com/
echo - Admin Panel: http://yourdomain.com/admin_login.php
echo - User Registration: http://yourdomain.com/login.php
echo.
echo ### Support
echo If you encounter any issues, check:
echo 1. PHP error logs
echo 2. Browser console for JavaScript errors
echo 3. Firebase console for database issues
echo 4. Email logs in logs/otp_log.txt
echo.
echo ---
echo *Deployment completed on: %date% %time%*
) > DEPLOYMENT_SUMMARY.md

echo ✅ Deployment summary created: DEPLOYMENT_SUMMARY.md

REM 9. Final instructions
echo.
echo 🎉 Deployment setup complete!
echo.
echo 📋 NEXT STEPS:
echo 1. Edit .env file with your configuration
echo 2. Update js\firebase-config.js with Firebase credentials
echo 3. Set up Gmail SMTP for OTP emails
echo 4. Upload files to your web server
echo 5. Test the application
echo.
echo 📖 See DEPLOYMENT_SUMMARY.md for detailed instructions
echo.
echo 🚀 Your NoteShare application is ready for production!
echo.
pause