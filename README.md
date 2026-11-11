# DRL CRÉATIONS — L'ÉLÉGANCE PREND FORME
**Plateforme Web Haute Couture, Confection Sur-Mesure & E-Commerce en PHP 8.2+ Natif, Supabase Auth et PostgreSQL**

---

## 1. Présentation du Projet
**DRL CRÉATIONS** est une plateforme web complète développée en **PHP 8.2+ natif orienté objet (POO)**, avec **Supabase Auth**, **PostgreSQL** et **Supabase Storage**, sans framework backend ni dépendance à Node.js en production.

### Fonctionnalités principales incluses :
- **Identité Visuelle Haute Couture** : Palette officielle Bleu Marine (`#142B49`), Or Champagne (`#B89A62`), Or Clair (`#D5BD91`), Blanc Ivoire (`#F8F6F0`), Beige Textile (`#EAE4D9`) et Gris (`#777777`), typographies *Playfair Display*, *Cormorant Garamond* et *Poppins*.
- **Navigation Flottante Moderne** : Barre de navigation flottante composée d'icônes modernes (sans menu hamburger classique), salutation dynamique personnalisée (`« Bonjour, [Prénom] »` ou `« Bienvenue dans l'univers DRL CRÉATIONS. »`), sélecteur de thème Clair/Sombre persistant (`localStorage`) et sélecteur multilingue flottant (`FR` / `EN`).
- **Espace « Nos Créations »** : Galerie des 7 catégories de modèles réalisables par l'atelier avec galerie photographique, référence unique, délai estimé, bouton **« Demander ce modèle »** (formulaire relié à PostgreSQL + message WhatsApp pré-rempli).
- **Espace « Boutique » Dynamique** : Gestion des 3 modes de tarification (**1. Prix fixe**, **2. Prix négociable**, **3. Prix sur demande**) et filtrage automatique des articles vendus, archivés ou masqués.
- **Authentification Client & OAuth 2.0** : Supabase Auth gère l'inscription, la confirmation par e-mail, la récupération du mot de passe et les connexions **Google**/**Apple** en flux PKCE.
- **DRL Assistant (IA)** : Assistant intelligent flottant connecté en temps réel à PostgreSQL via `Fetch API` (`api/assistant.php`) capable de présenter les créations, rechercher des articles, communiquer les tarifs officiels, expliquer les personnalisations et orienter vers WhatsApp sans jamais inventer de données.
- **Tableau de Bord Administrateur (`admin/`)** : Gestion complète des articles, modèles réalisables, catégories, clients, demandes sur-mesure, paramètres WhatsApp et base de connaissances IA.

---

## 2. Prérequis Serveur
- **Serveur Web** : Apache 2.4+ (avec `mod_rewrite` et `mod_headers` activés) — compatible **XAMPP**, **WAMP**, **Laragon**, **MAMP** ou hébergement mutualisé cPanel/Plesk.
- **PHP** : Version **8.2** ou supérieure avec les extensions `pdo_pgsql`, `curl`, `mbstring`, `json`, `fileinfo`, `openssl`.
- **Base de données** : projet Supabase avec PostgreSQL.

---

## 3. Installation Pas-à-Pas (XAMPP / WAMP / Laragon / Hébergement Web)

### Étape 1 : Déploiement des fichiers
1. Extrayez le dossier `DRL-CREATIONS` dans le répertoire racine de votre serveur web :
   - **XAMPP** : `C:/xampp/htdocs/DRL-CREATIONS`
   - **WAMP** : `C:/wamp64/www/DRL-CREATIONS`
   - **Laragon** : `C:/laragon/www/DRL-CREATIONS`
   - **Hébergement mutualisé** : contenu placé dans `public_html/`
2. Assurez-vous que le dossier `uploads/` (`uploads/products`, `uploads/creations`, `uploads/profiles`) dispose des permissions d'écriture (`chmod 755`).

### Étape 2 : Création du schéma PostgreSQL
1. Dans le tableau de bord Supabase, ouvrez **SQL Editor**.
2. Exécutez le contenu de `database/supabase_schema.sql` une fois. Le script crée les tables, les fonctions de profil/admin et le bucket public `drl-media`.

### Étape 3 : Configuration du fichier `.env`
1. Dupliquez le fichier `.env.example` à la racine du projet et renommez la copie en `.env` :
   ```bash
   cp .env.example .env
   ```
2. Indiquez l'URL du site, les paramètres PostgreSQL du bouton **Connect** du projet Supabase, l'URL du projet et sa clé `anon`/publishable :
   ```env
   APP_ENV=production
   APP_DEBUG=false
   APP_URL=https://votre-domaine.com/DRL-CREATIONS

   DB_DRIVER=pgsql
   DB_HOST=...
   DB_PORT=5432
   DB_NAME=postgres
   DB_USER=postgres...
   DB_PASSWORD=...
   DB_SSLMODE=require
   SUPABASE_URL=https://<project-ref>.supabase.co
   SUPABASE_ANON_KEY=...
   ```
   N'envoyez jamais de clé `service_role` ou de mot de passe dans le navigateur ou dans un dépôt public.

### Étape 4 : Connexion du site à Supabase
- Définissez **Site URL** sur `APP_URL` dans Supabase, puis autorisez les URL de confirmation, de récupération et le callback OAuth utilisés par le site.
- Dans les paramètres **Authentication → URL Configuration**, ajoutez `APP_URL/auth/verify-email.php`, `APP_URL/auth/reset-password.php` et `APP_URL/auth/oauth/supabase-callback.php` aux URL autorisées.
- Dans **Authentication → Providers**, activez l'e-mail et les fournisseurs souhaités. Configurez leurs identifiants OAuth dans Supabase et autorisez le callback affiché par le tableau de bord Supabase (généralement `https://<project-ref>.supabase.co/auth/v1/callback`).
- Pour les liens d'e-mail, configurez le modèle de confirmation avec `{{ .SiteURL }}/auth/verify-email.php?token_hash={{ .TokenHash }}&type=signup`; le modèle de récupération utilise le même lien avec `type=recovery`.
- Sur XAMPP, activez `extension=pdo_pgsql` dans `C:\xampp\php\php.ini`, redémarrez Apache, puis vérifiez que `pdo_pgsql` est chargé.
- Les fichiers envoyés après configuration vont dans Supabase Storage. Les images déjà présentes dans `uploads/` restent locales.

### Étape 5 : Migration des données MySQL existantes (facultative)
- Faites une sauvegarde de la base MySQL et du dossier `uploads/` avant la migration.
- Dans `.env`, renseignez `MYSQL_SOURCE_HOST`, `MYSQL_SOURCE_PORT`, `MYSQL_SOURCE_DB`, `MYSQL_SOURCE_USER` et `MYSQL_SOURCE_PASSWORD`; laissez `DB_DRIVER=pgsql` avec les identifiants Supabase dans `DB_*`.
- Lancez le script depuis la racine du site : `php database/migrate_mysql_to_supabase.php`.
- Exécutez cette migration avant de créer des données Supabase; le script s'arrête si les tables métier de destination ne sont pas vides.
- Le script copie les tables et relations en préservant les identifiants, sans transférer les hashes de mots de passe ni les jetons d'authentification PHP. Les anciens utilisateurs devront créer/récupérer leur accès Supabase. Il ne copie pas les fichiers du dossier `uploads/`.

### Étape 6 : Initialisation de l'administrateur
- Créez un compte depuis `auth/register.php`, puis confirmez l'adresse e-mail.
- Le premier compte Supabase vérifié reçoit les droits administrateur, sauf si un administrateur existe déjà (par exemple après migration). Cette attribution est enregistrée comme définitive, même si le compte est supprimé plus tard.
- Les comptes suivants restent des comptes client. Ne partagez pas le compte administrateur.
- **URL d'administration** : `http://localhost/DRL-CREATIONS/admin/dashboard.php`

---

## 6. Configuration de WhatsApp & DRL Assistant (IA)
- **Numéro WhatsApp** : Connectez-vous au tableau de bord (`admin/parametres.php`) et saisissez votre numéro WhatsApp au format international sans le signe `+` (ex: `2290197000000`). Tous les boutons WhatsApp du site et la classe `classes/WhatsApp.php` utiliseront immédiatement ce numéro.
- **DRL Assistant** : Depuis `admin/assistant-ia.php`, vous pouvez activer/désactiver l'assistant, personnaliser son message d'accueil en Français et en Anglais, et enrichir les Questions Fréquentes (`faq`) utilisées pour répondre aux clients avec les données exactes de PostgreSQL.
"# drl-creations" 
