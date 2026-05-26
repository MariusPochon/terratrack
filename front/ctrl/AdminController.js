/**
 * Admin controller. Extends UserController with CRUD operations,
 * drawing of new points/zones, and authentication management.
 */
class AdminController extends UserController {

    constructor() {
        super();
        this.authWorker = new AuthWorker();

        // Drawing state
        this.drawingMode = null;          // null | 'point' | 'zone'
        this.pendingCoords = [];          // [[lat,lng], ...]
        this.pendingLayers = [];          // temporary preview layers
        this.editingId = null;            // pk_observation being edited (null = create)
    }

    /**
     * Bootstraps the admin page: verifies auth, sets up the map and events.
     */
    async init() {
        const auth = await this.authWorker.check();
        if (!auth || !auth.authenticated) {
            window.location.href = 'login.html';
            return;
        }

        this.initMap('map');
        this.bindEvents();

        await this.loadCategories();
        await this.loadObservations();
        this.populateCategorySelect();
        this.listObservations();
    }

    bindEvents() {
        // Logout
        const logoutBtn = document.getElementById('logout-btn');
        if (logoutBtn) logoutBtn.addEventListener('click', () => this.logout());

        // Center map on user
        const centerBtn = document.getElementById('center-btn');
        if (centerBtn) centerBtn.addEventListener('click', () => this.centerMap());

        // Search
        const searchInput = document.getElementById('search-input');
        if (searchInput) {
            searchInput.addEventListener('input', (e) => this.searchObservation(e.target.value));
        }

        // Drawing toggles
        document.getElementById('draw-point-btn').addEventListener('click', () => this.startDrawing('point'));
        document.getElementById('draw-zone-btn').addEventListener('click', () => this.startDrawing('zone'));
        document.getElementById('cancel-draw-btn').addEventListener('click', () => this.cancelDrawing());
        document.getElementById('finish-zone-btn').addEventListener('click', () => this.finishZone());

        // Map clicks for drawing
        this.map.on('click', (e) => this.onMapClick(e));

        // Form submit
        document.getElementById('observation-form').addEventListener('submit', (e) => {
            e.preventDefault();
            this.submitForm();
        });

        // Cancel edit
        document.getElementById('cancel-edit-btn').addEventListener('click', () => this.resetForm());
    }

    populateCategorySelect() {
        const select = document.getElementById('category-select');
        if (!select) return;
        select.innerHTML = '';
        this.categories.forEach(cat => {
            const opt = document.createElement('option');
            opt.value = cat.pk_category;
            opt.textContent = cat.name;
            select.appendChild(opt);
        });
    }

    // -------------------------------------------------------------------------
    // Drawing
    // -------------------------------------------------------------------------

    startDrawing(mode) {
        this.cancelDrawing();
        this.drawingMode = mode;
        document.getElementById('draw-status').textContent =
            mode === 'point'
                ? "Cliquez sur la carte pour placer un point."
                : "Cliquez pour ajouter des sommets, puis 'Terminer la zone'.";
        document.getElementById('finish-zone-btn').style.display = mode === 'zone' ? 'inline-block' : 'none';
        document.getElementById('cancel-draw-btn').style.display = 'inline-block';
    }

    cancelDrawing() {
        this.drawingMode = null;
        this.pendingCoords = [];
        this.pendingLayers.forEach(l => this.map.removeLayer(l));
        this.pendingLayers = [];
        const status = document.getElementById('draw-status');
        if (status) status.textContent = '';
        const finish = document.getElementById('finish-zone-btn');
        const cancel = document.getElementById('cancel-draw-btn');
        if (finish) finish.style.display = 'none';
        if (cancel) cancel.style.display = 'none';
    }

    onMapClick(e) {
        if (!this.drawingMode) return;
        const latlng = [e.latlng.lat, e.latlng.lng];
        this.pendingCoords.push(latlng);

        const marker = L.circleMarker(latlng, { radius: 5, color: '#ff5500' }).addTo(this.map);
        this.pendingLayers.push(marker);

        if (this.drawingMode === 'point') {
            this.openFormForNew('point');
        } else {
            // Refresh polygon preview
            const existingPoly = this.pendingLayers.find(l => l instanceof L.Polyline && !(l instanceof L.CircleMarker));
            if (existingPoly) {
                this.map.removeLayer(existingPoly);
                this.pendingLayers = this.pendingLayers.filter(l => l !== existingPoly);
            }
            if (this.pendingCoords.length >= 2) {
                const poly = L.polyline(this.pendingCoords, { color: '#ff5500', dashArray: '4' }).addTo(this.map);
                this.pendingLayers.push(poly);
            }
        }
    }

    finishZone() {
        if (this.drawingMode !== 'zone') return;
        if (this.pendingCoords.length < 3) {
            this.showError("Une zone nécessite au moins 3 points.");
            return;
        }
        this.openFormForNew('zone');
    }

    openFormForNew(type) {
        this.editingId = null;
        document.getElementById('form-type').value = type;
        document.getElementById('observation-form').style.display = 'block';
        document.getElementById('form-title').textContent =
            type === 'point' ? 'Nouveau point' : 'Nouvelle zone';
    }

    // -------------------------------------------------------------------------
    // Form submission
    // -------------------------------------------------------------------------

    async submitForm() {
        const title = document.getElementById('title-input').value.trim();
        const description = document.getElementById('description-input').value.trim();
        const fkCategory = document.getElementById('category-select').value;
        const type = document.getElementById('form-type').value;
        const imagesInput = document.getElementById('images-input');

        if (!title) {
            this.showError("Le titre est obligatoire.");
            return;
        }
        if (!fkCategory) {
            this.showError("Sélectionnez une catégorie.");
            return;
        }

        const data = {
            title,
            description,
            type,
            isArea: type === 'zone',
            fkCategory: parseInt(fkCategory, 10),
            coordinates: this.pendingCoords.map((c, idx) => ({
                latitude: c[0],
                longitude: c[1],
                order_index: idx
            })),
            images: imagesInput ? imagesInput.files : []
        };

        try {
            if (this.editingId) {
                await this.editObservation(this.editingId, data);
            } else if (type === 'point') {
                await this.savePoint(data);
            } else {
                await this.saveZone(data);
            }
            this.resetForm();
            this.cancelDrawing();
            await this.loadObservations();
            this.listObservations();
        } catch (err) {
            this.showError("Échec de l'enregistrement : " + err.message);
        }
    }

    buildFormData(data) {
        const fd = new FormData();
        fd.append('title', data.title);
        fd.append('description', data.description || '');
        fd.append('type', data.type);
        fd.append('isArea', data.isArea ? '1' : '0');
        fd.append('fk_category', data.fkCategory);
        fd.append('coordinates', JSON.stringify(data.coordinates));

        if (data.images && data.images.length) {
            for (const file of data.images) {
                fd.append('images[]', file);
            }
        }
        return fd;
    }

    async savePoint(data) {
        const fd = this.buildFormData({ ...data, isArea: false, type: 'point' });
        return await this.observationWorker.postObservation(fd);
    }

    async saveZone(data) {
        const fd = this.buildFormData({ ...data, isArea: true, type: 'zone' });
        return await this.observationWorker.postObservation(fd);
    }

    // -------------------------------------------------------------------------
    // List / edit / delete
    // -------------------------------------------------------------------------

    listObservations() {
        const list = document.getElementById('observation-list');
        if (!list) return;
        list.innerHTML = '';

        this.observations.forEach(obs => {
            const row = document.createElement('div');
            row.className = 'list-row';
            row.innerHTML = `
                <span class="list-title">${this.escapeHtml(obs.title)}</span>
                <span class="list-type">${this.escapeHtml(obs.type)}</span>
                <button data-edit="${obs.pk_observation}">Éditer</button>
                <button data-delete="${obs.pk_observation}">Supprimer</button>
            `;
            list.appendChild(row);
        });

        list.querySelectorAll('button[data-edit]').forEach(btn => {
            btn.addEventListener('click', () => this.loadObservationForEdit(parseInt(btn.dataset.edit, 10)));
        });
        list.querySelectorAll('button[data-delete]').forEach(btn => {
            btn.addEventListener('click', () => this.deleteObservation(parseInt(btn.dataset.delete, 10)));
        });
    }

    loadObservationForEdit(pkObservation) {
        const obs = this.observations.find(o => o.pk_observation === pkObservation);
        if (!obs) return;

        this.cancelDrawing();
        this.editingId = pkObservation;

        document.getElementById('title-input').value = obs.title || '';
        document.getElementById('description-input').value = obs.description || '';
        document.getElementById('category-select').value = obs.fk_category;
        document.getElementById('form-type').value = obs.type;
        document.getElementById('form-title').textContent = 'Édition : ' + obs.title;
        document.getElementById('observation-form').style.display = 'block';

        // Pre-fill pending coordinates from current observation
        this.pendingCoords = (obs.coordinates || [])
            .slice()
            .sort((a, b) => (a.order_index || 0) - (b.order_index || 0))
            .map(c => [parseFloat(c.latitude), parseFloat(c.longitude)]);
    }

    async editObservation(pkObservation, data) {
        const fd = this.buildFormData(data);
        return await this.observationWorker.putObservation(pkObservation, fd);
    }

    async deleteObservation(pkObservation) {
        if (!confirm("Supprimer cette observation ?")) return;
        try {
            await this.observationWorker.deleteObservation(pkObservation);
            await this.loadObservations();
            this.listObservations();
        } catch (err) {
            this.showError("Échec de la suppression : " + err.message);
        }
    }

    resetForm() {
        const form = document.getElementById('observation-form');
        if (form) {
            form.reset();
            form.style.display = 'none';
        }
        this.editingId = null;
    }

    // -------------------------------------------------------------------------
    // Auth
    // -------------------------------------------------------------------------

    async logout() {
        try {
            await this.authWorker.postLogout();
        } catch (err) {
            // even if logout fails server-side, redirect
        }
        window.location.href = 'login.html';
    }
}
