# NoteShare - VIT Note Sharing Platform

A comprehensive web application for VIT students to buy, sell, rent, and share notes with classmates.

## Features

### Authentication
- **Email OTP Authentication**: First-time users verify via OTP sent to VIT email, then create password
- **Secure Session Management**: PHP session-based authentication

### Core Functionalities

#### 1. **Buy Notes**
- Search notes by course code and VIT slot (A1-G2)
- View detailed note information (subject, faculty, price)
- Contact sellers through messaging system
- Uses coins to initiate contact

#### 2. **Sell Notes**
- Upload notes with detailed descriptions
- Set pricing for notes
- Track selling history and earnings
- View buyer inquiries

#### 3. **Share Notes**
- Share notes within your batch (VIT slots A1-G2)
- Free sharing with batch-mates
- Organized by subject and course code

#### 4. **Rent Notes**
- Rent notes for temporary access
- Set daily rental prices and rental period
- Manage rental agreements
- Optional rental extension

#### 5. **Coin System**
- **Exchange Rate**: 100 coins = ₹10
- **Usage**: 1 coin required to message each person
- **Earning**: Gain coins from selling notes
- **Purchase**: Buy coins via integrated payment gateway

#### 6. **Messaging System**
- Real-time chat between buyers and sellers
- Coin-based messaging (1 coin per contact)
- Message history and conversation management
- Real-time updates using Firebase

#### 7. **Profile Management**
- View profile information
- Liked notes collection
- Selling history
- Rating system
- Member since date

## Project Structure

```
note_share/
├── index.php                 # Home/Dashboard
├── login.php                 # Login form + Create Account button
├── create_account.php        # 3-step account creation (email → OTP → password)
├── buy_notes.php             # Browse and buy notes
├── sell_notes.php            # Sell your notes
├── share_notes.php           # Share with batch-mates
├── rent_notes.php            # Rent notes
├── coins.php                 # Coin management
├── messages.php              # Messaging system
├── profile.php               # User profile
├── edit_profile.php          # Profile editing
├── logout.php                # Logout
│
├── includes/
│   ├── header.php            # Navigation header
│   ├── footer.php            # Footer
│   └── firebase_config.php   # Firebase configuration
│
├── auth/
│   └── google_auth.php       # Google OAuth handler
│
├── js/
│   └── firebase-config.js    # Firebase SDK config
│
├── css/
│   └── style.css             # Custom styles
│
└── README.md                 # This file
```

## Technology Stack

### Backend
- **PHP 7.4+**: Server-side logic
- **Session Management**: User authentication

### Frontend
- **HTML5**: Markup
- **Bootstrap 5**: Responsive UI framework
- **JavaScript (ES6+)**: Client-side interactivity
- **Firebase Realtime Database**: Real-time data synchronization

### Database
- **Firebase Realtime Database**: All data storage
  - User profiles
  - Notes (buying/selling/renting)
  - Shared notes
  - Messages and chats
  - Coin transactions
  - Ratings and reviews

## Database Schema

### Users Collection
```
users/{userId}/
├── email
├── name
├── year
├── department
├── coins
├── rating
├── created_at
├── liked_notes/{noteId}
└── profile_image
```

### Notes Collection (Buying/Selling)
```
notes/{noteId}/
├── subject_name
├── course_code
├── faculty_name
├── slot (A1-G2 VIT slot system)
├── year
├── description
├── price
├── seller_id
├── seller_name
├── seller_email
├── image_base64
├── ocr_text
├── status (available/sold)
├── created_at
└── likes
```

### Shared Notes Collection
```
shared_notes/{noteId}/
├── subject_name
├── course_code
├── faculty_name
├── year
├── batch (A1-G2 VIT slot system)
├── description
├── image_base64
├── sharer_id
├── sharer_name
├── created_at
└── likes
```

### Rental Notes Collection
```
rental_notes/{noteId}/
├── subject_name
├── course_code
├── faculty_name
├── slot
├── year
├── daily_price
├── rental_period
├── description
├── image_base64
├── renter_id
├── renter_name
├── available (boolean)
├── can_extend (boolean)
└── created_at
```

### Chats Collection
```
chats/{chatId}/
├── buyer_id / user1_id
├── seller_id / user2_id
├── note_id (optional)
├── created_at
├── last_message
└── last_message_time
```

### Messages Collection
```
messages/{messageId}/
├── chat_id
├── sender_id
├── sender_name
├── text
└── timestamp
```

### Coin Transactions Collection
```
coin_transactions/{transactionId}/
├── user_id
├── type (purchase/spend)
├── coins
├── price (for purchases)
├── timestamp
└── description
```

## Installation & Setup

### Prerequisites
- PHP 7.4 or higher
- Web server (Apache/Nginx)
- Firebase account
- Modern web browser

### Steps

1. **Clone/Download the project**
   ```bash
   cd note_share
   ```

2. **Setup Firebase**
   - Create a Firebase project at https://firebase.google.com
   - Enable Realtime Database
   - Get your Firebase config credentials
   - Update `js/firebase-config.js` with your credentials

   ```javascript
   const firebaseConfig = {
       apiKey: "YOUR_KEY",
       authDomain: "your-project.firebaseapp.com",
       databaseURL: "https://your-project.firebaseio.com",
       projectId: "your-project-id",
       storageBucket: "your-project.appspot.com",
       messagingSenderId: "YOUR_SENDER_ID",
       appId: "YOUR_APP_ID"
   };
   ```

3. **Setup Google OAuth (Optional but Recommended)**
   - Create OAuth credentials in Google Cloud Console
   - Configure authorized redirect URIs
   - Update `auth/google_auth.php` with credentials

4. **Configure Gmail SMTP for OTP Emails**
   - Enable 2-Factor Authentication on your Gmail account
   - Generate an App Password from Google Account settings
   - Update `.env` file with your Gmail credentials:
   
   ```env
   GMAIL_USERNAME=your-gmail@gmail.com
   GMAIL_APP_PASSWORD=your-16-character-app-password
   FROM_EMAIL=your-gmail@gmail.com
   FROM_NAME=NoteShare
   ```

5. **Configure Payment Gateway** (Razorpay/PayPal)
   - Create account on Razorpay (for Indian payments)
   - Add API keys to configuration
   - Update `coins.php` with payment integration

6. **Deploy**
   - Upload files to your web server
   - Ensure proper directory permissions
   - Access via http://localhost/note_share or your domain

### Testing OTP Emails

For development/testing, OTP codes are logged to `logs/otp_log.txt`. In production, emails will be sent to Gmail addresses.

**To enable actual Gmail sending:**
1. Set up Gmail App Password
2. Update `.env` file with real credentials
3. Ensure PHP mail() function is configured for SMTP

## Usage Guide

### For Students

**Buying Notes:**
1. Go to "Buy Notes"
2. Search by course code or slot
3. View note details
4. Click "Contact Seller" (costs 1 coin)
5. Start negotiating in chat

**Selling Notes:**
1. Click "Sell Notes"
2. Upload note image
3. Fill in details (subject, course code, faculty, etc.)
4. Set price
5. Wait for buyer inquiries

**Sharing Notes:**
1. Go to "Share Notes"
2. Select your VIT slot (A1-G2)
3. Upload and describe your notes
4. Notes are shared with batch-mates

**Managing Coins:**
1. Go to "Coins"
2. Buy coin packages (₹10 = 100 coins)
3. Use coins for messaging
4. View transaction history

### For Administrators

- Monitor user activity
- Manage inappropriate content
- Handle disputes
- View platform statistics

## Security Considerations

1. **Email Validation**: Only @vitstudent.ac.in emails allowed
2. **Session Security**: Secure session handling
3. **Data Validation**: All inputs validated
4. **Firebase Rules**: Configure proper security rules
5. **Payment Security**: Use HTTPS, secure payment gateways
6. **Image Handling**: Validate uploaded images
7. **Rate Limiting**: Implement to prevent abuse

## Coin Economy

### Pricing Model
- **100 coins**: ₹10 (₹0.10 per coin)
- **500 coins**: ₹45 (₹0.09 per coin) - 10% discount
- **1000 coins**: ₹80 (₹0.08 per coin) - 20% discount
- **5000 coins**: ₹350 (₹0.07 per coin) - 30% discount

### Coin Usage
- **1 coin**: Message one person
- **3 coins**: Access rented notes
- **5 coins**: Priority listing boost
- **Earn**: Get coins from selling notes (₹1 sale = 10 coins)

## Firebase Security Rules

```json
{
  "rules": {
    "users": {
      "$uid": {
        ".read": "$uid === auth.uid",
        ".write": "$uid === auth.uid",
        "coins": {
          ".validate": "newData.isNumber() && newData.val() >= 0"
        }
      }
    },
    "notes": {
      ".read": true,
      "$noteId": {
        ".write": "root.child('notes').child($noteId).child('seller_id').val() === auth.uid"
      }
    },
    "messages": {
      ".read": "query.limitToFirst(100).val() !== null",
      ".write": "root.child('chats').child(newData.child('chat_id').val()).exists()"
    },
    "chats": {
      ".read": true,
      ".write": true
    }
  }
}
```

## API Integration Points

### Payment Processing
- Integrate Razorpay for Indian payments
- Handle payment callbacks
- Update user coins on successful payment

### Email Notifications
- Send confirmation emails
- Notify on new messages
- Alert on note sales

### Cloud Storage
- Store images in Firebase Storage
- Compress images for faster loading
- Generate OCR text from images

## Performance Optimization

1. **Lazy Loading**: Load notes on demand
2. **Caching**: Cache user data in session
3. **Image Optimization**: Compress uploaded images
4. **Database Indexing**: Index frequently queried fields
5. **CDN**: Use CDN for static assets

## Troubleshooting

**Firebase Connection Issues:**
- Check Firebase config credentials
- Verify network connectivity
- Check Firebase security rules

**Login Issues:**
- Verify email format (@vitstudent.ac.in)
- Clear browser cache
- Check session settings

**Coin System Issues:**
- Verify payment gateway integration
- Check transaction logs
- Ensure coin balance updates

## Future Enhancements

1. **Rating & Review System**: Rate sellers and quality
2. **Advanced Search**: Filter by rating, recent uploads
3. **Wishlist Feature**: Save notes for later
4. **Analytics Dashboard**: View selling statistics
5. **Mobile App**: React Native/Flutter mobile version
6. **AI Recommendations**: Suggest relevant notes
7. **Note Comparison**: Compare notes before buying
8. **Bulk Discount**: Group purchases with discounts
9. **Subscription Model**: Monthly unlimited access
10. **Social Features**: Follow sellers, view feeds

## Support & Contribution

For issues or contributions, please contact:
- Email: support@noteshare.com
- Issues: Report bugs with detailed description
- Features: Suggest new features with use cases

## License

This project is licensed under MIT License.

## Disclaimer

This is an educational platform. Users are responsible for ensuring they have rights to share/sell notes. The platform is not responsible for copyright infringement.

## Contact

**NoteShare Team**
- Website: https://noteshare.vit.edu
- Email: support@noteshare.com
- Support Hours: 10 AM - 6 PM IST (Mon-Fri)

---

**Version**: 1.0.0  
**Last Updated**: December 2024  
**Maintenance**: Active
