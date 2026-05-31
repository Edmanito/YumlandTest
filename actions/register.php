<?php
// =========================================
// KAISEKI SHUNEI — ACTIONS/REGISTER.PHP
// =========================================

require_once '../includes/config.php';
require_once '../includes/fonctions.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../index.php');
    exit;
}

$prenom     = nettoyer($_POST['prenom']     ?? '');
$nom        = nettoyer($_POST['nom']        ?? '');
$login      = nettoyer($_POST['login']      ?? '');
$telephone  = nettoyer($_POST['telephone']  ?? '');
$etage      = nettoyer($_POST['etage']      ?? '');
$interphone = nettoyer($_POST['interphone'] ?? '');
$mdp        = $_POST['mdp'] ?? '';

// --- CORRECTION : Récupération et assemblage des morceaux de l'adresse ---
$adresse_rue   = nettoyer($_POST['adresse_rue']   ?? '');
$adresse_cp    = nettoyer($_POST['adresse_cp']    ?? '');
$adresse_ville = nettoyer($_POST['adresse_ville'] ?? '');

$adresse = '';
if (!empty($adresse_rue) && !empty($adresse_cp) && !empty($adresse_ville)) {
    $adresse = $adresse_rue . ', ' . $adresse_cp . ' ' . $adresse_ville;
}
// -------------------------------------------------------------------------

if (empty($prenom) || empty($nom) || empty($login) || empty($mdp) || empty($adresse)) {
    header('Location: ../php/inscription.php?erreur=champs_vides');
    exit;
}

if (!filter_var($login, FILTER_VALIDATE_EMAIL)) {
    header('Location: ../php/inscription.php?erreur=email_invalide');
    exit;
}

if (strlen($mdp) < 8) {
    header('Location: ../php/inscription.php?erreur=mdp_court');
    exit;
}

if (!preg_match('/^[0-9]{10}$/', $telephone) && !empty($telephone)) {
    header('Location: ../php/inscription.php?erreur=tel_invalide');
    exit;
}

if (loginExiste($login)) {
    header('Location: ../php/inscription.php?erreur=email_existe');
    exit;
}

$nouvelUser = [
    "id"            => genererID('U'),
    "login"         => $login,
    "mot_de_passe"  => hasherMotDePasse($mdp),
    "role"          => "client",
    "statut"        => "actif",
    "premium"       => false,
    "remise"        => 0,
    "infos"         => [
        "nom"        => $nom,
        "prenom"     => $prenom,
        "telephone"  => $telephone,
        "adresse"    => $adresse,
        "etage"      => $etage,
        "interphone" => $interphone
    ],
    "fidelite"      => [
        "points" => 0,
        "badge"  => "BRONZE"
    ],
    "dates"         => [
        "inscription"        => date('Y-m-d'),
        "derniere_connexion"  => date('Y-m-d')
    ]
];

if (ajouterUtilisateur($nouvelUser)) {
    unset($nouvelUser['mot_de_passe']);
    $_SESSION['user'] = $nouvelUser;
    header('Location: ../php/profil.php?success=inscription');
} else {
    header('Location: ../php/inscription.php?erreur=erreur_serveur');
}
exit;