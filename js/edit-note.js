document.addEventListener('DOMContentLoaded', async function () {
  try {
    if (window.NoteShareBoot && typeof window.NoteShareBoot.waitForApp === 'function') {
      await window.NoteShareBoot.waitForApp();
    }
  } catch (e) {
    console.error('App init failed:', e.message);
  }

  if (!window.NoteShareAuth || !window.NoteShareAuth.isLoggedIn()) {
    document.getElementById('edit-note-form').innerHTML = '<div class="alert alert-warning">Please login to edit notes.</div>';
    return;
  }

  const params = new URLSearchParams(window.location.search);
  const noteId = params.get('note_id');
  const container = document.getElementById('edit-note-form');
  if (!noteId) {
    container.innerHTML = '<div class="alert alert-danger">No note specified.</div>';
    return;
  }

  container.innerHTML = '<p class="text-muted">Loading note...</p>';

  try {
    const snap = await firebase.database().ref('notes/' + noteId).once('value');
    const note = snap.val();
    if (!note) {
      container.innerHTML = '<div class="alert alert-danger">Note not found.</div>';
      return;
    }

    // ensure ownership
    const userId = window.NoteShareAuth.getUserId();
    if (note.seller_id !== userId) {
      container.innerHTML = '<div class="alert alert-danger">You are not the owner of this note.</div>';
      return;
    }

    container.innerHTML = `
      <form id="note-edit-form">
        <div class="mb-3">
          <label class="form-label">Subject name</label>
          <input class="form-control" id="subject_name" value="${note.subject_name || ''}">
        </div>
        <div class="mb-3">
          <label class="form-label">Course code</label>
          <input class="form-control" id="course_code" value="${note.course_code || ''}">
        </div>
        <div class="mb-3">
          <label class="form-label">Faculty name</label>
          <input class="form-control" id="faculty_name" value="${note.faculty_name || ''}">
        </div>
        <div class="mb-3">
          <label class="form-label">Price (₹)</label>
          <input class="form-control" id="price" type="number" value="${note.price || 0}">
        </div>
        <div class="mb-3">
          <label class="form-label">Description</label>
          <textarea class="form-control" id="description">${note.description || ''}</textarea>
        </div>
        <button class="btn btn-primary" id="save-btn">Save</button>
        <button type="button" class="btn btn-danger ms-2" id="delete-btn">Delete</button>
      </form>
    `;

    document.getElementById('note-edit-form').addEventListener('submit', async function (e) {
      e.preventDefault();
      const saveBtn = document.getElementById('save-btn');
      saveBtn.disabled = true;
      try {
        const updated = {
          subject_name: document.getElementById('subject_name').value,
          course_code: document.getElementById('course_code').value,
          faculty_name: document.getElementById('faculty_name').value,
          price: parseFloat(document.getElementById('price').value) || 0,
          description: document.getElementById('description').value,
        };
        await firebase.database().ref('notes/' + noteId).update(updated);
        alert('Note updated successfully');
        window.location.href = 'my-notes.html';
      } catch (err) {
        alert('Update failed: ' + err.message);
      } finally {
        saveBtn.disabled = false;
      }
    });

    document.getElementById('delete-btn').addEventListener('click', async function () {
      if (!confirm('Delete this note? This cannot be undone.')) return;
      try {
        await firebase.database().ref('notes/' + noteId).remove();
        alert('Note deleted');
        window.location.href = 'my-notes.html';
      } catch (err) {
        alert('Delete failed: ' + err.message);
      }
    });

  } catch (err) {
    console.error('Load note failed:', err);
    container.innerHTML = '<div class="alert alert-danger">Error loading note: ' + err.message + '</div>';
  }
});
