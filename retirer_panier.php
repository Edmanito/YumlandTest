<?php
require_once '../includes/config.php';
require_once '../includes/fonctions.php';

if (isset($_GET['id']) && isset($_SESSION['panier'])) {
    $id_produit = $_GET['id'];

    foreach ($_SESSION['panier'] as $key => &$item) {
        if ($item['id'] == $id_produit) {
            if ($item['quantite'] > 1) {
                $item['quantite'] -= 1;
            } else {
                unset($_SESSION['panier'][$key]);
            }
            break;
        }
    }
    // Réindexer le tableau pour éviter des trous dans les clés
    $_SESSION['panier'] = array_values($_SESSION['panier']);
}

header('Location: ../php/panier.php');
exit();