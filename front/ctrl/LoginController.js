class LoginController {

    constructor() {
        this.authWorker = new AuthWorker();
    }

    init() {
        const form = document.getElementById('login-form');
        if (!form) return;

        form.addEventListener('submit', (e) => {
            e.preventDefault(); // empêche le rechargement de la page
            const username = document.getElementById('username-input').value.trim();
            const password = document.getElementById('password-input').value;
            this.login(username, password);
        });
    }

    async login(username, password) {
        if (!username || !password) {
            this.showError("Nom d'utilisateur et mot de passe requis.");
            return;
        }

        try {
            const result = await this.authWorker.postLogin(username, password);

            if (result && result.success === true) {
                window.location.href = 'admin.html';
            } else {
                this.showError(result.error ?? "Identifiants incorrects.");
            }
        } catch (err) {
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