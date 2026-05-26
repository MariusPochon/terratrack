/**
 * Worker handling category-related HTTP requests.
 */
class CategoryWorker {

    constructor() {
        this.baseUrl = '../../back/index.php';
    }

    async getCategories() {
        const response = await fetch(`${this.baseUrl}?action=getCategories`, {
            credentials: 'include'
        });
        if (!response.ok) {
            throw new Error(`Failed to load categories (HTTP ${response.status})`);
        }
        return await response.json();
    }
}
