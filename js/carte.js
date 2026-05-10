/* =========================================
   KAISEKI SHUNEI — CARTE.JS 
   ========================================= */

document.addEventListener('DOMContentLoaded', () => {

    // =========================================
    // 1. ÉLÉMENTS DE LA PAGE
    // =========================================
    const searchInput = document.getElementById('menuSearch');
    const regimeSelect = document.getElementById('filter-regime');
    const saveurSelect = document.getElementById('filter-saveur');
    const sortSelect = document.getElementById('sort-price');
    const sectionsContainer = document.querySelector('.carte-main');
    
    // Éléments de la modale d'image
    const modal = document.getElementById('imageModal');
    const modalImg = document.getElementById('modalImg');
    const modalCaption = document.getElementById('modalCaption');
    const modalClose = document.querySelector('.modal-close');

    let currentPlats = []; // Va stocker les plats récupérés par PHP

    // =========================================
    // 2. OBSERVATEURS (Scroll & Animations)
    // =========================================
    
    // Observateur pour mettre à jour la barre de navigation au scroll
    const catBtns = document.querySelectorAll('.cat-nav-btn');
    const sectionObserver = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                const id = entry.target.id;
                catBtns.forEach(btn => {
                    btn.classList.toggle('active', btn.getAttribute('data-target') === id);
                });
            }
        });
    }, { threshold: 0.3 });

    // Observateur pour l'animation d'apparition des cartes
    const animObserver = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.style.animationDelay = '0s';
                entry.target.classList.add('visible');
                animObserver.unobserve(entry.target);
            }
        });
    }, { threshold: 0.1 });

    // Initialisation des observateurs sur le HTML d'origine
    document.querySelectorAll('.cat-section, .menus-section').forEach(s => sectionObserver.observe(s));
    
    // Fonction pour gérer le scroll doux via les boutons de catégorie
    catBtns.forEach(btn => {
        btn.addEventListener('click', () => {
            const target = btn.getAttribute('data-target');
            const section = document.getElementById(target);
            if (section) {
                const offset = 80;
                const top = section.getBoundingClientRect().top + window.scrollY - offset;
                window.scrollTo({ top, behavior: 'smooth' });
            }
        });
    });


    // =========================================
    // 3. GESTION DE LA MODALE D'IMAGES
    // =========================================
    const closeModal = () => {
        if (modal) {
            modal.classList.remove('active');
            document.body.style.overflow = '';
        }
    };

    if (modalClose) modalClose.addEventListener('click', closeModal);
    if (modal) modal.addEventListener('click', (e) => { if (e.target === modal) closeModal(); });
    document.addEventListener('keydown', (e) => { if (e.key === 'Escape') closeModal(); });

    // Cette fonction attache les clics et animations sur les cartes (nécessaire après un Fetch)
    function attacherEvenementsCartes() {
        const cards = document.querySelectorAll('.dish-card, .drink-card, .menu-card');
        
        cards.forEach(card => {
            // On remet l'animation d'apparition
            animObserver.observe(card);

            // On remet le clic pour la modale (uniquement si data-img existe)
            if (card.hasAttribute('data-img')) {
                card.addEventListener('click', (e) => {
                    if (e.target.closest('.btn-ajouter')) return; // Ne pas ouvrir si on clique sur "Ajouter au panier"
                    const img = card.getAttribute('data-img');
                    const name = card.getAttribute('data-title');
                    if (img && modal) {
                        modalImg.src = img;
                        modalCaption.textContent = name;
                        modal.classList.add('active');
                        document.body.style.overflow = 'hidden';
                    }
                });
            }
        });
    }

    // Attacher les événements au chargement initial de la page
    attacherEvenementsCartes();


    // =========================================
    // 4. LOGIQUE ASYNCHRONE (FETCH) & FILTRES
    // =========================================

    async function chargerPlats() {
        // On vérifie que les éléments existent pour éviter des erreurs
        if (!searchInput || !regimeSelect || !saveurSelect) return;

        const query = searchInput.value.trim().toLowerCase();
        const regime = regimeSelect.value;
        const saveur = saveurSelect.value;

        // Appel au serveur PHP sans recharger la page
        const url = `../actions/filtrer_carte.php?recherche=${encodeURIComponent(query)}&regime=${regime}&saveur=${saveur}`;

        try {
            const response = await fetch(url);
            currentPlats = await response.json();
            
            appliquerTri(); // Trie les données reçues
            renderPlats();  // Redessine l'écran
        } catch (error) {
            console.error("Erreur lors du filtrage AJAX :", error);
        }
    }

    function appliquerTri() {
        if (!sortSelect || currentPlats.length === 0) return;
        
        const order = sortSelect.value;
        if (order === 'asc') {
            currentPlats.sort((a, b) => a.prix - b.prix);
        } else if (order === 'desc') {
            currentPlats.sort((a, b) => b.prix - a.prix);
        }
        // Si 'default', on laisse l'ordre naturel renvoyé par PHP
    }

    function renderPlats() {
        if (!sectionsContainer) return;

        if (currentPlats.length === 0) {
            sectionsContainer.innerHTML = '<div style="text-align:center; padding: 50px; color:#bc9c64;">Aucun plat ne correspond à vos critères.</div>';
            return;
        }

        // On vide tout et on crée un seul grand bloc pour afficher les résultats filtrés
        sectionsContainer.innerHTML = `
            <section class="cat-section" style="padding-top: 40px;">
                <div class="cat-header">
                    <div class="cat-title-block">
                        <h2>Résultats du filtre</h2>
                        <p>DÉCOUVREZ NOTRE SÉLECTION</p>
                    </div>
                </div>
                <div class="dishes-grid layout-2" id="grid-resultats"></div>
            </section>
        `;

        const grid = document.getElementById('grid-resultats');

        currentPlats.forEach(p => {
            const src = p.image ? `../${p.image}` : '';
            const isPremium = p.prix >= 60;
            
            // Génération des tags allergènes
            let allergenesHTML = '';
            if (p.allergenes && p.allergenes.length > 0) {
                allergenesHTML = '<div class="dish-allergenes">';
                p.allergenes.forEach(a => {
                    allergenesHTML += `<span class="allergen-tag">${a}</span>`;
                });
                allergenesHTML += '</div>';
            }

            // Génération de l'image (ou kanji par défaut)
            let imageHTML = '';
            if (src) {
                imageHTML = `
                    <div class="dish-img-wrap">
                        <img src="${src}" alt="${p.nom}" loading="lazy">
                        <div class="dish-overlay"></div>
                        ${isPremium ? '<span class="dish-badge">SIGNATURE</span>' : ''}
                    </div>
                `;
            } else {
                imageHTML = `
                    <div class="dish-no-img">
                        <span class="placeholder-kanji">料理</span>
                    </div>
                `;
            }

            // Construction finale de la carte
            const card = `
                <article class="dish-card" data-title="${p.nom}" data-img="${src}">
                    ${imageHTML}
                    <div class="dish-body">
                        <div class="dish-top">
                            <h3 class="dish-name">${p.nom}</h3>
                            <span class="dish-price">${p.prix}€</span>
                        </div>
                        <p class="dish-desc">${p.description}</p>
                        ${allergenesHTML}
                        <a href="ajouter_panier.php?id=${p.id}" class="btn-ajouter">AJOUTER AU PANIER</a>
                    </div>
                </article>
            `;
            grid.innerHTML += card;
        });

        // ⚠️ TRÈS IMPORTANT : On doit réattacher les clics d'images et les animations aux nouveaux plats créés !
        attacherEvenementsCartes();
    }

    // =========================================
    // 5. DÉCLENCHEURS DES FILTRES
    // =========================================
    
    // Si l'utilisateur tape ou sélectionne une option, on lance Fetch
    if (searchInput) searchInput.addEventListener('input', chargerPlats);
    if (regimeSelect) regimeSelect.addEventListener('change', chargerPlats);
    if (saveurSelect) saveurSelect.addEventListener('change', chargerPlats);
    
    // Le tri est local, il ne refait pas de Fetch, il trie juste les données déjà là et redessine
    if (sortSelect) {
        sortSelect.addEventListener('change', () => {
            // Si on n'a pas encore fait de Fetch, currentPlats est vide. 
            // On force un Fetch la première fois pour avoir la base de données propre.
            if (currentPlats.length === 0) {
                chargerPlats();
            } else {
                appliquerTri();
                renderPlats();
            }
        });
    }
});
// =========================================
    // 6. GESTION DE L'AJOUT AU PANIER (AJAX)
    // =========================================
    document.body.addEventListener('click', async (e) => {
        // On intercepte les clics sur tous les boutons d'ajout (Plats, Boissons, Menus)
        if (e.target.matches('.btn-ajouter, .drink-btn, .btn-menu')) {
            e.preventDefault(); // On bloque le rechargement brutal de la page
            
            const btn = e.target;
            const url = btn.getAttribute('href'); // On récupère "ajouter_panier.php?id=..."
            const texteOriginal = btn.textContent;

            // Effet visuel pendant le chargement
            btn.textContent = 'AJOUT...';
            btn.style.pointerEvents = 'none';
            btn.style.opacity = '0.7';

            try {
                // On discute avec le serveur en arrière-plan
                const response = await fetch(url);
                const data = await response.json();

                if (data.success) {
                    // Animation "Succès" sur le bouton
                    btn.textContent = 'AJOUTÉ ✓';
                    btn.style.background = '#4caf50'; // Vert succès
                    btn.style.color = '#fff';
                    btn.style.borderColor = '#4caf50';
                    btn.style.opacity = '1';

                    // --- Mise à jour de la pastille rouge dans le menu du haut ---
                    let cartBtn = document.querySelector('.btn-cart');
                    let cartBadge = cartBtn.querySelector('.cart-count');
                    
                    if (!cartBadge) {
                        // Si le panier était vide, on crée la bulle
                        cartBtn.innerHTML = `MON PANIER <span class="cart-count">${data.total_items}</span>`;
                    } else {
                        // Sinon, on met juste à jour le chiffre
                        cartBadge.textContent = data.total_items;
                    }
                    
                    // Petit effet d'animation (Pop) sur la pastille
                    const newBadge = document.querySelector('.cart-count');
                    if (newBadge) {
                        newBadge.style.transform = 'scale(1.4)';
                        newBadge.style.transition = 'transform 0.2s';
                        setTimeout(() => newBadge.style.transform = 'scale(1)', 200);
                    }

                    // On remet le bouton normal après 1.5 secondes
                    setTimeout(() => {
                        btn.textContent = texteOriginal;
                        btn.style.background = '';
                        btn.style.color = '';
                        btn.style.borderColor = '';
                        btn.style.pointerEvents = 'auto';
                    }, 1500);

                } else {
                    alert("Erreur lors de l'ajout.");
                    btn.textContent = texteOriginal;
                    btn.style.pointerEvents = 'auto';
                    btn.style.opacity = '1';
                }
            } catch (error) {
                console.error("Erreur AJAX Panier:", error);
                alert("Erreur de communication avec le serveur.");
                btn.textContent = texteOriginal;
                btn.style.pointerEvents = 'auto';
                btn.style.opacity = '1';
            }
        }
    });