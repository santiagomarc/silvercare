import Cropper from 'cropperjs';
import 'cropperjs/dist/cropper.css';

let cropper = null;
let previewObjectUrl = null;

function destroyCropper() {
    if (cropper) {
        cropper.destroy();
        cropper = null;
    }
}

window.openCropModal = function openCropModal(event) {
    const input = event?.target;
    if (!input || !input.files || !input.files[0]) {
        return;
    }

    const file = input.files[0];
    const validTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    const statusDiv = document.getElementById('photo-status');

    if (!validTypes.includes(file.type)) {
        statusDiv.innerHTML = '<span style="color:var(--sc-alert)">Please select a valid image (JPG, PNG, GIF, WebP)</span>';
        input.value = '';
        return;
    }

    if (file.size > 5 * 1024 * 1024) {
        statusDiv.innerHTML = '<span style="color:var(--sc-alert)">Image must be less than 5MB</span>';
        input.value = '';
        return;
    }

    const modal = document.getElementById('profile-photo-crop-modal');
    const cropImage = document.getElementById('crop-image');

    if (!modal || !cropImage) {
        return;
    }

    if (previewObjectUrl) {
        URL.revokeObjectURL(previewObjectUrl);
    }

    previewObjectUrl = URL.createObjectURL(file);
    cropImage.src = previewObjectUrl;
    modal.classList.remove('hidden');
    modal.classList.add('flex');

    setTimeout(() => {
        destroyCropper();
        cropper = new Cropper(cropImage, {
            aspectRatio: 1,
            viewMode: 1,
            autoCropArea: 0.9,
            responsive: true,
            restore: true,
            guides: true,
            highlight: true,
            cropBoxMovable: true,
            cropBoxResizable: true,
            toggleDragModeOnDblclick: true,
            background: false,
        });
    }, 100);
};

window.applyCrop = async function applyCrop() {
    if (!cropper) {
        return;
    }

    const modal = document.getElementById('profile-photo-crop-modal');
    const uploadButton = document.getElementById('crop-upload-button');
    const statusDiv = document.getElementById('photo-status');
    if (uploadButton) {
        uploadButton.disabled = true;
        uploadButton.textContent = 'Uploading…';
    }

    try {
        const canvas = cropper.getCroppedCanvas({
            maxWidth: 4096,
            maxHeight: 4096,
            fillColor: '#fff',
            imageSmoothingEnabled: true,
            imageSmoothingQuality: 'high',
        });

        canvas.toBlob(async (blob) => {
            if (!blob) {
                if (uploadButton) {
                    uploadButton.disabled = false;
                    uploadButton.textContent = 'Crop and upload';
                }
                return;
            }

            const formData = new FormData();
            formData.append('profile_photo', blob, 'profile.jpg');

            try {
                const response = await fetch('/profile/photo', {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content,
                        'Accept': 'application/json',
                    },
                    body: formData,
                });

                if (response.ok) {
                    if (modal) {
                        modal.classList.add('hidden');
                        modal.classList.remove('flex');
                    }
                    destroyCropper();
                    statusDiv.innerHTML = '<span style="color:var(--sc-ok)" class="inline-flex items-center gap-1.5"><svg class="sc-i w-5 h-5" aria-hidden="true" focusable="false"><use href="#i-check"/></svg> Photo updated</span>';
                    setTimeout(() => window.location.reload(), 1000);
                } else {
                    statusDiv.innerHTML = '<span style="color:var(--sc-alert)">Failed to upload. Please try again.</span>';
                }
            } catch (error) {
                console.error('Upload error:', error);
                statusDiv.innerHTML = '<span style="color:var(--sc-alert)">An error occurred. Please try again.</span>';
            } finally {
                if (uploadButton) {
                    uploadButton.disabled = false;
                    uploadButton.textContent = 'Crop and upload';
                }
            }
        }, 'image/jpeg', 0.95);
    } catch (error) {
        console.error('Crop error:', error);
        if (uploadButton) {
            uploadButton.disabled = false;
            uploadButton.textContent = 'Crop and upload';
        }
    }
};

window.cancelCrop = function cancelCrop() {
    const modal = document.getElementById('profile-photo-crop-modal');

    if (!modal) {
        return;
    }

    modal.classList.add('hidden');
    modal.classList.remove('flex');
    destroyCropper();

    if (previewObjectUrl) {
        URL.revokeObjectURL(previewObjectUrl);
        previewObjectUrl = null;
    }

    const input = document.getElementById('photo-upload');
    if (input) {
        input.value = '';
    }
};

window.removeProfilePhoto = async function removeProfilePhoto() {
    const statusDiv = document.getElementById('photo-status');
    statusDiv.innerHTML = '<span style="color:var(--sc-body)">Removing…</span>';

    const removeForm = document.getElementById('profile-photo-remove-form');
    if (removeForm) {
        removeForm.submit();
        return;
    }

    try {
        const response = await fetch('/profile/photo', {
            method: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content,
                'Accept': 'application/json',
            },
        });

        if (response.ok) {
            statusDiv.innerHTML = '<span style="color:var(--sc-ok)">Photo removed</span>';
            setTimeout(() => window.location.reload(), 1000);
        } else {
            statusDiv.innerHTML = '<span style="color:var(--sc-alert)">Failed to remove. Please try again.</span>';
        }
    } catch (_) {
        statusDiv.innerHTML = '<span style="color:var(--sc-alert)">An error occurred. Please try again.</span>';
    }
};
