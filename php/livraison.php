<?php
require_once '../includes/config.php';
require_once '../includes/fonctions.php';

requireRole('livreur');
$livreur = $_SESSION['user'];

$dataCommandes = lireJSON(JSON_COMMANDES);

// CORRECTION: On utilise ?? null pour éviter le plantage si id_livreur n'existe pas encore
$mesLivraisons = array_values(array_filter(
    $dataCommandes['commandes'] ?? [],
    fn($c) => ($c['id_livreur'] ?? null) === $livreur['id'] && ($c['statut'] ?? '') === 'en_livraison'
));

// Récupération des avis (commandes livrées par ce livreur avec une note de livraison)
$mesAvis = array_reverse(array_values(array_filter(
    $dataCommandes['commandes'] ?? [],
    fn($c) => ($c['id_livreur'] ?? null) === $livreur['id'] && ($c['statut'] ?? '') === 'livree' && isset($c['note_client']['livraison'])
)));
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <script>
        // Appliquer le thème AVANT le rendu pour éviter le flash
        (function(){
            const m = document.cookie.match(/(?:^|; )kaiseki_theme=([^;]*)/);
         const t = m ? decodeURIComponent(m[1]) : 'sombre';
            if (t === 'clair') {
             const l = document.createElement('link');
             l.rel = 'stylesheet'; l.id = 'theme-stylesheet';
             l.href = '../css/theme-clair.css';
                document.head.appendChild(l);
            }
        })();
    </script>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Livreur | Kaiseki Shunei</title>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700&family=Plus+Jakarta+Sans:wght@300;400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../css/livraison.css">
</head>
<body class="page-delivery">

    <header class="delivery-nav">
        <div class="status-indicator">
            <span class="pulse"></span>
            <h1>MISSION EN COURS</h1>
        </div>
        <a href="../index.php" class="btn-exit">ACCUEIL</a>
    </header>

    <main class="delivery-container">

        <?php if (empty($mesLivraisons)): ?>
            <div style="text-align:center;padding:100px 20px;color:#666;">
                <p style="font-size:1.2rem;margin-bottom:10px; color: var(--white);">Aucune livraison assignée</p>
                <p style="font-size:0.85rem; opacity: 0.6;">En attente d'une nouvelle mission...</p>
            </div>
        <?php endif; ?>

        <?php foreach ($mesLivraisons as $cmd):
            $dataUsers = lireJSON(JSON_USERS);
            $client = null;
            foreach ($dataUsers['utilisateurs'] as $u) {
                if ($u['id'] === $cmd['id_client']) { 
                    $client = $u; 
                    break; 
                }
            }

            $adresse = !empty($cmd['adresse_livraison']) ? $cmd['adresse_livraison'] : ($client['infos']['adresse'] ?? 'Adresse non renseignée');
            $etage = !empty($cmd['etage']) ? $cmd['etage'] : ($client['infos']['etage'] ?? '');
            $interphone = !empty($cmd['interphone']) ? $cmd['interphone'] : ($client['infos']['interphone'] ?? '');
            
            $adresseEncode = urlencode($adresse);
        ?>
        <article class="delivery-card ready" id="cmd-<?= $cmd['id'] ?>">
            <div class="card-header">
                <span class="order-number">COMMANDE #<?= htmlspecialchars($cmd['id']) ?></span>
                <span class="status-pill">EN LIVRAISON</span>
            </div>

            <section class="client-info">
                <h2 class="client-name">
                    <?= $client ? htmlspecialchars($client['infos']['prenom'] . ' ' . $client['infos']['nom']) : 'Client inconnu' ?>
                </h2>
                <address class="client-address"><?= htmlspecialchars($adresse) ?></address>
                
                <?php if (!empty($etage) || !empty($interphone)): ?>
                <div class="delivery-instructions">
                    <p>
                        <?= !empty($etage) ? 'Étage : <strong>' . htmlspecialchars($etage) . '</strong>. ' : '' ?>
                        <?= !empty($interphone) ? 'Interphone : <strong>' . htmlspecialchars($interphone) . '</strong>' : '' ?>
                    </p>
                </div>
                <?php endif; ?>
            </section>

            <section class="order-details">
                <div class="items">
                    <p class="items-title">Articles à livrer :</p>
                    <?php foreach ($cmd['articles'] as $art): ?>
                        <p>• <?= $art['quantite'] ?>× <?= htmlspecialchars($art['id']) ?></p>
                    <?php endforeach; ?>
                </div>
                <div class="payment-status <?= ($cmd['paiement']['statut'] === 'paye') ? 'paid' : 'pending' ?>">
                    TOTAL : <?= $cmd['prix_total'] ?>€
                    <span><?= $cmd['paiement']['statut'] === 'paye' ? 'PAYÉ' : 'À ENCAISSER' ?></span>
                </div>
            </section>

            <footer class="action-grid">
                <a href="https://www.google.com/maps/search/?api=1&query=<?= $adresseEncode ?>" target="_blank" class="btn-secondary">NAVIGUER</a>
                
                <?php if ($client && !empty($client['infos']['telephone'])): ?>
                    <a href="tel:<?= htmlspecialchars($client['infos']['telephone']) ?>" class="btn-secondary">APPELER</a>
                <?php else: ?>
                    <button disabled class="btn-secondary" style="opacity:0.4;">PAS DE TEL</button>
                <?php endif; ?>

                <a href="../actions/livrer.php?id=<?= $cmd['id'] ?>" class="btn-main" onclick="return confirm('Confirmer la livraison de cette commande ?')">TERMINER</a>
            </footer>
        </article>
        <?php endforeach; ?>

        <!-- Section des dernières notations -->
        <section class="delivery-reviews" style="margin-top: 60px; border-top: 1px solid rgba(188, 156, 100, 0.2); padding-top: 40px; padding-bottom: 40px;">
            <h2 style="font-family: 'Playfair Display', serif; color: #bc9c64; text-align: center; margin-bottom: 30px; letter-spacing: 2px; font-size: 1.5rem;">VOS DERNIERS AVIS</h2>
            
            <?php if (empty($mesAvis)): ?>
                <p style="text-align:center; color:#666; font-size:0.85rem; font-style: italic;">Aucune évaluation reçue pour le moment.</p>
            <?php else: ?>
                <div style="max-width: 500px; margin: 0 auto;">
                    <?php foreach (array_slice($mesAvis, 0, 5) as $avis): ?>
                        <div class="review-card" style="background: rgba(188,156,100,0.03); border: 1px solid rgba(188,156,100,0.1); padding: 20px; margin-bottom: 20px; border-radius: 4px;">
                            <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:12px;">
                                <span style="color: #bc9c64; font-weight: 700; font-size: 1.1rem;">⭐ <?= $avis['note_client']['livraison'] ?> / 5</span>
                                <span style="font-size: 0.75rem; color: #666;"><?= $avis['note_client']['date_note'] ?? 'Date inconnue' ?></span>
                            </div>
                            <div style="font-size: 0.7rem; color: #444; margin-top: 15px; text-align: right; letter-spacing: 1px;">COMMANDE #<?= $avis['id'] ?></div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </section>

    </main>

    <script src="../js/livraison.js"></script>
   <script src="../js/theme.js"></script>
</body>
</html>