/**
 * Admin controller. Extends UserController with CRUD operations,
 * Leaflet.draw integration for point/zone creation, and auth management.
 *
 * Pattern taken from test-09: modal overlay opens automatically after drawing,
 * the layer color updates live when the user picks a category, and cancelling
 * removes the drawn layer from the map.
 */
class AdminController extends UserController {

    constructor() {
        super();
        this.authWorker   = new AuthWorker();

        this.drawnItems   = null;  // L.FeatureGroup — shapes being drawn this session
        this.savedItems   = null;  // L.FeatureGroup — observations loaded from DB
        this.currentLayer = null;  // reference to the layer currently in the modal

        this.editingId    = null;  // pk_observation being edited (null = create mode)
    }

    // -------------------------------------------------------------------------
    // Bootstrap
    // -------------------------------------------------------------------------

    async init() {
        const auth = await this.authWorker.check();
        if (!auth || !auth.authenticated) {
            window.location.href = 'login.html';
            return;
        }

        this.initMap('map');
        this.setupDraw();
        this.bindUserEvents();
        this.bindAdminEvents();

        await this.loadCategories();
        await this.loadObservations();

        this.populateCategorySelect();
        this.listObservations();
    }

    // -------------------------------------------------------------------------
    // Leaflet.draw
    // -------------------------------------------------------------------------

    setupDraw() {
        // Two separate FeatureGroups: drawn (current session) and saved (from DB)
        this.drawnItems = new L.FeatureGroup();
        this.savedItems = new L.FeatureGroup();
        this.map.addLayer(this.savedItems);
        this.map.addLayer(this.drawnItems);

        const drawControl = new L.Control.Draw({
            draw: {
                marker:       true,
                polygon:      true,
                circle:       false,
                rectangle:    false,
                polyline:     false,
                circlemarker: false
            },
            edit: { featureGroup: this.drawnItems }
        });
        this.map.addControl(drawControl);

        // User finishes drawing → add to drawnItems and open the modal
        this.map.on(L.Draw.Event.CREATED, (e) => {
            const layer = e.layer;
            const type  = e.layerType; // 'marker' | 'polygon'

            if (type === 'polygon') {
                layer.setStyle({ color: '#95a5a6', fillColor: '#95a5a6', fillOpacity: 0.3 });
            }

            this.drawnItems.addLayer(layer);
            this.currentLayer = layer;

            if (type === 'marker') {
                this.openModal('point', layer);
            } else if (type === 'polygon') {
                this.openModal('zone', layer);
            }
        });
    }

    // -------------------------------------------------------------------------
    // Modal
    // -------------------------------------------------------------------------

    openModal(type, layer) {
        this.editingId = null;

        const form = document.getElementById('form-releve');
        form.reset();
        document.getElementById('modal-status').textContent = '';
        document.getElementById('modal-type').value         = type;
        document.getElementById('modal-titre').textContent  =
            type === 'point' ? 'Nouveau point' : 'Nouvelle zone';

        // Store coords in the hidden field (will be read at submit time)
        document.getElementById('modal-coords').value = JSON.stringify(
            this.extractCoords(layer, type)
        );

        document.getElementById('modal-overlay').classList.add('actif');
    }

    openModalForEdit(obs, layer) {
        this.editingId = obs.pk_observation;

        document.getElementById('form-releve').reset();
        document.getElementById('modal-status').textContent     = '';
        document.getElementById('modal-type').value             = obs.type;
        document.getElementById('modal-titre').textContent      = 'Édition : ' + obs.title;
        document.getElementById('modal-title-input').value      = obs.title || '';
        document.getElementById('modal-description-input').value = obs.description || '';
        document.getElementById('modal-category-select').value  = obs.fk_category;
        document.getElementById('modal-coords').value = JSON.stringify(
            this.extractCoords(layer, obs.type)
        );

        document.getElementById('modal-overlay').classList.add('actif');
    }

    closeModal(removeLayer = false) {
        if (removeLayer && this.currentLayer) {
            this.drawnItems.removeLayer(this.currentLayer);
        }
        this.currentLayer = null;
        this.editingId    = null;
        document.getElementById('modal-overlay').classList.remove('actif');
    }

    /**
     * Extracts coordinates from a Leaflet layer into [{latitude, longitude, order_index}].
     */
    extractCoords(layer, type) {
        if (type === 'point') {
            const ll = layer.getLatLng();
            return [{ latitude: ll.lat, longitude: ll.lng, order_index: 0 }];
        }
        // polygon
        return layer.getLatLngs()[0].map((ll, idx) => ({
            latitude:    ll.lat,
            longitude:   ll.lng,
            order_index: idx
        }));
    }

    // -------------------------------------------------------------------------
    // Events
    // -------------------------------------------------------------------------

    bindAdminEvents() {
        document.getElementById('logout-btn')
            .addEventListener('click', () => this.logout());

        // Cancel button — also triggers when clicking outside the modal box
        document.getElementById('btn-annuler')
            .addEventListener('click', () => this.closeModal(true));

        document.getElementById('modal-overlay')
            .addEventListener('click', (e) => {
                if (e.target === document.getElementById('modal-overlay')) {
                    this.closeModal(true);
                }
            });

        // Live color update when the user picks a category in the modal
        document.getElementById('modal-category-select')
            .addEventListener('change', (e) => {
                const cat = this.categoriesById[e.target.value];
                if (!cat || !this.currentLayer) return;
                if (this.currentLayer.setIcon) {
                    this.currentLayer.setStyle({ color: cat.color, fillColor: cat.color });
                }
            });

        document.getElementById('form-releve')
            .addEventListener('submit', (e) => { e.preventDefault(); this.submitModal(); });
    }

    // -------------------------------------------------------------------------
    // Category select population
    // -------------------------------------------------------------------------

    populateCategorySelect() {
        const select = document.getElementById('modal-category-select');
        // Keep the default empty option, remove the rest
        select.querySelectorAll('option:not([value=""])').forEach(o => o.remove());

        this.categories.forEach(cat => {
            const opt = document.createElement('option');
            opt.value           = cat.pk_category;
            opt.textContent     = cat.name;
            opt.dataset.color   = cat.color;
            select.appendChild(opt);
        });
    }

    // -------------------------------------------------------------------------
    // Override displayObservations to use savedItems FeatureGroup
    // -------------------------------------------------------------------------

    displayObservations(observations) {
        if (!this.savedItems) {
            this.showError("Erreur interne : la carte n'est pas encore prête.");
            return;
        }
        this.savedItems.clearLayers();

        observations.forEach(obs => {
            const layer = this.buildLayer(obs); // ← même méthode que le parent
            if (!layer) return;
            layer.bindPopup(this.buildPopup(obs), { maxWidth: 260 });
            this.savedItems.addLayer(layer);
        });
    }

    // -------------------------------------------------------------------------
    // Form submit
    // -------------------------------------------------------------------------

    async submitModal() {
        const title      = document.getElementById('modal-title-input').value.trim();
        const description = document.getElementById('modal-description-input').value.trim();
        const fkCategory = document.getElementById('modal-category-select').value;
        const type       = document.getElementById('modal-type').value;
        const coordsJson = document.getElementById('modal-coords').value;
        const imagesInput = document.getElementById('modal-images-input');
        const statusEl   = document.getElementById('modal-status');

        if (!title)      { statusEl.textContent = "Le titre est obligatoire.";   return; }
        if (!fkCategory) { statusEl.textContent = "Sélectionnez une catégorie."; return; }

        const coords = JSON.parse(coordsJson);

        const fd = new FormData();
        fd.append('title',       title);
        fd.append('description', description);
        fd.append('type',        type);
        fd.append('isArea',      type === 'zone' ? '1' : '0');
        fd.append('fk_category', fkCategory);
        fd.append('coordinates', JSON.stringify(coords));
        if (imagesInput && imagesInput.files.length) {
            for (const file of imagesInput.files) fd.append('images[]', file);
        }

        statusEl.textContent = "Envoi en cours...";

        if (this.editingId) {
            fd.append('id', this.editingId);
        }
        try {
            let result;
            if (this.editingId) {
                result = await this.observationWorker.putObservation(this.editingId, fd);
            } else {
                result = await this.observationWorker.postObservation(fd);
            }

            if (result && (result.success || result.pk_observation)) {
                // Bind the final styled popup to the layer on the map
                if (this.currentLayer) {
                    const fakeObs = {
                        title,
                        description,
                        type,
                        fk_category: parseInt(fkCategory, 10),
                        created_at:  new Date().toLocaleDateString('fr-CH'),
                        coordinates: coords,
                        images:      result.images || [],
                        category:    this.categoriesById[fkCategory] || null
                    };
                    this.currentLayer.bindPopup(this.buildPopup(fakeObs), { maxWidth: 260 });

                    // Move from drawnItems to savedItems so it's no longer editable via draw tools
                    this.drawnItems.removeLayer(this.currentLayer);
                    this.savedItems.addLayer(this.currentLayer);
                }

                statusEl.textContent = "Enregistré !";
                setTimeout(() => {
                    this.closeModal(false);
                    this.loadObservations().then(() => this.listObservations());
                }, 800);
            } else {
                statusEl.textContent = "Erreur : " + (result?.error || 'réponse inattendue');
            }
        } catch (err) {
            statusEl.textContent = "Erreur réseau : " + err.message;
        }
    }

    // -------------------------------------------------------------------------
    // Observation list (sidebar)
    // -------------------------------------------------------------------------

    listObservations() {
        const list = document.getElementById('observation-list');
        if (!list) return;
        list.innerHTML = '';

        this.observations.forEach(obs => {
            const color = this.getCategoryColor(obs);
            const row   = document.createElement('div');
            row.className = 'list-row';
            row.innerHTML = `
                <span class="legend-color" style="background:${color};flex-shrink:0;"></span>
                <span class="list-title">${this.escapeHtml(obs.title)}</span>
                <span class="list-type">${this.escapeHtml(obs.type)}</span>
                <button data-edit="${obs.pk_observation}">Éditer</button>
                <button data-delete="${obs.pk_observation}">Suppr.</button>
            `;
            list.appendChild(row);
        });

        list.querySelectorAll('button[data-edit]').forEach(btn => {
            btn.addEventListener('click', () =>
                this.loadObservationForEdit(parseInt(btn.dataset.edit, 10)));
        });
        list.querySelectorAll('button[data-delete]').forEach(btn => {
            btn.addEventListener('click', () =>
                this.deleteObservation(parseInt(btn.dataset.delete, 10)));
        });
    }

    loadObservationForEdit(pkObservation) {
        const obs = this.observations.find(o => o.pk_observation === pkObservation);
        if (!obs) return;

        const sorted = (obs.coordinates || [])
            .slice()
            .sort((a, b) => (a.order_index || 0) - (b.order_index || 0));

        const color = this.getCategoryColor(obs);

        // Build a temporary editable layer in drawnItems
        this.drawnItems.clearLayers();
        let layer;

        if (obs.type === 'zone' && sorted.length >= 3) {
            const latlngs = sorted.map(c => [parseFloat(c.latitude), parseFloat(c.longitude)]);
            layer = L.polygon(latlngs, { color, fillColor: color, fillOpacity: 0.3 });
        } else if (sorted.length) {
            layer = L.circleMarker(
                [parseFloat(c.latitude), parseFloat(c.longitude)],
                { color, fillColor: color, fillOpacity: 0.8, radius: 8 }
            );
        } else {
            return;
        }

        this.drawnItems.addLayer(layer);
        this.currentLayer = layer;

        // Pan map to the observation
        if (layer.getLatLng) {
            this.map.setView(layer.getLatLng(), 15);
        } else if (layer.getBounds) {
            this.map.fitBounds(layer.getBounds());
        }

        this.openModalForEdit(obs, layer);
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

    // -------------------------------------------------------------------------
    // Auth
    // -------------------------------------------------------------------------

    async logout() {
        try { await this.authWorker.postLogout(); } catch (_) { /* redirect anyway */ }
        window.location.href = 'login.html';
    }
}
