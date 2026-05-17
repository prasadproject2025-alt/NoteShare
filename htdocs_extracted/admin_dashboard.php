<?php
session_start();

// Check admin authentication and session timeout
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: admin_login.php');
    exit();
}

// Check session timeout (30 minutes)
$session_timeout = 30 * 60; // 30 minutes in seconds
if (isset($_SESSION['admin_login_time']) && (time() - $_SESSION['admin_login_time']) > $session_timeout) {
    session_destroy();
    header('Location: admin_login.php?timeout=1');
    exit();
}

// Refresh session time
$_SESSION['admin_login_time'] = time();

include 'includes/header.php';
?>

<main class="container mt-5">
    <div class="row">
        <div class="col-md-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2><i class="fas fa-tachometer-alt me-2"></i>Admin Dashboard</h2>
                <div>
                    <span class="badge bg-danger me-2">Admin: <?php echo htmlspecialchars($_SESSION['admin_username']); ?></span>
                    <a href="admin_logout.php" class="btn btn-outline-danger btn-sm">
                        <i class="fas fa-sign-out-alt me-1"></i>Logout
                    </a>
                </div>
            </div>

            <!-- Statistics Cards -->
            <div class="row mb-4">
                <div class="col-md-3">
                    <div class="card bg-primary text-white">
                        <div class="card-body">
                            <h5 class="card-title"><i class="fas fa-users me-2"></i>Total Users</h5>
                            <h3 id="total-users">0</h3>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card bg-success text-white">
                        <div class="card-body">
                            <h5 class="card-title"><i class="fas fa-file-upload me-2"></i>Total Notes</h5>
                            <h3 id="total-notes">0</h3>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card bg-warning text-white">
                        <div class="card-body">
                            <h5 class="card-title"><i class="fas fa-share me-2"></i>Shared Notes</h5>
                            <h3 id="shared-notes">0</h3>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card bg-info text-white">
                        <div class="card-body">
                            <h5 class="card-title"><i class="fas fa-comments me-2"></i>Total Chats</h5>
                            <h3 id="total-chats">0</h3>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Navigation Tabs -->
            <ul class="nav nav-tabs" id="adminTabs" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active" id="files-tab" data-bs-toggle="tab" data-bs-target="#files" type="button" role="tab">
                        <i class="fas fa-images me-1"></i>Uploaded Files
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="feedbacks-tab" data-bs-toggle="tab" data-bs-target="#feedbacks" type="button" role="tab">
                        <i class="fas fa-chart-bar me-1"></i>Chat Statistics
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="users-tab" data-bs-toggle="tab" data-bs-target="#users" type="button" role="tab">
                        <i class="fas fa-users me-1"></i>User Management
                    </button>
                </li>
            </ul>

            <!-- Tab Content -->
            <div class="tab-content mt-3" id="adminTabContent">
                <!-- Files Tab -->
                <div class="tab-pane fade show active" id="files" role="tabpanel">
                    <div class="card">
                        <div class="card-header">
                            <h5><i class="fas fa-images me-2"></i>All Uploaded Files</h5>
                        </div>
                        <div class="card-body">
                            <div class="mb-3">
                                <select class="form-select" id="file-type-filter">
                                    <option value="all">All Files</option>
                                    <option value="sell">Sell Notes</option>
                                    <option value="share">Shared Notes</option>
                                    <option value="rent">Rental Notes</option>
                                </select>
                            </div>
                            <div id="files-container">
                                <p class="text-muted text-center">Loading files...</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Feedbacks Tab -->
                <div class="tab-pane fade" id="feedbacks" role="tabpanel">
                    <div class="card">
                        <div class="card-header">
                            <h5><i class="fas fa-chart-bar me-2"></i>Chat Statistics</h5>
                        </div>
                        <div class="card-body">
                            <div id="feedbacks-container">
                                <p class="text-muted text-center">Loading messages...</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Users Tab -->
                <div class="tab-pane fade" id="users" role="tabpanel">
                    <div class="card">
                        <div class="card-header">
                            <h5><i class="fas fa-users me-2"></i>User Management</h5>
                        </div>
                        <div class="card-body">
                            <div id="users-container">
                                <p class="text-muted text-center">Loading users...</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>

<!-- Firebase SDK v8 (compat mode for legacy code) -->
<script src="https://www.gstatic.com/firebasejs/8.10.1/firebase-app.js"></script>
<script src="https://www.gstatic.com/firebasejs/8.10.1/firebase-database.js"></script>
<script src="https://www.gstatic.com/firebasejs/8.10.1/firebase-auth.js"></script>

<script src="js/firebase-config.js"></script>

<script>
// Load dashboard data when page loads
document.addEventListener('DOMContentLoaded', function() {
    loadStatistics();
    loadFiles();
    loadFeedbacks();
    loadUsers();

    // Tab change handlers
    document.getElementById('files-tab').addEventListener('click', loadFiles);
    document.getElementById('feedbacks-tab').addEventListener('click', loadFeedbacks);
    document.getElementById('users-tab').addEventListener('click', loadUsers);

    // File type filter
    document.getElementById('file-type-filter').addEventListener('change', loadFiles);
});

function loadStatistics() {
    // Load user count from admin API
    fetch('admin_api.php', {
        method: 'GET',
        headers: {
            'Content-Type': 'application/json'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            document.getElementById('total-users').textContent = data.total;
        } else {
            console.error('Error loading user count:', data.message);
            document.getElementById('total-users').textContent = 'Error';
        }
    })
    .catch(error => {
        console.error('Error loading user count:', error);
        document.getElementById('total-users').textContent = 'Error';
    });

    // Load notes count (keeping Firebase for notes)
    firebase.database().ref('notes').once('value').then(snapshot => {
        const notesCount = snapshot.numChildren();
        document.getElementById('total-notes').textContent = notesCount;
    }).catch(error => {
        console.error('Error loading notes count:', error);
        document.getElementById('total-notes').textContent = 'Error';
    });

    // Load shared notes count
    firebase.database().ref('shared_notes').once('value').then(snapshot => {
        const sharedCount = snapshot.numChildren();
        document.getElementById('shared-notes').textContent = sharedCount;
    }).catch(error => {
        console.error('Error loading shared notes count:', error);
        document.getElementById('shared-notes').textContent = 'Error';
    });

    // Load chats count
    firebase.database().ref('chats').once('value').then(snapshot => {
        const chatsCount = snapshot.numChildren();
        document.getElementById('total-chats').textContent = chatsCount;
    }).catch(error => {
        console.error('Error loading chats count:', error);
        document.getElementById('total-chats').textContent = 'Error';
    });
}

function loadFiles() {
    const container = document.getElementById('files-container');
    const filter = document.getElementById('file-type-filter').value;

    container.innerHTML = '<p class="text-muted text-center">Loading files...</p>';

    const promises = [];

    if (filter === 'all' || filter === 'sell') {
        promises.push(firebase.database().ref('notes').once('value'));
    }
    if (filter === 'all' || filter === 'share') {
        promises.push(firebase.database().ref('shared_notes').once('value'));
    }
    if (filter === 'all' || filter === 'rent') {
        promises.push(firebase.database().ref('rental_notes').once('value'));
    }

    Promise.all(promises).then(snapshots => {
        let allFiles = [];

        snapshots.forEach((snapshot, index) => {
            const type = filter === 'all' ?
                (index === 0 ? 'sell' : index === 1 ? 'share' : 'rent') :
                filter;

            snapshot.forEach(childSnapshot => {
                const file = childSnapshot.val();
                file.id = childSnapshot.key;
                file.type = type;
                allFiles.push(file);
            });
        });

        // Sort by creation date (newest first)
        allFiles.sort((a, b) => (b.created_at || 0) - (a.created_at || 0));

        if (allFiles.length === 0) {
            container.innerHTML = '<p class="text-muted text-center">No files found.</p>';
            return;
        }

        let html = '<div class="row">';
        allFiles.forEach(file => {
            const timeAgo = getTimeAgo(file.created_at);
            const badgeClass = file.type === 'sell' ? 'bg-success' :
                             file.type === 'share' ? 'bg-warning' : 'bg-info';

            html += `
                <div class="col-md-6 col-lg-4 mb-3">
                    <div class="card h-100">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-start mb-2">
                                <span class="badge ${badgeClass}">${file.type.toUpperCase()}</span>
                                <small class="text-muted">${timeAgo}</small>
                            </div>
                            ${ (file.image_base64 || (file.images && file.images.length)) ? `
                                <div class="text-center mb-2">
                                    <img src="${ (file.image_base64 && (file.image_base64.indexOf && file.image_base64.indexOf('data:')===0)) ? file.image_base64 : (file.image_base64 ? ('data:' + (file.image_mime_type || 'image/jpeg') + ';base64,' + file.image_base64) : ('data:' + (file.image_mime_type || (file.images && file.images[0] && file.images[0].mime_type) || 'image/jpeg') + ';base64,' + (file.images && file.images[0] && file.images[0].base64))) }" class="img-fluid rounded" style="max-height:160px; width:100%; object-fit:cover;">
                                </div>
                            ` : ''}
                            <h6 class="card-title">${file.subject_name || 'N/A'}</h6>
                            <p class="card-text small mb-2">
                                <strong>Course:</strong> ${file.course_code || 'N/A'}<br>
                                <strong>User:</strong> ${file.seller_name || file.sharer_name || 'Anonymous'}<br>
                                <strong>Slot:</strong> ${file.batch || file.slot || 'N/A'}
                            </p>
                            ${ (file.image_base64 || (file.images && file.images.length)) ? `
                                <div class="d-flex justify-content-start mb-2">
                                    <button class="btn btn-sm btn-outline-primary me-2" onclick="${file.images && file.images.length ? `viewImages('${encodeURIComponent(JSON.stringify(file.images))}', '${file.subject_name || 'Note'}')` : `viewImage('${file.image_base64}', '${file.image_mime_type || 'image/jpeg'}', '${file.subject_name || 'Note'}')`} ">
                                        <i class="fas fa-eye me-1"></i>View Image
                                    </button>
                                </div>
                            ` : '<span class="text-muted small">No image</span>'}
                            <div class="mt-2">
                                <button class="btn btn-sm btn-outline-danger" onclick="deleteNote('${file.id}', '${file.type}')">
                                    <i class="fas fa-trash me-1"></i>Delete
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            `;
        });
        html += '</div>';

        container.innerHTML = html;
    });
}

function loadFeedbacks() {
    const container = document.getElementById('feedbacks-container');
    container.innerHTML = '<p class="text-muted text-center">Loading chat statistics...</p>';

    firebase.database().ref('chats').once('value').then(snapshot => {
        const chatCount = snapshot.numChildren();
        const chats = [];

        snapshot.forEach(childSnapshot => {
            const chat = childSnapshot.val();
            chat.id = childSnapshot.key;
            chats.push(chat);
        });

        if (chats.length === 0) {
            container.innerHTML = '<p class="text-muted text-center">No chats found.</p>';
            return;
        }

        // Calculate statistics without showing private messages
        const totalMessages = chats.reduce((sum, chat) => sum + (chat.message_count || 1), 0);
        const activeChats = chats.filter(chat => chat.last_message_time &&
            (Date.now() - chat.last_message_time) < (7 * 24 * 60 * 60 * 1000)).length; // Active in last 7 days

        let html = '<div class="row">';
        html += `
            <div class="col-md-4">
                <div class="card bg-info text-white">
                    <div class="card-body text-center">
                        <h5 class="card-title"><i class="fas fa-comments me-2"></i>Total Chats</h5>
                        <h3>${chatCount}</h3>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card bg-success text-white">
                    <div class="card-body text-center">
                        <h5 class="card-title"><i class="fas fa-envelope me-2"></i>Total Messages</h5>
                        <h3>${totalMessages}</h3>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card bg-warning text-white">
                    <div class="card-body text-center">
                        <h5 class="card-title"><i class="fas fa-clock me-2"></i>Active Chats (7 days)</h5>
                        <h3>${activeChats}</h3>
                    </div>
                </div>
            </div>
        `;

        html += '</div>';

        // Show chat list with anonymized information only
        html += '<div class="mt-4"><h5>Recent Chat Activity</h5>';
        html += '<div class="list-group">';

        // Sort by most recent and show only last 10
        chats.sort((a, b) => (b.last_message_time || b.created_at || 0) - (a.last_message_time || a.created_at || 0));
        const recentChats = chats.slice(0, 10);

        recentChats.forEach(chat => {
            const timeAgo = getTimeAgo(chat.last_message_time || chat.created_at);
            const messageCount = chat.message_count || 1;

            html += `
                <div class="list-group-item">
                    <div class="d-flex w-100 justify-content-between">
                        <h6 class="mb-1">Chat Session</h6>
                        <small class="text-muted">${timeAgo}</small>
                    </div>
                    <p class="mb-1">
                        <span class="badge bg-secondary me-2">${messageCount} message${messageCount > 1 ? 's' : ''}</span>
                        <span class="text-muted">Private conversation between users</span>
                    </p>
                    <small class="text-muted">
                        Participants: ${chat.user1_id ? chat.user1_id.substring(0, 8) : 'Unknown'} ↔
                        ${chat.user2_id ? chat.user2_id.substring(0, 8) : 'Unknown'}
                    </small>
                </div>
            `;
        });

        html += '</div></div>';

        container.innerHTML = html;
    }).catch(error => {
        console.error('Error loading chat statistics:', error);
        container.innerHTML = '<p class="text-danger text-center">Error loading chat statistics.</p>';
    });
}

function loadUsers() {
    const container = document.getElementById('users-container');
    container.innerHTML = '<p class="text-muted text-center">Loading users...</p>';

    fetch('admin_api.php', {
        method: 'GET',
        headers: {
            'Content-Type': 'application/json'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (!data.success) {
            throw new Error(data.message || 'Failed to load users');
        }

        const users = data.users;

        if (users.length === 0) {
            container.innerHTML = '<p class="text-muted text-center">No users found.</p>';
            return;
        }

        let html = '<div class="table-responsive"><table class="table table-striped">';
        html += `
            <thead>
                <tr>
                    <th>User ID</th>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Coins</th>
                    <th>Joined</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
        `;

        users.forEach(user => {
            const joinDate = user.created_at ? new Date(user.created_at).toLocaleDateString() : 'Unknown';
            const coins = user.coins || 0;
            const isBlocked = user.blocked ? true : false;

            html += `
                <tr>
                    <td>${user.id.substring(0, 8)}...</td>
                    <td>${user.name || 'N/A'}</td>
                    <td>${user.email || 'N/A'}</td>
                    <td>${coins}</td>
                    <td>${joinDate}</td>
                    <td>
                        <button class="btn btn-sm btn-outline-${isBlocked ? 'success' : 'danger'} me-1" onclick="toggleBlockUser('${user.id}', ${isBlocked ? 'false' : 'true'})">
                            ${isBlocked ? '<i class="fas fa-unlock me-1"></i>Unblock' : '<i class="fas fa-ban me-1"></i>Block'}
                        </button>
                        <button class="btn btn-sm btn-outline-primary me-1" onclick="promptAddCoins('${user.id}')">
                            <i class="fas fa-coins me-1"></i>Add Coins
                        </button>
                        <button class="btn btn-sm btn-outline-secondary me-1" onclick="resetUserCoins('${user.id}')">
                            Reset Coins
                        </button>
                        <button class="btn btn-sm btn-outline-danger" onclick="deleteUser('${user.id}')">
                            <i class="fas fa-trash me-1"></i>Delete
                        </button>
                    </td>
                </tr>
            `;
        });

        html += '</tbody></table></div>';
        container.innerHTML = html;
    })
    .catch(error => {
        console.error('Error loading users:', error);
        container.innerHTML = '<p class="text-danger text-center">Error loading users. Please try again.</p>';
    });
}

function viewImage(base64Data, mimeType, title) {
    let imageSrc;
    if (base64Data.startsWith('data:')) {
        // Already a full data URL
        imageSrc = base64Data;
    } else {
        // Construct data URL from base64 string
        imageSrc = `data:${mimeType};base64,${base64Data}`;
    }

    const modal = document.createElement('div');
    modal.className = 'modal fade';
    modal.innerHTML = `
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">${title}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body text-center">
                    <img src="${imageSrc}" class="img-fluid" alt="${title}" onerror="this.style.display='none'; this.nextElementSibling.style.display='block';">
                    <div class="bg-light d-flex align-items-center justify-content-center" style="height: 200px; display: none;">
                        <i class="fas fa-image text-muted fa-3x"></i><br>
                        <small class="text-muted">Image failed to load</small>
                    </div>
                </div>
            </div>
        </div>
    `;
    document.body.appendChild(modal);

    const bsModal = new bootstrap.Modal(modal);
    bsModal.show();

    modal.addEventListener('hidden.bs.modal', () => {
        document.body.removeChild(modal);
    });
}

// View multiple images (accepts encoded JSON array of {base64, mime_type} objects)
function viewImages(encodedImages, title) {
    let images = [];
    try {
        images = JSON.parse(decodeURIComponent(encodedImages));
    } catch (e) {
        console.error('Failed to parse images', e);
        return viewImage(encodedImages, 'Images');
    }

    if (!images || !images.length) return alert('No images to display');

    // Build carousel
    const carouselId = 'adminImagesCarousel_' + Math.random().toString(36).substring(2,9);
    let indicators = '';
    let inner = '';
    images.forEach((img, idx) => {
        const mime = img.mime_type || img.mimetype || 'image/jpeg';
        const src = img.base64 && img.base64.indexOf && img.base64.indexOf('data:')===0 ? img.base64 : ('data:' + mime + ';base64,' + (img.base64 || ''));
        indicators += `<button type="button" data-bs-target="#${carouselId}" data-bs-slide-to="${idx}" ${idx===0? 'class="active" aria-current="true"' : ''} aria-label="Slide ${idx+1}"></button>`;
        inner += `
            <div class="carousel-item ${idx===0 ? 'active' : ''}">
                <img src="${src}" class="d-block w-100" style="max-height:600px; object-fit:contain;">
            </div>
        `;
    });

    const modal = document.createElement('div');
    modal.className = 'modal fade';
    modal.innerHTML = `
        <div class="modal-dialog modal-xl modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">${title}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div id="${carouselId}" class="carousel slide" data-bs-ride="carousel">
                        <div class="carousel-indicators">${indicators}</div>
                        <div class="carousel-inner">${inner}</div>
                        <button class="carousel-control-prev" type="button" data-bs-target="#${carouselId}" data-bs-slide="prev">
                            <span class="carousel-control-prev-icon" aria-hidden="true"></span>
                            <span class="visually-hidden">Previous</span>
                        </button>
                        <button class="carousel-control-next" type="button" data-bs-target="#${carouselId}" data-bs-slide="next">
                            <span class="carousel-control-next-icon" aria-hidden="true"></span>
                            <span class="visually-hidden">Next</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    `;

    document.body.appendChild(modal);
    const bsModal = new bootstrap.Modal(modal);
    bsModal.show();
    modal.addEventListener('hidden.bs.modal', () => document.body.removeChild(modal));
}

function resetUserCoins(userId) {
    if (confirm('Are you sure you want to reset this user\'s coins to 0?')) {
        fetch('admin_api.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                action: 'reset_coins',
                user_id: userId
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('User coins reset successfully!');
                loadUsers();
                loadStatistics();
            } else {
                alert('Error: ' + data.message);
            }
        })
        .catch(error => {
            alert('Error resetting coins: ' + error.message);
        });
    }
}

// Delete a note (sell/shared/rent) based on type
function deleteNote(noteId, type) {
    if (!confirm('Are you sure you want to delete this note? This action cannot be undone.')) return;
    let path = 'notes/' + noteId;
    if (type === 'share') path = 'shared_notes/' + noteId;
    if (type === 'rent') path = 'rental_notes/' + noteId;

    firebase.database().ref(path).remove().then(() => {
        alert('Note deleted successfully');
        loadFiles();
        loadStatistics();
    }).catch(error => {
        alert('Error deleting note: ' + error.message);
    });
}

// Block or unblock a user
function toggleBlockUser(userId, block) {
    const action = block ? 'block' : 'unblock';
    if (!confirm('Confirm to ' + action + ' this user?')) return;

    fetch('admin_api.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({
            action: 'toggle_block',
            user_id: userId,
            block: block
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('User ' + (block ? 'blocked' : 'unblocked') + ' successfully');
            loadUsers();
        } else {
            alert('Error: ' + data.message);
        }
    })
    .catch(error => {
        alert('Error updating user: ' + error.message);
    });
}

// Prompt admin to add coins to a user
function promptAddCoins(userId) {
    const amountStr = prompt('Enter number of coins to add (positive integer):', '10');
    if (!amountStr) return;
    const amount = parseInt(amountStr, 10);
    if (isNaN(amount) || amount <= 0) { alert('Invalid amount'); return; }

    fetch('admin_api.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({
            action: 'add_coins',
            user_id: userId,
            amount: amount
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Added ' + amount + ' coins to the user successfully!');
            loadUsers();
            loadStatistics();
        } else {
            alert('Error: ' + data.message);
        }
    })
    .catch(error => {
        alert('Error adding coins: ' + error.message);
    });
}

// Delete a user account
function deleteUser(userId) {
    if (!confirm('Are you sure you want to delete this user account? This action cannot be undone and will permanently remove all user data.')) return;

    fetch('admin_api.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({
            action: 'delete_user',
            user_id: userId
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('User account deleted successfully!');
            loadUsers();
            loadStatistics();
        } else {
            alert('Error: ' + data.message);
        }
    })
    .catch(error => {
        alert('Error deleting user: ' + error.message);
    });
}

function getTimeAgo(timestamp) {
    if (!timestamp) return 'Unknown';

    const now = Date.now();
    const diff = now - timestamp;
    const minutes = Math.floor(diff / 60000);
    const hours = Math.floor(diff / 3600000);
    const days = Math.floor(diff / 86400000);

    if (minutes < 1) return 'just now';
    if (minutes < 60) return `${minutes} minute${minutes > 1 ? 's' : ''} ago`;
    if (hours < 24) return `${hours} hour${hours > 1 ? 's' : ''} ago`;
    return `${days} day${days > 1 ? 's' : ''} ago`;
}
</script>

<?php include 'includes/footer.php'; ?>