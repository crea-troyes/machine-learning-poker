# ♠️ AI Poker Decision (PHP + Machine Learning)

AI Poker Decision est une démonstration d’apprentissage automatique appliquée au poker, écrite en PHP.
Le projet illustre comment collecter des données de mains, réentraîner un modèle K-Nearest Neighbors (KNN) et fournir une prédiction de décision (call / fold / raise) via une interface web simple.

## 🚀 Fonctions principales

- Enregistrement local des décisions et des paramètres de la main.
- Réentraînement automatique du modèle après ajout de nouvelles données.
- Prédiction instantanée de la décision la plus probable.
- Calcul et affichage de la précision du modèle (accuracy).
- Visualisation : fréquence des décisions et rentabilité par décision (Chart.js).
- Interface stylisée (thème sombre) avec compatibilité Safari pour les `<select>`.

## 📁 Structure du projet

```
ai-poker-decision/
├── poker_ml.php         # Script principal (backend + frontend)
├── poker_data.json      # Stockage local des données (généré automatiquement)
├── poker_model.model    # Modèle entraîné (généré automatiquement)
└── vendor/              # Dépendances Composer (php-ai/php-ml)
```

## ⚙️ Prérequis

- PHP 8+ (ou 7.4+ selon votre environnement)
- Composer
- Navigateur moderne (Chrome, Firefox, Safari)
- (Optionnel) serveur local PHP pour tester : `php -S`

## 🔧 Installation rapide

1.  Cloner le dépôt :
    ```bash
    git clone https://github.com/votre-utilisateur/ai-poker-decision.git
    cd ai-poker-decision
    ```
2.  Installer la dépendance ML (php-ai/php-ml) :
    ```bash
    composer require php-ai/php-ml
    ```
3.  Lancer un serveur PHP local :
    ```bash
    php -S localhost:8000
    ```
4.  Ouvrez dans votre navigateur :
    `http://localhost:8000/poker_ml.php`

## 🧭 Utilisation

Remplissez le formulaire avec :
- `main` : force de la main (ex. 0.00 → 1.00)
- `mise` : mise adverse (montant)
- `pos` : position à la table (1–9)
- `joueurs` : nombre de joueurs encore en course
- `pot` : pot actuel
- `stack` : votre tapis
- `tour` : préflop / flop / turn / river
- `decision` : call / fold / raise
- `resultat` : gain ou perte du coup (ex. 12.5 ou -5)

Cliquez **Prédire** pour obtenir la décision suggérée par le modèle (si suffisamment de données).
Cliquez **Enregistrer** pour stocker la donnée et lancer un réentraînement (si ≥ 20 entrées).

## 🧠 Détails Machine Learning

- **Algorithme** : K-Nearest Neighbors (KNN) via `php-ai/php-ml`.
- **Features (entrée)** :
  - `main` (float)
  - `mise` (float)
  - `pos` (int)
  - `joueurs` (int)
  - `pot` (float)
  - `stack` (float)
  - `tour` (encodé : flop → 1, turn → 2, river → 3)
- **Label** : `decision` (call / fold / raise)
- **Split** : 80% entraînement / 20% test
- **Métrique** : Accuracy (pourcentage)
- **Limitation** : fichier data limité à 500 entrées (les plus anciennes sont supprimées)

## 📄 Format du fichier poker_data.json

Chaque entrée est un objet JSON avec la structure suivante :

```json
{
  "main": 0.78,
  "mise": 5.0,
  "pos": 3,
  "joueurs": 4,
  "pot": 20.0,
  "stack": 150.0,
  "tour": "flop",
  "decision": "call",
  "resultat": 10.0
}
```

## 📊 Visualisations

- `chart1` : histogramme de la fréquence des décisions.
- `chart2` : histogramme de la rentabilité totale par décision (somme des `resultat` par décision).

Les graphiques sont créés avec Chart.js côté client (JavaScript).

## 🔒 Sécurité & bonnes pratiques

- Les données sont locales (JSON) : attention aux partages non souhaités.
- Limitez l’accès au fichier si vous hébergez en production.
- Pour un usage plus sérieux, envisagez :
  - une base de données (MySQL / SQLite),
  - authentification / profils utilisateur,
  - entraînement sur serveur hors requête utilisateur (batch).

## ✅ Tests et validation

- Vérifiez que Composer installe correctement `php-ai/php-ml`.
- Assurez-vous que le fichier `poker_data.json` est accessible en lecture/écriture par PHP.
- Si le modèle ne prédit pas, vérifiez le nombre d’entrées (≥ 10 pour prédiction, ≥ 20 pour évaluer l'accuracy).

## ♻️ Améliorations proposées

- Ajouter un système d’utilisateur pour modèles personnalisés.
- Déplacer l’entraînement en tâche planifiée (cron) plutôt que synchronisée.
- Tester d’autres algorithmes (RandomForest, SVM) et comparer.
- Normaliser / standardiser les features (scaling).
- Ajouter des tests unitaires et des fixtures de données.

## 🤝 Contribution

Vous pouvez contribuer via pull requests ou issues. Merci d’inclure :
- Une description claire du changement.
- Les étapes pour reproduire (si bugfix).
- Un cas de test (si fonctionnalité).

## 📞 Auteur & contact

- **Alban — Créa-Troyes**
- **Site** : https://crea-troyes.fr
- **Projet annexe** : https://code.crea-troyes.fr
- **Entreprise** : https://affnox.fr

## 🪪 Licence

Ce projet est distribué sous Licence MIT — voir le fichier `LICENSE` pour les détails.

## 📸 Capture d’écran

Ajoutez ici une capture d’écran de l’interface une fois le projet lancé :

!AI Poker Decision - Interface