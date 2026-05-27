/**
 * Contrôleur gérant la page de connexion.
 * Écoute la soumission du formulaire, appelle AuthWorker et redirige
 * l'utilisateur vers la page admin en cas de succès.
 */
class LoginController {

    constructor() {
        this.authWorker = new AuthWorker();
    }

    /**
     * Initialise les écouteurs d'événements sur le formulaire de connexion.
     * Doit être appelé une fois le DOM chargé.
     *
     * @returns {void}
     */
    init() {
        const form = document.getElementById('login-form');
        console.log('form trouvé:', form);
        if (!form) return;

        form.addEventListener('submit', (e) => {
            e.preventDefault(); // empêche le rechargement de la page
            const username = document.getElementById('username-input').value.trim();
            const password = document.getElementById('password-input').value;
            this.login(username, password);
        });
    }

    /**
     * Envoie les identifiants au serveur et gère la réponse.
     * Redirige vers admin.html si la connexion réussit,
     * affiche un message d'erreur sinon.
     *
     * @param {string} username Nom d'utilisateur saisi
     * @param {string} password Mot de passe saisi
     * @returns {Promise<void>}
     */
    async login(username, password) {
        console.log('login() appelé avec:', username, password);
        if (!username || !password) {
            this.showError("Nom d'utilisateur et mot de passe requis.");
            return;
        }

        try {
            const result = await this.authWorker.postLogin(username, password);
            console.log('Réponse du backend:', result);

            if (result && result.success === true) {
                console.log('Succès → redirect');
                window.location.href = 'admin.html';
            } else {
                console.log('Échec:', result);
                this.showError(result.error ?? "Identifiants incorrects.");
            }
        } catch (err) {
            console.log('Erreur catch:', err.message);
            this.showError("Identifiants incorrects.");
        }
    }

    /**
     * Affiche un message d'erreur dans la zone dédiée (#error).
     *
     * @param {string} message Message à afficher
     * @returns {void}
     */
    showError(message) {
        const box = document.getElementById('error');
        if (box) {
            box.textContent = message;
            box.style.display = 'block';
        }
    }
}
