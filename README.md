# DRL CRÉATIONS — L'ÉLÉGANCE PREND FORME
**Plateforme Web Haute Couture, Confection Sur-Mesure & E-Commerce en PHP 8.2+ Natif & MySQL 8**

---

## 1. Présentation du Projet
**DRL CRÉATIONS** est une plateforme web complète développée intégralement en **PHP 8.2+ natif orienté objet (POO)** et **MySQL 8**, sans aucun framework backend ni dépendance à un serveur Node.js en production.

### Fonctionnalités principales incluses :
- **Identité Visuelle Haute Couture** : Palette officielle Bleu Marine (`#142B49`), Or Champagne (`#B89A62`), Or Clair (`#D5BD91`), Blanc Ivoire (`#F8F6F0`), Beige Textile (`#EAE4D9`) et Gris (`#777777`), typographies *Playfair Display*, *Cormorant Garamond* et *Poppins*.
- **Navigation Flottante Moderne** : Barre de navigation flottante composée d'icônes modernes (sans menu hamburger classique), salutation dynamique personnalisée (`« Bonjour, [Prénom] »` ou `« Bienvenue dans l'univers DRL CRÉATIONS. »`), sélecteur de thème Clair/Sombre persistant (`localStorage`) et sélecteur multilingue flottant (`FR` / `EN`).
- **Espace « Nos Créations »** : Galerie des 7 catégories de modèles réalisables par l'atelier avec galerie photographique, référence unique, délai estimé, bouton **« Demander ce modèle »** (formulaire relié à MySQL + message WhatsApp pré-rempli).
- **Espace « Boutique » Dynamique** : Gestion des 3 modes de tarification (**1. Prix fixe**, **2. Prix négociable**, **3. Prix sur demande**) et filtrage automatique des articles vendus, archivés ou masqués.
- **Authentification Client & OAuth 2.0** : Inscription avec vérification de format d'e-mail, unicité MySQL, envoi de lien de confirmation, hachage `password_hash()` (Bcrypt coût 12), protection anti-force brute, et connecteurs OAuth 2.0 / OpenID Connect pour **Google** et **Apple**.
- **DRL Assistant (IA)** : Assistant intelligent flottant connecté en temps réel à la base de données MySQL via `Fetch API` (`api/assistant.php`) capable de présenter les créations, rechercher des articles, communiquer les tarifs officiels, expliquer les personnalisations et orienter vers WhatsApp sans jamais inventer de données.
- **Tableau de Bord Administrateur (`admin/`)** : Gestion complète des articles, modèles réalisables, catégories, clients, demandes sur-mesure, paramètres WhatsApp et base de connaissances IA.

---

## 2. Prérequis Serveur
- **Serveur Web** : Apache 2.4+ (avec `mod_rewrite` et `mod_headers` activés) — compatible **XAMPP**, **WAMP**, **Laragon**, **MAMP** ou hébergement mutualisé cPanel/Plesk.
- **PHP** : Version **8.2** ou supérieure avec les extensions `pdo_mysql`, `mbstring`, `json`, `fileinfo`, `openssl`.
- **Base de données** : **MySQL 8.0+** (ou MariaDB 10.6+).

---

## 3. Installation Pas-à-Pas (XAMPP / WAMP / Laragon / Hébergement Web)

### Étape 1 : Déploiement des fichiers
1. Extrayez le dossier `DRL-CREATIONS` dans le répertoire racine de votre serveur web :
   - **XAMPP** : `C:/xampp/htdocs/DRL-CREATIONS`
   - **WAMP** : `C:/wamp64/www/DRL-CREATIONS`
   - **Laragon** : `C:/laragon/www/DRL-CREATIONS`
   - **Hébergement mutualisé** : contenu placé dans `public_html/`
2. Assurez-vous que le dossier `uploads/` (`uploads/products`, `uploads/creations`, `uploads/profiles`) dispose des permissions d'écriture (`chmod 755`).

### Étape 2 : Importation de la base de données MySQL
1. Ouvrez **phpMyAdmin** (ex: `http://localhost/phpmyadmin`) ou utilisez la ligne de commande MySQL.
2. Importez le fichier SQL complet fourni :
   ```bash
   mysql -u root -p < database/drl_creations.sql
   ```
   Ce script crée automatiquement la base `drl_creations`, les tables relationnelles, les **7 catégories**, les modèles et articles de démonstration. Aucun compte administrateur ni mot de passe par défaut n'est créé.

### Étape 3 : Configuration du fichier `.env`
1. Dupliquez le fichier `.env.example` à la racine du projet et renommez la copie en `.env` :
   ```bash
   cp .env.example .env
   ```
2. Ajustez les paramètres MySQL et l'URL de votre site :
   ```env
   APP_ENV=production
   APP_DEBUG=false
   APP_URL=http://localhost/DRL-CREATIONS

   DB_HOST=127.0.0.1
   DB_PORT=3306
   DB_NAME=drl_creations
   DB_USER=root
   DB_PASS=
   ```

### Étape 4 : Initialisation de l'administrateur
- Créez un compte depuis `auth/register.php`, puis confirmez l'adresse e-mail.
- Le premier nouveau compte local confirmé (ou nouvellement créé via OAuth) reçoit les droits administrateur. Cette attribution est protégée contre les vérifications simultanées et enregistrée comme définitive, même si ce compte est supprimé plus tard.
- Les comptes suivants restent des comptes client. Ne partagez pas le compte administrateur.
- **URL d'administration** : `http://localhost/DRL-CREATIONS/admin/dashboard.php`

Sur une base déjà installée, le compte administrateur existant n'est pas rétrogradé automatiquement. Lors de la prochaine nouvelle inscription confirmée, son existence verrouille l'initialisation sans attribuer ce rôle à un autre compte. Si aucun administrateur n'existe, cette nouvelle inscription peut le devenir.

---

## 4. Configuration de Google OAuth 2.0 & Apple Sign-In
1. *(Optionnel)* Si vous utilisez Composer pour les librairies OAuth tierces :
   ```bash
   composer install --no-dev --optimize-autoloader
   ```
2. **Google OAuth 2.0** :
   - Rendez-vous sur [Google Cloud Console](https://console.cloud.google.com/apis/credentials).
   - Créez un **ID client OAuth 2.0** (Application Web).
   - Ajoutez l'URI de redirection autorisée :
     `http://localhost/DRL-CREATIONS/auth/oauth/callback.php?provider=google`
   - Renseignez `GOOGLE_CLIENT_ID` et `GOOGLE_CLIENT_SECRET` dans `.env`.
3. **Sign in with Apple** :
   - Dans votre compte Apple Developer (*Certificates, Identifiers & Profiles*), créez un **Services ID** et activez *Sign in with Apple*.
   - Configurez l'URL de retour :
     `https://votre-domaine.com/auth/oauth/callback.php?provider=apple`
   - Renseignez `APPLE_CLIENT_ID`, `APPLE_TEAM_ID`, `APPLE_KEY_ID` dans `.env`.

---

## 5. Configuration de WhatsApp & DRL Assistant (IA)
- **Numéro WhatsApp** : Connectez-vous au tableau de bord (`admin/parametres.php`) et saisissez votre numéro WhatsApp au format international sans le signe `+` (ex: `2290197000000`). Tous les boutons WhatsApp du site et la classe `classes/WhatsApp.php` utiliseront immédiatement ce numéro.
- **DRL Assistant** : Depuis `admin/assistant-ia.php`, vous pouvez activer/désactiver l'assistant, personnaliser son message d'accueil en Français et en Anglais, et enrichir les Questions Fréquentes (`faq`) utilisées pour répondre aux clients avec les données exactes de MySQL.
"# drl-creations" 
