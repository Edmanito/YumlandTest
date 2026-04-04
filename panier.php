<?php
require_once '../includes/config.php';
require_once '../includes/fonctions.php';
require_once '../includes/getapikey.php';

// --- 1. DÉTECTION DU RETOUR DE LA BANQUE ---
$paiement_reussi = false;
if (isset($_GET['status']) && $_GET['status'] === 'accepted') {
    // Sécurité : On vérifie que le contrôle MD5 est bon (comme demandé dans le PDF)
    $vendeur_ret = $_GET['vendeur'] ?? '';
    $api_key = getAPIKey($vendeur_ret);
    $chaine = $api_key."#".$_GET['transaction']."#".$_GET['montant']."#".$vendeur_ret."#".$_GET['status']."#";
    
    if ($_GET['control'] === md5($chaine)) {
        $paiement_reussi = true;

        // ENREGISTREMENT EN CUISINE (JSON)
        $dataCmd = lireJSON(JSON_COMMANDES);
        $nouvelle_cmd = [
            'id' => $_GET['transaction'],
            'id_client' => $_SESSION['user']['nom'] ?? 'Client',
            'type' => 'livraison',
            'statut' => 'en_attente',
            'prix_total' => $_GET['montant'],
            'dates' => ['commande' => date('Y-m-d H:i')],
            'articles' => $_SESSION['panier'] ?? []
        ];
        $dataCmd['commandes'][] = $nouvelle_cmd;
        ecrireJSON(JSON_COMMANDES, $dataCmd);

        // On vide le panier après enregistrement
        unset($_SESSION['panier']);
    }
}

// --- 2. CALCUL POUR L'AFFICHAGE NORMAL ---
$montant_total = 0;
if (isset($_SESSION['panier'])) {
    foreach ($_SESSION['panier'] as $item) {
        $montant_total += ($item['prix'] * $item['quantite']);
    }
}

// Configuration pour le formulaire
$vendeur = 'MI-1_A'; // À vérifier selon ton groupe
$transaction = strtoupper(uniqid('CMD'));
$montant_cybank = number_format($montant_total, 2, '.', '');
$protocole = isset($_SERVER['HTTPS']) ? 'https://' : 'http://';
$retour = $protocole . $_SERVER['HTTP_HOST'] . $_SERVER['PHP_SELF']; // Renvoie sur cette même page

$api_key = getAPIKey($vendeur);
$control = md5($api_key."#".$transaction."#".$montant_cybank."#".$vendeur."#".$retour."#");
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Panier | Kaiseki Shunei</title>
    <link rel="stylesheet" href="../css/commun.css">
    <style>
        body { background: #050505; color: white; font-family: sans-serif; padding: 50px; text-align: center; }
        .box { max-width: 500px; margin: 0 auto; border: 1px solid #bc9c64; padding: 30px; background: #111; }
        .btn-pay { background: #bc9c64; color: black; padding: 15px; border: none; cursor: pointer; width: 100%; font-weight: bold; margin-top: 20px; }
        .success-msg { color: #bc9c64; font-size: 1.2rem; }
    </style>
</head>
<body>

<div class="box">
    <?php if ($paiement_reussi): ?>
        <h2 class="success-msg">Paiement Accepté ! ✨</h2>
        <p>Votre commande a été envoyée en cuisine.</p>
        <p>N° Transaction : <?= htmlspecialchars($_GET['transaction']) ?></p>
        <a href="../index.php" style="color:#bc9c64; display:block; margin-top:20px;">Retour à l'accueil</a>

    <?php elseif ($montant_total > 0): ?>
        <h2>Votre Panier</h2>
        <hr style="border:0; border-top:1px solid #333; margin:20px 0;">
        
        <?php foreach ($_SESSION['panier'] as $item): ?>
            <div style="display:flex; justify-content:space-between; margin-bottom:10px;">
                <span><?= $item['quantite'] ?>x <?= htmlspecialchars($item['nom']) ?></span>
                <span><?= $item['prix'] * $item['quantite'] ?>€</span>
            </div>
        <?php endforeach; ?>

        <h3 style="color:#bc9c64; margin-top:20px;">TOTAL : <?= $montant_total ?> €</h3>

        <form action="https://www.plateforme-smc.fr/cybank/index.php" method="POST">
            <input type="hidden" name="transaction" value="<?= $transaction ?>">
            <input type="hidden" name="montant" value="<?= $montant_cybank ?>">
            <input type="hidden" name="vendeur" value="<?= $vendeur ?>">
            <input type="hidden" name="retour" value="<?= $retour ?>">
            <input type="hidden" name="control" value="<?= $control ?>">
            <button type="submit" class="btn-pay">PAYER</button>
        </form>

    <?php else: ?>
        <p>Votre panier est vide.</p>
        <a href="carte.php" style="color:#bc9c64;">Retour à la carte</a>
    <?php endif; ?>
</div>

</body>
</html>