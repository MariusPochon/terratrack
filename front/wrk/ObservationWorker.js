/**
 * Worker handling all observation-related HTTP requests.
 */
class ObservationWorker {

    constructor() {
        this.baseUrl = '../../back/index.php';
    }

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
