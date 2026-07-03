/** Client-side sell form — replaces PHP POST + session note_data */
function compressImageFile(file, maxWidth = 1200, quality = 0.8) {
  return new Promise((resolve, reject) => {
    const img = new Image();
    const objectUrl = URL.createObjectURL(file);

    img.onload = () => {
      try {
        const scale = Math.min(1, maxWidth / img.width);
        const width = Math.max(600, Math.round(img.width * scale));
        const height = Math.max(600, Math.round(img.height * scale));
        const canvas = document.createElement('canvas');
        canvas.width = width;
        canvas.height = height;

        const ctx = canvas.getContext('2d');
        ctx.drawImage(img, 0, 0, width, height);

        const dataUrl = canvas.toDataURL(file.type || 'image/jpeg', quality);
        const base64 = dataUrl.split(',')[1] || '';
        URL.revokeObjectURL(objectUrl);
        resolve({ base64, mime_type: file.type || 'image/jpeg', size: Math.round(base64.length * 0.75) });
      } catch (err) {
        URL.revokeObjectURL(objectUrl);
        reject(err);
      }
    };

    img.onerror = () => {
      URL.revokeObjectURL(objectUrl);
      reject(new Error('Could not read the selected image.'));
    };

    img.src = objectUrl;
  });
}

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
        if (file.size > 10 * 1024 * 1024) throw new Error('Max 10MB per file');
        const compressed = await compressImageFile(file);
        totalSize += compressed.size;
        images.push(compressed);
      }
      if (totalSize > 8 * 1024 * 1024) throw new Error('These images are too large for a reliable upload. Please choose smaller images.');

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

      const serialized = JSON.stringify(noteData);
      if (serialized.length > 4_500_000) {
        throw new Error('These images are too large to save in the browser session. Please choose smaller images.');
      }
      sessionStorage.setItem('noteshare_note_data', serialized);
      window.location.href = 'upload-progress.html';
    } catch (err) {
      alert(err.message || 'Upload failed');
      uploadBtn.disabled = false;
      if (uploadText) uploadText.textContent = 'Upload Notes';
      uploadSpinner?.classList.add('d-none');
    }
  });
});
