<?php
require_once '../includes/config.php';
require_once '../includes/fonctions.php';

// Sécurité de base
if (!estConnecte() || $_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../index.php');
    exit;
}

// Récupération des données
$idCmd         = nettoyer($_POST['cmd'] ?? '');
$noteProduits  = (int)($_POST['note_produits'] ?? 0);
$noteLivraison = (int)($_POST['note_livraison'] ?? 0);
$commentaire   = nettoyer($_POST['commentaire'] ?? '');

// 🛡️ SÉCURITÉ (Reprise de ton 1er script) : On vérifie que les notes sont bien entre 1 et 5
if (empty($idCmd) || $noteProduits < 1 || $noteProduits > 5 || $noteLivraison < 1 || $noteLivraison > 5) {
    header('Location: ../php/profil.php?erreur=note_invalide');
    exit;
}

$data = lireJSON(JSON_COMMANDES);
$trouve = false;

foreach ($data['commandes'] as &$cmd) {
    if ($cmd['id'] === $idCmd && $cmd['id_client'] === $_SESSION['user']['id']) {
        
        // On enregistre avec LES BONNES CLÉS pour que ton "oeil" fonctionne
        $cmd['note_client'] = [
            'produits'    => $noteProduits,
            'livraison'   => $noteLivraison,
            'commentaire' => $commentaire,
            'date_note'   => date('Y-m-d H:i:s')
        ];
        $trouve = true;
        break;
    }
}

if ($trouve) {
    // Si tu as une fonction ecrireJSON() au lieu de sauvegarderJSON(), mets ecrireJSON ici
    sauvegarderJSON(JSON_COMMANDES, $data);
    
    // On redirige vers le profil pour que le client voie directement l'oeil apparaitre !
    header('Location: ../php/profil.php');
} else {
    header('Location: ../php/profil.php?erreur=maj_impossible');
}
exit;