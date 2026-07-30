import './bootstrap';

import Alpine from 'alpinejs';
import collapse from '@alpinejs/collapse';

window.Alpine = Alpine;
Alpine.plugin(collapse);

window.__alpineReady = false;

/**
 * Helper: kirim POST/PUT/DELETE JSON dengan CSRF + cookie sesi.
 * Melempar error terstruktur agar UI tinggal menangkap status/message/error_code.
 */
window.apiRequest = async function apiRequest(url, { method = 'POST', body = null, signal = null } = {}) {
    const opts = {
        method,
        credentials: 'same-origin',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
            'X-Requested-With': 'XMLHttpRequest',
        },
        signal,
    };

    if (body !== null) {
        if (body instanceof FormData) {
            opts.body = body;
        } else {
            opts.headers['Content-Type'] = 'application/json';
            opts.body = JSON.stringify(body);
        }
    }

    let res;
    try {
        res = await fetch(url, opts);
    } catch (err) {
        if (err?.name === 'AbortError') throw err;
        throw ApiError.offline(err);
    }

    let payload = null;
    const ct = res.headers.get('content-type') || '';
    if (ct.includes('application/json')) {
        try { payload = await res.json(); } catch { payload = null; }
    } else {
        try { payload = { message: await res.text() }; } catch {}
    }

    if (!res.ok) {
        const err = new ApiError(
            payload?.message || `HTTP ${res.status}`,
            payload?.error_code || `HTTP_${res.status}`,
            res.status,
            payload?.data,
        );
        throw err;
    }

    if (payload && payload.success === false) {
        throw new ApiError(payload.message || 'Gagal', payload.error_code || 'UNKNOWN', res.status, payload.data);
    }

    return payload;
};

export class ApiError extends Error {
    static offline(cause) {
        return new ApiError('Presensi membutuhkan koneksi internet. Silakan periksa koneksi Anda.', 'OFFLINE', 0);
    }

    constructor(message, errorCode, status, data) {
        super(message);
        this.name = 'ApiError';
        this.errorCode = errorCode;
        this.status = status;
        this.data = data;
    }
}
window.ApiError = ApiError;

/**
 * Alpine.start() dipanggil dari base layout lewat window load listener,
 * supaya semua page module (attendance.js, admin-*.js) sudah mendaftarkan
 * komponennya terlebih dahulu. Lihat layouts/base.blade.php.
 */