<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$profile_user_id = $_GET['user_id'] ?? $_SESSION['user_id'];
$is_own_profile = ($profile_user_id === $_SESSION['user_id']);

include 'includes/header.php';
?>

<main class="container mt-5">
    <div class="row">
        <div class="col-md-4">
            <div class="card">
                <div class="card-header">
                    <h5>Profile Details</h5>
                </div>
                <div class="card-body" id="profile-section">
                    <p class="text-muted">Loading profile...</p>
                </div>
            </div>

            <?php if (!$is_own_profile): ?>
            <div class="card mt-3">
                <div class="card-body">
                    <button class="btn btn-primary w-100" onclick="messageUser()">
                        Message This User
                    </button>
                </div>
            </div>
            <?php endif; ?>

            <?php if ($is_own_profile): ?>
            <div class="card mt-3">
                <div class="card-header">
                    <h5>Edit Profile</h5>
                </div>
                <div class="card-body">
                    <a href="edit_profile.php" class="btn btn-warning w-100">Edit Profile</a>
                </div>
            </div>
            <?php endif; ?>
        </div>

        <div class="col-md-8">
            <ul class="nav nav-tabs mb-4" id="profileTabs">
                <li class="nav-item">
                    <a class="nav-link active" href="#selling" data-bs-toggle="tab">Selling Notes</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="#liked" data-bs-toggle="tab">Liked Notes</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="#rentals" data-bs-toggle="tab">Rentals</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="#shared" data-bs-toggle="tab">Shared Notes</a>
                </li>
            </ul>

            <div class="tab-content">
                <div class="tab-pane fade show active" id="selling">
                    <h5>Selling Notes</h5>
                    <div id="selling-notes">
                        <p class="text-muted">Loading...</p>
                    </div>
                </div>

                <div class="tab-pane fade" id="liked">
                    <h5>Liked Notes</h5>
                    <div id="liked-notes">
                        <p class="text-muted">Loading...</p>
                    </div>
                </div>

                <div class="tab-pane fade" id="rentals">
                    <h5>Rental Notes</h5>
                    <div id="rental-notes">
                        <p class="text-muted">Loading...</p>
                    </div>
                </div>

                <div class="tab-pane fade" id="shared">
                    <h5>Shared Notes</h5>
                    <div id="shared-notes">
                        <p class="text-muted">Loading...</p>
                    </div>
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
const profileUserId = '<?php echo $profile_user_id; ?>';
const isOwnProfile = <?php echo $is_own_profile ? 'true' : 'false'; ?>;

document.addEventListener('DOMContentLoaded', function() {
    loadProfileDetails();
    loadSellingNotes();
    loadLikedNotes();
    loadRentalNotes();
    loadSharedNotes();
});

function loadProfileDetails() {
    firebase.database().ref('users/' + profileUserId).once('value').then(snapshot => {
        const user = snapshot.val();
        if (!user) {
            document.getElementById('profile-section').innerHTML = '<p class="text-danger">User not found</p>';
            return;
        }

        let html = `
            <div class="text-center mb-3">
                <div class="profile-avatar" style="font-size: 48px;">👤</div>
            </div>
            <h5>${user.name || 'User'}</h5>
            <p class="text-muted mb-1">${user.email}</p>
            <p class="text-muted mb-1"><strong>Year:</strong> ${user.year || 'Not specified'}</p>
            <p class="text-muted mb-3"><strong>Department:</strong> ${user.department || 'Not specified'}</p>
            
            <div class="mb-3">
                <p class="mb-1"><strong>Coins:</strong> <span id="user-coins">Loading...</span></p>
                <p class="mb-1"><strong>Rating:</strong> <span id="user-rating">Loading...</span> ⭐</p>
                <p class="mb-0"><strong>Total Transactions:</strong> <span id="user-transactions">0</span></p>
            </div>
            
            <div class="stats">
                <p class="text-center mb-0"><small class="text-muted">Member since ${new Date(user.created_at || Date.now()).toLocaleDateString()}</small></p>
            </div>
        `;

        document.getElementById('profile-section').innerHTML = html;

        // Load coins and rating
        if (isOwnProfile) {
            // Load coins from API
            fetch('get_user_coins.php?t=' + Date.now(), {
                method: 'GET',
                headers: {
                    'Content-Type': 'application/json'
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    document.getElementById('user-coins').textContent = data.coins;
                } else {
                    console.error('Error loading coins:', data.message);
                    document.getElementById('user-coins').textContent = 'Error';
                }
            })
            .catch(error => {
                console.error('Error loading coins:', error);
                document.getElementById('user-coins').textContent = 'Error';
            });
        }

        firebase.database().ref('users/' + profileUserId + '/rating').once('value').then(snap => {
            document.getElementById('user-rating').textContent = (snap.val() || 0).toFixed(1);
        });
    });
}

function loadSellingNotes() {
    firebase.database().ref('notes').orderByChild('seller_id').equalTo(profileUserId)
        .once('value').then(snapshot => {
            const div = document.getElementById('selling-notes');
            const notes = [];

            snapshot.forEach(childSnapshot => {
                const note = childSnapshot.val();
                note.id = childSnapshot.key;
                notes.push(note);
            });

            if (notes.length === 0) {
                div.innerHTML = '<p class="text-muted">No selling notes yet</p>';
                return;
            }

            let html = '';
            notes.forEach(note => {
                html += `
                    <div class="card mb-3">
                        <div class="card-body">
                            <h5 class="card-title">${note.subject_name}</h5>
                            <p class="card-text">
                                <strong>Course:</strong> ${note.course_code} | 
                                <strong>Faculty:</strong> ${note.faculty_name}<br>
                                <strong>Price:</strong> ₹${note.price} | 
                                <strong>Likes:</strong> ${note.likes || 0}
                            </p>
                            <button class="btn btn-sm btn-outline-primary" onclick="likeNote('${note.id}')">
                                ♥ Like
                            </button>
                        </div>
                    </div>
                `;
            });
            div.innerHTML = html;
        });
}

function loadLikedNotes() {
    firebase.database().ref('users/' + profileUserId + '/liked_notes').once('value').then(snapshot => {
        const div = document.getElementById('liked-notes');
        const likedNoteIds = snapshot.val() || {};

        if (Object.keys(likedNoteIds).length === 0) {
            div.innerHTML = '<p class="text-muted">No liked notes yet</p>';
            return;
        }

        firebase.database().ref('notes').once('value').then(allNotesSnapshot => {
            let html = '';
            let count = 0;

            allNotesSnapshot.forEach(noteSnapshot => {
                const noteId = noteSnapshot.key;
                if (likedNoteIds[noteId]) {
                    const note = noteSnapshot.val();
                    html += `
                        <div class="card mb-3">
                            <div class="card-body">
                                <h5 class="card-title">${note.subject_name}</h5>
                                <p class="card-text">
                                    <strong>Course:</strong> ${note.course_code} | 
                                    <strong>Price:</strong> ₹${note.price}
                                </p>
                                <button class="btn btn-sm btn-primary" onclick="buyNoteFromProfile('${noteId}')">
                                    Buy Now
                                </button>
                            </div>
                        </div>
                    `;
                    count++;
                }
            });

            if (count === 0) {
                div.innerHTML = '<p class="text-muted">No liked notes found</p>';
            } else {
                div.innerHTML = html;
            }
        });
    });
}

function loadRentalNotes() {
    firebase.database().ref('rental_notes').orderByChild('renter_id').equalTo(profileUserId)
        .once('value').then(snapshot => {
            const div = document.getElementById('rental-notes');
            const notes = [];

            snapshot.forEach(childSnapshot => {
                const note = childSnapshot.val();
                note.id = childSnapshot.key;
                notes.push(note);
            });

            if (notes.length === 0) {
                div.innerHTML = '<p class="text-muted">No rental notes posted</p>';
                return;
            }

            let html = '';
            notes.forEach(note => {
                html += `
                    <div class="card mb-3">
                        <div class="card-body">
                            <h5 class="card-title">${note.subject_name}</h5>
                            <p class="card-text">
                                <strong>Daily Price:</strong> ₹${note.daily_price} | 
                                <strong>Period:</strong> ${note.rental_period} days
                            </p>
                        </div>
                    </div>
                `;
            });
            div.innerHTML = html;
        });
}

function loadSharedNotes() {
    firebase.database().ref('shared_notes').orderByChild('sharer_id').equalTo(profileUserId)
        .once('value').then(snapshot => {
            const div = document.getElementById('shared-notes');
            const notes = [];

            snapshot.forEach(childSnapshot => {
                const note = childSnapshot.val();
                note.id = childSnapshot.key;
                notes.push(note);
            });

            if (notes.length === 0) {
                div.innerHTML = '<p class="text-muted">No shared notes yet</p>';
                return;
            }

            let html = '';
            notes.forEach(note => {
                html += `
                    <div class="card mb-3">
                        <div class="card-body">
                            <h5 class="card-title">${note.subject_name}</h5>
                            <p class="card-text">
                                <strong>VIT Slot:</strong> ${note.batch || note.slot}
                            </p>
                        </div>
                    </div>
                `;
            });
            div.innerHTML = html;
        });
}

function likeNote(noteId) {
    const likedNotesRef = firebase.database().ref('users/' + userId + '/liked_notes/' + noteId);
    const notesRef = firebase.database().ref('notes/' + noteId);

    likedNotesRef.once('value').then(snapshot => {
        if (snapshot.exists()) {
            // Unlike
            likedNotesRef.remove();
            notesRef.once('value').then(snap => {
                const note = snap.val();
                notesRef.update({ likes: (note.likes || 1) - 1 });
            });
        } else {
            // Like
            likedNotesRef.set(true);
            notesRef.once('value').then(snap => {
                const note = snap.val();
                notesRef.update({ likes: (note.likes || 0) + 1 });
            });
        }
    });
}

function messageUser() {
    // Check if user has coins using API
    fetch('get_user_coins.php?t=' + Date.now(), {
        method: 'GET',
        headers: {
            'Content-Type': 'application/json'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (!data.success) {
            alert('Error checking coin balance. Please try again.');
            return;
        }

        const coins = data.coins;
        if (coins < 1) {
            alert('You need at least 1 coin to message');
            return;
        }

        // Deduct 1 coin from local storage via API
        fetch('update_user_coins.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                action: 'deduct',
                coins: 1,
                description: 'Message sent to user ' + profileUserId
            })
        })
        .then(response => response.json())
        .then(updateData => {
            if (!updateData.success) {
                alert('Error deducting coins. Please try again.');
                return;
            }

            const chatRef = firebase.database().ref('chats').push();
            chatRef.set({
                user1_id: userId,
                user2_id: profileUserId,
                created_at: Date.now(),
                last_message: '',
                last_message_time: Date.now()
            }).then(() => {
                window.location.href = 'messages.php?chat_id=' + chatRef.key;
            }).catch(error => {
                console.error('Error creating chat:', error);
                alert('Error creating conversation. Please try again.');
            });
        })
        .catch(error => {
            console.error('Error deducting coins:', error);
            alert('Error deducting coins. Please try again.');
        });
    })
    .catch(error => {
        console.error('Error checking coins:', error);
        alert('Error checking coin balance. Please try again.');
    });
}

function buyNoteFromProfile(noteId) {
    window.location.href = 'buy_notes.php#' + noteId;
}
</script>
