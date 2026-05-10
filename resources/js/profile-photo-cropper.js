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
        statusDiv.innerHTML = '<span class="text-rose-500 text-sm">Please select a valid image (JPG, PNG, GIF, WebP)</span>';
        input.value = '';
        return;
    }

    if (file.size > 5 * 1024 * 1024) {
        statusDiv.innerHTML = '<span class="text-rose-500 text-sm">Image must be less than 5MB</span>';
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
        uploadButton.textContent = 'Uploading...';
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
                    uploadButton.textContent = '✓ Crop & Upload';
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
                    },
                    body: formData,
                });

                if (response.ok) {
                    if (modal) {
                        modal.classList.add('hidden');
                        modal.classList.remove('flex');
                    }
                    destroyCropper();
                    statusDiv.innerHTML = '<span class="text-emerald-600 text-sm flex items-center gap-1"><svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg> Photo updated!</span>';
                    setTimeout(() => window.location.reload(), 1000);
                } else {
                    statusDiv.innerHTML = '<span class="text-rose-500 text-sm">Failed to upload. Please try again.</span>';
                }
            } catch (error) {
                console.error('Upload error:', error);
                statusDiv.innerHTML = '<span class="text-rose-500 text-sm">An error occurred. Please try again.</span>';
            } finally {
                if (uploadButton) {
                    uploadButton.disabled = false;
                    uploadButton.textContent = '✓ Crop & Upload';
                }
            }
        }, 'image/jpeg', 0.95);
    } catch (error) {
        console.error('Crop error:', error);
        if (uploadButton) {
            uploadButton.disabled = false;
            uploadButton.textContent = '✓ Crop & Upload';
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
    const confirmed = typeof window.scConfirm === 'function'
        ? await window.scConfirm({
            title: 'Remove profile photo?',
            text: 'Your avatar will be reset to the default profile image.',
            icon: 'warning',
            confirmButtonText: 'Remove photo',
            cancelButtonText: 'Cancel',
            elderly: true,
        })
        : window.confirm('Remove profile photo?');

    if (!confirmed) {
        return;
    }

    const statusDiv = document.getElementById('photo-status');
    statusDiv.innerHTML = '<span class="text-navy-500 text-sm">Removing...</span>';

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
            },
        });

        if (response.ok) {
            statusDiv.innerHTML = '<span class="text-emerald-600 text-sm">Photo removed!</span>';
            setTimeout(() => window.location.reload(), 1000);
        } else {
            statusDiv.innerHTML = '<span class="text-rose-500 text-sm">Failed to remove. Please try again.</span>';
        }
    } catch (_) {
        statusDiv.innerHTML = '<span class="text-rose-500 text-sm">An error occurred. Please try again.</span>';
    }
};