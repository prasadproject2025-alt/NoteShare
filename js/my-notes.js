document.addEventListener('DOMContentLoaded', async function () {
  try {
    if (window.NoteShareBoot && typeof window.NoteShareBoot.waitForApp === 'function') {
      await window.NoteShareBoot.waitForApp();
    }
  } catch (e) {
    console.error('App init failed:', e.message);
  }

  const list = document.getElementById('my-notes-list');
  // Debug header to help troubleshoot missing notes
  const dbg = document.createElement('div');
  dbg.id = 'my-notes-debug';
  dbg.style.marginBottom = '10px';
  list.parentNode.insertBefore(dbg, list);
  // show debug info (session/localStorage/firebase user)
  try {
    const sess = localStorage.getItem('noteshare_session') || 'null';
    const isLogged = window.NoteShareAuth && window.NoteShareAuth.isLoggedIn ? window.NoteShareAuth.isLoggedIn() : false;
    const uid = window.NoteShareAuth && window.NoteShareAuth.getUserId ? window.NoteShareAuth.getUserId() : null;
    const fUser = (typeof firebase !== 'undefined' && firebase.auth) ? firebase.auth().currentUser : null;
    dbg.innerHTML = `<div class="alert alert-secondary p-2"><strong>Debug:</strong> isLoggedIn=${isLogged} userId=${uid} firebaseUser=${fUser?fUser.email+'('+fUser.uid+')':'null'}<br><small>session=${escapeHtml(sess)}</small></div>`;
  } catch (e) {
    dbg.textContent = 'Debug init error: ' + e.message;
  }

  if (!window.NoteShareAuth || !window.NoteShareAuth.isLoggedIn()) {
    list.innerHTML = '<div class="alert alert-warning">Please login to manage your notes.</div>';
    return;
  }

  const userId = window.NoteShareAuth.getUserId();
  list.innerHTML = '<p class="text-muted">Loading your notes...</p>';

  try {
    const snapshot = await firebase.database().ref('notes').orderByChild('seller_id').equalTo(userId).once('value');
    const notes = [];
    snapshot.forEach(snap => {
      const note = snap.val();
      note.id = snap.key;
      notes.push(note);
    });

    if (notes.length === 0) {
      list.innerHTML = '<p class="text-muted">You have not uploaded any notes yet.</p>';
      return;
    }

    let html = '<div class="row">';
    notes.forEach(note => {
      html += `
        <div class="col-md-6">
          <div class="card mb-3">
            <div class="card-body">
              <h5 class="card-title">${note.subject_name || 'Untitled'}</h5>
              <p class="card-text"><strong>Course:</strong> ${note.course_code || '-'}<br><strong>Price:</strong> ₹${note.price || 0}</p>
              <a class="btn btn-sm btn-primary me-2" href="edit-note.html?note_id=${note.id}">Edit</a>
              <button class="btn btn-sm btn-danger" data-note-id="${note.id}">Delete</button>
            </div>
          </div>
        </div>
      `;
    });
    html += '</div>';
    list.innerHTML = html;

    // attach delete handlers
    list.querySelectorAll('button[data-note-id]').forEach(btn => {
      btn.addEventListener('click', async function () {
        const id = this.getAttribute('data-note-id');
        if (!confirm('Delete this note? This cannot be undone.')) return;
        try {
          await firebase.database().ref('notes/' + id).remove();
          this.closest('.col-md-6').remove();
        } catch (e) {
          alert('Failed to delete note: ' + e.message);
        }
      });
    });

  } catch (err) {
    console.error('Failed to load notes:', err);
    list.innerHTML = '<div class="alert alert-danger">Error loading notes: ' + (err.message || err) + '</div>';
  }
});
