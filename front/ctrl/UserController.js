/**
 * Contrôleur de base pour la vue publique (visiteur).
 * Gère l'initialisation de la carte Leaflet, l'affichage des observations,
 * la légende des catégories et la recherche par mot-clé.
 * Conçu pour être étendu par AdminController.
 */
class UserController {

    constructor() {
        this.map = null;
        this.observationWorker = new ObservationWorker();
        this.categoryWorker = new CategoryWorker();

        this.observations = [];
        this.categories = [];
        this.categoriesById = {};

        // Couches Leaflet regroupées par observation pour pouvoir les effacer au rechargement
        this.observationLayers = [];
    }

    /**
     * Initialise la carte et charge les données (catégories et observations).
     * Point d'entrée à appeler une fois le DOM prêt.
     *
     * @returns {Promise<void>}
     */
    async init() {
        this.initMap('map');
        this.bindUserEvents();

        await this.loadCategories();
        await this.loadObservations();
    }

    /**
     * Attache les écouteurs d'événements communs à tous les utilisateurs
     * (bouton de centrage et champ de recherche).
     *
     * @returns {void}
     */
    bindUserEvents() {
        document.getElementById('center-btn')
            .addEventListener('click', () => this.centerMap());

        document.getElementById('search-input')
            .addEventListener('input', (e) => this.searchObservation(e.target.value));
    }

    /**
     * Initialise la carte Leaflet centrée sur la Suisse par défaut.
     *
     * @param {string} containerId Identifiant DOM du conteneur de la carte
     * @returns {void}
     */
    initMap(containerId) {
        this.map = L.map(containerId).setView([46.8, 8.2], 8); // centré sur la Suisse par défaut
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            maxZoom: 19,
            attribution: '&copy; OpenStreetMap'
        }).addTo(this.map);
    }

    /**
     * Centre la carte sur la position géographique actuelle de l'utilisateur.
     * Affiche un marqueur et ouvre son popup. Gère le refus de géolocalisation.
     *
     * @returns {void}
     */
    centerMap() {
        if (!navigator.geolocation) {
            this.showError("Géolocalisation non supportée.");
            return;
        }
        navigator.geolocation.getCurrentPosition(
            (position) => {
                const lat = position.coords.latitude;
                const lng = position.coords.longitude;
                this.map.setView([lat, lng], 8);
                L.marker([lat, lng])
                    .addTo(this.map)
                    .bindPopup("Vous êtes ici !")
                    .openPopup();
            },
            () => {
                this.showError("Géolocalisation refusée.");
            },
            { timeout: 5000}
        );
    }

    /**
     * Charge les observations depuis le serveur, met à jour la liste interne
     * et les affiche sur la carte.
     *
     * @returns {Promise<void>}
     */
    async loadObservations() {
        try {
            const data = await this.observationWorker.getObservations();
            this.observations = data;
            this.displayObservations(data);
            this.updateCounter();
        } catch (err) {
            this.showError("Erreur lors du chargement des observations : " + err.message);
        }
    }

    /**
     * Affiche les observations sur la carte sous forme de points ou de zones colorés.
     * Supprime les couches précédentes avant d'en ajouter de nouvelles.
     *
     * @param {Array} observations Tableau d'objets observation à afficher
     * @returns {void}
     */
    displayObservations(observations) {
        // Suppression des couches existantes avant le rechargement
        this.observationLayers.forEach(layer => this.map.removeLayer(layer));
        this.observationLayers = [];

        observations.forEach(obs => {
            const layer = this.buildLayer(obs); // utilise la méthode commune de construction de couche
            if (!layer) return;
            layer.bindPopup(this.buildPopup(obs));
            layer.addTo(this.map);
            this.observationLayers.push(layer);
        });
    }

    /**
     * Met à jour les compteurs de points et de zones affichés dans l'interface.
     *
     * @returns {void}
     */
    updateCounter() {
        const points = this.observations.filter(obs => obs.type === 'point').length;
        const zones  = this.observations.filter(obs => obs.type === 'zone').length;

        const elPoints = document.getElementById('counter-points');
        const elZones  = document.getElementById('counter-zones');

        if (elPoints) elPoints.textContent = points;
        if (elZones)  elZones.textContent  = zones;
    }

    /**
     * Construit et retourne une couche Leaflet (point ou zone) pour une observation.
     * N'ajoute pas la couche à la carte — c'est à l'appelant de le faire.
     *
     * @param {Object} obs Objet observation (avec coordonnées et catégorie)
     * @returns {L.Layer|null} Couche Leaflet ou null si les coordonnées sont absentes
     */
    buildLayer(obs) {
        const color = this.getCategoryColor(obs);
        const coords = (obs.coordinates || [])
            .slice()
            .sort((a, b) => (a.order_index || 0) - (b.order_index || 0)); // tri par order_index pour respecter l'ordre de saisie

        if (!coords.length) return null;

        if (obs.type === 'zone' && coords.length >= 3) {
            const latlngs = coords.map(c => [parseFloat(c.latitude), parseFloat(c.longitude)]);
            return L.polygon(latlngs, {
                color: color,
                fillColor: color,
                fillOpacity: 0.35,
                weight: 2
            });
        } else {
            const c = coords[0];
            return L.circleMarker(
                [parseFloat(c.latitude), parseFloat(c.longitude)],
                { color: color, fillColor: color, fillOpacity: 0.8, radius: 8 }
            );
        }
    }

    /**
     * Construit le contenu HTML du popup affiché au clic sur une observation.
     *
     * @param {Object} obs Objet observation (titre, description, images, catégorie, date)
     * @returns {string} Chaîne HTML du popup
     */
    buildPopup(obs) {
        const catName = obs.category?.name ?? '';
        const date = obs.created_at ? new Date(obs.created_at).toLocaleDateString() : '';
        const desc = obs.description ? `<p>${this.escapeHtml(obs.description)}</p>` : '';
        const images = obs.images?.map(img =>
            `<img src="../../back/${this.escapeHtml(img.file_path)}" style="max-width:120px;margin:2px;">`
        ).join('') ?? '';

        return `
        <div class="popup">
            <h3>${this.escapeHtml(obs.title)}</h3>
            ${desc}
            ${images}
            <small>${this.escapeHtml(catName)}${date ? ` — ${date}` : ''}</small>
        </div>
    `;
    }

    /**
     * Retourne la couleur hexadécimale de la catégorie d'une observation.
     * Utilise la catégorie embarquée dans l'objet, puis le dictionnaire local, puis une couleur par défaut.
     *
     * @param {Object} obs Objet observation
     * @returns {string} Couleur hexadécimale (ex. "#3388ff")
     */
    getCategoryColor(obs) {
        if (obs.category && obs.category.color) return obs.category.color;
        const cat = this.categoriesById[obs.fk_category];
        return cat ? cat.color : '#3388ff'; // couleur Leaflet par défaut si aucune catégorie trouvée
    }

    /**
     * Charge les catégories depuis le serveur, construit le dictionnaire par id
     * et les affiche dans la légende.
     *
     * @returns {Promise<void>}
     */
    async loadCategories() {
        try {
            const data = await this.categoryWorker.getCategories();
            this.categories = data;
            this.categoriesById = {};
            data.forEach(cat => { this.categoriesById[cat.pk_category] = cat; }); // index par clé primaire pour accès O(1)
            this.displayCategories(data);
        } catch (err) {
            this.showError("Erreur lors du chargement des catégories : " + err.message);
        }
    }

    /**
     * Affiche la légende des catégories dans le conteneur #legend.
     * Ne fait rien si le conteneur est absent du DOM.
     *
     * @param {Array} categories Tableau d'objets catégorie
     * @returns {void}
     */
    displayCategories(categories) {
        const container = document.getElementById('legend');
        if (!container) return;

        container.innerHTML = ''; // efface avant de re-remplir pour éviter les doublons

        categories.forEach(cat => {
            const item = document.createElement('div');
            item.className = 'legend-item';
            item.innerHTML = `
                <span class="legend-color" style="background:${cat.color}"></span>
                <span>${this.escapeHtml(cat.name)}</span>
            `;
            container.appendChild(item);
        });
    }

    /**
     * Recherche des observations par mot-clé et met à jour la carte.
     * Recharge toutes les observations si le mot-clé fait moins de 3 caractères.
     *
     * @param {string} keyword Mot-clé saisi par l'utilisateur
     * @returns {Promise<void>}
     */
    async searchObservation(keyword) {
        try {
            if (!keyword || keyword.trim().length < 3) {
                await this.loadObservations(); // recharge tout si moins de 3 caractères
                return;
            }
            const data = await this.observationWorker.getObservationByNameDescription(keyword);
            this.observations = data;
            this.displayObservations(data);
            this.updateCounter();
        } catch (err) {
            this.showError("Erreur lors de la recherche : " + err.message);
        }
    }

    /**
     * Affiche un message d'erreur dans la zone #error pendant 5 secondes.
     * Utilise alert() si le conteneur est absent du DOM.
     *
     * @param {string} message Message d'erreur à afficher
     * @returns {void}
     */
    showError(message) {
        const box = document.getElementById('error');
        if (box) {
            box.textContent = message;
            box.style.display = 'block';
            setTimeout(() => { box.style.display = 'none'; }, 5000);
        } else {
            alert(message);
        }
    }

    /**
     * Échappe les caractères spéciaux HTML pour prévenir les injections XSS.
     *
     * @param {string|null} str Chaîne à échapper
     * @returns {string} Chaîne sécurisée
     */
    escapeHtml(str) {
        if (str == null) return '';
        return String(str)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#39;');
    }
}
