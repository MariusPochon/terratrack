/**
 * Worker gérant toutes les requêtes HTTP liées aux observations.
 * Fournit les méthodes de lecture, création, mise à jour et suppression
 * via l'API REST du backend.
 */
class ObservationWorker {

    constructor() {
        this.baseUrl = '../../back/index.php';
    }

    /**
     * Récupère la liste complète des observations depuis le serveur.
     *
     * @returns {Promise<Array>} Tableau d'objets observation (avec coordonnées, images et catégorie)
     * @throws {Error} Si la requête échoue ou si le serveur retourne une erreur HTTP
     */
    async getObservations() {
        try {
            const response = await fetch(`${this.baseUrl}?action=getObservations`, {
                credentials: 'include'
            });
            if (!response.ok) {
                throw new Error(`Failed to load observations (HTTP ${response.status})`);
            }
            return await response.json();
        } catch (error) {
            throw new Error(`Impossible de charger les observations : ${error.message}`);
        }
    }

    /**
     * Recherche des observations dont le titre ou la description contient le mot-clé.
     *
     * @param {string} keyword Mot-clé à rechercher (minimum 3 caractères côté serveur)
     * @returns {Promise<Array>} Tableau d'objets observation correspondant à la recherche
     * @throws {Error} Si la requête échoue ou si le serveur retourne une erreur HTTP
     */
    async getObservationByNameDescription(keyword) {
        try {
            const url = `${this.baseUrl}?action=searchObservations&q=${encodeURIComponent(keyword)}`;
            const response = await fetch(url, { credentials: 'include' });
            if (!response.ok) {
                throw new Error(`Search failed (HTTP ${response.status})`);
            }
            return await response.json();
        } catch (error) {
            throw new Error(`Impossible de rechercher les observations : ${error.message}`);
        }
    }

    /**
     * Crée une nouvelle observation en envoyant les données du formulaire au serveur.
     *
     * @param {FormData} formData Données du formulaire (titre, description, type, coordonnées, images)
     * @returns {Promise<Object>} Réponse JSON du serveur (contient "success" et "id")
     * @throws {Error} Si la requête échoue ou si la réponse n'est pas du JSON valide
     */
    async postObservation(formData) {
        try {
            const response = await fetch(`${this.baseUrl}?action=createObservation`, {
                method: 'POST',
                credentials: 'include',
                body: formData
            });
            const text = await response.text();
            console.log('Réponse brute:', text);
            return JSON.parse(text);
            if (!response.ok) {
                throw new Error(`Failed to create observation (HTTP ${response.status})`);
            }
            return await response.json();
        } catch (error) {
            throw new Error(`Impossible de créer l'observation : ${error.message}`);
        }
    }

    /**
     * Met à jour une observation existante identifiée par son id.
     *
     * @param {number} pkObservation Identifiant de l'observation à modifier
     * @param {FormData} formData    Nouvelles données du formulaire
     * @returns {Promise<Object>}    Réponse JSON du serveur
     * @throws {Error} Si la requête échoue ou si le serveur retourne une erreur HTTP
     */
    async putObservation(pkObservation, formData) {
        try {
            const url = `${this.baseUrl}?action=updateObservation&id=${encodeURIComponent(pkObservation)}`;
            const response = await fetch(url, {
                method: 'POST',
                credentials: 'include',
                body: formData
            });
            if (!response.ok) {
                throw new Error(`Failed to update observation (HTTP ${response.status})`);
            }
            return await response.json();
        } catch (error) {
            throw new Error(`Impossible de mettre à jour l'observation : ${error.message}`);
        }
    }

    /**
     * Supprime une observation identifiée par son id.
     *
     * @param {number} pkObservation Identifiant de l'observation à supprimer
     * @returns {Promise<Object>}    Réponse JSON du serveur (contient "success")
     * @throws {Error} Si la requête échoue ou si le serveur retourne une erreur HTTP
     */
    async deleteObservation(pkObservation) {
        try {
            const formData = new FormData();
            formData.append('id', pkObservation);

            const response = await fetch(`${this.baseUrl}?action=deleteObservation`, {
                method: 'POST',
                credentials: 'include',
                body: formData
            });
            if (!response.ok) {
                throw new Error(`Failed to delete observation (HTTP ${response.status})`);
            }
            return await response.json();
        } catch (error) {
            throw new Error(`Impossible de supprimer l'observation : ${error.message}`);
        }
    }
}
