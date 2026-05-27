/**
 * Worker gérant les requêtes HTTP liées à l'authentification.
 * Communique avec le backend pour la connexion, la déconnexion et la vérification de session.
 */
class AuthWorker {

    constructor() {
        this.baseUrl = '../../back/index.php';
    }

    /**
     * Envoie les identifiants de connexion au serveur via POST.
     *
     * @param {string} username Nom d'utilisateur
     * @param {string} password Mot de passe en clair
     * @returns {Promise<Object>} Réponse JSON du serveur (contient "success" ou "error")
     * @throws {Error} Si la requête échoue ou si le serveur retourne une erreur HTTP
     */
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
     * Envoie une requête de déconnexion au serveur.
     * Détruit la session côté serveur.
     *
     * @returns {Promise<Object>} Réponse JSON du serveur
     * @throws {Error} Si la requête échoue ou si le serveur retourne une erreur HTTP
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

    /**
     * Vérifie si l'utilisateur dispose d'une session active côté serveur.
     * En cas d'erreur HTTP, retourne { authenticated: false } sans lever d'exception.
     *
     * @returns {Promise<Object>} Objet contenant la propriété booléenne "authenticated"
     * @throws {Error} Si une erreur réseau survient
     */
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
