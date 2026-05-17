# 🚀 NoteShare - Production Deployment Checklist

## Pre-Deployment Checklist

### ✅ **Server Requirements**
- [ ] PHP 7.4 or higher installed
- [ ] Required PHP extensions: `curl`, `json`, `session`, `mbstring`
- [ ] Web server (Apache/Nginx) configured
- [ ] SSL certificate installed (HTTPS required)
- [ ] Domain name configured

### ✅ **Firebase Setup**
- [ ] Firebase project created
- [ ] Realtime Database enabled
- [ ] Authentication configured
- [ ] Security rules deployed
- [ ] API keys generated

### ✅ **Email Configuration**
- [ ] Gmail account with 2FA enabled
- [ ] App-specific password generated
- [ ] SMTP settings configured in `.env`

### ✅ **Payment Gateway** (Optional)
- [ ] Razorpay account created
- [ ] API keys configured
- [ ] Webhook URLs set up

## Deployment Steps

### 1. **Run Deployment Script**
```bash
# For Linux/Mac
chmod +x deploy.sh
./deploy.sh

# For Windows
deploy.bat
```

### 2. **Configure Environment**
Edit `.env` file with your actual credentials:
```bash
# Firebase
FIREBASE_API_KEY=your_actual_api_key
FIREBASE_PROJECT_ID=your_project_id

# Email
SMTP_USERNAME=your-email@gmail.com
SMTP_PASSWORD=your-app-password

# Admin
ADMIN_USERNAME=admin
ADMIN_PASSWORD=secure_password
```

### 3. **Upload Files**
Upload all files to your web server:
```bash
# Example using rsync
rsync -avz --exclude='.git' --exclude='logs/*' --exclude='data/*' ./ user@server:/path/to/webroot/

# Or use FTP/SFTP
```

### 4. **Set Permissions**
```bash
chmod 755 /path/to/note_share
chmod 644 /path/to/note_share/*.php
chmod 755 /path/to/note_share/data
chmod 755 /path/to/note_share/logs
```

### 5. **Test Application**
- [ ] Access main site: `https://yourdomain.com`
- [ ] Test user registration with OTP
- [ ] Test admin login: `https://yourdomain.com/admin_login.php`
- [ ] Test messaging functionality
- [ ] Test note upload/download

## Post-Deployment Tasks

### 🔧 **Security Hardening**
- [ ] Change default admin password
- [ ] Enable HTTPS redirect
- [ ] Configure firewall rules
- [ ] Set up monitoring/alerts
- [ ] Regular backup schedule

### 📊 **Monitoring Setup**
- [ ] PHP error logging enabled
- [ ] Firebase usage monitoring
- [ ] Email delivery monitoring
- [ ] User activity tracking

### 🔄 **Maintenance Tasks**
- [ ] Database cleanup (old logs)
- [ ] User session cleanup
- [ ] Firebase usage optimization
- [ ] Security updates

## Troubleshooting

### Common Issues

**❌ Emails not sending**
- Check SMTP credentials in `.env`
- Verify Gmail app password
- Check spam folder

**❌ Firebase connection failed**
- Verify API keys in `js/firebase-config.js`
- Check Firebase security rules
- Ensure project is not paused

**❌ Admin login not working**
- Check admin credentials in `.env`
- Verify session configuration
- Check PHP session path permissions

**❌ File uploads failing**
- Check upload directory permissions
- Verify PHP upload limits
- Check Firebase Storage configuration

## Performance Optimization

### Server Configuration
```apache
# .htaccess for Apache
<IfModule mod_rewrite.c>
    RewriteEngine On
    RewriteCond %{HTTPS} off
    RewriteRule ^(.*)$ https://%{HTTP_HOST}%{REQUEST_URI} [L,R=301]
</IfModule>

# PHP Configuration
upload_max_filesize = 10M
post_max_size = 10M
max_execution_time = 300
memory_limit = 256M
```

### Database Optimization
- Enable Firebase indexing for frequently queried fields
- Implement data archiving for old messages
- Set up Firebase usage quotas

## Support & Maintenance

### Regular Tasks
- [ ] Weekly: Check error logs
- [ ] Monthly: Database cleanup
- [ ] Quarterly: Security audit
- [ ] Annually: PHP/Firebase updates

### Contact Information
- **Technical Support**: [your-email@domain.com]
- **Documentation**: Check `README.md` and `DEPLOYMENT_SUMMARY.md`
- **Firebase Console**: [console.firebase.google.com]
- **Server Logs**: Check `/logs/` directory

---

## 🎉 Deployment Complete!

Your NoteShare application is now live and ready for VIT students to use!

**Live URLs:**
- Main Site: `https://yourdomain.com`
- Admin Panel: `https://yourdomain.com/admin_login.php`

**Next Steps:**
1. Announce the platform to VIT students
2. Monitor initial usage and feedback
3. Scale server resources as needed
4. Add new features based on user feedback

---

*Deployment completed on: $(date)*
*NoteShare v1.0.0 - Production Ready*