<?php
require_once '../includes/config.php';
require_once '../includes/fonctions.php';

header('Content-Type: application/json');

$categorie = $_GET['categorie'] ?? 'tous';
$regime = $_GET['regime'] ?? 'tous';
$saveur = $_GET['saveur'] ?? 'tous';
$recherche = strtolower($_GET['recherche'] ?? '');

$dataPlats = lireJSON(JSON_PLATS);
$plats = $dataPlats['plats'] ?? [];

$resultats = array_filter($plats, function($p) use ($categorie, $regime, $saveur, $recherche) {
    $match = true;

    // Filtre Catégorie
    if ($categorie !== 'tous' && $p['categorie'] !== $categorie) $match = false;

    // Filtre Régime (Végétarien, Vegan, etc.)
    if ($regime !== 'tous' && (!isset($p['regimes']) || !in_array($regime, $p['regimes']))) $match = false;

    // Filtre Saveur (Épicé, Salé, etc.)
    if ($saveur !== 'tous' && (!isset($p['saveurs']) || !in_array($saveur, $p['saveurs']))) $match = false;

    // Recherche textuelle
    if (!empty($recherche)) {
        if (!str_contains(strtolower($p['nom']), $recherche) && 
            !str_contains(strtolower($p['description']), $recherche)) {
            $match = false;
        }
    }

    return $match;
});

// On réindexe le tableau et on l'envoie
echo json_encode(array_values($resultats));