<?php
session_start();
include 'includes/header.php';
?>

<main class="container mt-5">
    <div class="row">
        <div class="col-md-12">
            <h2>Debug: Buy Notes Display</h2>
            <div class="alert alert-info">
                <strong>Debug Mode:</strong> This page bypasses authentication to test display functionality.
            </div>

            <div id="notes-list">
                <p class="text-muted">Loading notes...</p>
            </div>

            <button class="btn btn-primary mt-3" onclick="loadTestNotes()">Load Test Notes</button>
            <button class="btn btn-success mt-3" onclick="loadFirebaseNotes()">Load Firebase Notes</button>
            <button class="btn btn-info mt-3" onclick="validateImages()">Validate Images</button>
            <button class="btn btn-warning mt-3" onclick="testImageDisplay()">Test Image Display</button>
            <button class="btn btn-danger mt-3" onclick="testKnownImages()">Test Known Images</button>
        </div>
    </div>
</main>

<!-- Firebase SDK v8 (compat mode for legacy code) -->
<script src="https://www.gstatic.com/firebasejs/8.10.1/firebase-app.js"></script>
<script src="https://www.gstatic.com/firebasejs/8.10.1/firebase-database.js"></script>
<script src="https://www.gstatic.com/firebasejs/8.10.1/firebase-auth.js"></script>

<script src="js/firebase-config.js"></script>

<script>
function loadTestNotes() {
    console.log('Loading test notes...');
    const testNotes = [
        {
            id: 'test1',
            subject_name: 'Data Structures',
            course_code: 'CSE201',
            faculty_name: 'Dr. Smith',
            slot: 'A1',
            year: '2',
            description: 'Complete notes on data structures including trees, graphs, and algorithms.',
            price: 50,
            likes: 5,
            created_at: Date.now(),
            image_base64: null
        },
        {
            id: 'test2',
            subject_name: 'Database Management',
            course_code: 'CSE301',
            faculty_name: 'Dr. Johnson',
            slot: 'B1',
            year: '3',
            description: 'Comprehensive DBMS notes with SQL examples and ER diagrams.',
            price: 75,
            likes: 12,
            created_at: Date.now() - 86400000,
            image_base64: null
        }
    ];

    displayNotes(testNotes);
}

function loadFirebaseNotes() {
    console.log('Loading Firebase notes...');
    const notesList = document.getElementById('notes-list');
    notesList.innerHTML = '<p class="text-info">Connecting to Firebase...</p>';

    // Initialize Firebase auth
    firebase.auth().signInAnonymously().then(() => {
        console.log('Authenticated anonymously');

        firebase.database().ref('notes').once('value').then(snapshot => {
            console.log('Firebase query completed, exists:', snapshot.exists());
            console.log('Number of notes:', snapshot.numChildren());

            const notes = [];
            snapshot.forEach(childSnapshot => {
                const note = childSnapshot.val();
                note.id = childSnapshot.key;
                console.log('Found note:', note.subject_name, 'Status:', note.status);
                notes.push(note);
            });

            if (notes.length === 0) {
                notesList.innerHTML = '<div class="alert alert-warning">No notes found in Firebase database.</div>';
            } else {
                displayNotes(notes);
            }
        }).catch(error => {
            console.error('Firebase error:', error);
            notesList.innerHTML = '<div class="alert alert-danger">Firebase Error: ' + error.message + '</div>';
        });
    }).catch(authError => {
        console.error('Auth error:', authError);
        notesList.innerHTML = '<div class="alert alert-danger">Authentication Error: ' + authError.message + '</div>';
    });
}

function displayNotes(notes) {
    const notesList = document.getElementById('notes-list');

    let html = `<div class="mb-3">
        <h5 class="text-muted"><i class="fas fa-list me-2"></i>Available Notes (${notes.length})</h5>
    </div>`;

    notes.forEach(note => {
        const timeAgo = getTimeAgo(note.created_at);
        
        // DEBUG: Log image data
        console.log('Note:', note.subject_name, 'has image_base64:', !!note.image_base64);
        if (note.image_base64) {
            console.log('Image MIME type:', note.image_mime_type);
            console.log('Image base64 length:', note.image_base64.length);
            console.log('Image base64 starts with:', note.image_base64.substring(0, 50));
        }
        
        // Determine image source
        let imageSrc = '';
        if (note.image_base64) {
            let base64Data = note.image_base64;
            
            // Check if base64 already includes data URL prefix
            if (base64Data.startsWith('data:')) {
                imageSrc = base64Data;
                console.log('Using full data URL for', note.subject_name);
            } else {
                const mimeType = note.image_mime_type || 'image/jpeg';
                imageSrc = `data:${mimeType};base64,${base64Data}`;
                console.log('Constructed data URL for', note.subject_name, ':', imageSrc.substring(0, 100) + '...');
            }
            
            // Test if base64 is valid
            try {
                const dataToTest = base64Data.startsWith('data:') ? base64Data.split(',')[1] : base64Data;
                atob(dataToTest);
                console.log('Base64 is valid for', note.subject_name);
            } catch (e) {
                console.error('Base64 is INVALID for', note.subject_name, ':', e.message);
                console.error('Base64 data:', base64Data.substring(0, 100));
            }
        }
        
        const imageHtml = imageSrc ?
            `<img src="${imageSrc}" class="card-img-top" alt="Note image" style="height: 200px; object-fit: cover;" onload="console.log('Image loaded successfully for', '${note.subject_name}')" onerror="console.error('Image failed to load for', '${note.subject_name}', 'Error:', event); this.style.border='2px solid red';">` :
            `<div class="bg-light d-flex align-items-center justify-content-center" style="height: 200px;"><i class="fas fa-image text-muted fa-3x"></i></div>`;

        const fallbackHtml = imageSrc ? 
            `<div class="bg-light d-flex align-items-center justify-content-center" style="height: 200px; display: none;"><i class="fas fa-image text-muted fa-3x"></i><br><small class="text-muted">Image failed to load</small></div>` : '';

        html += `
            <div class="card mb-3 note-card">
                ${imageHtml}
                ${fallbackHtml}
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-8">
                            <h5 class="card-title">${note.subject_name || 'No Subject'}</h5>
                            <p class="card-text mb-2">
                                <span class="badge bg-primary me-2">${note.course_code || 'N/A'}</span>
                                <span class="badge bg-secondary me-2">${note.slot || 'N/A'}</span>
                                <span class="badge bg-info">${note.year || 'N/A'} Year</span>
                            </p>
                            <p class="card-text">
                                <strong>Faculty:</strong> ${note.faculty_name || 'N/A'}<br>
                                <small class="text-muted">Posted ${timeAgo}</small>
                            </p>
                            <p class="card-text"><small class="text-muted">${note.description || 'No description'}</small></p>
                        </div>
                        <div class="col-md-4 text-end">
                            <div class="mb-3">
                                <h4 class="text-success">₹${note.price || 0}</h4>
                                <small class="text-muted">${note.likes || 0} likes</small>
                            </div>
                            <button class="btn btn-primary btn-sm" onclick="alert('Message functionality not available in debug mode')">
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

async function validateImages() {
    updateResults('Validating image data...', 'info');

    try {
        // Ensure authenticated first
        if (!firebase.auth().currentUser) {
            await firebase.auth().signInAnonymously();
        }

        const snapshot = await firebase.database().ref('notes').once('value');

        if (snapshot.exists()) {
            const imageStats = {
                total: 0,
                withImages: 0,
                validImages: 0,
                invalidImages: 0,
                mimeTypes: {},
                errors: []
            };

            snapshot.forEach(child => {
                const note = child.val();
                imageStats.total++;

                if (note.image_base64) {
                    imageStats.withImages++;

                    // Check if base64 is valid
                    try {
                        // Try to decode base64
                        atob(note.image_base64);
                        imageStats.validImages++;

                        // Check MIME type
                        const mimeType = note.image_mime_type || 'unknown';
                        imageStats.mimeTypes[mimeType] = (imageStats.mimeTypes[mimeType] || 0) + 1;

                        console.log(`Valid image in note ${child.key}: ${mimeType}, size: ${note.image_base64.length} chars`);
                        console.log('Base64 preview:', note.image_base64.substring(0, 50) + '...');
                    } catch (e) {
                        imageStats.invalidImages++;
                        imageStats.errors.push(`Note ${child.key}: ${e.message}`);
                        console.error(`Invalid base64 in note ${child.key}:`, e.message);
                        console.error('Base64 data:', note.image_base64.substring(0, 100));
                    }
                } else {
                    console.log(`Note ${child.key} has no image data`);
                }
            });

            updateResults(`Image validation complete. Found ${imageStats.withImages} notes with images, ${imageStats.validImages} valid, ${imageStats.invalidImages} invalid.`, 'success');
            updateRawData(imageStats);
        } else {
            updateResults('⚠️ No notes found to validate', 'warning');
        }
    } catch (error) {
        updateResults('❌ Error validating images: ' + error.message, 'error');
        updateRawData({ error: error.message, code: error.code });
    }
}

// Test image display with known good base64 data
function testImageDisplay() {
    console.log('Testing image display with known base64 data...');
    
    // Test with a small transparent PNG (1x1 pixel)
    const testNotes = [
        {
            id: 'test1',
            subject_name: 'Test PNG Image',
            course_code: 'TEST101',
            faculty_name: 'Test Faculty',
            slot: 'A1',
            year: '1',
            description: 'Testing PNG image display',
            price: 10,
            likes: 0,
            created_at: Date.now(),
            image_base64: 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNkYPhfDwAChwGA60e6kgAAAABJRU5ErkJggg==',
            image_mime_type: 'image/png'
        },
        {
            id: 'test2',
            subject_name: 'Test JPEG Image',
            course_code: 'TEST102',
            faculty_name: 'Test Faculty',
            slot: 'B1',
            year: '2',
            description: 'Testing JPEG image display',
            price: 20,
            likes: 0,
            created_at: Date.now(),
            image_base64: '/9j/4AAQSkZJRgABAQEAYABgAAD/2wBDAAEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQH/2wBDAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQH/wAARCAABAAEDASIAAhEBAxEB/8QAFQABAQAAAAAAAAAAAAAAAAAAAAv/xAAUEAEAAAAAAAAAAAAAAAAAAAAA/8QAFQEBAQAAAAAAAAAAAAAAAAAAAAX/xAAUEQEAAAAAAAAAAAAAAAAAAAAA/9oADAMBAAIRAxEAPwA/vAA='
        }
    ];
    
    displayNotes(testNotes);
}

// Auto-load test notes on page load
document.addEventListener('DOMContentLoaded', function() {
    setTimeout(loadTestNotes, 1000);
});
</script>

<style>
.note-card {
    transition: all 0.3s ease;
}

.note-card:hover {
    border-color: #007bff;
    box-shadow: 0 4px 12px rgba(0, 123, 255, 0.2);
}
</style>

<?php include 'includes/footer.php'; ?>