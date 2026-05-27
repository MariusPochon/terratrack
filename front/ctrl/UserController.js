/**
 * Base controller for the public visitor view.
 * Handles map setup, observation rendering and category legend.
 */
class UserController {

    constructor() {
        this.map = null;
        this.observationWorker = new ObservationWorker();
        this.categoryWorker = new CategoryWorker();

        this.observations = [];
        this.categories = [];
        this.categoriesById = {};

        // Leaflet layers grouped per observation so they can be cleared on refresh
        this.observationLayers = [];
    }

    async init() {
        this.initMap('map');
        this.bindUserEvents();

        await this.loadCategories();
        await this.loadObservations();
    }

    bindUserEvents() {
        document.getElementById('center-btn')
            .addEventListener('click', () => this.centerMap());

        document.getElementById('search-input')
            .addEventListener('input', (e) => this.searchObservation(e.target.value));
    }

    /**
     * Initializes the Leaflet map.
     * @param {string} containerId DOM id of the map container.
     */
    initMap(containerId) {
        this.map = L.map(containerId).setView([46.8, 8.2], 8); // defaut: Suisse
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            maxZoom: 19,
            attribution: '&copy; OpenStreetMap'
        }).addTo(this.map);
    }

    /**
     * Centers the map on the user's current location.
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
     * Renders observations on the map (points and zones), colored by category.
     */
    displayObservations(observations) {
        // Clear existing layers
        this.observationLayers.forEach(layer => this.map.removeLayer(layer));
        this.observationLayers = [];

        observations.forEach(obs => {
            const layer = this.buildLayer(obs); // ← on utilise la méthode commune
            if (!layer) return;
            layer.bindPopup(this.buildPopup(obs));
            layer.addTo(this.map);
            this.observationLayers.push(layer);
        });
    }

    updateCounter() {
        const points = this.observations.filter(obs => obs.type === 'point').length;
        const zones  = this.observations.filter(obs => obs.type === 'zone').length;

        const elPoints = document.getElementById('counter-points');
        const elZones  = document.getElementById('counter-zones');

        if (elPoints) elPoints.textContent = points;
        if (elZones)  elZones.textContent  = zones;
    }

    /**
     * Creates and returns a Leaflet layer (point or zone) for an observation.
     * Does not add it to the map — the caller decides where it goes.
     * @param {object} obs - The observation object.
     * @returns {L.Layer|null}
     */
    buildLayer(obs) {
        const color = this.getCategoryColor(obs);
        const coords = (obs.coordinates || [])
            .slice()
            .sort((a, b) => (a.order_index || 0) - (b.order_index || 0));

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

    getCategoryColor(obs) {
        if (obs.category && obs.category.color) return obs.category.color;
        const cat = this.categoriesById[obs.fk_category];
        return cat ? cat.color : '#3388ff';
    }

    async loadCategories() {
        try {
            const data = await this.categoryWorker.getCategories();
            this.categories = data;
            this.categoriesById = {};
            data.forEach(cat => { this.categoriesById[cat.pk_category] = cat; });
            this.displayCategories(data);
        } catch (err) {
            this.showError("Erreur lors du chargement des catégories : " + err.message);
        }
    }

    /**
     * Renders the category legend in the #legend container, if present.
     */
    displayCategories(categories) {
        const container = document.getElementById('legend');
        if (!container) return;

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

    async searchObservation(keyword) {
        try {
            if (!keyword || keyword.trim().length < 3) {
                await this.loadObservations(); // recharge tout si moins de 3 chars
                return;
            }
            const data = await this.observationWorker.getObservationByNameDescription(keyword);
            this.observations = data;
            this.displayObservations(data);
        } catch (err) {
            this.showError("Erreur lors de la recherche : " + err.message);
        }
    }

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
