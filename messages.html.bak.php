<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

include 'includes/header.php';

$chat_id = $_GET['chat_id'] ?? '';
?>

<main class="container mt-5">
    <div class="row" style="height: 75vh;">
        <div class="col-md-8">
            <div class="card h-100 d-flex flex-column">
                <div class="card-header">
                    <h5 id="chat-header">Messages</h5>
                </div>
                <div class="card-body flex-grow-1 overflow-auto" id="messages-container">
                    <?php if ($chat_id): ?>
                        <p class="text-muted text-center">Loading messages...</p>
                    <?php else: ?>
                        <p class="text-muted text-center">Select a conversation or start a new one</p>
                    <?php endif; ?>
                </div>
                <div class="card-footer">
                    <form id="message-form" class="input-group">
                        <input type="text" class="form-control" id="message-input" placeholder="Type your message..." 
                               <?php echo empty($chat_id) ? 'disabled' : ''; ?>>
                        <button class="btn btn-primary" type="submit" <?php echo empty($chat_id) ? 'disabled' : ''; ?>>
                            Send
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card h-100 d-flex flex-column">
                <div class="card-header">
                    <h5>Conversations</h5>
                </div>
                <div class="card-body flex-grow-1 overflow-auto" id="conversations-list">
                    <p class="text-muted">Loading conversations...</p>
                </div>
            </div>
        </div>
    </div>
</main>

<?php include 'includes/footer.php'; ?>

<!-- Firebase SDK -->
<script src="https://www.gstatic.com/firebasejs/9.23.0/firebase-app.js"></script>
<script src="https://www.gstatic.com/firebasejs/9.23.0/firebase-database.js"></script>
<script src="https://www.gstatic.com/firebasejs/9.23.0/firebase-storage.js"></script>
<script src="https://www.gstatic.com/firebasejs/9.23.0/firebase-auth.js"></script>

<script src="js/firebase-config.js"></script>
<script>
const userId = '<?php echo $_SESSION['user_id']; ?>';
const userName = '<?php echo $_SESSION['user_name']; ?>';
const chatId = '<?php echo $chat_id; ?>';
let currentChatId = chatId;

document.addEventListener('DOMContentLoaded', function() {
    loadConversations();
    if (chatId) {
        loadMessages(chatId);
    }
});

function loadConversations() {
    firebase.database().ref('chats').orderByChild('created_at').once('value').then(snapshot => {
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

        const convoDiv = document.getElementById('conversations-list');
        if (conversations.length === 0) {
            convoDiv.innerHTML = '<p class="text-muted">No conversations yet</p>';
            return;
        }

        let html = '';
        conversations.forEach(conv => {
            const otherUserId = conv.buyer_id === userId ? conv.seller_id : 
                                (conv.seller_id === userId ? conv.buyer_id :
                                 (conv.user1_id === userId ? conv.user2_id : conv.user1_id));
            
            const isActive = conv.id === currentChatId;
            
            html += `
                <div class="conversation-item p-2 border-bottom cursor-pointer ${isActive ? 'bg-light' : ''}" 
                     onclick="selectConversation('${conv.id}')" style="cursor: pointer;">
                    <p class="mb-1 font-weight-bold">${conv.subject || 'Conversation'}</p>
                    <small class="text-muted">${conv.last_message || 'No messages yet'}</small><br>
                    <small class="text-muted">${new Date(conv.last_message_time).toLocaleDateString()}</small>
                </div>
            `;
        });
        convoDiv.innerHTML = html;
    });
}

function selectConversation(chatId) {
    currentChatId = chatId;
    window.location.href = 'messages.php?chat_id=' + chatId;
}

function loadMessages(chatId) {
    firebase.database().ref('chats/' + chatId).once('value').then(snapshot => {
        const chat = snapshot.val();
        if (!chat) {
            alert('Chat not found');
            return;
        }

        // Update header with other user info
        const header = document.getElementById('chat-header');
        const otherUserId = chat.buyer_id === userId ? chat.seller_id : 
                            (chat.seller_id === userId ? chat.buyer_id :
                             (chat.user1_id === userId ? chat.user2_id : chat.user1_id));
        
        header.textContent = chat.subject || 'Chat';

        // Load chat messages
        firebase.database().ref('messages').orderByChild('chat_id').equalTo(chatId)
            .once('value').then(snapshot => {
                const container = document.getElementById('messages-container');
                const messages = [];

                snapshot.forEach(childSnapshot => {
                    messages.push(childSnapshot.val());
                });

                if (messages.length === 0) {
                    container.innerHTML = '<p class="text-muted text-center">No messages yet. Start the conversation!</p>';
                    return;
                }

                let html = '';
                messages.forEach(msg => {
                    const isSender = msg.sender_id === userId;
                    const timestamp = new Date(msg.timestamp).toLocaleTimeString();
                    
                    html += `
                        <div class="mb-3 ${isSender ? 'text-end' : 'text-start'}">
                            <div class="d-inline-block p-2 rounded ${isSender ? 'bg-primary text-white' : 'bg-light'}">
                                <p class="mb-1">${msg.text}</p>
                                <small class="${isSender ? 'text-light' : 'text-muted'}">${timestamp}</small>
                            </div>
                        </div>
                    `;
                });
                container.innerHTML = html;
                
                // Scroll to bottom
                container.scrollTop = container.scrollHeight;

                // Set up real-time listener for new messages
                firebase.database().ref('messages').orderByChild('chat_id').equalTo(chatId)
                    .on('child_added', (snapshot) => {
                        const msg = snapshot.val();
                        if (!messages.find(m => m.text === msg.text && m.timestamp === msg.timestamp)) {
                            loadMessages(chatId);
                        }
                    });
            });
    });
}

document.getElementById('message-form').addEventListener('submit', function(e) {
    e.preventDefault();
    sendMessage();
});

function sendMessage() {
    if (!currentChatId) {
        alert('Please select a conversation first');
        return;
    }

    const messageText = document.getElementById('message-input').value.trim();
    if (!messageText) {
        return;
    }

    firebase.database().ref('messages').push({
        chat_id: currentChatId,
        sender_id: userId,
        sender_name: userName,
        text: messageText,
        timestamp: Date.now()
    }).then(() => {
        document.getElementById('message-input').value = '';
        
        // Update chat's last message
        firebase.database().ref('chats/' + currentChatId).update({
            last_message: messageText,
            last_message_time: Date.now()
        });

        loadMessages(currentChatId);
        loadConversations();
    }).catch(error => {
        alert('Error sending message: ' + error.message);
    });
}
</script>
