/**
 * Worker gérant les requêtes HTTP liées aux catégories.
 * Communique avec le backend via l'API REST pour récupérer la liste des catégories.
 */
class CategoryWorker {

    constructor() {
        this.baseUrl = '../../back/index.php';
    }

    /**
     * Récupère la liste complète des catégories depuis le serveur.
     *
     * @returns {Promise<Array>} Tableau d'objets catégorie
     * @throws {Error} Si la requête échoue ou si le serveur retourne une erreur HTTP
     */
    async getCategories() {
        try {
            const response = await fetch(`${this.baseUrl}?action=getCategories`, {
                credentials: 'include'
            });
            if (!response.ok) {
                throw new Error(`Failed to load categories (HTTP ${response.status})`);
            }
            return await response.json();
        } catch (error) {
            throw new Error(`Impossible de charger les catégories : ${error.message}`);
        }
    }
}
