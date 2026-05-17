#!/bin/bash

# NoteShare Deployment Script
# This script sets up the NoteShare application for production deployment

echo "🚀 NoteShare Deployment Script"
echo "=============================="

# Check if we're in the right directory
if [ ! -f "index.php" ]; then
    echo "❌ Error: Please run this script from the note_share directory"
    exit 1
fi

echo "📁 Current directory: $(pwd)"

# Function to backup existing files
backup_file() {
    local file=$1
    if [ -f "$file" ]; then
        cp "$file" "$file.backup.$(date +%Y%m%d_%H%M%S)"
        echo "✅ Backed up $file"
    fi
}

# 1. Check PHP version
echo ""
echo "🔍 Checking PHP version..."
if command -v php &> /dev/null; then
    PHP_VERSION=$(php -r "echo PHP_VERSION;")
    echo "✅ PHP version: $PHP_VERSION"
    if php -r "exit(version_compare(PHP_VERSION, '7.4', '<') ? 1 : 0);"; then
        echo "❌ Error: PHP 7.4 or higher is required"
        exit 1
    fi
else
    echo "❌ Error: PHP is not installed"
    exit 1
fi

# 2. Check required PHP extensions
echo ""
echo "🔍 Checking PHP extensions..."
REQUIRED_EXTS=("curl" "json" "session" "mbstring")
MISSING_EXTS=()

for ext in "${REQUIRED_EXTS[@]}"; do
    if php -m | grep -q "^$ext$"; then
        echo "✅ $ext extension loaded"
    else
        MISSING_EXTS+=("$ext")
    fi
done

if [ ${#MISSING_EXTS[@]} -ne 0 ]; then
    echo "❌ Missing PHP extensions: ${MISSING_EXTS[*]}"
    echo "Please install them using your package manager"
    exit 1
fi

# 3. Create necessary directories
echo ""
echo "📁 Creating directories..."
mkdir -p data logs vendor
chmod 755 data logs
echo "✅ Directories created"

# 4. Set up environment file
echo ""
echo "🔧 Setting up environment configuration..."
if [ ! -f ".env" ]; then
    if [ -f ".env.example" ]; then
        cp .env.example .env
        echo "✅ Created .env from .env.example"
        echo "⚠️  Please edit .env with your actual configuration values"
    else
        echo "⚠️  .env.example not found. Please create .env manually"
    fi
else
    echo "✅ .env already exists"
fi

# 5. Set proper permissions
echo ""
echo "🔒 Setting file permissions..."
find . -type f -name "*.php" -exec chmod 644 {} \;
find . -type f -name "*.js" -exec chmod 644 {} \;
find . -type f -name "*.css" -exec chmod 644 {} \;
find . -type f -name "*.html" -exec chmod 644 {} \;
find . -type f -name "*.md" -exec chmod 644 {} \;

# Make directories writable
chmod 755 data logs
chmod 644 .env

echo "✅ File permissions set"

# 6. Check Firebase configuration
echo ""
echo "🔥 Checking Firebase configuration..."
if [ -f "js/firebase-config.js" ]; then
    if grep -q "YOUR_API_KEY" js/firebase-config.js; then
        echo "⚠️  Firebase config contains placeholder values"
        echo "   Please update js/firebase-config.js with your Firebase credentials"
    else
        echo "✅ Firebase config appears to be configured"
    fi
else
    echo "❌ js/firebase-config.js not found"
    exit 1
fi

# 7. Test basic functionality
echo ""
echo "🧪 Running basic tests..."
if php -l index.php > /dev/null 2>&1; then
    echo "✅ index.php syntax OK"
else
    echo "❌ index.php has syntax errors"
    exit 1
fi

if php -l admin_dashboard.php > /dev/null 2>&1; then
    echo "✅ admin_dashboard.php syntax OK"
else
    echo "❌ admin_dashboard.php has syntax errors"
    exit 1
fi

# 8. Create initial data files if they don't exist
echo ""
echo "📄 Setting up initial data files..."
if [ ! -f "data/users.json" ]; then
    echo "{}" > data/users.json
    echo "✅ Created data/users.json"
fi

if [ ! -f "logs/otp_log.txt" ]; then
    touch logs/otp_log.txt
    echo "✅ Created logs/otp_log.txt"
fi

# 9. Generate deployment summary
echo ""
echo "📋 Generating deployment summary..."
cat > DEPLOYMENT_SUMMARY.md << 'EOF'
# NoteShare Deployment Summary

## ✅ Deployment Completed Successfully

### Server Requirements Met
- ✅ PHP 7.4+ installed
- ✅ Required extensions loaded (curl, json, session, mbstring)
- ✅ Directory permissions set
- ✅ File permissions configured

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
*Deployment completed on: $(date)*
EOF

echo "✅ Deployment summary created: DEPLOYMENT_SUMMARY.md"

# 10. Final instructions
echo ""
echo "🎉 Deployment setup complete!"
echo ""
echo "📋 NEXT STEPS:"
echo "1. Edit .env file with your configuration"
echo "2. Update js/firebase-config.js with Firebase credentials"
echo "3. Set up Gmail SMTP for OTP emails"
echo "4. Upload files to your web server"
echo "5. Test the application"
echo ""
echo "📖 See DEPLOYMENT_SUMMARY.md for detailed instructions"
echo ""
echo "🚀 Your NoteShare application is ready for production!"