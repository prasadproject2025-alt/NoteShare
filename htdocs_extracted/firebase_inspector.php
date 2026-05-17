<?php
session_start();
include 'includes/header.php';
?>

<main class="container mt-5">
    <div class="row">
        <div class="col-md-12">
            <h2>Firebase Data Inspector</h2>
            <div class="alert alert-info">
                <strong>Debug Tool:</strong> This page inspects the raw Firebase data to see what notes contain.
            </div>

            <div id="data-output">
                <p class="text-muted">Click "Inspect Data" to load Firebase data...</p>
            </div>

            <button class="btn btn-primary mt-3" onclick="inspectFirebaseData()">Inspect Firebase Data</button>
            <button class="btn btn-secondary mt-3" onclick="clearOutput()">Clear Output</button>
        </div>
    </div>
</main>

<!-- Firebase SDK v8 (compat mode for legacy code) -->
<script src="https://www.gstatic.com/firebasejs/8.10.1/firebase-app.js"></script>
<script src="https://www.gstatic.com/firebasejs/8.10.1/firebase-database.js"></script>
<script src="https://www.gstatic.com/firebasejs/8.10.1/firebase-auth.js"></script>

<script src="js/firebase-config.js"></script>

<script>
function inspectFirebaseData() {
    const output = document.getElementById('data-output');
    output.innerHTML = '<p class="text-info">Connecting to Firebase...</p>';

    // Initialize Firebase auth
    firebase.auth().signInAnonymously().then(() => {
        output.innerHTML += '<p class="text-success">✓ Authenticated</p>';

        firebase.database().ref('notes').once('value').then(snapshot => {
            output.innerHTML += '<p class="text-success">✓ Database query completed</p>';
            output.innerHTML += '<p class="text-info">Found ' + snapshot.numChildren() + ' notes</p>';

            let html = '<div class="mt-3"><h4>Raw Note Data:</h4>';

            snapshot.forEach(childSnapshot => {
                const note = childSnapshot.val();
                const noteId = childSnapshot.key;

                html += `
                <div class="card mb-3">
                    <div class="card-header">
                        <strong>Note ID:</strong> ${noteId}<br>
                        <strong>Subject:</strong> ${note.subject_name || 'N/A'}
                    </div>
                    <div class="card-body">
                        <h6>All Fields:</h6>
                        <pre class="bg-light p-2 small">${JSON.stringify(note, null, 2)}</pre>

                        <h6>Image Data Check:</h6>
                        <ul>
                            <li><strong>Has image_base64:</strong> ${!!note.image_base64}</li>
                            <li><strong>image_base64 length:</strong> ${note.image_base64 ? note.image_base64.length : 'N/A'}</li>
                            <li><strong>image_mime_type:</strong> ${note.image_mime_type || 'N/A'}</li>
                        </ul>

                        ${note.image_base64 ? `
                        <h6>Image Preview:</h6>
                        <img src="data:${note.image_mime_type || 'image/jpeg'};base64,${note.image_base64}"
                             style="max-width: 200px; max-height: 200px;" alt="Note image"
                             onerror="this.style.display='none'; this.nextElementSibling.style.display='block';">
                        <div style="display: none; background: #f8f9fa; padding: 20px; text-align: center;">
                            <i class="fas fa-exclamation-triangle text-warning"></i>
                            Image failed to load
                        </div>
                        ` : '<p class="text-muted">No image data</p>'}
                    </div>
                </div>
                `;
            });

            html += '</div>';
            output.innerHTML = html;
        }).catch(error => {
            output.innerHTML += '<p class="text-danger">✗ Database error: ' + error.message + '</p>';
        });
    }).catch(authError => {
        output.innerHTML += '<p class="text-danger">✗ Auth error: ' + authError.message + '</p>';
    });
}

function clearOutput() {
    document.getElementById('data-output').innerHTML = '<p class="text-muted">Output cleared. Click "Inspect Data" to load Firebase data...</p>';
}
</script>

<?php include 'includes/footer.php'; ?>