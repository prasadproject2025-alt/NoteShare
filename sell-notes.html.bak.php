<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$error_message = '';
$success_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $subject_name = $_POST['subject_name'] ?? '';
    $course_code = $_POST['course_code'] ?? '';
    $faculty_name = $_POST['faculty_name'] ?? '';
    $slot = $_POST['slot'] ?? '';
    $year = $_POST['year'] ?? '';
    $description = $_POST['description'] ?? '';
    $price = $_POST['price'] ?? '';

    // Validate inputs
    if (empty($subject_name) || empty($course_code) || empty($faculty_name) ||
        empty($slot) || empty($year) || empty($description) || empty($price)) {
        $error_message = 'All fields are required.';
    } elseif (!isset($_FILES['note_images']) || empty($_FILES['note_images']['name'][0])) {
        $error_message = 'Please upload at least one image.';
    } else {
        $images = [];
        $total_size = 0;

        // Process multiple images
        foreach ($_FILES['note_images']['tmp_name'] as $key => $tmp_name) {
            if ($_FILES['note_images']['error'][$key] !== UPLOAD_ERR_OK) {
                continue; // Skip failed uploads
            }

            $file_content = file_get_contents($tmp_name);
            $file_size = strlen($file_content);

            // Detect MIME type using finfo with fallbacks (avoid mime_content_type undefined error)
            $mime_type = null;
            if (function_exists('finfo_open')) {
                $finfo = finfo_open(FILEINFO_MIME_TYPE);
                if ($finfo) {
                    $detected = finfo_file($finfo, $tmp_name);
                    if ($detected !== false) {
                        $mime_type = $detected;
                    }
                    finfo_close($finfo);
                }
            }
            // Fallback to getimagesize if finfo not available or failed
            if (empty($mime_type)) {
                $img_info = @getimagesize($tmp_name);
                if ($img_info && !empty($img_info['mime'])) {
                    $mime_type = $img_info['mime'];
                }
            }
            // Final fallback
            if (empty($mime_type)) {
                $mime_type = 'application/octet-stream';
            }

            $file_base64 = base64_encode($file_content);
            $base64_size = strlen($file_base64);

            $total_size += $file_size;

            // Check individual file size
            if ($file_size > 10 * 1024 * 1024) { // 10MB limit per file
                $error_message = 'One or more files are too large. Maximum allowed size per file is 10MB.';
                break;
            }

            $images[] = [
                'base64' => $file_base64,
                'mime_type' => $mime_type,
                'size' => $file_size
            ];
        }

        // Check total size
        if ($total_size > 50 * 1024 * 1024) { // 50MB total limit
            $error_message = 'Total file size too large. Maximum allowed total size is 50MB.';
        } elseif (count($images) > 5) {
            $error_message = 'Maximum 5 images allowed.';
        } elseif (count($images) == 0) {
            $error_message = 'No valid images uploaded.';
        } elseif (empty($error_message)) {
            // Convert images to text using OCR (placeholder)
            $ocr_text = "Note images uploaded and converted to text";

            // Save to Firebase
            include 'includes/firebase_config.php';

            // Store in session for JavaScript to save to Firebase
            $_SESSION['note_data'] = [
                'subject_name' => $subject_name,
                'course_code' => $course_code,
                'faculty_name' => $faculty_name,
                'slot' => $slot,
                'year' => $year,
                'description' => $description,
                'price' => (float)$price,
                'seller_id' => $_SESSION['user_id'],
                'seller_name' => $_SESSION['user_name'],
                'seller_email' => $_SESSION['user_email'],
                'images' => $images, // Array of images instead of single image
                'ocr_text' => $ocr_text,
                'status' => 'available',
                'created_at' => time(),
                'likes' => 0
            ];
            // Redirect to a dedicated upload progress page to avoid duplicate uploads
            header('Location: upload_progress.php');
            exit();
        }
    }
}

include 'includes/header.php';
?>

<main class="container mt-5">
    <div class="row">
        <div class="col-md-8">
            <h2>Sell Your Notes</h2>

            <?php if ($error_message): ?>
                <div class="alert alert-danger"><?php echo htmlspecialchars($error_message); ?></div>
            <?php endif; ?>

            <?php if ($success_message): ?>
                <div class="alert alert-info" id="processing-alert">
                    <i class="fas fa-spinner fa-spin me-2"></i><?php echo htmlspecialchars($success_message); ?>
                </div>
            <?php endif; ?>
            
            <div id="upload-progress" class="alert alert-info d-none">
                <div class="d-flex align-items-center">
                    <i class="fas fa-spinner fa-spin me-2" id="progress-icon"></i>
                    <span id="progress-text">Preparing upload...</span>
                </div>
                <div class="progress mt-3" style="height:10px;">
                    <div id="progress-bar" class="progress-bar progress-bar-striped progress-bar-animated" role="progressbar" style="width:0%"></div>
                </div>
            </div>

            <form method="POST" enctype="multipart/form-data" id="sell-notes-form">
                <div class="mb-3">
                    <label for="subject_name" class="form-label">Subject Name</label>
                    <input type="text" class="form-control" id="subject_name" name="subject_name" required>
                </div>
                

                <div class="mb-3">
                    <label for="course_code" class="form-label">Course Code</label>
                    <input type="text" class="form-control" id="course_code" name="course_code" 
                           placeholder="e.g., CSE101" required>
                </div>

                <div class="mb-3">
                    <label for="faculty_name" class="form-label">Faculty Name</label>
                    <input type="text" class="form-control" id="faculty_name" name="faculty_name" required>
                </div>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="slot" class="form-label">VIT Slot</label>
                        <select class="form-control" id="slot" name="slot" required>
                            <option value="">Select Your Slot</option>
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

                    <div class="col-md-6 mb-3">
                        <label for="year" class="form-label">Year</label>
                        <select class="form-control" id="year" name="year" required>
                            <option value="">Select Year</option>
                            <option value="1">1st Year</option>
                            <option value="2">2nd Year</option>
                            <option value="3">3rd Year</option>
                            <option value="4">4th Year</option>
                        </select>
                    </div>
                </div>

                <div class="mb-3">
                    <label for="description" class="form-label">Description</label>
                    <textarea class="form-control" id="description" name="description" rows="3" required></textarea>
                </div>

                <div class="mb-3">
                    <label for="price" class="form-label">Price (₹)</label>
                    <input type="number" class="form-control" id="price" name="price" 
                           step="0.01" min="0" required>
                </div>

                <div class="mb-3">
                    <label class="form-label">Upload Note Images (Maximum 5)</label>
                    <div id="image-upload-container">
                        <div class="image-upload-item mb-2">
                            <div class="input-group">
                                <input type="file" class="form-control" name="note_images[]" accept="image/*" required>
                                <button type="button" class="btn btn-outline-danger remove-image" style="display: none;">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                    <button type="button" class="btn btn-outline-primary btn-sm" id="add-image-btn">
                        <i class="fas fa-plus me-1"></i>Add Another Image
                    </button>
                    <small class="text-muted d-block mt-1">Upload clear, legible images of your notes (Max 5 images, 10MB each, Total 50MB)</small>
                </div>

                <button type="submit" class="btn btn-success" id="upload-btn">
                    <span id="upload-text">Upload Notes</span>
                    <span id="upload-spinner" class="spinner-border spinner-border-sm d-none" role="status" aria-hidden="true"></span>
                </button>
                <button type="button" id="add-another-btn" class="btn btn-outline-primary ms-2 d-none">Add Another Note</button>
                <div id="upload-status" class="mt-2"></div>

                <!-- Debug information -->
                <div class="mt-3 p-3 bg-light rounded">
                    <h6>Debug Info:</h6>
                    <div id="debug-info">
                        <p><strong>Session user_id:</strong> <?php echo isset($_SESSION['user_id']) ? htmlspecialchars($_SESSION['user_id']) : 'Not set'; ?></p>
                        <p><strong>Request method:</strong> <?php echo $_SERVER['REQUEST_METHOD']; ?></p>
                        <p><strong>Has note_data in session:</strong> <?php echo isset($_SESSION['note_data']) ? 'Yes' : 'No'; ?></p>
                        <?php if (isset($_SESSION['note_data']) && isset($_SESSION['note_data']['images'])): ?>
                        <p><strong>Images count:</strong> <?php echo count($_SESSION['note_data']['images']); ?></p>
                        <?php endif; ?>
                    </div>
                </div>
            </form>
        </div>

        <div class="col-md-4">
            <div class="card">
                <div class="card-header">
                    <h5>Selling Tips</h5>
                </div>
                <div class="card-body">
                    <ul class="list-unstyled">
                        <li>✓ Clear, legible note images</li>
                        <li>✓ Detailed descriptions</li>
                        <li>✓ Competitive pricing</li>
                        <li>✓ Complete course materials</li>
                        <li>✓ Respond quickly to buyers</li>
                    </ul>
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
// Image upload management
document.addEventListener('DOMContentLoaded', function() {
    const addImageBtn = document.getElementById('add-image-btn');
    const imageContainer = document.getElementById('image-upload-container');
    let imageCount = 1;

    addImageBtn.addEventListener('click', function() {
        if (imageCount >= 5) {
            alert('Maximum 5 images allowed');
            return;
        }

        imageCount++;
        const imageItem = document.createElement('div');
        imageItem.className = 'image-upload-item mb-2';
        imageItem.innerHTML = `
            <div class="input-group">
                <input type="file" class="form-control" name="note_images[]" accept="image/*">
                <button type="button" class="btn btn-outline-danger remove-image">
                    <i class="fas fa-trash"></i>
                </button>
            </div>
        `;

        imageContainer.appendChild(imageItem);
        updateRemoveButtons();
    });

    // Handle remove buttons
    imageContainer.addEventListener('click', function(e) {
        if (e.target.classList.contains('remove-image') || e.target.closest('.remove-image')) {
            e.preventDefault();
            const imageItem = e.target.closest('.image-upload-item');
            if (imageItem) {
                imageItem.remove();
                imageCount--;
                updateRemoveButtons();
            }
        }
    });

    function updateRemoveButtons() {
        const removeButtons = document.querySelectorAll('.remove-image');
        removeButtons.forEach(button => {
            button.style.display = imageCount > 1 ? 'block' : 'none';
        });
    }

    // Initial setup
    updateRemoveButtons();
});

// Form submission debugging
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('sell-notes-form');
    const uploadBtn = document.getElementById('upload-btn');
    const uploadText = document.getElementById('upload-text');
    const uploadSpinner = document.getElementById('upload-spinner');
    const uploadStatus = document.getElementById('upload-status');

    if (form) {
        form.addEventListener('submit', function(e) {
            console.log('Form submitted');

            // Show loading state
            uploadBtn.disabled = true;
            uploadText.textContent = 'Processing...';
            uploadSpinner.classList.remove('d-none');
            uploadStatus.innerHTML = '<div class="text-info">Submitting form...</div>';
        });
    }
});
</script>

<script>
const userId = '<?php echo $_SESSION['user_id']; ?>';

// Save note to Firebase after form submission
<?php if (isset($_SESSION['note_data']) && $_SERVER['REQUEST_METHOD'] === 'POST'): ?>
// Wait for Firebase to be fully initialized
function waitForFirebase() {
    return new Promise((resolve, reject) => {
        let attempts = 0;
        const maxAttempts = 100; // 10 seconds max wait
        
        const checkFirebase = setInterval(() => {
            attempts++;
            
            if (typeof firebase !== 'undefined') {
                console.log('Firebase object found');
                if (firebase.database && firebase.database().ref) {
                    console.log('Firebase database API available');
                    clearInterval(checkFirebase);
                    resolve();
                    return;
                } else {
                    console.log('Waiting for Firebase database API... attempt', attempts);
                }
            } else {
                console.log('Waiting for Firebase SDK... attempt', attempts);
            }
            
            if (attempts >= maxAttempts) {
                clearInterval(checkFirebase);
                reject(new Error('Firebase initialization timeout after 10 seconds. Check if Firebase SDK is loaded correctly.'));
            }
        }, 100);
    });
}

document.addEventListener('DOMContentLoaded', function() {
    <?php
    $noteDataJson = 'null';
    if (isset($_SESSION['note_data'])) {
        $noteDataJson = json_encode($_SESSION['note_data']);
        if ($noteDataJson === false) {
            // JSON encoding failed, probably due to size
            $noteDataJson = 'null';
            error_log('Failed to json_encode note_data: ' . json_last_error_msg());
        }
    }
    ?>
    const noteData = <?php echo $noteDataJson; ?>;
    const progressDiv = document.getElementById('upload-progress');
    const progressText = document.getElementById('progress-text');

    console.log('Page loaded, noteData:', noteData ? 'present' : 'null');
    // Only proceed if we have note data to save
    if (!noteData) {
        console.log('No note data to save, skipping Firebase upload');
        return;
    }

    console.log('Note data found, starting Firebase upload process');

    // Show progress indicator
    if (progressDiv) {
        progressDiv.classList.remove('d-none');
        progressText.textContent = 'Initializing Firebase...';
    }
    // helper to update progress bar and text
    function setProgress(percent, text) {
        const pbar = document.getElementById('progress-bar');
        if (pbar) {
            pbar.style.width = percent + '%';
            pbar.setAttribute('aria-valuenow', percent);
        }
        if (text && progressText) progressText.textContent = text;
    }
    setProgress(5, 'Initializing...');
    
    // Convert created_at from Unix timestamp to milliseconds for Firebase
    noteData.created_at = noteData.created_at * 1000;
    
    console.log('Attempting to save note to Firebase');
    console.log('Note data keys:', Object.keys(noteData));
    console.log('Note data size:', JSON.stringify(noteData).length, 'bytes');
    
    // Wait for Firebase to be ready
    waitForFirebase().then(() => {
        console.log('Firebase is ready');
        if (progressText) progressText.textContent = 'Firebase ready. Authenticating...';
        setProgress(25, 'Firebase ready. Authenticating...');
        
        if (!firebase || !firebase.database) {
            throw new Error('Firebase database not available');
        }
        
        // Ensure we're authenticated (anonymous auth)
        const auth = firebase.auth();
        return new Promise((resolve) => {
            // Check if already signed in
            if (auth.currentUser) {
                console.log('Already authenticated:', auth.currentUser.uid);
                resolve();
                return;
            }
            
            // Sign in anonymously
            console.log('Signing in anonymously...');
            if (progressText) progressText.textContent = 'Signing in...';
            setProgress(45, 'Signing in...');
            
            auth.signInAnonymously().then(() => {
                console.log('Anonymous authentication successful:', auth.currentUser.uid);
                if (progressText) progressText.textContent = 'Authentication successful. Preparing data...';
                setProgress(60, 'Authentication successful. Preparing data...');
                resolve();
            }).catch((error) => {
                console.warn('Anonymous auth failed:', error);
                console.warn('Error code:', error.code, 'Error message:', error.message);
                if (progressText) progressText.textContent = 'Auth failed, continuing anyway...';
                setProgress(50, 'Auth failed, continuing...');
                // Continue anyway - rules might allow unauthenticated writes
                resolve();
            });
        });
    }).then(() => {
        // Wait a bit more for everything to be ready
        return new Promise((resolve) => {
            setTimeout(() => {
                const notesRef = firebase.database().ref('notes');
                console.log('Notes reference created');
                resolve(notesRef);
            }, 500);
        });
    }).then((notesRef) => {
        console.log('Pushing data to Firebase...');
        if (progressText) progressText.textContent = 'Uploading to Firebase...';
        setProgress(75, 'Uploading to Firebase...');

        // Check if images are too large (Firebase has limits)
        let totalSize = 0;
        if (noteData.images && Array.isArray(noteData.images)) {
            noteData.images.forEach(img => {
                if (img.base64) totalSize += img.base64.length;
            });
            console.log('Total images size:', totalSize, 'bytes');
        }

        if (totalSize > 50 * 1024 * 1024) {
            throw new Error('Images are too large for Firebase. Maximum total size is 50MB.');
        }

        if (progressText) progressText.textContent = 'Saving data (this may take a moment for large images)...';

        return notesRef.push(noteData);
    }).then((snapshot) => {
        console.log('Note saved successfully with key:', snapshot.key);
        if (progressText) progressText.textContent = 'Upload successful! Clearing session...';

        // Clear session data then show final success UI (do NOT reload to avoid re-upload)
        fetch('clear_session.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: 'clear_note_data=1'
        }).then(() => {
            if (progressDiv) {
                progressDiv.classList.remove('alert-info');
                progressDiv.classList.add('alert-success');
                const icon = document.getElementById('progress-icon');
                if (icon) icon.className = 'fas fa-check me-2';
                progressText.innerHTML = 'Note uploaded successfully!';
            }

            // Fill progress bar to 100%
            setProgress(100, 'Upload complete');

            // Show Add Another button and keep form visible for additional uploads
            const addAnother = document.getElementById('add-another-btn');
            const uploadBtnEl = document.getElementById('upload-btn');
            if (addAnother) addAnother.classList.remove('d-none');
            if (uploadBtnEl) uploadBtnEl.disabled = true;

            // When user clicks Add Another, reset form and UI
            if (addAnother) {
                addAnother.addEventListener('click', function() {
                    // Reset UI
                    if (progressDiv) {
                        progressDiv.classList.remove('alert-success', 'alert-warning', 'alert-danger');
                        progressDiv.classList.add('d-none', 'alert-info');
                        const icon = document.getElementById('progress-icon');
                        if (icon) icon.className = 'fas fa-spinner fa-spin me-2';
                        const ptext = document.getElementById('progress-text');
                        if (ptext) ptext.textContent = 'Preparing upload...';
                        const pbar2 = document.getElementById('progress-bar');
                        if (pbar2) pbar2.style.width = '0%';
                    }
                    // Reset form fields
                    const form = document.getElementById('sell-notes-form');
                    if (form) form.reset();
                    // hide Add Another, re-enable upload button
                    addAnother.classList.add('d-none');
                    if (uploadBtnEl) {
                        uploadBtnEl.disabled = false;
                        const uploadTextEl = document.getElementById('upload-text');
                        const uploadSpinnerEl = document.getElementById('upload-spinner');
                        if (uploadTextEl) uploadTextEl.textContent = 'Upload Notes';
                        if (uploadSpinnerEl) uploadSpinnerEl.classList.add('d-none');
                    }
                });
            }
        }).catch(error => {
            console.error('Error clearing session:', error);
            if (progressDiv) {
                progressDiv.classList.remove('alert-info');
                progressDiv.classList.add('alert-warning');
                progressText.innerHTML = '<i class="fas fa-exclamation-triangle me-2"></i>Note saved but session clear failed.';
            }
            // still show Add Another so user can proceed
            const addAnother = document.getElementById('add-another-btn');
            if (addAnother) addAnother.classList.remove('d-none');
        });
    }).catch(error => {
        console.error('Firebase error:', error);
        console.error('Error code:', error.code);
        console.error('Error message:', error.message);
        console.error('Full error:', error);
        
        if (progressDiv) {
            progressDiv.classList.remove('alert-info');
            progressDiv.classList.add('alert-danger');
            let errorDisplay = '<i class="fas fa-times me-2"></i>Error: ' + error.message;
            if (error.code) {
                errorDisplay += '<br><small>Error code: ' + error.code + '</small>';
            }
            progressText.innerHTML = errorDisplay;
        }
        
        let errorMsg = 'Error saving to Firebase: ' + error.message;
        if (error.code) {
            errorMsg += '\n\nError code: ' + error.code;
            if (error.code === 'PERMISSION_DENIED') {
                errorMsg += '\n\nThis usually means Firebase security rules are blocking the write.';
                errorMsg += '\nPlease check Firebase Console → Realtime Database → Rules';
                errorMsg += '\nMake sure rules allow writes for authenticated users.';
            }
        }
        errorMsg += '\n\nPlease check the browser console (F12) for more details.';
        
        alert(errorMsg);
    });
    
    // Add timeout protection - if upload takes more than 60 seconds, show error
    setTimeout(() => {
        if (progressDiv && progressDiv.classList.contains('alert-info')) {
            console.error('Upload timeout - taking too long');
            progressDiv.classList.remove('alert-info');
            progressDiv.classList.add('alert-warning');
            progressText.innerHTML = '<i class="fas fa-exclamation-triangle me-2"></i>Upload is taking longer than expected. Check console for errors or try again.';
        }
    }, 60000); // 60 second timeout
});
<?php endif; ?>

// Add form submission handler to show loading state
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('sell-notes-form');
    const uploadBtn = document.getElementById('upload-btn');
    const uploadText = document.getElementById('upload-text');
    const uploadSpinner = document.getElementById('upload-spinner');
    const progressDivMain = document.getElementById('upload-progress');

    if (form && uploadBtn) {
        form.addEventListener('submit', function() {
            uploadBtn.disabled = true;
            if (uploadText) uploadText.textContent = 'Processing...';
            if (uploadSpinner) uploadSpinner.classList.remove('d-none');
            if (progressDivMain) {
                progressDivMain.classList.remove('d-none');
            }
            // small visual progress
            try { setProgress(10, 'Preparing upload...'); } catch (e) {}
        });
    }
});
</script>
