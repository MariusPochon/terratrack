class LoginController {

    constructor() {
        this.authWorker = new AuthWorker();
    }

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

    showError(message) {
        const box = document.getElementById('error');
        if (box) {
            box.textContent = message;
            box.style.display = 'block';
        }
    }
}