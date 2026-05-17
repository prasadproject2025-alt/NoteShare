<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

include 'includes/header.php';
?>

<style>
/* Image Gallery Styles */
.image-gallery {
    border-radius: 8px;
    overflow: hidden;
}

.main-image-container {
    transition: transform 0.2s ease;
}

.main-image-container:hover {
    transform: scale(1.02);
}

.thumbnail-strip {
    scrollbar-width: thin;
    scrollbar-color: #dee2e6 transparent;
}

.thumbnail-strip::-webkit-scrollbar {
    height: 4px;
}

.thumbnail-strip::-webkit-scrollbar-track {
    background: #f1f1f1;
    border-radius: 2px;
}

.thumbnail-strip::-webkit-scrollbar-thumb {
    background: #dee2e6;
    border-radius: 2px;
}

.thumbnail-strip::-webkit-scrollbar-thumb:hover {
    background: #adb5bd;
}

.thumbnail-img.active {
    box-shadow: 0 0 0 2px #007bff;
}

.zoom-overlay {
    transition: opacity 0.3s ease;
}

.main-image-container:hover .zoom-overlay {
    opacity: 0.9;
}

/* Modal Styles */
.image-modal {
    animation: fadeIn 0.3s ease;
}

@keyframes fadeIn {
    from { opacity: 0; }
    to { opacity: 1; }
}

.modal-nav {
    transition: all 0.2s ease;
}

.modal-nav:hover {
    background: rgba(0,0,0,0.9) !important;
    transform: translateY(-50%) scale(1.1);
}

.modal-close:hover {
    background: rgba(0,0,0,0.9) !important;
    transform: scale(1.1);
}

/* Responsive adjustments */
@media (max-width: 768px) {
    .main-image-container {
        height: 250px;
    }

    .thumbnail-img {
        width: 50px !important;
        height: 50px !important;
    }

    .zoom-overlay, .image-count {
        font-size: 10px !important;
        padding: 3px 8px !important;
    }
}
</style>

<main class="container mt-5">
    <div class="row">
        <div class="col-md-8">
            <h2>Buy Notes</h2>

            <div class="card mb-4">
                <div class="card-header">
                    <h5>Search Notes</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label for="course_code" class="form-label">Course Code</label>
                            <input type="text" class="form-control" id="course_code" 
                                   placeholder="e.g., CSE101">
                        </div>

                        <div class="col-md-4 mb-3">
                            <label for="subject_name" class="form-label">Subject Name</label>
                            <input type="text" class="form-control" id="subject_name" placeholder="e.g., Data Structures">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="slot" class="form-label">VIT Slot</label>
                            <select class="form-control" id="slot">
                                <option value="">All Slots</option>
                                <option value="A1">A1 (Morning)</option>
                                <option value="A2">A2 (Afternoon)</option>
                                <option value="B1">B1 (Morning)</option>
                                <option value="B2">B2 (Afternoon)</option>
                                <option value="C1">C1 (Morning)</option>
                                <option value="C2">C2 (Afternoon)</option>
                                <option value="D1">D1 (Morning)</option>
                                <option value="D2">D2 (Afternoon)</option>
                                <option value="E1">E1 (Morning)</option>
                                <option value="E2">E2 (Afternoon)</option>
                                <option value="F1">F1 (Morning)</option>
                                <option value="F2">F2 (Afternoon)</option>
                                <option value="G1">G1 (Morning)</option>
                                <option value="G2">G2 (Afternoon)</option>
                            </select>
                        </div>
                    </div>
                    <button class="btn btn-primary" onclick="searchNotes()">Search</button>
                    <button class="btn btn-secondary ms-2" onclick="loadAllAvailableNotes()">Refresh Notes</button>
                    <button class="btn btn-info ms-2" onclick="testFirebaseConnection()">Test Connection</button>
                </div>
            </div>

            <div id="notes-list">
                <!-- Notes will be loaded here -->
                <p class="text-muted">Loading available notes...</p>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card">
                <div class="card-header">
                    <h5>Your Coins</h5>
                </div>
                <div class="card-body">
                    <h3 id="coin-balance">0 Coins</h3>
                    <p class="text-muted">1 coin = Access to chat with seller</p>
                    <a href="coins.php" class="btn btn-warning w-100">Buy Coins</a>
                </div>
            </div>
        </div>
    </div>
</main>

<?php include 'includes/footer.php'; ?>

<!-- Firebase SDK v8 (compat mode for legacy code) -->
<script src="https://www.gstatic.com/firebasejs/8.10.1/firebase-app.js"></script>
<script src="https://www.gstatic.com/firebasejs/8.10.1/firebase-database.js"></script>
<script src="https://www.gstatic.com/firebasejs/8.10.1/firebase-auth.js"></script>

<script src="js/firebase-config.js"></script>
<script>
const userId = '<?php echo $_SESSION['user_id']; ?>';

// Load coin balance and available notes on page load
document.addEventListener('DOMContentLoaded', function() {
    console.log('Page loaded, initializing...');
    
    // Wait for Firebase to be ready and authenticated
    initializeFirebaseAuth().then(() => {
        console.log('Firebase auth initialized, loading data...');
        loadCoinBalance();
        loadAllAvailableNotes();
    }).catch(error => {
        console.error('Firebase auth error:', error);
        document.getElementById('notes-list').innerHTML = '<div class="alert alert-danger">Error connecting to database. Please refresh the page.</div>';
    });
});

function initializeFirebaseAuth() {
    return new Promise((resolve, reject) => {
        // Check if Firebase is loaded
        if (typeof firebase === 'undefined') {
            reject(new Error('Firebase SDK not loaded'));
            return;
        }

        // Wait for Firebase to be initialized
        const checkFirebase = () => {
            if (firebase.apps && firebase.apps.length > 0 && firebase.auth && firebase.database) {
                console.log('Firebase initialized, checking auth...');
                
                const auth = firebase.auth();
                
                // Check if already authenticated
                if (auth.currentUser) {
                    console.log('Already authenticated:', auth.currentUser.uid);
                    resolve();
                    return;
                }

                // Try to sign in anonymously
                console.log('Signing in anonymously...');
                auth.signInAnonymously().then(() => {
                    console.log('Anonymous authentication successful:', auth.currentUser.uid);
                    resolve();
                }).catch(error => {
                    console.warn('Anonymous auth failed:', error);
                    // Try to continue anyway - some operations might still work
                    resolve();
                });
            } else {
                console.log('Waiting for Firebase...');
                setTimeout(checkFirebase, 100);
            }
        };
        
        // Timeout after 10 seconds
        setTimeout(() => {
            reject(new Error('Firebase initialization timeout'));
        }, 10000);
        
        checkFirebase();
    });
}

function loadCoinBalance() {
    // Load coins from API with cache busting
    fetch('get_user_coins.php?t=' + Date.now(), {
        method: 'GET',
        headers: {
            'Content-Type': 'application/json'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            document.getElementById('coin-balance').textContent = data.coins + ' Coins';
        } else {
            console.error('Error loading coins:', data.message);
            document.getElementById('coin-balance').textContent = 'Error';
        }
    })
    .catch(error => {
        console.error('Error loading coins:', error);
        document.getElementById('coin-balance').textContent = 'Error';
    });
}

function loadAllAvailableNotes() {
    console.log('loadAllAvailableNotes function called');
    
    const notesList = document.getElementById('notes-list');
    notesList.innerHTML = '<p class="text-muted">Loading available notes...</p>';

    console.log('Loading notes from Firebase...');
    console.log('Current user:', firebase.auth().currentUser);

    firebase.database().ref('notes').once('value').then(snapshot => {
        console.log('Firebase query completed');
        console.log('Snapshot exists:', snapshot.exists());
        console.log('Snapshot has children:', snapshot.numChildren());
        console.log('Snapshot value:', snapshot.val());

        if (!snapshot.exists()) {
            console.log('No snapshot exists');
            notesList.innerHTML = '<div class="alert alert-warning">No notes found in database.</div>';
            return;
        }

        const notes = [];
        snapshot.forEach(childSnapshot => {
            const note = childSnapshot.val();
            note.id = childSnapshot.key;
            console.log('Processing note:', note.id, note.subject_name);
            notes.push(note);
        });

        console.log('Total notes processed:', notes.length);

        if (notes.length === 0) {
            notesList.innerHTML = '<div class="alert alert-info">No notes available.</div>';
            return;
        }

        displayNotes(notes);
    }).catch(error => {
        console.error('Firebase query error:', error);
        notesList.innerHTML = '<div class="alert alert-danger">Error loading notes: ' + error.message + '</div>';
    });
}

function searchNotes() {
    const courseCode = document.getElementById('course_code').value.toUpperCase().trim();
    const subjectName = (document.getElementById('subject_name').value || '').toLowerCase().trim();
    const slot = document.getElementById('slot').value;

    const notesList = document.getElementById('notes-list');
    notesList.innerHTML = '<p class="text-muted">Searching...</p>';

    firebase.database().ref('notes').once('value').then(snapshot => {
        const notes = [];
        snapshot.forEach(childSnapshot => {
            const note = childSnapshot.val();
            note.id = childSnapshot.key;

            // Apply filters
            const matchesCourse = courseCode === '' || (note.course_code && note.course_code.toString().toUpperCase().includes(courseCode));
            const matchesSlot = slot === '' || note.slot === slot;
            const matchesSubject = subjectName === '' || (note.subject_name && note.subject_name.toString().toLowerCase().includes(subjectName));
            const isAvailable = !note.status || note.status === 'available';

            if (matchesCourse && matchesSlot && matchesSubject && isAvailable) {
                notes.push(note);
            }
        });

        if (notes.length === 0) {
            if (courseCode || slot) {
                notesList.innerHTML = '<div class="alert alert-warning"><i class="fas fa-search me-2"></i>No notes found matching your search criteria. Try different filters or <button class="btn btn-link p-0" onclick="loadAllAvailableNotes()">view all notes</button>.</div>';
            } else {
                notesList.innerHTML = '<div class="alert alert-info"><i class="fas fa-info-circle me-2"></i>No notes are currently available for sale.</div>';
            }
            return;
        }

        displayNotes(notes);
    }).catch(error => {
        console.error('Error searching notes:', error);
        notesList.innerHTML = '<div class="alert alert-danger"><i class="fas fa-exclamation-triangle me-2"></i>Error searching notes. Please try again.</div>';
    });
}

function displayNotes(notes) {
    console.log('displayNotes called with', notes.length, 'notes');

    const notesList = document.getElementById('notes-list');
    if (!notesList) {
        console.error('notes-list element not found');
        return;
    }

    let html = `<div class="mb-3">
        <h5 class="text-muted"><i class="fas fa-list me-2"></i>Available Notes (${notes.length})</h5>
    </div>`;

    console.log('Processing notes...');

    notes.forEach(note => {
        const timeAgo = getTimeAgo(note.created_at);

        // Handle both old single image format and new multiple images format
        let images = [];
        if (note.images && Array.isArray(note.images)) {
            images = note.images;
        } else if (note.image_base64) {
            // Convert old format to new format
            images = [{
                base64: note.image_base64,
                mime_type: note.image_mime_type || 'image/jpeg'
            }];
        }

        // Store images for modal access
        if (images.length > 0) {
            storeNoteImages(note.id, images);
        }

        // Create image gallery HTML
        let imageGalleryHtml = '';
        if (images.length > 0) {
            imageGalleryHtml = `
                <div class="image-gallery mb-3" style="position: relative;">
                    <div class="main-image-container" style="position: relative; height: 300px; overflow: hidden; border-radius: 8px; cursor: zoom-in;" onclick="openImageModal('${note.id}', 0)">
                        <img id="main-image-${note.id}" src="data:${images[0].mime_type};base64,${images[0].base64}"
                             class="img-fluid w-100 h-100" style="object-fit: cover;" alt="Note image">
                        <div class="zoom-overlay" style="position: absolute; top: 10px; right: 10px; background: rgba(0,0,0,0.7); color: white; padding: 5px 10px; border-radius: 4px; font-size: 12px;">
                            <i class="fas fa-search-plus me-1"></i>Click to zoom
                        </div>
                        ${images.length > 1 ? `<div class="image-count" style="position: absolute; bottom: 10px; right: 10px; background: rgba(0,0,0,0.7); color: white; padding: 5px 10px; border-radius: 4px; font-size: 12px;">
                            <i class="fas fa-images me-1"></i>${images.length} photos
                        </div>` : ''}
                    </div>
                    ${images.length > 1 ? `
                        <div class="thumbnail-strip d-flex mt-2" style="gap: 8px; overflow-x: auto; padding-bottom: 5px;">
                            ${images.map((img, index) => `
                                <img src="data:${img.mime_type};base64,${img.base64}"
                                     class="thumbnail-img ${index === 0 ? 'active' : ''}"
                                     style="width: 60px; height: 60px; object-fit: cover; border-radius: 4px; cursor: pointer; border: 2px solid ${index === 0 ? '#007bff' : '#dee2e6'};"
                                     onclick="changeMainImage('${note.id}', ${index})"
                                     alt="Thumbnail ${index + 1}">
                            `).join('')}
                        </div>
                    ` : ''}
                </div>
            `;
        } else {
            imageGalleryHtml = `<div class="bg-light d-flex align-items-center justify-content-center mb-3" style="height: 200px; border-radius: 8px;">
                <i class="fas fa-image text-muted fa-3x"></i>
            </div>`;
        }

        html += `
            <div class="card mb-3 note-card">
                ${imageGalleryHtml}
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-8">
                            <h5 class="card-title">${note.subject_name || 'No Title'}</h5>
                            <p class="card-text mb-2">
                                <span class="badge bg-primary me-2">${note.course_code || 'N/A'}</span>
                                <span class="badge bg-secondary me-2">${note.slot || 'N/A'}</span>
                                <span class="badge bg-info">${note.year || 'N/A'} Year</span>
                            </p>
                            <p class="card-text">
                                <strong>Faculty:</strong> ${note.faculty_name || 'N/A'}<br>
                                <small class="text-muted">Posted ${timeAgo}</small>
                            </p>
                            <p class="card-text"><small class="text-muted">${note.description || 'No description available'}</small></p>
                        </div>
                        <div class="col-md-4 text-end">
                            <div class="mb-3">
                                <h4 class="text-success">₹${note.price || 0}</h4>
                                <small class="text-muted">${note.likes || 0} likes</small>
                            </div>
                            <button class="btn btn-primary btn-sm" onclick="contactSeller('${note.seller_id || 'N/A'}', '${note.id}')">
                                <i class="fas fa-envelope me-1"></i>Message (1 coin)
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        `;
    });

    notesList.innerHTML = html;
}

function getTimeAgo(timestamp) {
    if (!timestamp) return 'recently';

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

function testDisplay() {
    console.log('Testing display function...');
    const testNotes = [
        {
            id: 'test1',
            subject_name: 'Test Subject 1',
            course_code: 'CSE101',
            faculty_name: 'Dr. Test Faculty',
            slot: 'A1',
            year: '2',
            description: 'This is a test note description',
            price: 50,
            likes: 5,
            created_at: Date.now(),
            image_base64: null // No image for test
        },
        {
            id: 'test2',
            subject_name: 'Test Subject 2',
            course_code: 'CSE102',
            faculty_name: 'Dr. Another Faculty',
            slot: 'B1',
            year: '3',
            description: 'Another test note',
            price: 75,
            likes: 10,
            created_at: Date.now() - 86400000, // 1 day ago
            image_base64: null
        }
    ];

    // Render these test notes using the existing display function
    displayNotes(testNotes);
}

function contactSeller(sellerId, noteId) {
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
            alert('You need at least 1 coin to message the seller.');
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
                description: 'Message sent to seller for note ' + noteId
            })
        })
        .then(response => response.json())
        .then(updateData => {
            if (!updateData.success) {
                alert('Error deducting coins. Please try again.');
                return;
            }

            // Create chat conversation
            const chatRef = firebase.database().ref('chats').push();
            chatRef.set({
                buyer_id: userId,
                seller_id: sellerId,
                note_id: noteId,
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

// Global variable to store current note images for modal
let currentNoteImages = [];

// Change main image when thumbnail is clicked
function changeMainImage(noteId, imageIndex) {
    const mainImage = document.getElementById(`main-image-${noteId}`);
    const thumbnails = document.querySelectorAll(`[onclick*="changeMainImage('${noteId}'"]`);

    if (!currentNoteImages[noteId] || !currentNoteImages[noteId][imageIndex]) {
        console.error('Image not found for note:', noteId, 'index:', imageIndex);
        return;
    }

    const image = currentNoteImages[noteId][imageIndex];
    mainImage.src = `data:${image.mime_type};base64,${image.base64}`;

    // Update thumbnail active state
    thumbnails.forEach((thumb, index) => {
        if (index === imageIndex) {
            thumb.classList.add('active');
            thumb.style.border = '2px solid #007bff';
        } else {
            thumb.classList.remove('active');
            thumb.style.border = '2px solid #dee2e6';
        }
    });
}

// Open image modal for zoom functionality
function openImageModal(noteId, startIndex = 0) {
    if (!currentNoteImages[noteId] || currentNoteImages[noteId].length === 0) {
        console.error('No images found for note:', noteId);
        return;
    }

    const images = currentNoteImages[noteId];
    let currentImageIndex = startIndex;

    // Create modal HTML
    const modalHtml = `
        <div class="image-modal" id="imageModal" style="position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.9); z-index: 9999; display: flex; align-items: center; justify-content: center; cursor: zoom-out;" onclick="closeImageModal()">
            <div class="modal-content" style="position: relative; max-width: 90%; max-height: 90%;" onclick="event.stopPropagation()">
                <img id="modalImage" src="data:${images[currentImageIndex].mime_type};base64,${images[currentImageIndex].base64}"
                     style="max-width: 100%; max-height: 100%; object-fit: contain;">

                ${images.length > 1 ? `
                    <button class="modal-nav prev" onclick="navigateImage(-1)" style="position: absolute; left: 10px; top: 50%; transform: translateY(-50%); background: rgba(0,0,0,0.7); color: white; border: none; padding: 15px; border-radius: 50%; font-size: 18px; cursor: pointer;">
                        <i class="fas fa-chevron-left"></i>
                    </button>
                    <button class="modal-nav next" onclick="navigateImage(1)" style="position: absolute; right: 10px; top: 50%; transform: translateY(-50%); background: rgba(0,0,0,0.7); color: white; border: none; padding: 15px; border-radius: 50%; font-size: 18px; cursor: pointer;">
                        <i class="fas fa-chevron-right"></i>
                    </button>
                    <div class="image-counter" style="position: absolute; bottom: 20px; left: 50%; transform: translateX(-50%); background: rgba(0,0,0,0.7); color: white; padding: 5px 15px; border-radius: 20px; font-size: 14px;">
                        ${currentImageIndex + 1} / ${images.length}
                    </div>
                ` : ''}

                <button class="modal-close" onclick="closeImageModal()" style="position: absolute; top: 10px; right: 10px; background: rgba(0,0,0,0.7); color: white; border: none; padding: 10px; border-radius: 50%; font-size: 16px; cursor: pointer;">
                    <i class="fas fa-times"></i>
                </button>
            </div>
        </div>
    `;

    document.body.insertAdjacentHTML('beforeend', modalHtml);

    // Store current modal state
    window.currentModalImages = images;
    window.currentModalIndex = currentImageIndex;

    // Add keyboard navigation
    document.addEventListener('keydown', handleModalKeydown);
}

function closeImageModal() {
    const modal = document.getElementById('imageModal');
    if (modal) {
        modal.remove();
    }
    document.removeEventListener('keydown', handleModalKeydown);
}

function navigateImage(direction) {
    if (!window.currentModalImages) return;

    window.currentModalIndex += direction;

    if (window.currentModalIndex < 0) {
        window.currentModalIndex = window.currentModalImages.length - 1;
    } else if (window.currentModalIndex >= window.currentModalImages.length) {
        window.currentModalIndex = 0;
    }

    const image = window.currentModalImages[window.currentModalIndex];
    const modalImage = document.getElementById('modalImage');
    modalImage.src = `data:${image.mime_type};base64,${image.base64}`;

    // Update counter
    const counter = document.querySelector('.image-counter');
    if (counter) {
        counter.textContent = `${window.currentModalIndex + 1} / ${window.currentModalImages.length}`;
    }
}

function handleModalKeydown(event) {
    switch(event.key) {
        case 'ArrowLeft':
            navigateImage(-1);
            break;
        case 'ArrowRight':
            navigateImage(1);
            break;
        case 'Escape':
            closeImageModal();
            break;
    }
}

// Store note images globally for modal access
function storeNoteImages(noteId, images) {
    currentNoteImages[noteId] = images;
}

function testFirebaseConnection() {
    console.log('Testing Firebase connection...');
    
    if (typeof firebase === 'undefined') {
        alert('Firebase not loaded');
        return;
    }
    
    if (!firebase.apps || firebase.apps.length === 0) {
        alert('Firebase not initialized');
        return;
    }
    
    // Test anonymous auth
    firebase.auth().signInAnonymously().then(() => {
        console.log('Anonymous auth successful');
        
        // Test database access
        const notesRef = firebase.database().ref('notes');
        notesRef.once('value').then(snapshot => {
            const count = snapshot.numChildren();
            const exists = snapshot.exists();
            alert('Firebase connection successful!\nNotes found: ' + count + '\nData exists: ' + exists);
            console.log('Connection test successful, notes count:', count, 'exists:', exists);
            if (exists) {
                console.log('Sample data:', snapshot.val());
            }
        }).catch(error => {
            alert('Database access failed: ' + error.message);
            console.error('Database access failed:', error);
        });
    }).catch(authError => {
        alert('Anonymous auth failed: ' + authError.message);
        console.error('Auth failed:', authError);
    });
}
</script>
