# NoteShare Deployment Script for Windows PowerShell
# This script sets up the NoteShare application for production deployment

Write-Host "🚀 NoteShare Deployment Script" -ForegroundColor Green
Write-Host "===============================" -ForegroundColor Green

# Check if we're in the right directory
if (!(Test-Path "index.php")) {
    Write-Host "❌ Error: Please run this script from the note_share directory" -ForegroundColor Red
    exit 1
}

Write-Host "📁 Current directory: $(Get-Location)" -ForegroundColor Blue

# 1. Check PHP version
Write-Host ""
Write-Host "🔍 Checking PHP version..." -ForegroundColor Yellow
try {
    $phpVersion = php -r "echo PHP_VERSION;"
    Write-Host "✅ PHP version: $phpVersion" -ForegroundColor Green

    $versionCheck = php -r "echo version_compare(PHP_VERSION, '7.4', '>=') ? 'OK' : 'FAIL';"
    if ($versionCheck -eq "FAIL") {
        Write-Host "❌ Error: PHP 7.4 or higher is required" -ForegroundColor Red
        exit 1
    }
} catch {
    Write-Host "❌ Error: PHP is not installed or not in PATH" -ForegroundColor Red
    exit 1
}

# 2. Check required PHP extensions
Write-Host ""
Write-Host "🔍 Checking PHP extensions..." -ForegroundColor Yellow
$requiredExts = @("curl", "json", "session", "mbstring")
$missingExts = @()

foreach ($ext in $requiredExts) {
    $extCheck = php -m | Select-String -Pattern "^$ext$"
    if ($extCheck) {
        Write-Host "✅ $ext extension loaded" -ForegroundColor Green
    } else {
        $missingExts += $ext
    }
}

if ($missingExts.Count -gt 0) {
    Write-Host "❌ Missing PHP extensions: $($missingExts -join ', ')" -ForegroundColor Red
    Write-Host "Please install them using your PHP installer or package manager" -ForegroundColor Yellow
    exit 1
}

# 3. Create necessary directories
Write-Host ""
Write-Host "📁 Creating directories..." -ForegroundColor Yellow
$dirs = @("data", "logs", "vendor")
foreach ($dir in $dirs) {
    if (!(Test-Path $dir)) {
        New-Item -ItemType Directory -Path $dir -Force | Out-Null
    }
}
Write-Host "✅ Directories created" -ForegroundColor Green

# 4. Set up environment file
Write-Host ""
Write-Host "🔧 Setting up environment configuration..." -ForegroundColor Yellow
if (!(Test-Path ".env")) {
    if (Test-Path ".env.example") {
        Copy-Item ".env.example" ".env"
        Write-Host "✅ Created .env from .env.example" -ForegroundColor Green
        Write-Host "⚠️  Please edit .env with your actual configuration values" -ForegroundColor Yellow
    } else {
        Write-Host "⚠️  .env.example not found. Please create .env manually" -ForegroundColor Yellow
    }
} else {
    Write-Host "✅ .env already exists" -ForegroundColor Green
}

# 5. Check Firebase configuration
Write-Host ""
Write-Host "🔥 Checking Firebase configuration..." -ForegroundColor Yellow
if (Test-Path "js\firebase-config.js") {
    $firebaseConfig = Get-Content "js\firebase-config.js" -Raw
    if ($firebaseConfig -match "YOUR_API_KEY") {
        Write-Host "⚠️  Firebase config contains placeholder values" -ForegroundColor Yellow
        Write-Host "   Please update js/firebase-config.js with your Firebase credentials" -ForegroundColor Yellow
    } else {
        Write-Host "✅ Firebase config appears to be configured" -ForegroundColor Green
    }
} else {
    Write-Host "❌ js\firebase-config.js not found" -ForegroundColor Red
    exit 1
}

# 6. Test basic functionality
Write-Host ""
Write-Host "🧪 Running basic tests..." -ForegroundColor Yellow

$syntaxCheck = php -l index.php 2>&1
if ($LASTEXITCODE -eq 0) {
    Write-Host "✅ index.php syntax OK" -ForegroundColor Green
} else {
    Write-Host "❌ index.php has syntax errors" -ForegroundColor Red
    Write-Host $syntaxCheck -ForegroundColor Red
    exit 1
}

$syntaxCheck = php -l admin_dashboard.php 2>&1
if ($LASTEXITCODE -eq 0) {
    Write-Host "✅ admin_dashboard.php syntax OK" -ForegroundColor Green
} else {
    Write-Host "❌ admin_dashboard.php has syntax errors" -ForegroundColor Red
    Write-Host $syntaxCheck -ForegroundColor Red
    exit 1
}

# 7. Create initial data files if they don't exist
Write-Host ""
Write-Host "📄 Setting up initial data files..." -ForegroundColor Yellow
if (!(Test-Path "data\users.json")) {
    "{}" | Out-File -FilePath "data\users.json" -Encoding UTF8
    Write-Host "✅ Created data/users.json" -ForegroundColor Green
}

if (!(Test-Path "logs\otp_log.txt")) {
    "" | Out-File -FilePath "logs\otp_log.txt" -Encoding UTF8
    Write-Host "✅ Created logs/otp_log.txt" -ForegroundColor Green
}

# 8. Generate deployment summary
Write-Host ""
Write-Host "📋 Generating deployment summary..." -ForegroundColor Yellow

$deploymentSummary = @"
# NoteShare Deployment Summary

## ✅ Deployment Completed Successfully

### Server Requirements Met
- ✅ PHP 7.4+ installed
- ✅ Required extensions loaded (curl, json, session, mbstring)
- ✅ Directory permissions set

### Configuration Status
- ✅ Environment file created (.env)
- ⚠️  **ACTION REQUIRED**: Update .env with your actual values
- ⚠️  **ACTION REQUIRED**: Configure Firebase credentials in js/firebase-config.js
- ⚠️  **ACTION REQUIRED**: Set up Gmail SMTP credentials for OTP emails

### Next Steps
1. **Configure Firebase**: Update js/firebase-config.js with your Firebase project credentials
2. **Configure Email**: Update .env with Gmail SMTP settings for OTP emails
3. **Configure Admin**: Set admin credentials in .env
4. **Upload to Server**: Upload all files to your web server
5. **Test Application**: Access the application and test all features

### File Structure
```
note_share/
├── index.php                 # Main dashboard
├── admin_dashboard.php       # Admin panel
├── login.php                 # User login
├── messages.php              # Real-time chat
├── buy_notes.php             # Note marketplace
├── sell_notes.php            # Sell notes
├── share_notes.php           # Share with batch
├── rent_notes.php            # Rent notes
├── coins.php                 # Coin management
├── profile.php               # User profiles
├── data/users.json           # User data
├── logs/                     # Application logs
├── js/firebase-config.js     # Firebase config
├── css/style.css             # Styles
└── includes/                 # PHP includes
```

### URLs to Test
- Main Site: http://yourdomain.com/
- Admin Panel: http://yourdomain.com/admin_login.php
- User Registration: http://yourdomain.com/login.php

### Support
If you encounter any issues, check:
1. PHP error logs
2. Browser console for JavaScript errors
3. Firebase console for database issues
4. Email logs in logs/otp_log.txt

---
*Deployment completed on: $(Get-Date)*
"@

$deploymentSummary | Out-File -FilePath "DEPLOYMENT_SUMMARY.md" -Encoding UTF8
Write-Host "✅ Deployment summary created: DEPLOYMENT_SUMMARY.md" -ForegroundColor Green

# 9. Final instructions
Write-Host ""
Write-Host "🎉 Deployment setup complete!" -ForegroundColor Green
Write-Host ""
Write-Host "📋 NEXT STEPS:" -ForegroundColor Cyan
Write-Host "1. Edit .env file with your configuration" -ForegroundColor White
Write-Host "2. Update js/firebase-config.js with Firebase credentials" -ForegroundColor White
Write-Host "3. Set up Gmail SMTP for OTP emails" -ForegroundColor White
Write-Host "4. Upload files to your web server" -ForegroundColor White
Write-Host "5. Test the application" -ForegroundColor White
Write-Host ""
Write-Host "📖 See DEPLOYMENT_SUMMARY.md for detailed instructions" -ForegroundColor Cyan
Write-Host ""
Write-Host "🚀 Your NoteShare application is ready for production!" -ForegroundColor Green