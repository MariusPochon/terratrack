/**
 * Worker handling authentication-related HTTP requests.
 */
class AuthWorker {

    constructor() {
        this.baseUrl = '../../back/index.php';
    }

    async postLogin(username, password) {
        const formData = new FormData();
        formData.append('username', username);
        formData.append('password', password);

        const response = await fetch(`${this.baseUrl}?action=login`, {
            method: 'POST',
            credentials: 'include',
            body: formData
        });
        if (!response.ok) {
            throw new Error(`Erreur lors du Login (HTTP ${response.status})`);
        }
        return await response.json();
    }

    /**
     * sdsdws
     * @returns {Promise<any>}
     */
    async postLogout() {
        const response = await fetch(`${this.baseUrl}?action=logout`, {
            method: 'POST',
            credentials: 'include'
        });
        if (!response.ok) {
            throw new Error(`Logout failed (HTTP ${response.status})`);
        }
        return await response.json();
    }

    async check() {
        const response = await fetch(`${this.baseUrl}?action=checkAuth`, {
            credentials: 'include'
        });
        if (!response.ok) {
            return { authenticated: false };
        }
        return await response.json();
    }
}
