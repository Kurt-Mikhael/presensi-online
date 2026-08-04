import 'leaflet/dist/leaflet.css';
import L from 'leaflet';

const ACC_CHECK_URL = '/api/attendance/today';
const WEEKDAYS = ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];
const MONTHS = ['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
                'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];

function formatDateLong(d) {
    const dt = d instanceof Date ? d : new Date(d);
    return `${WEEKDAYS[dt.getDay()]}, ${dt.getDate()} ${MONTHS[dt.getMonth()]} ${dt.getFullYear()}`;
}

function formatClock(d) {
    const dt = d instanceof Date ? d : new Date(d);
    const p = (n, l = 2) => String(n).padStart(l, '0');
    return `${p(dt.getHours())}:${p(dt.getMinutes())}:${p(dt.getSeconds())}`;
}

function haversineMeter(lat1, lng1, lat2, lng2) {
    const R = 6371000;
    const toRad = (d) => (d * Math.PI) / 180;
    const dLat = toRad(lat2 - lat1);
    const dLng = toRad(lng2 - lng1);
    const a = Math.sin(dLat / 2) ** 2
        + Math.cos(toRad(lat1)) * Math.cos(toRad(lat2)) * Math.sin(dLng / 2) ** 2;
    return 2 * R * Math.asin(Math.sqrt(a));
}

function pointInPolygon(lat, lng, ring) {
    let inside = false;
    for (let i = 0, j = ring.length - 1; i < ring.length; j = i++) {
        const xi = ring[i].lat, yi = ring[i].lng;
        const xj = ring[j].lat, yj = ring[j].lng;
        const intersect = ((yi > lng) !== (yj > lng))
            && (lat < ((xj - xi) * (lng - yi)) / (yj - yi) + xi);
        if (intersect) inside = !inside;
    }
    return inside;
}

function createPhotoCaptureError(message, code) {
    const error = new Error(message);
    error.code = code;
    return error;
}

/**
 * State factory untuk halaman absensi.
 * Mengexpose ke Alpine via window.presensiPage(...).
 */
window.presensiPage = function presensiPage(initial) {
    return {
        areas: initial.areas || [],
        connection: { checked: false, online: null, lastCheck: null, serverTime: null },
        record: {
            has_check_in: !!initial.check_in_at,
            has_check_out: !!initial.check_out_at,
            check_in_at: initial.check_in_at || null,
            check_out_at: initial.check_out_at || null,
            check_in_accuracy: initial.check_in_accuracy ?? null,
            check_out_accuracy: initial.check_out_accuracy ?? null,
            check_in_photo_url: initial.check_in_photo_url || null,
            check_in_photo_taken_at: initial.check_in_photo_taken_at || null,
            check_in_area: null,
            check_out_area: null,
        },
        photo: {
            previewUrl: initial.check_in_photo_url || null,
            takenAt: initial.check_in_photo_taken_at || null,
            capturing: false,
            error: '',
            supported: !!(navigator.mediaDevices && navigator.mediaDevices.getUserMedia),
        },
        // Status lokasi live: searching | inside | outside | no_area | error
        loc: { phase: 'searching', accuracy: null, distance: null, areaName: '', radius: null, error: '' },
        status: { phase: 'idle', message: '', code: '', accuracy: null },
        busy: false,
        todayLabel: '',
        serverClock: '',

        init() {
            this.todayLabel = formatDateLong(new Date());
            this.tick();
            setInterval(() => this.tick(), 1000);
            this.checkConnection();
            this.$nextTick(() => this.initMiniMap());
            this.startLocationWatch();
        },

        tick() {
            this.serverClock = formatClock(new Date());
        },

        async checkConnection() {
            try {
                const r = await window.apiRequest('/api/connection-check', { method: 'GET' });
                this.connection = { checked: true, online: true, lastCheck: Date.now(), serverTime: r.server_time };
                this.syncRecord();
                return true;
            } catch (e) {
                this.connection = { checked: true, online: false, lastCheck: Date.now(), serverTime: null };
                return false;
            }
        },

        async syncRecord() {
            try {
                const r = await window.apiRequest(ACC_CHECK_URL, { method: 'GET' });
                if (r?.success && r.data) {
                    this.record.has_check_in = !!r.data.has_check_in;
                    this.record.has_check_out = !!r.data.has_check_out;
                    this.record.check_in_at = r.data.check_in_at;
                    this.record.check_out_at = r.data.check_out_at;
                }
            } catch (e) {}
        },

        // ---------- Mini map ----------

        initMiniMap() {
            if (!this.areas.length) return;
            const el = document.getElementById('mini-map');
            if (!el) return;

            this.miniMap = L.map(el, {
                zoomControl: false,
                attributionControl: false,
                scrollWheelZoom: false,
                doubleClickZoom: false,
                boxZoom: false,
                keyboard: false,
                touchZoom: true,
            });

            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', { maxZoom: 19 })
                .addTo(this.miniMap);

            this.areaLayers = L.featureGroup().addTo(this.miniMap);
            const style = { color: '#1e4fa8', weight: 2, fillColor: '#1e4fa8', fillOpacity: 0.08 };
            const latLngs = [];

            this.areas.forEach((a) => {
                if (a.area_type === 'circle' && a.center) {
                    const layer = L.circle([a.center.lat, a.center.lng], { radius: a.radius_meter, ...style });
                    layer.addTo(this.areaLayers);
                    latLngs.push({ lat: a.center.lat, lng: a.center.lng, r: a.radius_meter });
                } else if (a.area_type === 'polygon' && a.polygon && a.polygon.length >= 3) {
                    const layer = L.polygon(a.polygon.map((p) => [p.lat, p.lng]), style);
                    layer.addTo(this.areaLayers);
                    a.polygon.forEach((p) => latLngs.push({ lat: p.lat, lng: p.lng, r: 0 }));
                }
            });

            if (latLngs.length) {
                let minLat = Infinity, maxLat = -Infinity, minLng = Infinity, maxLng = -Infinity;
                latLngs.forEach(({ lat, lng, r }) => {
                    const d = (r || 0) / 111300;
                    minLat = Math.min(minLat, lat - d);
                    maxLat = Math.max(maxLat, lat + d);
                    minLng = Math.min(minLng, lng - d / Math.cos(lat * Math.PI / 180));
                    maxLng = Math.max(maxLng, lng + d / Math.cos(lat * Math.PI / 180));
                });
                this.miniMap.fitBounds([[minLat, minLng], [maxLat, maxLng]], { padding: [30, 30] });
            }
        },

        updateMiniMapMarker(lat, lng, accuracy) {
            if (!this.miniMap) return;

            if (!this.userMarker) {
                this.userMarker = L.circleMarker([lat, lng], {
                    radius: 7, color: '#ffffff', weight: 2.5, fillColor: '#1e4fa8', fillOpacity: 1,
                }).addTo(this.miniMap);
                this.accCircle = L.circle([lat, lng], {
                    radius: accuracy, color: '#1e4fa8', weight: 1, dashArray: '3 3', fillOpacity: 0.08,
                }).addTo(this.miniMap);

                this.miniMap.setView([lat, lng], 15);
            } else {
                this.userMarker.setLatLng([lat, lng]);
                this.accCircle.setLatLng([lat, lng]).setRadius(accuracy);
            }
        },

        // ---------- Status lokasi live ----------

        startLocationWatch() {
            if (!this.areas.length) {
                this.loc.phase = 'no_area';
                return;
            }
            if (!('geolocation' in navigator)) {
                this.loc = { ...this.loc, phase: 'error', error: 'Perangkat tidak mendukung GPS.' };
                return;
            }
            const update = (p) => this.evaluatePosition(p.coords.latitude, p.coords.longitude, p.coords.accuracy);
            const fail = (err) => {
                const msg = err.code === err.PERMISSION_DENIED
                    ? 'Izin akses lokasi ditolak. Aktifkan GPS untuk absensi.'
                    : 'Lokasi tidak dapat ditentukan.';
                this.loc = { ...this.loc, phase: 'error', error: msg };
            };
            navigator.geolocation.getCurrentPosition(update, fail, { enableHighAccuracy: true, timeout: 10000 });
            navigator.geolocation.watchPosition(update, () => {}, { enableHighAccuracy: true, maximumAge: 10000 });
        },

        evaluatePosition(lat, lng, accuracy) {
            let best = null;

            for (const a of this.areas) {
                let inside = false, dist = null, radius = null;

                if (a.area_type === 'circle' && a.center) {
                    dist = haversineMeter(lat, lng, a.center.lat, a.center.lng);
                    radius = a.radius_meter;
                    inside = dist <= radius;
                } else if (a.area_type === 'polygon' && a.polygon && a.polygon.length >= 3) {
                    inside = pointInPolygon(lat, lng, a.polygon);
                    dist = inside ? 0 : Math.min(...a.polygon.map((p) => haversineMeter(lat, lng, p.lat, p.lng)));
                }

                if (dist === null) continue;
                if (inside) { best = { inside: true, distance: dist, area: a, radius }; break; }
                if (!best || best.inside || dist < best.distance) best = { inside: false, distance: dist, area: a, radius };
            }

            if (!best) {
                this.loc.phase = 'no_area';
                return;
            }

            this.loc = {
                phase: best.inside ? 'inside' : 'outside',
                accuracy,
                distance: best.distance,
                areaName: best.area.name,
                radius: best.radius,
                error: '',
            };

            this.updateMiniMapMarker(lat, lng, accuracy);

            if (this.status.phase === 'idle') {
                this.status.accuracy = accuracy;
            }
        },

        get locMessage() {
            switch (this.loc.phase) {
                case 'no_area': return 'Belum ada area absensi aktif.';
                case 'searching': return 'Menentukan lokasi Anda…';
                case 'error': return this.loc.error || 'Lokasi tidak dapat ditentukan.';
                case 'inside': return `Anda berada di dalam area absensi · ${this.loc.areaName}`;
                case 'outside': return `Di luar area absensi · ±${Math.round(this.loc.distance)} m dari ${this.loc.areaName}`;
                default: return '';
            }
        },

        get canCheckIn() {
            return !this.record.has_check_in && !this.busy && this.loc.phase === 'inside';
        },
        get canCheckOut() {
            return this.record.has_check_in && !this.record.has_check_out && !this.busy && this.loc.phase === 'inside';
        },

        get checkInButtonLabel() {
            if (this.record.has_check_in) return 'Sudah Check In';
            if (this.loc.phase === 'inside') return 'Check In';
            if (this.loc.phase === 'outside') return 'Di Luar Area Absensi';
            if (this.loc.phase === 'no_area') return 'Area Belum Tersedia';
            return 'Check In';
        },

        // ---------- Aksi absensi ----------

        async doCheckIn() {
            await this.run('check-in');
        },
        async doCheckOut() {
            await this.run('check-out');
        },

        async run(type) {
            if (this.busy) return;
            if (this.connection.checked && this.connection.online === false) {
                await this.checkConnection();
            }
            if (this.connection.online !== true) {
                this.status = { phase: 'error', code: 'OFFLINE', message: 'Absensi membutuhkan koneksi internet. Periksa koneksi Anda.', accuracy: null };
                return;
            }

            this.busy = true;
            this.status = { phase: 'locating', code: '', message: type === 'check-in' ? 'Menyiapkan kamera dan lokasi…' : 'Mencari lokasi…', accuracy: null };

            let pos;
            let photo;
            try {
                if (type === 'check-in') {
                    this.status = { phase: 'capturing', code: '', message: 'Mengambil foto dan lokasi…', accuracy: null };
                    const [capturedPhoto, position] = await Promise.all([
                        this.captureAttendancePhoto(),
                        this.getPosition(),
                    ]);
                    photo = capturedPhoto;
                    pos = position;
                    this.setPhotoPreview(photo.previewUrl, photo.takenAt);
                } else {
                    pos = await this.getPosition();
                }
            } catch (e) {
                this.busy = false;
                this.status = {
                    phase: 'error',
                    code: e.code || 'LOCATION_UNAVAILABLE',
                    message: e.message || 'Lokasi tidak dapat ditentukan.',
                };
                return;
            }

            this.status = { phase: 'locating', code: '', message: `Lokasi ditemukan · Akurasi ${Math.round(pos.accuracy)} meter`, accuracy: pos.accuracy };

            if ('vibrate' in navigator) { try { navigator.vibrate(10); } catch {} }

            this.status = { phase: 'validating', code: '', message: 'Memvalidasi area…', accuracy: pos.accuracy };

            try {
                const requestBody = type === 'check-in'
                    ? this.buildCheckInFormData(pos, photo)
                    : {
                        latitude: pos.lat,
                        longitude: pos.lng,
                        accuracy: pos.accuracy,
                        captured_at: pos.captured_at,
                    };

                const res = await window.apiRequest(
                    type === 'check-in' ? '/api/attendance/check-in' : '/api/attendance/check-out',
                    {
                        method: 'POST',
                        body: requestBody,
                    }
                );

                if (type === 'check-in') {
                    this.record.has_check_in = true;
                    this.record.check_in_at = res.data?.server_time || new Date().toISOString();
                    this.record.check_in_area = res.data?.area_name || this.loc.areaName || null;
                    this.record.check_in_accuracy = res.data?.accuracy ?? pos.accuracy;
                    this.record.check_in_photo_url = res.data?.photo_url || this.photo.previewUrl;
                    this.record.check_in_photo_taken_at = res.data?.photo_taken_at || this.photo.takenAt;
                    this.setPhotoPreview(this.record.check_in_photo_url, this.record.check_in_photo_taken_at);
                } else {
                    this.record.has_check_out = true;
                    this.record.check_out_at = res.data?.server_time || new Date().toISOString();
                    this.record.check_out_area = res.data?.area_name || this.loc.areaName || null;
                    this.record.check_out_accuracy = res.data?.accuracy ?? pos.accuracy;
                }

                this.status = {
                    phase: 'done',
                    code: '',
                    message: type === 'check-in' ? 'Absensi masuk berhasil.' : 'Absensi pulang berhasil.',
                    accuracy: pos.accuracy,
                };
            } catch (e) {
                if (type === 'check-in') {
                    this.photo.error = e.message || 'Foto absensi gagal diambil.';
                }

                this.status = {
                    phase: 'error',
                    code: e.errorCode || 'UNKNOWN',
                    message: e.message || 'Terjadi kesalahan.',
                    accuracy: pos?.accuracy ?? null,
                };
            } finally {
                this.busy = false;
                await this.checkConnection();
            }
        },

        setPhotoPreview(url, takenAt = null) {
            if (this.photo.previewUrl?.startsWith('blob:')) {
                try { URL.revokeObjectURL(this.photo.previewUrl); } catch {}
            }

            this.photo.previewUrl = url || null;
            this.photo.takenAt = takenAt || null;
            this.photo.error = '';
        },

        buildCheckInFormData(position, photo) {
            const formData = new FormData();
            formData.append('latitude', position.lat);
            formData.append('longitude', position.lng);
            formData.append('accuracy', position.accuracy);
            formData.append('captured_at', position.captured_at);
            formData.append('check_in_photo', photo.file);
            return formData;
        },

        async captureAttendancePhoto() {
            if (!this.photo.supported) {
                throw createPhotoCaptureError('Perangkat tidak mendukung kamera.', 'PHOTO_UNSUPPORTED');
            }

            if (!window.isSecureContext && location.hostname !== 'localhost') {
                throw createPhotoCaptureError('Kamera hanya bisa dipakai lewat koneksi aman (HTTPS).', 'PHOTO_UNSUPPORTED');
            }

            this.photo.capturing = true;
            this.photo.error = '';

            let stream;
            try {
                stream = await navigator.mediaDevices.getUserMedia({
                    video: {
                        facingMode: 'user',
                    },
                    audio: false,
                });

                const video = document.createElement('video');
                video.playsInline = true;
                video.muted = true;
                video.srcObject = stream;

                await new Promise((resolve, reject) => {
                    const timeout = window.setTimeout(() => reject(createPhotoCaptureError('Kamera terlalu lama merespons.', 'PHOTO_TIMEOUT')), 10000);
                    video.onloadedmetadata = () => {
                        clearTimeout(timeout);
                        resolve();
                    };
                    video.onerror = () => {
                        clearTimeout(timeout);
                        reject(createPhotoCaptureError('Kamera gagal dibuka.', 'PHOTO_CAPTURE_FAILED'));
                    };
                });

                await video.play();
                await new Promise((resolve) => window.requestAnimationFrame(() => resolve()));

                const canvas = document.createElement('canvas');
                canvas.width = video.videoWidth || 1280;
                canvas.height = video.videoHeight || 720;

                const context = canvas.getContext('2d');
                if (!context) {
                    throw createPhotoCaptureError('Tidak dapat menyiapkan kanvas foto.', 'PHOTO_CAPTURE_FAILED');
                }

                context.drawImage(video, 0, 0, canvas.width, canvas.height);

                const blob = await new Promise((resolve) => canvas.toBlob(resolve, 'image/jpeg', 0.9));
                if (!blob) {
                    throw createPhotoCaptureError('Foto gagal diambil.', 'PHOTO_CAPTURE_FAILED');
                }

                const file = new File([blob], `check-in-${Date.now()}.jpg`, { type: 'image/jpeg' });
                const previewUrl = URL.createObjectURL(blob);

                return {
                    file,
                    previewUrl,
                    takenAt: new Date().toISOString(),
                };
            } catch (err) {
                if (err?.code) throw err;

                if (err?.name === 'NotAllowedError' || err?.name === 'PermissionDeniedError') {
                    throw createPhotoCaptureError('Izin kamera ditolak. Aktifkan kamera untuk melanjutkan.', 'PHOTO_PERMISSION_DENIED');
                }

                throw createPhotoCaptureError(err?.message || 'Foto gagal diambil.', 'PHOTO_CAPTURE_FAILED');
            } finally {
                if (stream) {
                    stream.getTracks().forEach((track) => track.stop());
                }
                this.photo.capturing = false;
            }
        },

        getPosition() {
            const timeout = Number(document.body.dataset.locTimeout || 15000);
            const maxAge = Number(document.body.dataset.maxAge || 30000);
            return new Promise((resolve, reject) => {
                if (!('geolocation' in navigator)) {
                    const e = new Error('Perangkat tidak mendukung GPS.');
                    e.code = 'LOCATION_UNAVAILABLE';
                    return reject(e);
                }

                navigator.geolocation.getCurrentPosition(
                    (p) => resolve({
                        lat: p.coords.latitude,
                        lng: p.coords.longitude,
                        accuracy: p.coords.accuracy,
                        captured_at: new Date(p.timestamp).toISOString(),
                    }),
                    (err) => {
                        let message = 'Lokasi tidak dapat ditentukan.', code = 'LOCATION_UNAVAILABLE';
                        if (err.code === err.PERMISSION_DENIED) { message = 'Izin akses lokasi ditolak.'; code = 'LOCATION_PERMISSION_DENIED'; }
                        else if (err.code === err.POSITION_UNAVAILABLE) { message = 'Lokasi tidak tersedia.'; code = 'LOCATION_UNAVAILABLE'; }
                        else if (err.code === err.TIMEOUT) { message = 'Pengambilan lokasi kehabisan waktu.'; code = 'LOCATION_UNAVAILABLE'; }
                        const e = new Error(message);
                        e.code = code;
                        reject(e);
                    },
                    { enableHighAccuracy: true, timeout, maximumAge: maxAge / 2 }
                );
            });
        },
    };
};
