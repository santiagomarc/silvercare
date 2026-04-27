import Swal from 'sweetalert2';
import 'sweetalert2/dist/sweetalert2.min.css';

const BASE_CUSTOM_CLASS = {
    popup: 'swal2-silvercare-popup',
    title: 'swal2-silvercare-title',
    htmlContainer: 'swal2-silvercare-content',
    icon: 'swal2-silvercare-icon',
    actions: 'swal2-silvercare-actions',
    confirmButton: 'swal2-silvercare-btn swal2-silvercare-btn-confirm',
    cancelButton: 'swal2-silvercare-btn swal2-silvercare-btn-cancel',
};

function parseBoolean(value) {
    return String(value).toLowerCase() === 'true';
}

function normalizeAlertOptions(input, maybeOptions = {}) {
    if (typeof input === 'string') {
        return { text: input, ...maybeOptions };
    }

    return input || {};
}

function buildPopupClasses(config, customPopupClass = '') {
    const tone = config.tone || config.icon || 'info';
    const toneClass = `swal2-silvercare-tone-${tone}`;
    const elderlyClass = config.elderly ? 'swal2-silvercare-elderly' : '';

    return [BASE_CUSTOM_CLASS.popup, toneClass, elderlyClass, customPopupClass]
        .filter(Boolean)
        .join(' ')
        .trim();
}

function toDialogConfig(options = {}) {
    const {
        elderly = false,
        tone,
        customClass = {},
        showCancelButton = false,
        icon = 'info',
        title = 'Notice',
        text,
        html,
        confirmButtonText = 'OK',
        cancelButtonText = 'Cancel',
        reverseButtons = true,
        focusCancel = showCancelButton,
        allowOutsideClick = true,
        allowEscapeKey = true,
        ...rest
    } = options;

    return {
        title,
        text,
        html,
        icon,
        showCancelButton,
        confirmButtonText,
        cancelButtonText,
        reverseButtons,
        focusCancel,
        allowOutsideClick,
        allowEscapeKey,
        buttonsStyling: false,
        customClass: {
            ...BASE_CUSTOM_CLASS,
            ...customClass,
            popup: buildPopupClasses({ tone, icon, elderly }, customClass.popup),
        },
        ...rest,
    };
}

async function fireDialog(options = {}) {
    return Swal.fire(toDialogConfig(options));
}

export async function showAlert(input, maybeOptions = {}) {
    const options = normalizeAlertOptions(input, maybeOptions);

    await fireDialog({
        icon: 'info',
        title: 'Notice',
        confirmButtonText: 'OK',
        ...options,
    });
}

export async function showConfirm(options = {}) {
    const result = await fireDialog({
        icon: 'warning',
        title: 'Please confirm',
        showCancelButton: true,
        confirmButtonText: 'Confirm',
        cancelButtonText: 'Cancel',
        focusCancel: true,
        ...options,
    });

    return result.isConfirmed;
}

function createFormConfirmOptions(form) {
    return {
        title: form.dataset.confirmTitle || 'Please confirm',
        text: form.dataset.confirmText || form.dataset.confirm || 'Are you sure you want to continue?',
        icon: form.dataset.confirmIcon || 'warning',
        confirmButtonText: form.dataset.confirmConfirmText || 'Confirm',
        cancelButtonText: form.dataset.confirmCancelText || 'Cancel',
        elderly: parseBoolean(form.dataset.confirmElderly),
    };
}

function bindConfirmForms() {
    document.querySelectorAll('form[data-confirm]').forEach((form) => {
        if (form.dataset.confirmBound === 'true') {
            return;
        }

        form.dataset.confirmBound = 'true';

        form.addEventListener('submit', async (event) => {
            event.preventDefault();

            const confirmed = await showConfirm(createFormConfirmOptions(form));
            if (confirmed) {
                form.submit();
            }
        });
    });
}

export function installDialogHelpers() {
    window.scAlert = (input, maybeOptions = {}) => showAlert(input, maybeOptions);
    window.scConfirm = (options = {}) => showConfirm(options);
    window.scConfirmForm = async (event, options = {}) => {
        if (event) {
            event.preventDefault();
        }

        const form = event?.target;
        const confirmed = await showConfirm(options);

        if (confirmed && form && typeof form.submit === 'function') {
            form.submit();
        }

        return false;
    };
    window.scBindConfirmForms = bindConfirmForms;

    bindConfirmForms();
    document.addEventListener('DOMContentLoaded', bindConfirmForms);
}
