# NoteShare Setup & Deployment Guide

## Quick Start (Local Development)

### 1. Install Dependencies
```bash
# Install PHP and required extensions
# For Windows: Download PHP from php.net
# For Mac: brew install php
# For Linux: apt-get install php php-curl php-json

# Install Composer (optional, for package management)
composer install
```

### 2. Configure Firebase

1. Go to [Firebase Console](https://console.firebase.google.com/)
2. Create a new project named "NoteShare"
3. Enable Realtime Database (Start in test mode)
4. **Enable Authentication:**
   - Go to Authentication → Sign-in method
   - Enable "Email/Password" authentication (for admin purposes)
   - **Note:** Email OTP is handled server-side, not through Firebase Auth
5. Copy your project credentials

5. Update `js/firebase-config.js`:
```javascript
const firebaseConfig = {
    apiKey: "YOUR_API_KEY_HERE",
    authDomain: "your-project.firebaseapp.com",
    databaseURL: "https://your-project.firebaseio.com",
    projectId: "your-project-id",
    storageBucket: "your-project.appspot.com",
    messagingSenderId: "YOUR_MESSAGING_SENDER_ID",
    appId: "YOUR_APP_ID"
};
```

6. Update `includes/firebase_config.php` with same credentials

### 3. Configure Google OAuth (Recommended)

1. Go to [Google Cloud Console](https://console.cloud.google.com/)
2. Create OAuth 2.0 credentials (Web application)
3. Add authorized redirect URIs:
   ```
   http://localhost:8000/note_share/auth/google_callback.php
   ```
4. Update `includes/firebase_config.php`:
```php
define('GOOGLE_CLIENT_ID', 'YOUR_CLIENT_ID.apps.googleusercontent.com');
define('GOOGLE_CLIENT_SECRET', 'YOUR_CLIENT_SECRET');
```

### 4. Configure Gmail SMTP for OTP Emails

1. **Enable 2-Factor Authentication** on your Gmail account
2. **Generate App Password**:
   - Go to [Google Account Settings](https://myaccount.google.com/)
   - Security → 2-Step Verification → App passwords
   - Generate password for "Mail"
3. **Update `.env` file**:
```env
GMAIL_USERNAME=your-gmail@gmail.com
GMAIL_APP_PASSWORD=your-16-character-app-password
FROM_EMAIL=your-gmail@gmail.com
FROM_NAME=NoteShare
```

### 5. Setup Payment Gateway (Razorpay)

1. Create account at [Razorpay](https://razorpay.com/)
2. Get API keys from Dashboard
3. Update `includes/firebase_config.php`:
```php
define('RAZORPAY_KEY_ID', 'YOUR_KEY_ID');
define('RAZORPAY_KEY_SECRET', 'YOUR_KEY_SECRET');
```

### 5. Start Local Server

**Option A: PHP Built-in Server**
```bash
cd note_share
php -S localhost:8000
# Access: http://localhost:8000
```

**Option B: Apache**
```bash
# Move folder to htdocs (Windows) or var/www (Linux)
# Enable mod_rewrite in Apache config
# Access: http://localhost/note_share
```

**Option C: Docker**
```bash
# Create Dockerfile
docker build -t noteshare .
docker run -p 8000:80 noteshare
```

## Firebase Security Rules Setup

Replace default rules with:

```json
{
  "rules": {
    "users": {
      "$uid": {
        ".read": "$uid === auth.uid || root.child('users').child($uid).child('public_profile').val() === true",
        ".write": "$uid === auth.uid",
        "email": {
          ".validate": "newData.val().matches(/^[a-zA-Z0-9._%+-]+@vitstudent\\.ac\\.in$/) || newData.val().matches(/^[a-zA-Z0-9._%+-]+@gmail\\.com$/)"
        },
        "coins": {
          ".validate": "newData.isNumber() && newData.val() >= 0"
        }
      }
    },
    "notes": {
      ".read": true,
      ".indexOn": ["seller_id", "course_code", "slot", "status"],
      "$noteId": {
        ".write": "root.child('notes').child($noteId).child('seller_id').val() === auth.uid || !root.child('notes').child($noteId).exists()"
      }
    },
    "shared_notes": {
      ".read": true,
      ".indexOn": ["sharer_id", "batch"],
      "$noteId": {
        ".write": "root.child('shared_notes').child($noteId).child('sharer_id').val() === auth.uid || !root.child('shared_notes').child($noteId).exists()"
      }
    },
    "rental_notes": {
      ".read": true,
      ".indexOn": ["renter_id", "course_code", "slot"],
      "$noteId": {
        ".write": "root.child('rental_notes').child($noteId).child('renter_id').val() === auth.uid || !root.child('rental_notes').child($noteId).exists()"
      }
    },
    "chats": {
      ".read": "root.child('chats').child($chatId).child('buyer_id').val() === auth.uid || root.child('chats').child($chatId).child('seller_id').val() === auth.uid || root.child('chats').child($chatId).child('user1_id').val() === auth.uid || root.child('chats').child($chatId).child('user2_id').val() === auth.uid",
      ".write": "root.child('chats').child($chatId).child('buyer_id').val() === auth.uid || root.child('chats').child($chatId).child('seller_id').val() === auth.uid || root.child('chats').child($chatId).child('user1_id').val() === auth.uid || root.child('chats').child($chatId).child('user2_id').val() === auth.uid || !root.child('chats').child($chatId).exists()"
    },
    "messages": {
      ".read": "query.limitToFirst(100).val() !== null",
      ".write": "newData.child('sender_id').val() === auth.uid && (root.child('chats').child(newData.child('chat_id').val()).child('buyer_id').val() === auth.uid || root.child('chats').child(newData.child('chat_id').val()).child('seller_id').val() === auth.uid || root.child('chats').child(newData.child('chat_id').val()).child('user1_id').val() === auth.uid || root.child('chats').child(newData.child('chat_id').val()).child('user2_id').val() === auth.uid)",
      ".indexOn": ["chat_id", "timestamp"]
    },
    "coin_transactions": {
      ".read": "$uid === auth.uid",
      ".write": "newData.child('user_id').val() === auth.uid",
      ".indexOn": ["user_id", "timestamp"]
    }
  }
}
```

## Production Deployment

### 1. Choose Hosting Provider

- **Bluehost** (WordPress recommended, but supports PHP)
- **SiteGround** (Great PHP support)
- **GCP App Engine** (Google Cloud)
- **AWS Elastic Beanstalk**
- **Heroku** (with buildpacks)
- **DigitalOcean** (Droplets or App Platform)

### 2. Pre-Deployment Checklist

```bash
# 1. Remove debug information
# Update includes/firebase_config.php:
define('APP_ENV', 'production');

# 2. Update error handling
# Set error_reporting to not show errors in production
error_reporting(0);
ini_set('display_errors', 0);

# 3. Enable HTTPS
# Get SSL certificate (Let's Encrypt is free)
# Update all URLs to use https://

# 4. Update database URLs
# Change from localhost URLs to production URLs

# 5. Optimize database
# Create indexes for frequently queried fields
# Enable caching
```

### 3. Deploy to Hosting

**Using FTP:**
1. Connect via FTP client (FileZilla, WinSCP)
2. Upload all files (except vendor, node_modules)
3. Set proper permissions (755 for folders, 644 for files)

**Using Git:**
```bash
git init
git add .
git commit -m "Initial commit"
git push heroku main  # or git push origin main for GitHub
```

**Using cPanel:**
1. Zip the entire folder
2. Upload via File Manager
3. Extract in public_html

### 4. Configure Web Server

**Apache (.htaccess):**
```apache
<IfModule mod_rewrite.c>
    RewriteEngine On
    RewriteCond %{REQUEST_FILENAME} !-f
    RewriteCond %{REQUEST_FILENAME} !-d
    RewriteRule ^(.*)$ index.php?path=$1 [QSA,L]
</IfModule>

# Prevent directory listing
Options -Indexes

# Enable compression
<IfModule mod_deflate.c>
    AddOutputFilterByType DEFLATE text/html text/plain text/xml text/css text/javascript application/javascript
</IfModule>

# Cache static files
<IfModule mod_expires.c>
    ExpiresActive On
    ExpiresByType image/jpeg "access plus 1 year"
    ExpiresByType image/gif "access plus 1 year"
    ExpiresByType image/png "access plus 1 year"
    ExpiresByType text/css "access plus 1 month"
    ExpiresByType application/javascript "access plus 1 month"
</IfModule>
```

**Nginx:**
```nginx
server {
    listen 80;
    server_name yourdomain.com www.yourdomain.com;
    root /var/www/note_share;
    
    index index.php index.html;
    
    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }
    
    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php7.4-fpm.sock;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }
    
    # Cache static files
    location ~* \.(jpg|jpeg|png|gif|ico|css|js)$ {
        expires 365d;
        add_header Cache-Control "public, immutable";
    }
}
```

### 5. Enable HTTPS

```bash
# Using Certbot (Let's Encrypt)
sudo apt-get install certbot python3-certbot-nginx
sudo certbot certonly --nginx -d yourdomain.com

# Update Nginx/Apache config to use SSL certificate
```

### 6. Database Backups

Set up automatic Firebase backups:
1. Go to Firebase Console
2. Enable automated backups
3. Set backup location
4. Configure retention period

## Environment Variables

Create `.env` file in root:
```bash
# Firebase
FIREBASE_API_KEY=your_api_key
FIREBASE_AUTH_DOMAIN=your_domain
FIREBASE_DATABASE_URL=your_database_url
FIREBASE_PROJECT_ID=your_project_id

# Google OAuth
GOOGLE_CLIENT_ID=your_client_id
GOOGLE_CLIENT_SECRET=your_client_secret

# Payment
RAZORPAY_KEY_ID=your_key_id
RAZORPAY_KEY_SECRET=your_key_secret

# Email
SMTP_USERNAME=your_email
SMTP_PASSWORD=your_password

# App
APP_ENV=production
APP_DEBUG=false
```

Load in PHP:
```php
$env = parse_ini_file('.env');
define('FIREBASE_API_KEY', $env['FIREBASE_API_KEY']);
// ... etc
```

## Monitoring & Maintenance

### 1. Monitor Performance
- Set up Google Analytics
- Monitor Firebase usage
- Track coin transactions
- Monitor server logs

### 2. Regular Backups
```bash
# Backup entire application
tar -czf note_share_backup_$(date +%Y%m%d).tar.gz /var/www/note_share/

# Backup Firebase database
gcloud firestore export gs://your-bucket/backup_$(date +%Y%m%d)
```

### 3. Security Updates
- Keep PHP updated
- Update dependencies (composer update)
- Monitor Firebase security advisories
- Regular security audits

### 4. Performance Optimization
- Enable database indexing
- Use CDN for static files
- Implement caching
- Optimize images

## Troubleshooting Production Issues

### Database Connection Issues
```bash
# Check Firebase connectivity
# Verify API keys
# Check security rules
# Monitor Firebase quota usage
```

### Payment Issues
```bash
# Verify Razorpay credentials
# Check payment gateway logs
# Monitor failed transactions
# Test with test API keys first
```

### Email Issues
```bash
# Verify SMTP credentials
# Check firewall rules
# Test email sending
# Monitor email logs
```

## Rollback Procedure

```bash
# Keep previous version
git checkout previous_commit_hash

# Or restore from backup
tar -xzf note_share_backup_20240115.tar.gz
```

## Performance Benchmarks

- Page load time: < 2 seconds
- Database queries: < 100ms
- Image upload: < 5MB max
- Concurrent users: Support 1000+ users

## Scaling Considerations

- Use Firebase Firestore (scalable alternative)
- Implement CDN for global distribution
- Use caching (Redis, Memcached)
- Load balancing for multiple servers
- Database replication for high availability

---

**Need Help?** Contact: support@noteshare.com
