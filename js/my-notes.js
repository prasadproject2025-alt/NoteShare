document.addEventListener('DOMContentLoaded', async function () {
  try {
    if (window.NoteShareBoot && typeof window.NoteShareBoot.waitForApp === 'function') {
      await window.NoteShareBoot.waitForApp();
    }
  } catch (e) {
    console.error('App init failed:', e.message);
  }

  const list = document.getElementById('my-notes-list');

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

    let html = '<div class="row row-cols-1 row-cols-sm-2 row-cols-md-3 row-cols-xl-4 g-3">';
    notes.forEach(note => {
      html += `
        <div class="col note-item-card">
          <div class="card h-100 shadow-sm border" style="border-top: 3px solid #f59e0b !important; border-radius: 4px;">
            <div class="card-body p-3 d-flex flex-column">
              <span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle mb-2 align-self-start" style="font-size: 10.5px;">${note.course_code || 'VIT Note'}</span>
              <h6 class="fw-bold text-dark text-truncate mb-1" title="${note.subject_name || 'Untitled'}" style="font-size: 0.9rem;">${note.subject_name || 'Untitled'}</h6>
              <div class="text-muted small text-truncate mb-2" style="font-size: 11.5px;"><i class="fas fa-user-tie me-1"></i>${note.faculty_name || 'Faculty N/A'}</div>
              <div class="mt-auto pt-2 border-top d-flex justify-content-between align-items-center mb-2">
                <span class="fw-bold text-success" style="font-size: 1rem;">₹${note.price || 0}</span>
                <small class="text-muted" style="font-size: 11px;"><i class="fas fa-heart text-danger me-1"></i>${note.likes || 0}</small>
              </div>
              <div class="d-flex gap-2">
                <a class="btn btn-sm btn-outline-primary w-50 py-1" style="font-size: 11.5px;" href="buy-notes.html?note_id=${note.id}">View</a>
                <button class="btn btn-sm btn-outline-danger w-50 py-1" style="font-size: 11.5px;" data-note-id="${note.id}">Delete</button>
              </div>
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
          this.closest('.note-item-card').remove();
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
