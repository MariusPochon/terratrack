/**
 * Contrôleur administrateur. Étend UserController avec les opérations CRUD,
 * l'intégration de Leaflet.draw pour la création de points/zones,
 * et la gestion de l'authentification.
 *
 * Particularité : deux FeatureGroups distincts sont utilisés — drawnItems pour
 * les couches en cours de dessin, et savedItems pour les observations chargées
 * depuis la base de données. Annuler ou fermer la modale supprime la couche de drawnItems.
 */
class AdminController extends UserController {

    constructor() {
        super();
        this.authWorker   = new AuthWorker();

        this.drawnItems   = null;  // L.FeatureGroup — couches dessinées lors de cette session
        this.savedItems   = null;  // L.FeatureGroup — observations chargées depuis la BDD
        this.currentLayer = null;  // référence à la couche actuellement ouverte dans la modale

        this.editingId    = null;  // pk_observation en cours d'édition (null = mode création)
    }

    // -------------------------------------------------------------------------
    // Initialisation
    // -------------------------------------------------------------------------

    /**
     * Initialise la page admin : vérifie l'authentification, configure la carte,
     * charge les données et peuple les composants de l'interface.
     *
     * @returns {Promise<void>}
     */
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

    /**
     * Configure les outils de dessin Leaflet.draw sur la carte.
     * Deux FeatureGroups sont ajoutés à la carte, et les événements de fin de dessin
     * ouvrent automatiquement la modale de saisie.
     *
     * @returns {void}
     */
    setupDraw() {
        // Deux FeatureGroups séparés : dessin en cours et observations sauvegardées
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

        // L'utilisateur termine un dessin → ajout à drawnItems et ouverture de la modale
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
    // Modale
    // -------------------------------------------------------------------------

    /**
     * Ouvre la modale en mode création pour un nouveau point ou une nouvelle zone.
     * Réinitialise le formulaire et pré-remplit les coordonnées depuis la couche dessinée.
     *
     * @param {string}   type  Type de l'observation : "point" ou "zone"
     * @param {L.Layer}  layer Couche Leaflet dont on extrait les coordonnées
     * @returns {void}
     */
    openModal(type, layer) {
        this.editingId = null;

        const form = document.getElementById('form-releve');
        form.reset();
        document.getElementById('modal-status').textContent = '';
        document.getElementById('modal-type').value         = type;
        document.getElementById('modal-titre').textContent  =
            type === 'point' ? 'Nouveau point' : 'Nouvelle zone';

        // Stocke les coordonnées dans le champ caché pour lecture à la soumission
        document.getElementById('modal-coords').value = JSON.stringify(
            this.extractCoords(layer, type)
        );

        document.getElementById('modal-overlay').classList.add('actif');
    }

    /**
     * Ouvre la modale en mode édition pour une observation existante.
     * Pré-remplit tous les champs avec les données actuelles de l'observation.
     *
     * @param {Object}  obs   Objet observation à éditer
     * @param {L.Layer} layer Couche Leaflet représentant l'observation sur la carte
     * @returns {void}
     */
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

    /**
     * Ferme la modale et réinitialise les références internes.
     * Si removeLayer est true, supprime également la couche de drawnItems.
     *
     * @param {boolean} [removeLayer=false] Supprimer la couche de dessin en cours
     * @returns {void}
     */
    closeModal(removeLayer = false) {
        if (removeLayer && this.currentLayer) {
            this.drawnItems.removeLayer(this.currentLayer);
        }
        this.currentLayer = null;
        this.editingId    = null;
        document.getElementById('modal-overlay').classList.remove('actif');
    }

    /**
     * Extrait les coordonnées d'une couche Leaflet au format [{latitude, longitude, order_index}].
     *
     * @param {L.Layer} layer Couche Leaflet (marker ou polygon)
     * @param {string}  type  Type de la couche : "point" ou "zone"
     * @returns {Array<{latitude: number, longitude: number, order_index: number}>}
     */
    extractCoords(layer, type) {
        if (type === 'point') {
            const ll = layer.getLatLng();
            return [{ latitude: ll.lat, longitude: ll.lng, order_index: 0 }];
        }
        // polygon : récupère le premier anneau de coordonnées
        return layer.getLatLngs()[0].map((ll, idx) => ({
            latitude:    ll.lat,
            longitude:   ll.lng,
            order_index: idx
        }));
    }

    // -------------------------------------------------------------------------
    // Événements
    // -------------------------------------------------------------------------

    /**
     * Attache les écouteurs d'événements spécifiques à l'admin
     * (déconnexion, annulation de modale, mise à jour de couleur en direct, soumission).
     *
     * @returns {void}
     */
    bindAdminEvents() {
        document.getElementById('logout-btn')
            .addEventListener('click', () => this.logout());

        // Bouton Annuler — ferme également la modale en cliquant sur l'overlay en dehors
        document.getElementById('btn-annuler')
            .addEventListener('click', () => this.closeModal(true));

        document.getElementById('modal-overlay')
            .addEventListener('click', (e) => {
                if (e.target === document.getElementById('modal-overlay')) {
                    this.closeModal(true);
                }
            });

        document.getElementById('modal-category-select')
            .addEventListener('change', (e) => {
                const cat = this.categoriesById[e.target.value];
            });

        document.getElementById('form-releve')
            .addEventListener('submit', (e) => { e.preventDefault(); this.submitModal(); });
    }

    // -------------------------------------------------------------------------
    // Peuplement du select de catégories
    // -------------------------------------------------------------------------

    /**
     * Peuple le menu déroulant des catégories dans la modale.
     * Conserve l'option vide par défaut et supprime les options précédentes.
     *
     * @returns {void}
     */
    populateCategorySelect() {
        const select = document.getElementById('modal-category-select');
        // Conserve l'option vide initiale, supprime toutes les autres
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
    // Surcharge de displayObservations pour utiliser savedItems
    // -------------------------------------------------------------------------

    /**
     * Affiche les observations dans le FeatureGroup savedItems (géré par Leaflet.draw).
     * Surcharge la méthode parente pour éviter d'ajouter les couches directement à la carte.
     *
     * @param {Array} observations Tableau d'objets observation
     * @returns {void}
     */
    displayObservations(observations) {
        if (!this.savedItems) {
            this.showError("Erreur interne : la carte n'est pas encore prête.");
            return;
        }
        this.savedItems.clearLayers();

        observations.forEach(obs => {
            const layer = this.buildLayer(obs); // même méthode de construction que le parent
            if (!layer) return;
            layer.bindPopup(this.buildPopup(obs), { maxWidth: 260 });
            this.savedItems.addLayer(layer);
        });
    }

    // -------------------------------------------------------------------------
    // Soumission du formulaire
    // -------------------------------------------------------------------------

    /**
     * Lit les données de la modale, les envoie au serveur (création ou mise à jour),
     * puis déplace la couche de drawnItems vers savedItems et rafraîchit la liste.
     *
     * @returns {Promise<void>}
     */
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
                // Attache le popup définitif à la couche sur la carte
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

                    // Déplace la couche de drawnItems vers savedItems pour qu'elle ne soit plus modifiable via les outils de dessin
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
    // Liste des observations (barre latérale)
    // -------------------------------------------------------------------------

    /**
     * Affiche la liste des observations dans la barre latérale (#observation-list).
     * Génère les boutons Éditer et Supprimer pour chaque ligne.
     *
     * @returns {void}
     */
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

    /**
     * Charge une observation en mode édition : crée une couche temporaire dans drawnItems,
     * recentre la carte et ouvre la modale pré-remplie.
     *
     * @param {number} pkObservation Identifiant de l'observation à éditer
     * @returns {void}
     */
    loadObservationForEdit(pkObservation) {
        const obs = this.observations.find(o => o.pk_observation === pkObservation);
        if (!obs) return;

        const sorted = (obs.coordinates || [])
            .slice()
            .sort((a, b) => (a.order_index || 0) - (b.order_index || 0));

        const color = this.getCategoryColor(obs);

        // Construit une couche temporaire modifiable dans drawnItems
        this.drawnItems.clearLayers();
        let layer;

        if (obs.type === 'zone' && sorted.length >= 3) {
            const latlngs = sorted.map(c => [parseFloat(c.latitude), parseFloat(c.longitude)]);
            layer = L.polygon(latlngs, { color, fillColor: color, fillOpacity: 0.3 });
        } else if (sorted.length) {
            layer = L.circleMarker(
                [parseFloat(sorted[0].latitude), parseFloat(sorted[0].longitude)],
                { color, fillColor: color, fillOpacity: 0.8, radius: 8 }
            );
        } else {
            return;
        }

        this.drawnItems.addLayer(layer);
        this.currentLayer = layer;

        // Recentre la carte sur l'observation
        if (layer.getLatLng) {
            this.map.setView(layer.getLatLng(), 15);
        } else if (layer.getBounds) {
            this.map.fitBounds(layer.getBounds());
        }

        this.openModalForEdit(obs, layer);
    }

    /**
     * Demande confirmation puis supprime une observation.
     * Rafraîchit la liste après suppression.
     *
     * @param {number} pkObservation Identifiant de l'observation à supprimer
     * @returns {Promise<void>}
     */
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
    // Authentification
    // -------------------------------------------------------------------------

    /**
     * Déconnecte l'utilisateur côté serveur puis redirige vers la page de connexion.
     * La redirection se produit même en cas d'erreur réseau.
     *
     * @returns {Promise<void>}
     */
    async logout() {
        try { await this.authWorker.postLogout(); } catch (_) { /* redirection quoi qu'il arrive */ }
        window.location.href = 'login.html';
    }
}
