/* =========================================
   KAISEKI SHUNEI — PROFIL.JS
   ========================================= */

document.addEventListener('DOMContentLoaded', () => {

    // 1. GESTION DE LA MODALE D'ÉDITION
    const btnEdit = document.getElementById('btn-edit-profile');
    const modal = document.getElementById('edit-profile-modal');
    const btnClose = document.getElementById('close-edit-modal');

    if (btnEdit && modal && btnClose) {
        btnEdit.addEventListener('click', () => {
            modal.classList.add('active');
            // Initialiser les compteurs à l'ouverture
            if (emailInput) updateCounter(emailInput, counterEmail);
            if (mdpInput) updateCounter(mdpInput, counterMdp);
        });

        btnClose.addEventListener('click', () => {
            modal.classList.remove('active');
        });

        // Fermer la modale si on clique en dehors
        window.addEventListener('click', (e) => {
            if (e.target === modal) {
                modal.classList.remove('active');
            }
        });
    }

    // 2. GESTION DU FORMULAIRE ET DES COMPTEURS
    const formEdit = document.getElementById('form-edit-profile');
    if (!formEdit) return;

    const emailInput = formEdit.querySelector('input[name="login"]');
    const mdpInput = formEdit.querySelector('input[name="mdp"]');
    const telInput = formEdit.querySelector('input[name="telephone"]');
    const btnSubmit = formEdit.querySelector('.btn-submit');

    // -- Afficher/Masquer Mot de passe --
    const togglePassword = document.getElementById('toggleEditPassword');
    if (togglePassword && mdpInput) {
        togglePassword.addEventListener('click', () => {
            const type = mdpInput.getAttribute('type') === 'password' ? 'text' : 'password';
            mdpInput.setAttribute('type', type);
            togglePassword.textContent = type === 'password' ? '👁️' : '🙈';
        });
    }

    // -- Compteurs de caractères --
    const counterEmail = document.getElementById('counter-edit-email');
    const counterMdp = document.getElementById('counter-edit-mdp');

    function updateCounter(inputElement, counterElement) {
        if (!inputElement || !counterElement) return;
        const currentLength = inputElement.value.length;
        const maxLength = inputElement.getAttribute('maxlength');
        counterElement.textContent = `${currentLength} / ${maxLength}`;
        
        if (currentLength >= maxLength) {
            counterElement.style.color = '#ff4444';
        } else {
            counterElement.style.color = '#888';
        }
    }

    if (emailInput) emailInput.addEventListener('input', () => updateCounter(emailInput, counterEmail));
    if (mdpInput) mdpInput.addEventListener('input', () => updateCounter(mdpInput, counterMdp));

    // -- Formatage du téléphone --
    if (telInput) {
        telInput.addEventListener('input', () => {
            let val = telInput.value.replace(/\D/g, '').substring(0, 10);
            if (val.length > 0) {
                val = val.match(/.{1,2}/g).join(' ');
            }
            telInput.value = val;
        });
    }

    // -- Validation avant soumission (Blocage de l'envoi) --
    formEdit.addEventListener('submit', (e) => {
        let isValid = true;
        
        emailInput.style.outline = 'none';
        telInput.style.outline = 'none';
        mdpInput.style.outline = 'none';

        // Validation Email
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        if (!emailRegex.test(emailInput.value)) {
            isValid = false;
            emailInput.style.outline = '1px solid #ff4444';
        }

        // Validation Téléphone
        const phoneRegex = /^(\+33|0)[1-9](\s?\d{2}){4}$/;
        if (!phoneRegex.test(telInput.value.replace(/\s/g, ''))) {
            isValid = false;
            telInput.style.outline = '1px solid #ff4444';
        }

        // Validation Mot de passe (seulement s'il est rempli, car il est optionnel pour la modif)
        if (mdpInput.value.length > 0 && mdpInput.value.length < 8) {
            isValid = false;
            mdpInput.style.outline = '1px solid #ff4444';
        }

        if (!isValid) {
            // ON BLOQUE L'ENVOI AU SERVEUR SI INVALIDE !
            e.preventDefault();
            btnSubmit.style.animation = 'shake 0.4s';
            setTimeout(() => btnSubmit.style.animation = '', 400);
        } else {
            // TOUT EST VALIDE : ON ENVOIE EN AJAX (FETCH)
            
            // 1. On empêche le rechargement classique de la page (TRÈS IMPORTANT)
            e.preventDefault(); 
            
            // 2. On prépare les données du formulaire
            const formData = new FormData(formEdit);
            
            // Effet visuel de chargement
            btnSubmit.textContent = 'ENREGISTREMENT...';
            btnSubmit.style.opacity = '0.7';
            btnSubmit.style.pointerEvents = 'none'; // Empêche le double clic

            // 3. On envoie la requête HTTP en arrière-plan
            fetch('../actions/modifier_profil.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json()) // On dit qu'on attend du JSON en retour
            .then(data => {
                if (data.success) {
                    // SUCCÈS ! 
                    btnSubmit.style.background = '#4caf50'; // Bouton en vert
                    btnSubmit.style.color = 'white';
                    btnSubmit.textContent = 'PROFIL MIS À JOUR ✓';

                    // On ferme la modale après 1.5 seconde
                    setTimeout(() => {
                        document.getElementById('edit-profile-modal').classList.remove('active');
                        
                        // Reset du bouton pour la prochaine fois
                        btnSubmit.style.background = '#bc9c64';
                        btnSubmit.style.color = 'black';
                        btnSubmit.textContent = 'ENREGISTRER';
                        btnSubmit.style.opacity = '1';
                        btnSubmit.style.pointerEvents = 'auto';

                        // On recharge la page silencieusement pour afficher les nouvelles infos 
                        // (ou tu pourrais juste changer le texte avec JS si tu as mis des IDs)
                        window.location.reload(); 
                    }, 1500);

                } else {
                    // ERREUR CÔTÉ SERVEUR (ex: email déjà pris)
                    alert('Erreur : ' + data.message);
                    btnSubmit.textContent = 'ENREGISTRER';
                    btnSubmit.style.opacity = '1';
                    btnSubmit.style.pointerEvents = 'auto';
                }
            })
            .catch(error => {
                console.error('Erreur Fetch:', error);
                alert('Une erreur est survenue lors de la communication avec le serveur.');
                btnSubmit.textContent = 'ENREGISTRER';
                btnSubmit.style.opacity = '1';
                btnSubmit.style.pointerEvents = 'auto';
            });
        }
    });
});