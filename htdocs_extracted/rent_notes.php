<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

include 'includes/header.php';
?>

<main class="container mt-5">
    <div class="row">
        <div class="col-md-8">
            <h2>Rent Notes</h2>
            <p class="text-muted">Rent notes temporarily for a short period</p>

            <div class="card mb-4">
                <div class="card-header">
                    <h5>Search Rentable Notes</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="rent_course" class="form-label">Course Code</label>
                            <input type="text" class="form-control" id="rent_course" placeholder="e.g., CSE101">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="rent_slot" class="form-label">VIT Slot</label>
                            <select class="form-control" id="rent_slot">
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
                    <button class="btn btn-primary" onclick="searchRentableNotes()">Search</button>
                </div>
            </div>

            <div id="rentable-notes">
                <p class="text-muted">Search for rentable notes above.</p>
            </div>

            <hr>

            <h3>Rent Out Your Notes</h3>

            <form id="rent-form" enctype="multipart/form-data">
                <div class="mb-3">
                    <label for="rent_subject" class="form-label">Subject Name</label>
                    <input type="text" class="form-control" id="rent_subject" required>
                </div>

                <div class="mb-3">
                    <label for="rent_course_code" class="form-label">Course Code</label>
                    <input type="text" class="form-control" id="rent_course_code" required>
                </div>

                <div class="mb-3">
                    <label for="rent_faculty" class="form-label">Faculty Name</label>
                    <input type="text" class="form-control" id="rent_faculty" required>
                </div>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="rent_slot_select" class="form-label">VIT Slot</label>
                        <select class="form-control" id="rent_slot_select" required>
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
                        <label for="rent_year" class="form-label">Year</label>
                        <select class="form-control" id="rent_year" required>
                            <option value="">Select Year</option>
                            <option value="1">1st Year</option>
                            <option value="2">2nd Year</option>
                            <option value="3">3rd Year</option>
                            <option value="4">4th Year</option>
                        </select>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="daily_price" class="form-label">Daily Rental Price (₹)</label>
                        <input type="number" class="form-control" id="daily_price" step="0.01" min="0" required>
                    </div>

                    <div class="col-md-6 mb-3">
                        <label for="rental_period" class="form-label">Rental Period (Days)</label>
                        <input type="number" class="form-control" id="rental_period" min="1" max="30" value="7" required>
                    </div>
                </div>

                <div class="mb-3">
                    <label for="rent_description" class="form-label">Description</label>
                    <textarea class="form-control" id="rent_description" rows="3" required></textarea>
                </div>

                <div class="mb-3">
                    <label for="rent_image" class="form-label">Upload Note Images (up to 5)</label>
                    <input type="file" class="form-control" id="rent_image" accept="image/*" multiple required>
                    <div class="form-text">You may upload up to 5 images. At least one image is required.</div>
                </div>

                <div class="form-check mb-3">
                    <input class="form-check-input" type="checkbox" id="can_extend" checked>
                    <label class="form-check-label" for="can_extend">
                        Allow Rental Extension
                    </label>
                </div>

                <button type="submit" class="btn btn-warning">Post for Rent</button>
            </form>
        </div>

        <div class="col-md-4">
            <div class="card">
                <div class="card-header">
                    <h5>How Rental Works</h5>
                </div>
                <div class="card-body">
                    <ol class="list-unstyled small">
                        <li><strong>1.</strong> Post your notes for rent</li>
                        <li><strong>2.</strong> Set daily rental price</li>
                        <li><strong>3.</strong> Define rental period</li>
                        <li><strong>4.</strong> Users rent your notes</li>
                        <li><strong>5.</strong> Access is revoked after period</li>
                        <li><strong>6.</strong> Earn coins for each rental</li>
                    </ol>
                </div>
            </div>

            <div class="card mt-3">
                <div class="card-header">
                    <h5>Rental Tips</h5>
                </div>
                <div class="card-body">
                    <ul class="list-unstyled small">
                        <li>✓ Competitive pricing</li>
                        <li>✓ Clear note quality</li>
                        <li>✓ Quick approval</li>
                        <li>✓ Professional notes</li>
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
<script src="https://www.gstatic.com/firebasejs/8.10.1/firebase-storage.js"></script>
<script src="https://www.gstatic.com/firebasejs/8.10.1/firebase-auth.js"></script>

<script src="js/firebase-config.js"></script>
<script>
const userId = '<?php echo $_SESSION['user_id']; ?>';

document.getElementById('rent-form').addEventListener('submit', function(e) {
    e.preventDefault();
    postRentalNote();
});

function searchRentableNotes() {
    const courseCode = document.getElementById('rent_course').value.toUpperCase();
    const slot = document.getElementById('rent_slot').value;

    if (!courseCode && !slot) {
        alert('Please enter course code or select slot');
        return;
    }

    const notesDiv = document.getElementById('rentable-notes');
    notesDiv.innerHTML = '<p class="text-muted">Loading...</p>';

    firebase.database().ref('rental_notes').once('value').then(snapshot => {
        const notes = [];
        snapshot.forEach(childSnapshot => {
            const note = childSnapshot.val();
            note.id = childSnapshot.key;

            if ((courseCode === '' || note.course_code === courseCode) &&
                (slot === '' || note.slot === slot) &&
                note.available === true) {
                notes.push(note);
            }
        });

        if (notes.length === 0) {
            notesDiv.innerHTML = '<p class="text-muted">No rental notes found.</p>';
            return;
        }

        let html = '';
        notes.forEach(note => {
            const totalPrice = note.daily_price * note.rental_period;
            html += `
                <div class="card mb-3">
                    <div class="card-body">
                        <h5 class="card-title">${note.subject_name}</h5>
                        <p class="card-text">
                            <strong>Course:</strong> ${note.course_code}<br>
                            <strong>Faculty:</strong> ${note.faculty_name}<br>
                            <strong>Slot:</strong> ${note.slot}<br>
                            <strong>Year:</strong> ${note.year}<br>
                            <strong>Daily Price:</strong> ₹${note.daily_price}<br>
                            <strong>Period:</strong> ${note.rental_period} days<br>
                            <strong>Total Cost:</strong> ₹${totalPrice}
                        </p>
                        <button class="btn btn-warning btn-sm" onclick="rentNote('${note.id}', '${note.renter_id}', ${totalPrice})">
                            Rent Now
                        </button>
                    </div>
                </div>
            `;
        });
        notesDiv.innerHTML = html;
    });
}

function rentNote(noteId, renterId, totalPrice) {
    // Get user's coins
    const coinsRef = firebase.database().ref('users/' + userId + '/coins');
    coinsRef.once('value').then(snapshot => {
        const coins = snapshot.val() || 0;
        const requiredCoins = Math.ceil(totalPrice / 10); // Assuming 1 coin = ₹10

        if (coins < requiredCoins) {
            alert('You need ' + requiredCoins + ' coins to rent this note. You have ' + coins + ' coins.');
            return;
        }

        // Deduct coins
        coinsRef.set(coins - requiredCoins);

        // Create rental agreement
        const rentalRef = firebase.database().ref('rentals').push();
        rentalRef.set({
            note_id: noteId,
            renter_id: renterId,
            renter_name: '<?php echo $_SESSION['user_name']; ?>',
            renter_email: '<?php echo $_SESSION['user_email']; ?>',
            rental_start: Date.now(),
            status: 'active',
            coins_paid: requiredCoins
        }).then(() => {
            alert('Note rented successfully! You have access for the specified period.');
            document.getElementById('rent_course').value = '';
            document.getElementById('rent_slot').value = '';
            searchRentableNotes();
        });
    });
}

function postRentalNote() {
    const files = Array.from(document.getElementById('rent_image').files);
    if (!files || files.length === 0) {
        alert('Please select at least one image');
        return;
    }
    if (files.length > 5) {
        alert('You can upload a maximum of 5 images');
        return;
    }

    function readFileAsDataURL(file) {
        return new Promise((resolve, reject) => {
            const reader = new FileReader();
            reader.onload = e => resolve(e.target.result);
            reader.onerror = e => reject(e);
            reader.readAsDataURL(file);
        });
    }

    Promise.all(files.map(f => readFileAsDataURL(f))).then(imageBase64Array => {
        const rentalNoteData = {
            subject_name: document.getElementById('rent_subject').value,
            course_code: document.getElementById('rent_course_code').value,
            faculty_name: document.getElementById('rent_faculty').value,
            slot: document.getElementById('rent_slot_select').value,
            year: document.getElementById('rent_year').value,
            daily_price: parseFloat(document.getElementById('daily_price').value),
            rental_period: parseInt(document.getElementById('rental_period').value),
            description: document.getElementById('rent_description').value,
            can_extend: document.getElementById('can_extend').checked,
            images_base64: imageBase64Array,
            renter_id: userId,
            renter_name: '<?php echo $_SESSION['user_name']; ?>',
            available: true,
            created_at: Date.now()
        };

        firebase.database().ref('rental_notes').push(rentalNoteData).then(() => {
            alert('Rental note posted successfully!');
            document.getElementById('rent-form').reset();
        }).catch(error => {
            alert('Error posting rental: ' + error.message);
        });
    }).catch(err => {
        alert('Error reading images: ' + err.message);
    });
}
</script>
