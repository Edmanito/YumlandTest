<?php
require_once '../includes/config.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// On indique qu'on va renvoyer des données invisibles (JSON)
header('Content-Type: application/json');

$id = $_GET['id'] ?? null;

if ($id) {
    if (!isset($_SESSION['panier'])) { $_SESSION['panier'] = []; }

    $cle_ligne = $id . "_default";

    if (isset($_SESSION['panier'][$cle_ligne])) {
        $_SESSION['panier'][$cle_ligne]['qte']++;
    } else {
        $_SESSION['panier'][$cle_ligne] = [
            'id' => $id,
            'qte' => 1,
            'retraits' => [] 
        ];
    }

    // On calcule combien d'articles on a au total maintenant
    $panierCount = 0;
    foreach ($_SESSION['panier'] as $item) {
        $panierCount += $item['qte'];
    }

    // On renvoie un message de succès avec le nouveau total
    echo json_encode(['success' => true, 'total_items' => $panierCount]);
    exit;
}

echo json_encode(['success' => false, 'message' => 'ID manquant']);
exit;