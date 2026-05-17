# NoteShare - Quick Reference Guide

## 🚀 Getting Started (5 Minutes)

### Step 1: Setup Firebase
1. Go to https://firebase.google.com
2. Create new project "NoteShare"
3. Get your config from Project Settings
4. Copy to `js/firebase-config.js`

### Step 2: Start Server
```bash
cd note_share
php -S localhost:8000
# Open: http://localhost:8000
```

### Step 3: Test Login
- Email: `test@vitstudent.ac.in`
- Click "Sign in with Gmail"

---

## 📋 Feature Quick Links

| Feature | File | Key Function |
|---------|------|--------------|
| 🏠 Home | `index.php` | Main dashboard |
| 🔐 Login | `login.php` | Login form + Create Account button |
| 👤 Create Account | `create_account.php` | 3-step account creation process |
| 🛒 Buy | `buy_notes.php` | Search & purchase |
| 📝 Sell | `sell_notes.php` | Upload & sell |
| 👥 Share | `share_notes.php` | Batch sharing |
| 🔄 Rent | `rent_notes.php` | Temporary access |
| 💰 Coins | `coins.php` | Purchase & manage |
| 💬 Chat | `messages.php` | Real-time messaging |
| 👤 Profile | `profile.php` | User profile |
| ⚙️ Settings | `edit_profile.php` | Edit details |

---

## 🔑 Important API Keys to Configure

```php
// firebase_config.js
const firebaseConfig = {
    apiKey: "YOUR_API_KEY",
    authDomain: "your-project.firebaseapp.com",
    databaseURL: "https://your-project.firebaseio.com",
    projectId: "your-project-id",
    storageBucket: "your-project.appspot.com",
    messagingSenderId: "YOUR_MESSAGING_SENDER_ID",
    appId: "YOUR_APP_ID"
};

// Firebase Database Initialization
firebase.initializeApp(firebaseConfig);
```

---

## 💾 Database Quick Reference

### Create User
```javascript
firebase.database().ref('users/' + userId).set({
    email: email,
    name: name,
    year: 1,
    department: 'CSE',
    coins: 10,
    rating: 0,
    created_at: Date.now()
});
```

### Add Note
```javascript
firebase.database().ref('notes').push({
    subject_name: 'Data Structures',
    course_code: 'CSE101',
    faculty_name: 'Dr. Smith',
    slot: 'morning',
    year: 1,
    description: 'Complete notes with examples',
    price: 50,
    seller_id: userId,
    status: 'available',
    created_at: Date.now()
});
```

### Send Message
```javascript
firebase.database().ref('messages').push({
    chat_id: chatId,
    sender_id: userId,
    sender_name: userName,
    text: messageText,
    timestamp: Date.now()
});
```

---

## 💰 Coin System Reference

| Action | Coins | Amount |
|--------|-------|--------|
| Message someone | 1 | ₹0.10 |
| Rent notes | 3-10 | Variable |
| Priority listing | 5 | ₹0.50 |
| Buy 100 coins | - | ₹10 |
| Buy 500 coins | - | ₹45 |
| Sell notes (₹100) | +10 | Earn |

---

## 🔍 Search & Filter Examples

### Search Notes by Course Code
```javascript
firebase.database().ref('notes').orderByChild('course_code')
    .equalTo('CSE101')
    .once('value').then(snapshot => {
        // Process results
    });
```

### Search Shared Notes by Batch
```javascript
firebase.database().ref('shared_notes')
    .orderByChild('batch')
    .equalTo('morning')
    .once('value').then(snapshot => {
        // Process results
    });
```

---

## 🔐 Security Rules Quick Setup

```json
{
  "rules": {
    "users": {
      "$uid": {
        ".read": "$uid === auth.uid",
        ".write": "$uid === auth.uid",
        "coins": {
          ".validate": "newData.isNumber()"
        }
      }
    },
    "notes": {
      ".read": true,
      ".indexOn": ["seller_id", "course_code"]
    }
  }
}
```

---

## 🎨 CSS Classes Quick Reference

```html
<!-- Cards -->
<div class="card">
    <div class="card-header">Title</div>
    <div class="card-body">Content</div>
</div>

<!-- Buttons -->
<button class="btn btn-primary">Primary</button>
<button class="btn btn-success">Success</button>
<button class="btn btn-warning">Warning</button>

<!-- Alerts -->
<div class="alert alert-success">Success message</div>
<div class="alert alert-danger">Error message</div>

<!-- Forms -->
<div class="mb-3">
    <label class="form-label">Label</label>
    <input class="form-control" type="text">
</div>
```

---

## 📱 Responsive Breakpoints

```css
Mobile:    < 576px
Tablet:    576px - 768px
Desktop:   768px - 992px
Large:     992px - 1200px
XLarge:    > 1200px

/* Bootstrap Grid */
col-12       /* Mobile - full width */
col-md-6     /* Tablet - 50% */
col-lg-4     /* Desktop - 33% */
```

---

## 🐛 Common Issues & Solutions

### Issue: "Firebase config is missing"
**Solution**: Update `js/firebase-config.js` with valid credentials

### Issue: "Can't send message - insufficient coins"
**Solution**: User needs to buy coins first via `coins.php`

### Issue: "Login failing"
**Solution**: 
1. Check email ends with @vitstudent.ac.in
2. Clear browser cache
3. Verify Firebase authentication enabled

### Issue: "Notes not loading"
**Solution**: 
1. Check Firebase database has data
2. Verify security rules allow read access
3. Check browser console for errors

### Issue: "Payment failed"
**Solution**:
1. Verify Razorpay credentials
2. Check API keys in config
3. Test with test mode first

---

## 📊 Database Query Examples

### Get All User's Notes
```javascript
firebase.database().ref('notes')
    .orderByChild('seller_id')
    .equalTo(userId)
    .once('value')
    .then(snapshot => {
        snapshot.forEach(child => {
            console.log(child.val());
        });
    });
```

### Get User's Coin Balance
```javascript
firebase.database().ref('users/' + userId + '/coins')
    .once('value')
    .then(snapshot => {
        console.log('Coins:', snapshot.val());
    });
```

### Get User's Messages
```javascript
firebase.database().ref('chats')
    .orderByChild('created_at')
    .limitToLast(10)
    .once('value')
    .then(snapshot => {
        // Last 10 chats
    });
```

---

## 🔄 Real-Time Listeners

### Listen to Note Updates
```javascript
firebase.database().ref('notes/' + noteId)
    .on('value', snapshot => {
        const note = snapshot.val();
        // Update UI
    });
```

### Listen for New Messages
```javascript
firebase.database().ref('messages')
    .orderByChild('chat_id')
    .equalTo(chatId)
    .on('child_added', snapshot => {
        const message = snapshot.val();
        // Add to chat UI
    });
```

---

## 💡 Best Practices

1. **Always validate email format**
   ```php
   if (!str_ends_with($email, '@vitstudent.ac.in')) {
       // Reject
   }
   ```

2. **Check coins before deducting**
   ```javascript
   if (coins < requiredCoins) {
       alert('Insufficient coins');
       return;
   }
   ```

3. **Compress images before upload**
   ```javascript
   // Resize images to max 500x500px
   ```

4. **Use batch operations for multiple updates**
   ```javascript
   const updates = {};
   updates['notes/' + id + '/likes'] = newCount;
   updates['users/' + userId + '/liked_notes/' + id] = true;
   firebase.database().ref().update(updates);
   ```

5. **Add indexes for frequently queried fields**
   ```json
   ".indexOn": ["seller_id", "course_code", "created_at"]
   ```

---

## 🚀 Deployment Checklist

- [ ] Firebase project created and configured
- [ ] Google OAuth credentials set up
- [ ] Razorpay payment gateway configured
- [ ] Email SMTP configured
- [ ] Security rules set in Firebase
- [ ] Database backups enabled
- [ ] HTTPS certificate installed
- [ ] Error logging configured
- [ ] Performance monitoring enabled
- [ ] Documentation reviewed

---

## 📞 Quick Support

**Firebase Issues**: https://firebase.google.com/support  
**Razorpay Issues**: https://razorpay.com/support  
**PHP Documentation**: https://php.net  
**Bootstrap Help**: https://getbootstrap.com  

---

## 🎓 Learning Resources

- [Firebase Documentation](https://firebase.google.com/docs)
- [JavaScript Guide](https://developer.mozilla.org/en-US/docs/Web/JavaScript/Guide)
- [PHP Manual](https://www.php.net/manual)
- [Bootstrap 5 Docs](https://getbootstrap.com/docs/5.0)
- [HTML/CSS Guide](https://developer.mozilla.org/en-US/docs/Web)

---

**Created**: December 2024  
**Version**: 1.0.0  
**Status**: Ready to Deploy  

For complete documentation, see [README.md](README.md) and [PROJECT_SUMMARY.md](PROJECT_SUMMARY.md)
