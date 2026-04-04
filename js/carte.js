document.addEventListener('DOMContentLoaded', () => {
    const searchInput = document.getElementById('menuSearch');
    const cards = document.querySelectorAll('.dish-card');
    const modal = document.getElementById('imageZoom');
    const modalImg = document.getElementById('imgFull');
    const caption = document.getElementById('caption');
    const closeBtn = document.querySelector('.close-zoom');

    // Filtrage dynamique
    if (searchInput) {
        searchInput.addEventListener('input', (e) => {
            const query = e.target.value.toLowerCase();
            
            cards.forEach(card => {
                // SÉCURITÉ : On cherche 'data-title'. S'il n'y en a pas, on prend le texte du <h3>
                const rawTitle = card.getAttribute('data-title') || card.querySelector('h3').innerText;
                const title = rawTitle.toLowerCase();
                
                const descEl = card.querySelector('.dish-desc');
                const desc = descEl ? descEl.innerText.toLowerCase() : '';
                
                card.style.display = (title.includes(query) || desc.includes(query)) ? '' : 'none';
            });
        });
    }

    // Zoom Image
    if (modal && modalImg) {
        cards.forEach(card => {
            card.addEventListener('click', (e) => {
                // SÉCURITÉ : On ne zoome pas si on clique sur le bouton "AJOUTER AU PANIER"
                if (e.target.tagName.toLowerCase() === 'a' || e.target.classList.contains('btn-add-cart')) {
                    return; 
                }

                modal.style.display = 'flex';
                
                // On récupère l'image haute définition (data-img) ou l'image normale s'il n'y en a pas
                const imgElement = card.querySelector('img');
                modalImg.src = card.getAttribute('data-img') || (imgElement ? imgElement.src : '');
                
                // On récupère le titre pour la légende
                caption.innerText = card.getAttribute('data-title') || card.querySelector('h3').innerText;
                
                document.body.style.overflow = 'hidden';
            });
        });

        // Fermeture
        const close = () => {
            modal.style.display = 'none';
            document.body.style.overflow = '';
        };
        
        if (closeBtn) closeBtn.onclick = close;
        modal.onclick = (e) => { if(e.target === modal) close(); };
    }
});