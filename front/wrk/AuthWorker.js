/**
 * Worker handling authentication-related HTTP requests.
 */
class AuthWorker {

    constructor() {
        this.baseUrl = '../../back/index.php';
    }

    async postLogin(username, password) {
        try {
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
        } catch (error) {
            throw new Error(`Impossible de se connecter : ${error.message}`);
        }
    }

    /**
     * sdsdws
     * @returns {Promise<any>}
     */
    async postLogout() {
        try {
            const response = await fetch(`${this.baseUrl}?action=logout`, {
                method: 'POST',
                credentials: 'include'
            });
            if (!response.ok) {
                throw new Error(`Logout failed (HTTP ${response.status})`);
            }
            return await response.json();
        } catch (error) {
            throw new Error(`Impossible de se déconnecter : ${error.message}`);
        }
    }

    async check() {
        try {
            const response = await fetch(`${this.baseUrl}?action=checkAuth`, {
                credentials: 'include'
            });
            if (!response.ok) {
                return { authenticated: false };
            }
            return await response.json();
        } catch (error) {
            throw new Error(`Impossible de vérifier l'authentification : ${error.message}`);
        }
    }
}
