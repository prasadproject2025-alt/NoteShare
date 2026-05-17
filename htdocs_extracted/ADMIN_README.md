# Admin System Documentation

## Overview
The admin system provides administrators with access to view and manage all uploaded files, user feedback, and system statistics.

## Features

### 1. Admin Login
- **URL**: `/admin_login.php`
- **Credentials**: `admin` / `admin123` (change in production)
- **Security**: Session-based authentication

### 2. Admin Dashboard
- **URL**: `/admin_dashboard.php`
- **Features**:
  - System statistics (users, notes, chats)
  - View all uploaded files with images
  - Browse user feedback and messages
  - User management (view users, reset coins)

### 3. File Management
- View all uploaded notes (sell, share, rent)
- Filter by file type
- Preview images in modal dialogs
- See upload timestamps and user information

### 4. Feedback & Messages
- View all chat conversations
- See message types and timestamps
- Monitor user communications

### 5. User Management
- View all registered users
- See user details (name, email, coins)
- Reset user coins to 0
- View registration dates

## Security Notes

### Production Deployment
1. **Change Default Credentials**: Update the admin username/password in `admin_login.php`
2. **Use Secure Passwords**: Implement proper password hashing
3. **Add IP Restrictions**: Consider restricting admin access to specific IPs
4. **Enable HTTPS**: Ensure admin pages are served over HTTPS
5. **Session Security**: Add session timeout and regeneration

### Code Security
- Admin authentication is session-based
- All admin pages check for `$_SESSION['admin_logged_in']`
- Firebase data access is read-only for admin functions

## Database Structure

The admin system reads from existing Firebase collections:
- `users` - User accounts
- `notes` - Sell notes
- `shared_notes` - Shared notes
- `rental_notes` - Rental notes
- `chats` - User conversations

## Navigation

- Admin login link appears in main navigation for non-logged-in users
- Admin panel link appears in navigation when admin is logged in
- Admin logout clears session and redirects to login

## Future Enhancements

1. **User Deletion**: Add ability to delete user accounts
2. **File Deletion**: Add ability to remove inappropriate files
3. **Audit Logs**: Track admin actions
4. **Email Notifications**: Notify admins of new uploads
5. **Advanced Filtering**: Filter files by date, user, etc.
6. **Export Data**: Export user/file data to CSV
7. **Role-Based Access**: Multiple admin levels