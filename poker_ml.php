<?php
// ================================================================
//  poker_ml.php
//  Script d’apprentissage automatique en PHP appliqué au poker.
//  Nécessite l’installation de la librairie PHP-ML : 
//  -> composer require php-ai/php-ml
// ================================================================

// Chargement automatique des classes PHP-ML
require 'vendor/autoload.php';

// Importation des classes nécessaires depuis PHP-ML
use Phpml\Classification\KNearestNeighbors;
use Phpml\ModelManager;
use Phpml\Metric\Accuracy;

// Définition des fichiers de stockage des données et du modèle
$dataFile = 'poker_data.json';     // Contient l’historique des parties
$modelFile = 'poker_model.model';  // Contient le modèle entraîné

// Chargement du jeu de données existant (ou initialisation vide)
$data = file_exists($dataFile) ? json_decode(file_get_contents($dataFile), true) : [];

// ================================================================
// --- SECTION 1 : ENREGISTREMENT DES DONNÉES + RÉENTRAÎNEMENT ---
// ================================================================
if (isset($_POST['save'])) {

    // --- Création d’une nouvelle entrée à partir du formulaire ---
    $entry = [
        'main' => floatval($_POST['main']),       // Force de la main (0 à 1)
        'mise' => floatval($_POST['mise']),       // Montant de la mise adverse
        'pos' => intval($_POST['pos']),           // Position du joueur à la table
        'joueurs' => intval($_POST['joueurs']),   // Nombre de joueurs en jeu
        'pot' => floatval($_POST['pot']),         // Montant total du pot
        'stack' => floatval($_POST['stack']),     // Votre tapis (stack)
        'tour' => $_POST['tour'],                 // Phase du jeu : préflop, flop, turn, river
        'decision' => $_POST['decision'],         // Décision prise (call, fold, raise)
        'resultat' => floatval($_POST['resultat'])// Résultat final (+ ou - gain)
    ];

    // Ajout de l’entrée au tableau des données
    $data[] = $entry;

    // Limitation du fichier à 500 entrées pour éviter une surcharge
    if (count($data) > 500) array_shift($data);

    // Sauvegarde du nouveau jeu de données dans poker_data.json
    file_put_contents($dataFile, json_encode($data));

    // ------------------------------------------------------------
    // --- Entraînement automatique du modèle après chaque ajout ---
    // ------------------------------------------------------------
    $accuracy = 0;

    // On ne réentraîne que si on possède un minimum de données
    if (count($data) > 20) {

        // Création des échantillons (variables d’entrée)
        $samples = array_map(fn($d) => [
            $d['main'], $d['mise'], $d['pos'], $d['joueurs'], $d['pot'], $d['stack'],
            // Conversion du tour de jeu en valeur numérique
            $d['tour']=='flop'?1:($d['tour']=='turn'?2:3)
        ], $data);

        // Création des étiquettes (résultats attendus : décisions)
        $labels = array_map(fn($d) => $d['decision'], $data);

        // Division du jeu de données : 80% entraînement / 20% test
        $split = intval(count($samples) * 0.8);
        $trainSamples = array_slice($samples, 0, $split);
        $trainLabels  = array_slice($labels, 0, $split);
        $testSamples  = array_slice($samples, $split);
        $testLabels   = array_slice($labels, $split);

        // Création et entraînement du modèle K-Nearest Neighbors
        $model = new KNearestNeighbors();
        $model->train($trainSamples, $trainLabels);

        // Prédictions sur les données de test
        $predicted = [];
        foreach ($testSamples as $s) $predicted[] = $model->predict($s);

        // Calcul de la précision du modèle
        $accuracy = count($predicted) > 0 ? round(Accuracy::score($testLabels, $predicted) * 100, 2) : 0;

        // Sauvegarde du modèle entraîné sur disque
        (new ModelManager())->saveToFile($model, $modelFile);
    }

    // Envoi de la précision au navigateur (affichée en JS)
    echo $accuracy;
    exit;
}

// ================================================================
// --- SECTION 2 : PRÉDICTION DE DÉCISION SELON LES DONNÉES ---
// ================================================================
if (isset($_POST['predict'])) {

    // Vérification du volume de données avant prédiction
    if (count($data) < 10) { 
        echo "Pas assez de données"; 
        exit; 
    }

    // Extraction des échantillons et étiquettes
    $samples = array_map(fn($d) => [
        $d['main'], $d['mise'], $d['pos'], $d['joueurs'], $d['pot'], $d['stack'],
        $d['tour']=='flop'?1:($d['tour']=='turn'?2:3)
    ], $data);
    $labels = array_map(fn($d) => $d['decision'], $data);

    // Entraînement du modèle sur toutes les données disponibles
    $model = new KNearestNeighbors();
    $model->train($samples, $labels);

    // Conversion des données utilisateur en entrée pour la prédiction
    $input = [
        floatval($_POST['main']),
        floatval($_POST['mise']),
        intval($_POST['pos']),
        intval($_POST['joueurs']),
        floatval($_POST['pot']),
        floatval($_POST['stack']),
        $_POST['tour']=='flop'?1:($_POST['tour']=='turn'?2:3)
    ];

    // Prédiction de la décision la plus probable
    $prediction = $model->predict($input);

    // Envoi du résultat au navigateur
    echo $prediction;
    exit;
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<title>AI Poker Decision</title>

<!-- Importation de jQuery et Chart.js -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<!-- Feuille de style (version sombre, moderne et compatible Safari) -->
<style>
/* === Structure générale === */
body {
    font-family: 'Segoe UI', Roboto, Arial, sans-serif;
    margin: 40px;
    background: #1e1e1e;
    color: #f1f1f1;
    line-height: 1.6;
}

/* === Titres === */
h1, h2 {
    text-align: center;
    margin-bottom: 25px;
}
h1 {
    color: #4caf50;
}
h2 {
    color: #9cdcfe;
}

/* === Formulaire principal === */
form {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 12px 20px;
    max-width: 650px;
    margin: 0 auto 30px auto;
    padding: 25px;
    background: #2a2a2a;
    border-radius: 12px;
    box-shadow: 0 4px 12px rgba(0,0,0,0.3);
}

/* === Champs de saisie généraux === */
input, select, button {
    padding: 10px;
    border-radius: 6px;
    border: 1px solid #444;
    background: #333;
    color: #f9f9f9;
    font-size: 0.95em;
    transition: all 0.2s ease-in-out;
}

/* === Effet focus === */
input:focus, select:focus {
    outline: none;
    border-color: #4caf50;
    background: #2f2f2f;
}

/* === Sélecteurs personnalisés === */
select {
    /* Retirer le style natif des navigateurs */
    -webkit-appearance: none;
    -moz-appearance: none;
    appearance: none;

    /* Style moderne */
    background-color: #333;
    border: 1px solid #555;
    border-radius: 6px;
    color: #fff;
    padding: 10px 35px 10px 12px;
    font-size: 0.95em;
    cursor: pointer;

    /* Ajouter une flèche personnalisée */
    background-image: linear-gradient(45deg, transparent 50%, #4caf50 50%),
                      linear-gradient(135deg, #4caf50 50%, transparent 50%);
    background-position: calc(100% - 18px) center, calc(100% - 13px) center;
    background-size: 5px 5px, 5px 5px;
    background-repeat: no-repeat;
}

/* Flèche changée au survol pour effet dynamique */
select:hover {
    border-color: #4caf50;
    background-image: linear-gradient(45deg, transparent 50%, #81c784 50%),
                      linear-gradient(135deg, #81c784 50%, transparent 50%);
}

/* === Boutons === */
button {
    background: #4caf50;
    color: #fff;
    font-weight: bold;
    border: none;
    cursor: pointer;
    flex: 1;
    transition: background 0.2s;
}
button:hover {
    background: #45a049;
}

/* === Bloc de boutons === */
form div[style*="grid-column"] {
    grid-column: 1 / 3;
    display: flex;
    justify-content: center;
    gap: 15px;
    margin-top: 10px;
}

/* === Zone de résultats === */
#result, #accuracy {
    text-align: center;
    margin-top: 15px;
}
#result {
    font-size: 1.4em;
    color: #ffb74d;
}
#accuracy {
    font-size: 1.2em;
    color: #00e676;
}

/* === Section statistiques === */
canvas {
    background: #fff;
    border-radius: 12px;
    padding: 15px;
    margin: 20px auto;
    display: block;
    max-width: 600px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.25);
}
</style>

</head>
<body>
<h1>Décision Poker (Machine Learning PHP)</h1>

<!-- ============================================================
     Formulaire principal permettant de saisir les données de jeu
     ============================================================ -->
<form id="pokerForm">
    <input type="number" name="main" placeholder="Force de la main (0-1)" step="0.01" required>
    <input type="number" name="mise" placeholder="Mise adverse" step="0.01" required>
    <input type="number" name="pos" placeholder="Position (1-9)" required>
    <input type="number" name="joueurs" placeholder="Nb joueurs" required>
    <input type="number" name="pot" placeholder="Pot actuel" step="0.01" required>
    <input type="number" name="stack" placeholder="Mon Stack" step="0.01" required>

    <!-- Sélecteur du tour de jeu -->
    <select name="tour">
        <option value="preflop">Pré-flop</option>
        <option value="flop">Flop</option>
        <option value="turn">Turn</option>
        <option value="river">River</option>
    </select>

    <!-- Sélecteur de la décision prise -->
    <select name="decision">
        <option value="call">Call</option>
        <option value="fold">Fold</option>
        <option value="raise">Raise</option>
    </select>

    <!-- Champ pour entrer le résultat final -->
    <input name="resultat" placeholder="Résultat (+/- gain)">

    <!-- Boutons d’action -->
    <div style="grid-column:1/3;display:flex;gap:10px;">
        <button type="button" id="predictBtn">Prédire</button>
        <button type="button" id="saveBtn">Enregistrer</button>
    </div>
</form>

<!-- Zones de retour d’information -->
<div id="result"></div>
<div id="accuracy"></div>

<!-- ============================================================
     Graphiques statistiques : fréquence et rentabilité
     ============================================================ -->
<h2>Statistiques</h2>
<canvas id="chart1" width="400" height="200"></canvas>
<canvas id="chart2" width="400" height="200"></canvas>

<!-- ============================================================
     Script JavaScript : communication AJAX + graphiques
     ============================================================ -->
<script>
// Fonction utilitaire : récupère toutes les données du formulaire
function getFormData() {
    return Object.fromEntries(new FormData($("#pokerForm")[0]).entries());
}

// --- Bouton "Prédire" : envoi des données pour obtenir une décision AI ---
$("#predictBtn").on("click",()=>{
    $.post("poker_ml.php",{predict:1,...getFormData()},res=>{
        $("#result").text("Décision suggérée : "+res);
        $("input[name='decision']").val(res); // Remplir automatiquement la décision
    });
});

// --- Bouton "Enregistrer" : stocke les données + réentraîne le modèle ---
$("#saveBtn").on("click",()=>{
    $.post("poker_ml.php",{save:1,...getFormData()},acc=>{
        $("#accuracy").text("Précision actuelle du modèle : "+acc+"%");
        $("#result").text("Données enregistrées ✅");
        $("#pokerForm")[0].reset(); // Réinitialisation du formulaire
        updateCharts();              // Mise à jour des statistiques
    });
});

// --- Fonction de mise à jour des graphiques ---
function updateCharts(){
    fetch('poker_data.json').then(r=>r.json()).then(d=>{
        if(!d || !d.length) return;

        let actions={}, gains={};
        d.forEach(x=>{
            actions[x.decision]=(actions[x.decision]||0)+1;     // Comptage des décisions
            gains[x.decision]=(gains[x.decision]||0)+x.resultat; // Somme des résultats
        });

        // Premier graphique : fréquence des décisions
        new Chart(chart1,{
            type:'bar',
            data:{
                labels:Object.keys(actions),
                datasets:[{label:'Fréquence décisions',data:Object.values(actions)}]
            }
        });

        // Deuxième graphique : rentabilité totale par type de décision
        new Chart(chart2,{
            type:'bar',
            data:{
                labels:Object.keys(gains),
                datasets:[{label:'Rentabilité totale',data:Object.values(gains)}]
            }
        });
    });
}

// Chargement initial des graphiques au démarrage
updateCharts();
</script>
</body>
</html>
