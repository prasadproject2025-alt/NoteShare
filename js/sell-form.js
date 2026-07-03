/** Client-side sell form — replaces PHP POST + session note_data */
document.addEventListener('DOMContentLoaded', function () {
  const form = document.getElementById('sell-notes-form');
  if (!form) return;

  form.addEventListener('submit', async function (e) {
    e.preventDefault();
    const uploadBtn = document.getElementById('upload-btn');
    const uploadText = document.getElementById('upload-text');
    const uploadSpinner = document.getElementById('upload-spinner');

    uploadBtn.disabled = true;
    if (uploadText) uploadText.textContent = 'Processing...';
    uploadSpinner?.classList.remove('d-none');

    try {
      const fileInputs = Array.from(form.querySelectorAll('input[type="file"]')); 
      const files = fileInputs.reduce((acc, input) => {
        if (input.files && input.files[0]) acc.push(input.files[0]);
        return acc;
      }, []);
      if (!files.length) throw new Error('Please upload at least one image');

      const images = [];
      let totalSize = 0;
      for (let i = 0; i < Math.min(files.length, 5); i++) {
        const file = files[i];
        const buf = await file.arrayBuffer();
        const bytes = new Uint8Array(buf);
        let binary = '';
        for (let j = 0; j < bytes.length; j++) binary += String.fromCharCode(bytes[j]);
        const base64 = btoa(binary);
        totalSize += file.size;
        if (file.size > 10 * 1024 * 1024) throw new Error('Max 10MB per file');
        images.push({ base64, mime_type: file.type || 'image/jpeg', size: file.size });
      }
      if (totalSize > 50 * 1024 * 1024) throw new Error('Total size max 50MB');

      const noteData = {
        subject_name: form.subject_name.value,
        course_code: form.course_code.value,
        faculty_name: form.faculty_name.value,
        slot: form.slot.value,
        year: form.year.value,
        description: form.description.value,
        price: parseFloat(form.price.value),
        seller_id: window.NoteShareAuth.getUserId(),
        seller_name: window.NoteShareAuth.getUserName(),
        seller_email: window.NoteShareAuth.getUserEmail(),
        images,
        ocr_text: 'Note images uploaded',
        status: 'available',
        created_at: Date.now(),
        likes: 0,
      };

      sessionStorage.setItem('noteshare_note_data', JSON.stringify(noteData));
      window.location.href = 'upload-progress.html';
    } catch (err) {
      alert(err.message || 'Upload failed');
      uploadBtn.disabled = false;
      if (uploadText) uploadText.textContent = 'Upload Notes';
      uploadSpinner?.classList.add('d-none');
    }
  });
});
