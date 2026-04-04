<?php
require_once '../includes/config.php';
require_once '../includes/fonctions.php';

if (!isset($_SESSION['panier'])) {
    $_SESSION['panier'] = [];
}

if (isset($_GET['id'])) {
    $id_produit = $_GET['id'];
    $dataPlats = lireJSON(JSON_PLATS);
    $dataMenus = lireJSON(JSON_MENUS);
    
    $produit_trouve = false;
    
    // Recherche dans les menus
    if (!empty($dataMenus['menus'])) {
        foreach ($dataMenus['menus'] as $menu) {
            if ($menu['id'] == $id_produit) {
                $produit_trouve = ['id' => $menu['id'], 'nom' => $menu['nom'], 'prix' => $menu['prix_total'], 'quantite' => 1];
                break;
            }
        }
    }
    
    // Recherche dans les plats
    if (!$produit_trouve && !empty($dataPlats['plats'])) {
        foreach ($dataPlats['plats'] as $plat) {
            if ($plat['id'] == $id_produit) {
                $produit_trouve = ['id' => $plat['id'], 'nom' => $plat['nom'], 'prix' => $plat['prix'], 'quantite' => 1];
                break;
            }
        }
    }
    
    if ($produit_trouve) {
        $existe_deja = false;
        foreach ($_SESSION['panier'] as &$item) {
            if ($item['id'] == $id_produit) {
                $item['quantite'] += 1;
                $existe_deja = true;
                break;
            }
        }
        if (!$existe_deja) {
            $_SESSION['panier'][] = $produit_trouve;
        }
    }
}

// 🪄 LA MAGIE DU REDIRECT INTELLIGENT
// Si on vient du panier, on retourne au panier. Sinon, on va sur la carte.
if (isset($_SERVER['HTTP_REFERER']) && strpos($_SERVER['HTTP_REFERER'], 'panier.php') !== false) {
    header('Location: ../php/panier.php');
} else {
    header('Location: ../php/carte.php');
}
exit();