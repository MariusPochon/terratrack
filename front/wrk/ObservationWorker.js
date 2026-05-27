/**
 * Worker handling all observation-related HTTP requests.
 */
class ObservationWorker {

    constructor() {
        this.baseUrl = '../../back/index.php';
    }

    async getObservations() {
        const response = await fetch(`${this.baseUrl}?action=getObservations`, {
            credentials: 'include'
        });
        if (!response.ok) {
            throw new Error(`Failed to load observations (HTTP ${response.status})`);
        }
        return await response.json();
    }

    async getObservationByNameDescription(keyword) {
        const url = `${this.baseUrl}?action=searchObservations&q=${encodeURIComponent(keyword)}`;
        const response = await fetch(url, { credentials: 'include' });
        if (!response.ok) {
            throw new Error(`Search failed (HTTP ${response.status})`);
        }
        return await response.json();
    }

    async postObservation(formData) {
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
    }

    async putObservation(pkObservation, formData) {
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
    }

    async deleteObservation(pkObservation) {
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
    }
}
