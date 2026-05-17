<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

include 'includes/header.php';

$chat_id = $_GET['chat_id'] ?? '';
?>

<style>
/* WhatsApp-like styling */
.messaging-container {
    height: 85vh;
    background: #f0f0f0;
    border-radius: 10px;
    overflow: hidden;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
}

.sidebar {
    background: #ffffff;
    border-right: 1px solid #e0e0e0;
    height: 100%;
}

.chat-list {
    height: calc(100% - 60px);
    overflow-y: auto;
}

.conversation-item {
    padding: 15px;
    border-bottom: 1px solid #f0f0f0;
    cursor: pointer;
    transition: background-color 0.2s;
    display: flex;
    align-items: center;
}

.conversation-item:hover {
    background-color: #f5f5f5;
}

.conversation-item.active {
    background-color: #e1f5fe;
}

.conversation-avatar {
    width: 50px;
    height: 50px;
    border-radius: 50%;
    background: #007bff;
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: bold;
    margin-right: 15px;
    flex-shrink: 0;
}

.conversation-info {
    flex: 1;
    min-width: 0;
}

.conversation-name {
    font-weight: 600;
    margin-bottom: 2px;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.conversation-last-message {
    color: #666;
    font-size: 14px;
    margin-bottom: 2px;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.conversation-time {
    color: #999;
    font-size: 12px;
}

.chat-area {
    height: 100%;
    display: flex;
    flex-direction: column;
}

.chat-header {
    background: #ffffff;
    padding: 15px 20px;
    border-bottom: 1px solid #e0e0e0;
    display: flex;
    align-items: center;
}

.chat-avatar {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    background: #007bff;
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: bold;
    margin-right: 15px;
}

.chat-info h5 {
    margin: 0;
    font-size: 16px;
}

.chat-info p {
    margin: 0;
    color: #666;
    font-size: 14px;
}

.messages-container {
    flex: 1;
    padding: 20px;
    overflow-y: auto;
    background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><text fill="%23f0f0f0" font-size="20" y="50%">💬</text></svg>') repeat;
    background-size: 50px 50px;
}

.message-bubble {
    max-width: 70%;
    margin-bottom: 10px;
    padding: 10px 15px;
    border-radius: 18px;
    position: relative;
    word-wrap: break-word;
}

.message-sent {
    background: #007bff;
    color: white;
    margin-left: auto;
    border-bottom-right-radius: 4px;
}

.message-received {
    background: #ffffff;
    color: #333;
    margin-right: auto;
    border-bottom-left-radius: 4px;
    box-shadow: 0 1px 2px rgba(0,0,0,0.1);
}

.message-time {
    font-size: 11px;
    opacity: 0.7;
    margin-top: 5px;
    text-align: right;
}

.message-received .message-time {
    text-align: left;
}

.message-status {
    font-size: 11px;
    margin-left: 5px;
}

.typing-indicator {
    display: none;
    padding: 10px 15px;
    background: #ffffff;
    border-radius: 18px;
    margin-bottom: 10px;
    box-shadow: 0 1px 2px rgba(0,0,0,0.1);
}

.typing-dots {
    display: inline-block;
}

.typing-dots span {
    display: inline-block;
    width: 6px;
    height: 6px;
    border-radius: 50%;
    background: #007bff;
    animation: typing 1.4s infinite;
}

.typing-dots span:nth-child(2) {
    animation-delay: 0.2s;
}

.typing-dots span:nth-child(3) {
    animation-delay: 0.4s;
}

@keyframes typing {
    0%, 60%, 100% {
        transform: translateY(0);
        opacity: 0.4;
    }
    30% {
        transform: translateY(-10px);
        opacity: 1;
    }
}

.message-input-area {
    background: #ffffff;
    padding: 15px 20px;
    border-top: 1px solid #e0e0e0;
    display: flex;
    align-items: center;
}

.message-input {
    flex: 1;
    border: none;
    outline: none;
    padding: 10px 15px;
    border-radius: 25px;
    background: #f5f5f5;
    margin-right: 10px;
}

.message-input:focus {
    background: #ffffff;
    box-shadow: 0 0 0 2px #007bff;
}

.send-button {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    background: #007bff;
    border: none;
    color: white;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: background-color 0.2s;
}

.send-button:hover {
    background: #0056b3;
}

.send-button:disabled {
    background: #cccccc;
    cursor: not-allowed;
}

.empty-state {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    height: 100%;
    color: #666;
}

.empty-state i {
    font-size: 48px;
    margin-bottom: 20px;
    opacity: 0.5;
}

.online-status {
    width: 12px;
    height: 12px;
    border-radius: 50%;
    background: #28a745;
    border: 2px solid white;
    position: absolute;
    bottom: 0;
    right: 0;
}

.offline-status {
    background: #6c757d;
}

.search-container {
    padding: 15px;
    border-bottom: 1px solid #e0e0e0;
}

.search-input {
    width: 100%;
    padding: 10px 15px;
    border: none;
    border-radius: 25px;
    background: #f5f5f5;
    outline: none;
}

.search-input:focus {
    background: #ffffff;
    box-shadow: 0 0 0 2px #007bff;
}

.new-chat-button {
    position: absolute;
    bottom: 20px;
    right: 20px;
    width: 56px;
    height: 56px;
    border-radius: 50%;
    background: #007bff;
    border: none;
    color: white;
    font-size: 24px;
    cursor: pointer;
    box-shadow: 0 4px 12px rgba(0,123,255,0.3);
    transition: all 0.2s;
}

.new-chat-button:hover {
    transform: scale(1.1);
    box-shadow: 0 6px 16px rgba(0,123,255,0.4);
}

@media (max-width: 768px) {
    .messaging-container {
        height: 100vh;
        border-radius: 0;
    }

    .sidebar {
        display: none;
    }

    .chat-area {
        display: block !important;
    }
}

/* User search styles */
.user-search-item {
    padding: 12px;
    border-bottom: 1px solid #f0f0f0;
    cursor: pointer;
    transition: background-color 0.2s;
}

.user-search-item:hover {
    background-color: #f8f9fa;
}

.user-avatar {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    background: #007bff;
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: bold;
    flex-shrink: 0;
}
</style>

<main class="container-fluid mt-3">
    <div class="row justify-content-center">
        <div class="col-12 col-lg-10">
            <div class="messaging-container">
                <div class="row h-100">
                    <!-- Sidebar with conversations -->
                    <div class="col-md-4 sidebar d-none d-md-block">
                        <div class="search-container">
                            <div class="d-flex">
                                <input type="text" class="search-input flex-grow-1" placeholder="Search conversations..." id="search-input">
                                <button class="btn btn-primary ms-2" data-bs-toggle="modal" data-bs-target="#newChatModal">
                                    <i class="fas fa-plus"></i>
                                </button>
                            </div>
                        </div>
                        <div class="chat-list" id="conversations-list">
                            <div class="empty-state">
                                <i class="fas fa-comments"></i>
                                <h5>No conversations yet</h5>
                                <p>Start a new conversation to begin messaging</p>
                            </div>
                        </div>
                    </div>

                    <!-- Chat area -->
                    <div class="col-md-8 chat-area" id="chat-area">
                        <?php if ($chat_id): ?>
                            <div class="chat-header d-flex justify-content-between align-items-center" id="chat-header">
                                <div class="d-flex align-items-center">
                                    <div class="chat-avatar" id="chat-avatar">?</div>
                                    <div class="chat-info">
                                        <h5 id="chat-name">Loading...</h5>
                                        <p id="chat-status">Offline</p>
                                    </div>
                                </div>
                                <button class="btn btn-outline-danger btn-sm" onclick="deleteConversation()" title="Delete chat">
                                    <i class="fas fa-trash-alt"></i>
                                </button>
                            </div>
                            <div class="messages-container" id="messages-container">
                                <div class="typing-indicator" id="typing-indicator">
                                    <div class="typing-dots">
                                        <span></span>
                                        <span></span>
                                        <span></span>
                                    </div>
                                </div>
                            </div>
                            <div class="message-input-area">
                                <input type="text" class="message-input" id="message-input" placeholder="Type a message...">
                                <button class="send-button" id="send-button" disabled>
                                    <i class="fas fa-paper-plane"></i>
                                </button>
                            </div>
                        <?php else: ?>
                            <div class="empty-state">
                                <i class="fas fa-comments"></i>
                                <h5>Select a conversation</h5>
                                <p>Choose a conversation from the sidebar to start messaging</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- New Chat Modal -->
    <div class="modal fade" id="newChatModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Start New Conversation</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="text" class="form-control mb-3" id="user-search" placeholder="Search users by name...">
                    <div id="user-search-results"></div>
                </div>
            </div>
        </div>
    </div>
</main>

<!-- New Chat Button (Mobile) -->
<button class="new-chat-button d-md-none" onclick="openNewChatModal()">
    <i class="fas fa-plus"></i>
</button>

<?php include 'includes/footer.php'; ?>

<!-- Firebase SDK (compat builds to support namespaced APIs used below) -->
<script src="https://www.gstatic.com/firebasejs/9.23.0/firebase-app-compat.js"></script>
<script src="https://www.gstatic.com/firebasejs/9.23.0/firebase-database-compat.js"></script>
<script src="https://www.gstatic.com/firebasejs/9.23.0/firebase-auth-compat.js"></script>

<script src="js/firebase-config.js"></script>
<script>
const userId = '<?php echo $_SESSION['user_id']; ?>';
const userName = '<?php echo $_SESSION['user_name']; ?>';
const userEmail = '<?php echo $_SESSION['user_email']; ?>';
const chatId = '<?php echo $chat_id; ?>';
let currentChatId = chatId;
let currentChatUsers = {};
let typingTimeouts = {};
let messageListener = null;

// Initialize Firebase auth
document.addEventListener('DOMContentLoaded', function() {
    initializeFirebaseAuth().then(() => {
        loadConversations();
        if (chatId) {
            loadChat(chatId);
        }
        setupMessageInput();
        setupSearch();
    });
});

function initializeFirebaseAuth() {
    return new Promise((resolve, reject) => {
        if (typeof firebase === 'undefined') {
            reject(new Error('Firebase SDK not loaded'));
            return;
        }

        const auth = firebase.auth();
        if (auth.currentUser) {
            resolve();
            return;
        }

        auth.signInAnonymously().then(() => {
            // Map Firebase auth UID to our PHP userId so rules can check membership
            const uid = auth.currentUser && auth.currentUser.uid;
            if (uid) {
                firebase.database().ref('sessions/' + uid).set(userId)
                    .catch(err => console.warn('Session mapping failed:', err));
            }
            resolve();
        }).catch(error => {
            console.warn('Anonymous auth failed:', error);
            resolve(); // Continue anyway
        });
    });
}

function setupMessageInput() {
    const input = document.getElementById('message-input');
    const sendButton = document.getElementById('send-button');

    if (!input || !sendButton) return;

    input.addEventListener('input', function() {
        sendButton.disabled = !this.value.trim();
        handleTyping();
    });

    input.addEventListener('keypress', function(e) {
        if (e.key === 'Enter' && !e.shiftKey) {
            e.preventDefault();
            sendMessage();
        }
    });

    sendButton.addEventListener('click', sendMessage);
}

function setupSearch() {
    const searchInput = document.getElementById('search-input');
    if (searchInput) {
        searchInput.addEventListener('input', function() {
            filterConversations(this.value);
        });
    }

    // Setup new chat modal
    const userSearchInput = document.getElementById('user-search');
    if (userSearchInput) {
        userSearchInput.addEventListener('input', function() {
            searchUsers(this.value);
        });
    }
}

function loadConversations() {
    firebase.database().ref('chats').orderByChild('last_message_time').once('value').then(snapshot => {
        const conversations = [];
        snapshot.forEach(childSnapshot => {
            const chat = childSnapshot.val();
            const chatKey = childSnapshot.key;

            // Check if current user is part of this chat
            if ((chat.buyer_id === userId || chat.seller_id === userId) ||
                (chat.user1_id === userId || chat.user2_id === userId)) {
                chat.id = chatKey;
                conversations.push(chat);
            }
        });

        // Sort by last message time (most recent first)
        conversations.sort((a, b) => (b.last_message_time || 0) - (a.last_message_time || 0));

        // Load user names for conversations
        loadUserNamesForConversations(conversations);
    }).catch(error => {
        console.error('Error loading conversations:', error);
    });
}

function loadUserNamesForConversations(conversations) {
    if (conversations.length === 0) {
        displayConversations([]);
        return;
    }

    // Get all unique user IDs we need to look up
    const userIds = new Set();
    conversations.forEach(conv => {
        const otherUserId = getOtherUserId(conv);
        if (otherUserId) {
            userIds.add(otherUserId);
        }
    });

    // Fetch user data
    fetch('get_user_data.php?user_ids=' + Array.from(userIds).join(','))
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Add user names to conversations
                conversations.forEach(conv => {
                    const otherUserId = getOtherUserId(conv);
                    if (otherUserId && data.users[otherUserId]) {
                        conv.other_user_name = data.users[otherUserId].name;
                        conv.other_user_email = data.users[otherUserId].email;
                    }
                });
            }
            displayConversations(conversations);
        })
        .catch(error => {
            console.error('Error loading user names:', error);
            displayConversations(conversations);
        });
}

function displayConversations(conversations) {
    const convoDiv = document.getElementById('conversations-list');

    if (conversations.length === 0) {
        convoDiv.innerHTML = `
            <div class="empty-state">
                <i class="fas fa-comments"></i>
                <h5>No conversations yet</h5>
                <p>Start a new conversation to begin messaging</p>
            </div>
        `;
        return;
    }

    let html = '';
    conversations.forEach(conv => {
        const otherUserId = getOtherUserId(conv);
        const otherUserName = conv.other_user_name || getOtherUserName(conv);
        const isActive = conv.id === currentChatId;
        const lastMessage = conv.last_message || 'No messages yet';
        const lastMessageTime = conv.last_message_time ? formatMessageTime(conv.last_message_time) : '';

        html += `
            <div class="conversation-item ${isActive ? 'active' : ''}" onclick="selectConversation('${conv.id}')">
                <div class="conversation-avatar">${getInitials(otherUserName)}</div>
                <div class="conversation-info">
                    <div class="conversation-name">${otherUserName}</div>
                    <div class="conversation-last-message">${lastMessage}</div>
                    <div class="conversation-time">${lastMessageTime}</div>
                </div>
            </div>
        `;
    });

    convoDiv.innerHTML = html;
}

function getOtherUserId(chat) {
    if (chat.buyer_id && chat.seller_id) {
        return chat.buyer_id === userId ? chat.seller_id : chat.buyer_id;
    }
    if (chat.user1_id && chat.user2_id) {
        return chat.user1_id === userId ? chat.user2_id : chat.user1_id;
    }
    return null;
}

function getOtherUserName(chat) {
    // Use the loaded user name if available
    if (chat.other_user_name) {
        return chat.other_user_name;
    }

    // Try to get from chat data first
    if (chat.buyer_id === userId && chat.seller_name) return chat.seller_name;
    if (chat.seller_id === userId && chat.buyer_name) return chat.buyer_name;
    if (chat.user1_id === userId && chat.user2_name) return chat.user2_name;
    if (chat.user2_id === userId && chat.user1_name) return chat.user1_name;
    if (chat.other_user_email) return chat.other_user_email;

    // Fallback to generic names
    return 'User';
}

function getInitials(name) {
    if (!name || name === 'User' || name === 'Buyer' || name === 'Seller') {
        return '?';
    }
    return name.split(' ').map(n => n[0]).join('').toUpperCase().slice(0, 2);
}

function selectConversation(chatId) {
    currentChatId = chatId;
    window.location.href = 'messages.php?chat_id=' + chatId;
}

function loadChat(chatId) {
    firebase.database().ref('chats/' + chatId).once('value').then(snapshot => {
        const chat = snapshot.val();
        if (!chat) {
            showEmptyState('Chat not found');
            return;
        }

        // Update header
        updateChatHeader(chat);

        // Load messages
        loadMessages(chatId);

        // Mark messages as read
        markMessagesAsRead(chatId);
    }).catch(error => {
        console.error('Error loading chat:', error);
        showEmptyState('Error loading chat');
    });
}

function updateChatHeader(chat) {
    const nameElement = document.getElementById('chat-name');
    const statusElement = document.getElementById('chat-status');
    const avatarElement = document.getElementById('chat-avatar');

    if (nameElement && statusElement && avatarElement) {
        const otherUserName = getOtherUserName(chat);
        nameElement.textContent = otherUserName;
        statusElement.textContent = 'Online'; // Could be enhanced with real online status
        avatarElement.textContent = getInitials(otherUserName);
    }
}

function loadMessages(chatId) {
    // Remove previous listener
    if (messageListener) {
        firebase.database().ref('messages').off('child_added', messageListener);
    }

    firebase.database().ref('messages').orderByChild('chat_id').equalTo(chatId)
        .once('value').then(snapshot => {
            const container = document.getElementById('messages-container');
            const messages = [];

            snapshot.forEach(childSnapshot => {
                messages.push(childSnapshot.val());
            });

            displayMessages(messages);

            // Set up real-time listener for new messages
            messageListener = firebase.database().ref('messages')
                .orderByChild('chat_id').equalTo(chatId)
                .on('child_added', (snapshot) => {
                    const newMessage = snapshot.val();
                    // Check if message is not already displayed
                    const existingMessages = container.querySelectorAll('.message-bubble');
                    const isDuplicate = Array.from(existingMessages).some(el => {
                        const timeEl = el.querySelector('.message-time');
                        return timeEl && timeEl.textContent === formatMessageTime(newMessage.timestamp);
                    });

                    if (!isDuplicate) {
                        appendMessage(newMessage);
                    }
                });
        }).catch(error => {
            console.error('Error loading messages:', error);
        });
}

function displayMessages(messages) {
    const container = document.getElementById('messages-container');

    if (messages.length === 0) {
        container.innerHTML = `
            <div class="empty-state">
                <i class="fas fa-comments"></i>
                <h5>No messages yet</h5>
                <p>Start the conversation!</p>
            </div>
        `;
        return;
    }

    let html = '';
    messages.forEach(msg => {
        html += createMessageHTML(msg);
    });

    container.innerHTML = html;
    scrollToBottom();
}

function appendMessage(message) {
    const container = document.getElementById('messages-container');
    const messageHTML = createMessageHTML(message);
    container.insertAdjacentHTML('beforeend', messageHTML);
    scrollToBottom();
}

function createMessageHTML(msg) {
    const isSender = msg.sender_id === userId;
    const timestamp = formatMessageTime(msg.timestamp);
    const status = isSender ? '<i class="fas fa-check message-status"></i>' : '';

    return `
        <div class="message-bubble ${isSender ? 'message-sent' : 'message-received'}">
            <div class="message-text">${escapeHtml(msg.text)}</div>
            <div class="message-time">${timestamp} ${status}</div>
        </div>
    `;
}

function formatMessageTime(timestamp) {
    if (!timestamp) return '';

    const date = new Date(timestamp);
    const now = new Date();
    const diff = now - date;

    if (diff < 60000) { // Less than 1 minute
        return 'Just now';
    } else if (diff < 3600000) { // Less than 1 hour
        const minutes = Math.floor(diff / 60000);
        return `${minutes} min ago`;
    } else if (diff < 86400000) { // Less than 1 day
        return date.toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'});
    } else if (diff < 604800000) { // Less than 1 week
        const days = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];
        return days[date.getDay()];
    } else {
        return date.toLocaleDateString();
    }
}

function formatMessageTime(timestamp) {
    if (!timestamp) return '';

    const date = new Date(timestamp);
    const now = new Date();
    const diff = now - date;

    if (diff < 60000) { // Less than 1 minute
        return 'Just now';
    } else if (diff < 3600000) { // Less than 1 hour
        const minutes = Math.floor(diff / 60000);
        return `${minutes} min ago`;
    } else if (diff < 86400000) { // Less than 1 day
        return date.toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'});
    } else if (diff < 604800000) { // Less than 1 week
        const days = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];
        return days[date.getDay()];
    } else {
        return date.toLocaleDateString();
    }
}

function sendMessage() {
    if (!currentChatId) {
        alert('Please select a conversation first');
        return;
    }

    const input = document.getElementById('message-input');
    const messageText = input.value.trim();
    if (!messageText) {
        return;
    }

    // Clear input immediately
    input.value = '';

    // Send message
    firebase.database().ref('messages').push({
        chat_id: currentChatId,
        sender_id: userId,
        sender_name: userName,
        text: messageText,
        timestamp: Date.now(),
        status: 'sent'
    }).then(() => {
        // Update chat's last message
        firebase.database().ref('chats/' + currentChatId).update({
            last_message: messageText,
            last_message_time: Date.now()
        });

        // Reload conversations to update last message
        loadConversations();
    }).catch(error => {
        console.error('Error sending message:', error);
        alert('Error sending message: ' + error.message);
        // Restore message if sending failed
        input.value = messageText;
    });
}

function handleTyping() {
    if (!currentChatId) return;

    // Send typing indicator
    firebase.database().ref('typing/' + currentChatId + '/' + userId).set({
        timestamp: Date.now()
    });

    // Clear typing after 3 seconds
    clearTimeout(typingTimeouts[currentChatId]);
    typingTimeouts[currentChatId] = setTimeout(() => {
        firebase.database().ref('typing/' + currentChatId + '/' + userId).remove();
    }, 3000);
}

function markMessagesAsRead(chatId) {
    // Mark messages as read (could be enhanced)
    firebase.database().ref('messages')
        .orderByChild('chat_id').equalTo(chatId)
        .once('value').then(snapshot => {
            snapshot.forEach(childSnapshot => {
                const msg = childSnapshot.val();
                if (msg.sender_id !== userId && msg.status !== 'read') {
                    childSnapshot.ref.update({status: 'read'});
                }
            });
        });
}

function filterConversations(searchTerm) {
    const items = document.querySelectorAll('.conversation-item');
    const term = searchTerm.toLowerCase();

    items.forEach(item => {
        const name = item.querySelector('.conversation-name').textContent.toLowerCase();
        const message = item.querySelector('.conversation-last-message').textContent.toLowerCase();

        if (name.includes(term) || message.includes(term)) {
            item.style.display = 'flex';
        } else {
            item.style.display = 'none';
        }
    });
}

function openNewChatModal() {
    const modal = new bootstrap.Modal(document.getElementById('newChatModal'));
    modal.show();

    // Clear previous search
    document.getElementById('user-search').value = '';
    document.getElementById('user-search-results').innerHTML = '';

    // Focus on search input
    setTimeout(() => {
        document.getElementById('user-search').focus();
    }, 500);
}

// User search functionality
document.getElementById('user-search').addEventListener('input', function() {
    const query = this.value.trim();
    const resultsDiv = document.getElementById('user-search-results');

    if (query.length < 2) {
        resultsDiv.innerHTML = '';
        return;
    }

    // Show loading
    resultsDiv.innerHTML = '<div class="text-center"><small>Searching...</small></div>';

    // Search users
    fetch(`search_users.php?q=${encodeURIComponent(query)}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                displaySearchResults(data.users);
            } else {
                resultsDiv.innerHTML = '<div class="text-center text-danger"><small>Error searching users</small></div>';
            }
        })
        .catch(error => {
            console.error('Search error:', error);
            resultsDiv.innerHTML = '<div class="text-center text-danger"><small>Error searching users</small></div>';
        });
});

function displaySearchResults(users) {
    const resultsDiv = document.getElementById('user-search-results');

    if (users.length === 0) {
        resultsDiv.innerHTML = '<div class="text-center text-muted"><small>No users found</small></div>';
        return;
    }

    let html = '';
    users.forEach(user => {
        html += `
            <div class="user-search-item" onclick="startConversation('${user.id}', '${user.name}', '${user.email}')">
                <div class="d-flex align-items-center">
                    <div class="user-avatar me-3">
                        ${user.name.charAt(0).toUpperCase()}
                    </div>
                    <div class="flex-grow-1">
                        <div class="fw-bold">${user.name}</div>
                        <small class="text-muted">${user.email}</small>
                    </div>
                    <div class="text-end">
                        <small class="text-muted">${user.coins} coins</small>
                    </div>
                </div>
            </div>
        `;
    });

    resultsDiv.innerHTML = html;
}

function startConversation(userId, userName, userEmail) {
    // Close modal
    const modal = bootstrap.Modal.getInstance(document.getElementById('newChatModal'));
    modal.hide();

    // Check if conversation already exists
    const existingChat = Object.keys(currentChatUsers).find(chatId => {
        return currentChatUsers[chatId].includes(userId);
    });

    if (existingChat) {
        // Load existing conversation
        loadChat(existingChat);
        return;
    }

    // Create new conversation
    messageUser(userId, userName, userEmail);
}

function messageUser(userId, userName, userEmail) {
    // Create new chat
    const chatRef = firebase.database().ref('chats').push();
    chatRef.set({
        user1_id: '<?php echo $_SESSION['user_id']; ?>',
        user2_id: userId,
        user1_name: userNameFromSession(),
        user2_name: userName,
        user1_email: '<?php echo $_SESSION['user_email']; ?>',
        user2_email: userEmail,
        created_at: Date.now(),
        last_message: '',
        last_message_time: Date.now(),
        message_count: 0
    }).then(() => {
        // Redirect to the new chat
        window.location.href = 'messages.php?chat_id=' + chatRef.key;
    }).catch(error => {
        console.error('Error creating chat:', error);
        alert('Error starting conversation: ' + error.message);
    });
}

function userNameFromSession() {
    return '<?php echo addslashes($_SESSION['user_name']); ?>';
}

function deleteConversation() {
    if (!currentChatId) {
        alert('No chat selected');
        return;
    }
    if (!confirm('Delete this chat and all its messages?')) return;

    const db = firebase.database();
    const updates = {};

    // Remove all messages for this chat
    db.ref('messages').orderByChild('chat_id').equalTo(currentChatId).once('value')
        .then(snapshot => {
            snapshot.forEach(child => {
                updates['/messages/' + child.key] = null;
            });
            // Remove chat and typing indicator
            updates['/chats/' + currentChatId] = null;
            updates['/typing/' + currentChatId] = null;
            return db.ref().update(updates);
        })
        .then(() => {
            // Redirect to list view
            window.location.href = 'messages.php';
        })
        .catch(error => {
            console.error('Error deleting chat:', error);
            alert('Error deleting chat: ' + error.message);
        });
}

function scrollToBottom() {
    const container = document.getElementById('messages-container');
    if (container) {
        container.scrollTop = container.scrollHeight;
    }
}

function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

function showEmptyState(message) {
    const chatArea = document.getElementById('chat-area');
    chatArea.innerHTML = `
        <div class="empty-state">
            <i class="fas fa-comments"></i>
            <h5>${message}</h5>
        </div>
    `;
}

// Handle window resize for mobile responsiveness
window.addEventListener('resize', function() {
    // Could add mobile-specific logic here
});
</script>
