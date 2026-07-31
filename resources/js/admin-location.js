import 'leaflet/dist/leaflet.css';
import '@geoman-io/leaflet-geoman-free/dist/leaflet-geoman.css';
import L from 'leaflet';
import '@geoman-io/leaflet-geoman-free';

window.adminLocationPage = function adminLocationPage(initial) {
    const STYLE_REF = { color: '#1e4fa8', weight: 2, fillColor: '#1e4fa8', fillOpacity: 0.08, dashArray: null };
    const STYLE_ACTIVE = { color: '#1e4fa8', weight: 2.5, fillColor: '#1e4fa8', fillOpacity: 0.12, dashArray: null };
    const STYLE_SELECTED = { color: '#1e4fa8', weight: 3.5, fillColor: '#1e4fa8', fillOpacity: 0.22, dashArray: null };

    return {
        map: null,
        refLayers: null,
        selectedLayer: null,   // layer yang sedang dipilih (bisa layer tersimpan atau hasil gambar)
        unsavedLayer: null,    // layer hasil gambar yang belum pernah disimpan
        areas: initial.areas || [],
        activeId: initial.activeId || null,
        editingId: null,       // id area di DB yang sedang diedit; null = area baru
        form: {
            area_type: 'circle',
            name: '',
            is_active: true,
            maximum_accuracy_meter: initial.defaultAccuracy ?? 50,
            radius_meter: 100,
            center_lat: null,
            center_lng: null,
            polygon: [],
        },
        saving: false,
        message: { type: '', text: '' },

        init() {
            this.initMap();
            this.renderAreas();
        },

        initMap() {
            const c = initial.mapCenter;
            this.map = L.map('map', { zoomControl: true })
                .setView([c.lat, c.lng], c.zoom);

            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                maxZoom: 19,
                attribution: '&copy; OpenStreetMap',
            }).addTo(this.map);

            this.refLayers = L.featureGroup().addTo(this.map);

            this.map.pm.addControls({
                position: 'topleft',
                drawCircle: true,
                drawPolygon: true,
                drawRectangle: false,
                editMode: true,
                dragMode: true,
                cutPolygon: false,
                removalMode: true,
                drawCircleMarker: false,
                drawMarker: false,
                drawPolyline: false,
                drawText: false,
                rotateMode: false,
            });

            this.map.pm.setLang('id');

            // Setiap hasil gambar = AREA BARU (tidak pernah menimpa layer lain).
            this.map.on('pm:create', (e) => {
                if (this.unsavedLayer) { this.map.removeLayer(this.unsavedLayer); }
                this.resetSelectedStyle();

                this.editingId = null;
                this.unsavedLayer = e.layer;
                this.selectedLayer = e.layer;
                this.form.name = '';
                this.ingestLayer(e.layer);

                e.layer.on('pm:edit', () => this.ingestLayer(e.layer));
                e.layer.on('pm:dragend', () => this.ingestLayer(e.layer));

                setTimeout(() => this.map.pm.disableDraw(), 0);
            });

            // Tool hapus (eraser): layer tersimpan ikut dihapus dari database.
            this.map.on('pm:remove', async (e) => {
                const layer = e.layer;
                if (layer === this.unsavedLayer) this.unsavedLayer = null;
                if (layer === this.selectedLayer) this.selectedLayer = null;

                if (layer._areaId) {
                    if (this.editingId === layer._areaId) this.clearSelection();
                    try {
                        await window.apiRequest(`/api/admin/location/${layer._areaId}`, { method: 'DELETE' });
                        this.flash('success', 'Lokasi dihapus.');
                    } catch (err) {
                        this.flash('error', err.message || 'Gagal menghapus.');
                    }
                    await this.reload();
                } else if (!this.editingId) {
                    this.resetFormGeometry();
                }
            });

            setTimeout(() => this.labelGeomanToolbar(), 500);
        },

        labelGeomanToolbar() {
            const labels = {
                circle: 'Lingkaran',
                polygon: 'Polygon',
                edit: 'Edit',
                drag: 'Geser',
                delete: 'Hapus',
            };
            document.querySelectorAll('.leaflet-pm-toolbar .leaflet-pm-toolbar-button').forEach((btn) => {
                const icon = btn.querySelector('[class*="leaflet-pm-icon-"]');
                if (!icon) return;
                const cls = Array.from(icon.classList).find((c) => c.startsWith('leaflet-pm-icon-'));
                if (!cls) return;
                const key = cls.replace('leaflet-pm-icon-', '');
                const label = labels[key];
                if (label) {
                    btn.setAttribute('title', label);
                    const span = document.createElement('span');
                    span.textContent = label;
                    span.className = 'pm-toolbar-label';
                    btn.appendChild(span);
                }
            });
        },

        // ---------- Render area tersimpan ----------

        styleFor(area) {
            return area.is_active ? STYLE_ACTIVE : STYLE_REF;
        },

        renderAreas() {
            this.refLayers.clearLayers();
            this.areas.forEach((a) => this.drawArea(a));
        },

        drawArea(area) {
            let layer;
            if (area.area_type === 'circle' && area.center) {
                layer = L.circle([area.center.lat, area.center.lng], {
                    radius: area.radius_meter,
                    ...this.styleFor(area),
                });
            } else if (area.area_type === 'polygon' && area.polygon) {
                layer = L.polygon(area.polygon.map((p) => [p.lat, p.lng]), this.styleFor(area));
            }
            if (!layer) return;

            // Layer tersimpan TIDAK pmIgnore: tool Geser/Edit bekerja langsung
            // pada layer ini — tidak pernah membuat duplikat.
            layer._areaId = area.id;
            layer.on('click', () => this.selectArea(area, layer));
            layer.on('pm:dragstart', () => this.selectArea(area, layer));
            layer.on('pm:edit', () => { this.selectArea(area, layer); this.ingestLayer(layer); });
            layer.on('pm:dragend', () => this.ingestLayer(layer));
            this.refLayers.addLayer(layer);
        },

        findLayerByArea(id) {
            let found = null;
            this.refLayers.eachLayer((l) => { if (l._areaId === id) found = l; });
            return found;
        },

        // ---------- Seleksi & sinkronisasi form ----------

        selectArea(area, layer = null) {
            this.map.pm.disableDraw();

            if (this.editingId !== area.id) {
                this.resetSelectedStyle();
                this.editingId = area.id;
                this.form.name = area.name;
                this.form.area_type = area.area_type;
                this.form.is_active = area.is_active;
                this.form.maximum_accuracy_meter = area.maximum_accuracy_meter ?? initial.defaultAccuracy;

                if (area.area_type === 'circle' && area.center) {
                    this.form.center_lat = area.center.lat;
                    this.form.center_lng = area.center.lng;
                    this.form.radius_meter = area.radius_meter;
                    this.form.polygon = [];
                } else if (area.polygon) {
                    this.form.polygon = area.polygon;
                }
            }

            this.selectedLayer = layer || this.findLayerByArea(area.id) || this.selectedLayer;
            if (this.selectedLayer) this.selectedLayer.setStyle(STYLE_SELECTED);
        },

        resetSelectedStyle() {
            if (this.selectedLayer && this.selectedLayer._areaId) {
                const area = this.areas.find((a) => a.id === this.selectedLayer._areaId);
                if (area) this.selectedLayer.setStyle(this.styleFor(area));
            }
        },

        clearSelection() {
            this.resetSelectedStyle();
            this.selectedLayer = null;
            this.editingId = null;
        },

        ingestLayer(layer) {
            const shape = layer?.pm?.getShape?.();
            if (shape === 'Circle' || layer instanceof L.Circle) {
                this.form.area_type = 'circle';
                this.form.center_lat = layer.getLatLng().lat;
                this.form.center_lng = layer.getLatLng().lng;
                this.form.radius_meter = Math.round(layer.getRadius());
            } else if (shape === 'Polygon' || layer instanceof L.Polygon) {
                this.form.area_type = 'polygon';
                const ring = layer.getLatLngs()[0];
                this.form.polygon = ring.map((ll) => ({ lat: ll.lat, lng: ll.lng }));
            }
        },

        resetFormGeometry() {
            this.form.center_lat = null;
            this.form.center_lng = null;
            this.form.radius_meter = 100;
            this.form.polygon = [];
        },

        // ---------- Aksi UI ----------

        selectType(type) {
            // Menggambar via toolbar selalu berarti area baru.
            this.map.pm.disableGlobalDragMode();
            this.map.pm.disableGlobalEditMode();
            this.clearSelection();
            if (this.unsavedLayer) { this.map.removeLayer(this.unsavedLayer); this.unsavedLayer = null; }

            this.form.area_type = type;
            this.form.name = '';
            this.resetFormGeometry();

            this.map.pm.enableDraw(type === 'circle' ? 'Circle' : 'Polygon', {
                snappable: false, hintline: true, cursorMarker: true,
            });
        },

        updateFromInputs() {
            if (this.form.area_type !== 'circle' || !this.selectedLayer || !this.selectedLayer.setRadius) return;
            const lat = parseFloat(this.form.center_lat);
            const lng = parseFloat(this.form.center_lng);
            const r = parseInt(this.form.radius_meter, 10);
            if (!isNaN(r) && r >= 1) this.selectedLayer.setRadius(r);
            if (!isNaN(lat) && !isNaN(lng)) this.selectedLayer.setLatLng([lat, lng]);
        },

        resetForm() {
            this.map.pm.disableDraw();
            this.map.pm.disableGlobalDragMode();
            this.map.pm.disableGlobalEditMode();
            this.clearSelection();
            if (this.unsavedLayer) { this.map.removeLayer(this.unsavedLayer); this.unsavedLayer = null; }

            this.form.name = '';
            this.form.area_type = 'circle';
            this.form.is_active = true;
            this.form.maximum_accuracy_meter = initial.defaultAccuracy ?? 50;
            this.resetFormGeometry();
        },

        async save() {
            if (this.saving) return;
            if (!this.form.name.trim()) { this.flash('error', 'Nama lokasi wajib diisi.'); return; }

            if (this.form.area_type === 'circle') {
                if (!this.form.center_lat || !this.form.center_lng) { this.flash('error', 'Tentukan titik pusat pada peta.'); return; }
            } else {
                if (this.form.polygon.length < 3) { this.flash('error', 'Gambar polygon minimal 3 titik.'); return; }
            }

            const body = {
                area_type: this.form.area_type,
                name: this.form.name.trim(),
                is_active: this.form.is_active,
                maximum_accuracy_meter: this.form.maximum_accuracy_meter || null,
            };

            // id ada -> backend UPDATE (timpa); tidak ada -> INSERT (area baru).
            if (this.editingId) {
                body.id = this.editingId;
            }

            if (this.form.area_type === 'circle') {
                body.center = { lat: parseFloat(this.form.center_lat), lng: parseFloat(this.form.center_lng) };
                body.radius_meter = parseInt(this.form.radius_meter, 10);
            } else {
                body.polygon = this.form.polygon;
            }

            this.saving = true;
            try {
                const res = await window.apiRequest('/api/admin/location', { method: 'PUT', body });
                this.flash('success', res.message || 'Lokasi disimpan.');

                // Layer hasil gambar kini tersimpan: serahkan ke grup referensi
                // supaya digambar ulang dari data DB (dengan _areaId) saat reload.
                if (this.unsavedLayer && res.data?.id) {
                    this.refLayers.addLayer(this.unsavedLayer);
                    this.unsavedLayer = null;
                }
                this.editingId = res.data?.id ?? this.editingId;
                await this.reload();
            } catch (e) {
                this.flash('error', e.message || 'Gagal menyimpan.');
            } finally {
                this.saving = false;
            }
        },

        async reload() {
            try {
                const res = await window.apiRequest('/api/admin/location', { method: 'GET' });
                if (res?.success) {
                    this.areas = res.data || [];
                    const active = this.areas.find((a) => a.is_active);
                    this.activeId = active?.id ?? null;

                    // Pertahankan seleksi: setelah save berikutnya tetap UPDATE id yang sama.
                    const keepEditing = this.editingId;
                    this.selectedLayer = null;
                    this.editingId = null;
                    this.renderAreas();
                    if (keepEditing) {
                        const area = this.areas.find((a) => a.id === keepEditing);
                        if (area) this.selectArea(area, this.findLayerByArea(area.id));
                    }
                }
            } catch (e) {}
        },

        async toggleActive(area) {
            try {
                await window.apiRequest(`/api/admin/location/${area.id}/toggle`, {
                    method: 'PATCH',
                    body: { is_active: !area.is_active },
                });
                await this.reload();
                this.flash('success', area.is_active ? 'Lokasi dinonaktifkan.' : 'Lokasi diaktifkan.');
            } catch (e) {
                this.flash('error', e.message || 'Gagal mengubah status.');
            }
        },

        async destroy(area) {
            if (!confirm(`Hapus lokasi "${area.name}"?`)) return;
            try {
                await window.apiRequest(`/api/admin/location/${area.id}`, { method: 'DELETE' });
                if (this.editingId === area.id) this.clearSelection();
                await this.reload();
                this.flash('success', 'Lokasi dihapus.');
            } catch (e) {
                this.flash('error', e.message || 'Gagal menghapus.');
            }
        },

        flash(type, text) {
            this.message = { type, text };
            clearTimeout(this.__t);
            this.__t = setTimeout(() => (this.message = { type: '', text: '' }), 4000);
        },
    };
};
