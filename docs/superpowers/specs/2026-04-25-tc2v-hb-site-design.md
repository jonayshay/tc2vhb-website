# Spécification technique — Site web TC2V Handball

**Club :** TC2V Handball — Triel, Chanteloup, Vernouillet, Verneuil (Yvelines, 78)
**Date :** 25 avril 2026
**Statut :** Spécification initiale — à compléter par itération

---

## À qui s'adresse ce document ?

Ce document décrit comment le site du TC2V Handball va être construit : les technologies choisies, les fonctionnalités prévues, et comment les différentes parties s'articulent. Il est destiné à des collègues intéressés par le projet mais pas nécessairement développeurs — chaque concept technique est accompagné d'une explication simple.

---

## 1. Objectifs du site

Le site répond à deux publics distincts :

- **Les membres et parents actuels** : retrouver les informations pratiques (planning, lieux, résultats)
- **Les personnes souhaitant rejoindre le club** : découvrir le club, les équipes, s'inscrire

Le site doit pouvoir être **géré au quotidien par des responsables du club sans compétences techniques**, via un espace d'administration clair et simple. Les tâches plus complexes (configuration, intégrations) seront assurées par un technicien. Le site doit être consultable aisément sur smartphone — en particulier les plannings et les lieux d'entraînement. La partie back-office est réservée à un usage desktop.

---

## 2. Stack technique choisie

> **C'est quoi une "stack technique" ?** C'est l'ensemble des outils et langages utilisés pour construire le site. Chaque outil a un rôle précis.

| Composant                                    | Outil choisi           | Rôle                                                        |
| -------------------------------------------- | ---------------------- | ----------------------------------------------------------- |
| **Frontend** (ce que l'utilisateur voit)     | Vue.js + Inertia.js    | Interface moderne, rendue côté serveur par Laravel          |
| **Backend** (le cerveau côté serveur)        | Laravel (PHP)          | Gère les données, la logique, les emails, le routing        |
| **Back-office** (interface d'administration) | Filament               | Interface admin pour les gestionnaires du club              |
| **Base de données**                          | MySQL                  | Stockage de toutes les données du club                      |
| **Hébergement**                              | OVH WebCloud Starter   | Serveurs français, conformes RGPD                           |
| **Paiements**                                | HelloAsso              | Plateforme française pour les associations, sans commission |
| **Résultats / Calendrier**                   | Scorenco (API)         | Service spécialisé handball, synchronisé automatiquement    |

### Pourquoi Vue.js + Inertia.js + Laravel ?

> **C'est quoi Inertia.js ?** C'est un "pont" entre Laravel et Vue. Laravel gère le routing et prépare les données côté serveur ; Vue gère l'affichage dans le navigateur. Le résultat : une interface fluide comme une application mobile, mais dont chaque page est rendue côté serveur — ce qui règle le problème de référencement sans configuration supplémentaire.

- **Une seule application** : le site public et le back-office partagent le même projet Laravel. Pas d'API REST à maintenir, pas de double déploiement.
- **SEO natif** : Laravel envoie du HTML complet au navigateur et aux moteurs de recherche — aucun compromis de référencement, aucun besoin de SSG ou de prebuild.
- **Pas de Node.js en production** : Node.js est utilisé uniquement au moment de la compilation des assets (CSS/JS via Vite). Sur OVH, seul PHP tourne.
- **Authentification simplifiée** : Inertia utilise les sessions Laravel standard — pas de tokens JWT, pas de gestion de refresh côté client.
- **Filament** est un back-office "clé en main" qui s'intègre à Laravel : les administrateurs du club ont une interface soignée sans qu'on ait besoin de la coder from scratch.

---

## 3. Architecture générale

```
┌──────────────────────────────────────────────────────────┐
│                    Hébergement OVH                       │
│                                                          │
│  ┌───────────────────────────────┐  ┌─────────────────┐ │
│  │  tc2v-handball.fr                  │  │ back.tc2v-handball.fr │ │
│  │  Laravel + Inertia + Vue      │  │ Filament        │ │
│  │  (site public + routing +     │  │ back-office     │ │
│  │   rendu HTML côté serveur)    │  │ (desktop only)  │ │
│  └───────────────┬───────────────┘  └────────┬────────┘ │
│                  │                           │          │
│         ┌────────▼───────────────────────────▼──────┐   │
│         │               Base MySQL                  │   │
│         └────────────────────────────────────────────┘  │
└──────────────────────────────────────────────────────────┘
          │                        │
          ▼                        ▼
    Scorenco API              HelloAsso
    (résultats, matchs)       (inscriptions, boutique)
```

> **Comment ça marche ?** Quand un visiteur arrive sur une page, Laravel la construit côté serveur (HTML complet) et l'envoie au navigateur. Vue.js prend ensuite le relais pour les interactions — navigation fluide sans rechargement de page. Les moteurs de recherche reçoivent du HTML complet : **référencement natif, sans configuration particulière**.

**Deux sous-domaines** sur le même hébergement OVH :
- `tc2v-handball.fr` → application Laravel + Inertia/Vue (site public, responsive, optimisé smartphone)
- `back.tc2v-handball.fr` → back-office Filament (usage desktop uniquement)

Les deux partagent le **même projet Laravel et la même base MySQL**. Plus besoin d'API REST séparée.

---

## 4. Modules fonctionnels

Le site est découpé en **modules indépendants**, développés et mis en ligne par itérations successives. Chaque module fait l'objet d'une spécification détaillée à part.

**Rubrique "Le Club"** — contenu institutionnel

| Module                      | Public                             | Admin Filament                                          |
| --------------------------- | ---------------------------------- | ------------------------------------------------------- |
| **Présentation du club**    | Lecture (texte riche)              | Éditer le contenu                                       |
| **Bureau & CA**             | Lecture (organigramme)             | Gérer les membres, rôles, photos, ordre                 |
| **Commissions**             | Lecture                            | Gérer les commissions et leurs membres                  |
| **Entraîneurs & arbitres**  | Lecture                            | Gérer les profils (contenu — pas de compte utilisateur) |

**Rubrique "Infos pratiques"** — fonctionnel

| Module                         | Public                    | Admin Filament             |
| ------------------------------ | ------------------------- | -------------------------- |
| **Inscription au club**        | Lien / widget HelloAsso   | —                          |
| **Demande de cours d'essai**   | Formulaire public         | Voir et traiter les demandes |
| **Planning des entraînements** | Lecture + carte des lieux | Gérer les créneaux         |

**Autres rubriques**

| Module                    | Public                    | Admin Filament                        |
| ------------------------- | ------------------------- | ------------------------------------- |
| **Actualités**            | Lecture                   | Créer / modifier / supprimer          |
| **Catégories & équipes**  | Lecture (1 page/catégorie)| Gérer saisons, catégories, équipes    |
| **Calendrier des matchs** | Via Scorenco (par équipe) | —                                     |
| **Résultats**             | Via Scorenco (par équipe) | —                                     |
| **Galerie photos**        | Lecture                   | Upload et organisation                |
| **Boutique**              | Voir + lien HelloAsso     | Gérer les articles                    |
| **Partenaires / sponsors**| Lecture                   | Gérer les partenaires                 |

### Module "Le Club" — détail

Quatre sous-pages gérées indépendamment dans Filament :

1. **Présentation du club** : contenu texte riche (éditeur WYSIWYG dans Filament), une seule entrée éditable.
2. **Bureau et Conseil d'Administration** : liste de `BoardMember` avec rôle, photo et ordre. Affiché sous forme d'**organigramme** côté Vue.js (composant visuel hiérarchique configurable). Une entrée peut être liée à un `User` existant (optionnel).
3. **Commissions** : liste de `Commission` avec nom et description. Chaque commission a des `CommissionMember` : nom, photo, rôle au sein de la commission, ordre d'affichage.
4. **Entraîneurs & arbitres** : liste des `StaffMember` — entraîneurs et arbitres du club, avec leur type, catégories (texte libre) et bio courte. Contenu géré dans Filament, sans compte utilisateur associé.

### Module demande de cours d'essai — détail

Formulaire public accessible sans connexion :
- Champs : prénom, nom, âge, créneau souhaité (liste déroulante)
- Les **créneaux disponibles** sont filtrés dynamiquement selon l'âge saisi : le système calcule la catégorie correspondante et propose les entraînements de cette catégorie (`TrainingSession`)
- À la soumission : email de confirmation au demandeur + notification email à l'admin
- Dans Filament, les admins voient la liste des demandes avec un statut (nouveau / contacté / validé / refusé)

### Module planning des entraînements — détail

- Liste des créneaux par catégorie (jour, heure, lieu)
- **Carte interactive** (Google Maps ou Leaflet/OpenStreetMap) affichant les gymnases (`Venue`) avec leur adresse — clic sur un marqueur pour voir les créneaux associés

---

## 5. Structure du frontend (Vue.js + Inertia.js)

> **Vue.js** est un framework JavaScript pour construire des interfaces web en composants réutilisables. **Inertia.js** est le pont qui connecte Vue à Laravel : chaque page Vue reçoit ses données directement du contrôleur Laravel correspondant, sans passer par une API REST.

Le code frontend vit dans le projet Laravel, dans le dossier `resources/` :

```
resources/
├── js/
│   ├── app.js                     ← Point d'entrée Inertia
│   ├── layouts/
│   │   └── MainLayout.vue         ← Layout public (header, footer, nav)
│   ├── components/                ← Composants Vue réutilisables (cartes, boutons, carte...)
│   └── pages/                     ← Une page Vue par route
│       ├── Home.vue
│       ├── Actualites/
│       │   ├── Index.vue          ← Liste des articles
│       │   └── Show.vue           ← Détail d'un article
│       ├── LeClub/
│       │   ├── Index.vue          ← Page d'entrée "Le Club"
│       │   ├── Presentation.vue
│       │   ├── Bureau.vue         ← Organigramme
│       │   ├── Commissions.vue
│       │   └── EntraineursArbitres.vue
│       ├── InfosPratiques/
│       │   ├── Index.vue          ← Page d'entrée "Infos pratiques"
│       │   ├── Planning.vue       ← Planning + carte interactive
│       │   ├── Inscription.vue
│       │   └── Essai.vue
│       ├── Equipes/
│       │   ├── Index.vue          ← Liste des catégories
│       │   └── Show.vue           ← Page d'une catégorie
│       ├── Boutique.vue
│       ├── Galerie.vue
│       └── Partenaires.vue
├── css/
│   └── app.css
└── views/
    └── app.blade.php              ← Template racine Inertia (point d'entrée HTML)
```

**Routes principales :**

| Route                                  | Contenu                                                  |
| -------------------------------------- | -------------------------------------------------------- |
| `/`                                    | Page d'accueil                                           |
| `/actualites`                          | Liste des articles                                       |
| `/actualites/:slug`                    | Détail d'un article                                      |
| `/le-club`                             | Page d'entrée — présentation générale                    |
| `/le-club/presentation`                | Présentation du club (texte riche)                       |
| `/le-club/bureau`                      | Bureau et Conseil d'Administration (organigramme)        |
| `/le-club/commissions`                 | Liste et description des commissions                     |
| `/le-club/entraineurs-arbitres`        | Entraîneurs et arbitres                                  |
| `/equipes`                             | Liste de toutes les catégories de la saison courante     |
| `/equipes/:slug`                       | Page catégorie : équipes, calendrier, résultats Scorenco |
| `/infos-pratiques`                     | Page d'entrée — infos pratiques                          |
| `/infos-pratiques/planning`            | Planning des entraînements + carte des lieux             |
| `/infos-pratiques/inscription`         | Page d'inscription (widget/lien HelloAsso)               |
| `/infos-pratiques/essai`               | Formulaire de demande de cours d'essai                   |
| `/boutique`                            | Articles + liens HelloAsso                               |
| `/galerie`                             | Galerie photos                                           |
| `/partenaires`                         | Sponsors et partenaires                                  |

> Pas de page dédiée par équipe individuelle à ce stade — la page catégorie agrège tout. À réévaluer si une catégorie a beaucoup d'équipes.

- Les routes sont définies dans `routes/web.php` (Laravel), pas dans un fichier frontend séparé.
- La navigation entre pages se fait sans rechargement complet (Inertia intercepte les clics et charge uniquement les nouvelles données).

---

## 6. Structure du backend Laravel

> **Laravel** est un framework PHP. PHP est le langage de programmation le plus utilisé pour les sites web côté serveur. Laravel en est la version la plus moderne et structurée.

### Organisation du code

```
app/
├── Http/
│   ├── Controllers/        ← Un controller par page (retourne une vue Inertia)
│   │   ├── ActualitesController.php
│   │   ├── EquipesController.php
│   │   └── ...
│   └── Requests/           ← Validation des données reçues (formulaires)
├── Models/                 ← Représentation des données (voir section suivante)
├── Notifications/          ← Emails automatiques (demandes d'essai, notifications admin...)
├── Services/
│   └── ScorencoService.php ← Connexion à l'API Scorenco + mise en cache
└── Filament/Resources/     ← Écrans du back-office admin
routes/
├── web.php                 ← Toutes les routes du site (entièrement publiques)
└── (pas d'api.php nécessaire pour le frontend Inertia)
```

### Authentification

L'authentification est réservée aux administrateurs du back-office Filament. Le site public est entièrement accessible sans connexion.

Inertia utilise l'**authentification par session Laravel** — le mécanisme standard de PHP. Après identification (email + mot de passe), Laravel crée une session côté serveur. Plus simple et plus sécurisé qu'un système de tokens : pas de token à stocker côté client, pas de refresh à gérer.

---

## 7. Les modèles de données (Eloquent)

> **C'est quoi un modèle de données ?** C'est la représentation en code d'une "table" dans la base de données. Par exemple, un modèle `User` correspond à la table des utilisateurs. **Eloquent** est le système Laravel qui fait le lien entre le code PHP et la base MySQL — il permet d'écrire `User::find(1)` plutôt que de rédiger du SQL brut.

> **C'est quoi une "relation" entre modèles ?** Comme dans Excel, une ligne d'une table peut être liée à une ligne d'une autre table. Par exemple, un `Player` appartient à une `Category`, et une `Category` a plusieurs `Player`.

### Gestion des rôles utilisateur

La gestion des rôles s'appuie sur **Spatie laravel-permission**, la librairie de référence pour Laravel. Le site public est entièrement public (aucune connexion requise). Seuls deux rôles existent, pour l'accès au back-office Filament :

| Rôle | Description |
|---|---|
| `super_admin` | Accès total au back-office, configuration technique |
| `admin` | Gestionnaire du club (actualités, équipes, galerie, partenaires…) |

### Modèles prévus

| Modèle            | Table MySQL         | Description                                                                                                                                                                                                                   |
| ----------------- | ------------------- | ----------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| `User`            | `users`             | Administrateurs du back-office. Contient email, mot de passe, prénom, nom. Les rôles sont gérés via Spatie (table séparée).                                                                                                   |
| `Season`          | `seasons`           | Une saison sportive (ex: "2026-2027"). Contient les dates de début/fin et un flag `is_current`.                                                                                                                               |
| `Category`        | `categories`        | Une catégorie d'âge et de genre pour une saison donnée. Ex : "U13 Féminines 2026-2027". Contient le nom, le genre (M/F/Mixte), les années de naissance éligibles (`birth_year_min`, `birth_year_max`), et la saison associée. |
| `Team`            | `teams`             | Une équipe au sein d'une catégorie. Ex : "Équipe 1" dans U13 Féminines. Contient un identifiant Scorenco pour récupérer son calendrier/résultats.                                                                             |
| `Player`          | `players`           | Un joueur du club : prénom, nom, date de naissance, photo, catégorie. Contenu pur (pas de compte utilisateur) — alimenté via import CSV, affiché dans la galerie par équipe.                                                 |
| `StaffMember`     | `staff_members`     | Un entraîneur ou arbitre du club : nom, type (coach/arbitre), photo, bio courte, catégories en texte libre. Contenu géré dans Filament, affiché sur la page Entraîneurs & Arbitres.                                          |
| `BoardMember`     | `board_members`     | Entrée d'organigramme : rôle au sein du CA/bureau, photo, ordre d'affichage.                                                                                                                                                  |
| `Commission`      | `commissions`       | Une commission du club : nom, description.                                                                                                                                                                                    |
| `CommissionMember`| `commission_members`| Membre d'une commission : nom, photo, rôle au sein de la commission, ordre d'affichage. Lié à une `Commission`.                                                                                                              |
| `TrialRequest`    | `trial_requests`    | Demande de cours d'essai : nom, prénom, âge, créneau souhaité, statut (nouveau / contacté / validé / refusé).                                                                                                                |
| `News`            | `news`              | Un article d'actualité : titre, contenu, date, image.                                                                                                                                                                         |
| `Photo`           | `photos`            | Une photo de galerie, associée à un album ou une catégorie.                                                                                                                                                                   |
| `Partner`         | `partners`          | Un sponsor ou partenaire : logo, nom, lien, niveau de partenariat.                                                                                                                                                            |
| `Product`         | `products`          | Un article de la boutique : nom, description, image, lien HelloAsso.                                                                                                                                                          |
| `TrainingSession` | `training_sessions` | Un créneau d'entraînement récurrent ou ponctuel : jour, heure, catégorie, lieu.                                                                                                                                               |
| `Venue`           | `venues`            | Un lieu d'entraînement ou de match : nom, adresse, coordonnées GPS.                                                                                                                                                           |

### Gestion des catégories et des saisons

> **Pourquoi découper par saison ?** Les catégories d'âge changent chaque année. Un joueur U13 cette saison sera U15 dans deux ans. En liant les catégories à une saison, on peut archiver l'historique sans perdre de données.

Les années de naissance éligibles sont **saisies manuellement** par un admin lors de la création de la catégorie. Exemple :

| Saison    | Catégorie         | Années de naissance |
| --------- | ----------------- | ------------------- |
| 2026-2027 | U13 Masculins     | 2014 et 2015        |
| 2026-2027 | U15 Féminines     | 2012 et 2013        |
| 2026-2027 | Seniors Masculins | 2006 et avant       |

> La règle de calcul des tranches d'âge dépend de la fédération — on ne l'automatise pas pour garder la flexibilité.

### Import des joueurs par CSV

Pour éviter de saisir chaque joueur à la main, le back-office Filament permettra d'**importer une liste de joueurs via un fichier CSV**. Le fichier contiendra au minimum : prénom, nom, date de naissance, photo (optionnelle). Le système attribuera automatiquement chaque joueur à la catégorie correspondant à son année de naissance pour la saison en cours. Les joueurs sont du **contenu pur** (pas de compte utilisateur) — ils sont affichés dans la galerie de l'équipe sur la page catégorie.

### Schéma des relations principales

```
Season ──── Category ──────────────────── Team (scorenco_id)
                │
    ┌───────────┴──────────────────┐
  Player                    TrainingSession
                                   │
                                 Venue

Commission ── CommissionMember

StaffMember  BoardMember  (contenu standalone, sans relation DB)
```

---

## 8. Intégration Scorenco

> **Scorenco** est une plateforme spécialisée dans la gestion des clubs de handball. Elle fournit une API (interface de communication entre services) qui permet de récupérer automatiquement les calendriers de matchs et les résultats.

- Laravel interroge l'API Scorenco périodiquement et **met les données en cache** (stockage temporaire) pour éviter de la solliciter à chaque visite du site.
- Le frontend Vue.js affiche ces données comme n'importe quelle autre donnée — transparent pour l'utilisateur.
- L'intégration exacte (identifiants, fréquence de synchro) sera précisée lors de l'itération du module calendrier.

---

## 9. Intégration HelloAsso

> **HelloAsso** est une plateforme française dédiée aux associations. Elle gère les paiements en ligne sans commission pour l'association. Elle propose une API et des widgets intégrables.

Deux usages prévus :
1. **Inscriptions au club** : lien ou widget HelloAsso intégré dans une page dédiée
2. **Boutique** : chaque article renvoie vers une campagne HelloAsso (vente en ligne)

---

## 10. Environnement local (WSL)

Tout le développement se fait en local sur **WSL (Windows Subsystem for Linux)** avant tout déploiement sur OVH. L'environnement local reproduit les conditions de production.

### Stack locale à installer sur WSL

| Composant | Outil | Usage |
|---|---|---|
| **PHP 8.2+** | `apt install php8.2 php8.2-{cli,curl,mbstring,xml,mysql,zip,gd}` | Exécuter Laravel |
| **MySQL** | `apt install mysql-server` | Base de données locale |
| **Composer** | Script officiel getcomposer.org | Gestionnaire de dépendances PHP |
| **Node.js** | Via `nvm` (Node Version Manager) | Compiler les assets Vue/CSS avec Vite |
| **Git** | Préinstallé ou `apt install git` | Gestion du code source |

### Lancer le projet en local

Deux processus à lancer en parallèle lors du développement :

```bash
# Terminal 1 — serveur PHP Laravel
php artisan serve          # → http://localhost:8000

# Terminal 2 — compilateur Vue/CSS avec hot-reload
npm run dev                # → recharge le navigateur à chaque modification
```

Le back-office Filament est accessible à `http://localhost:8000/admin`.

### Configuration locale spécifique

- **Fichier `.env`** : copié depuis `.env.example`, configure la connexion MySQL locale, les clés d'API (Scorenco, HelloAsso en sandbox), l'envoi d'emails via [Mailpit](https://mailpit.axllent.org/) (outil local qui capture les emails sans les envoyer réellement)
- **Mailpit** : intercepte tous les emails de l'application (convocations, demandes d'essai) — permet de les visualiser dans un navigateur sans configuration SMTP réelle
- **Migrations** : `php artisan migrate` crée les tables en local
- **Seeders** : `php artisan db:seed` peuple la base avec des données de test (équipes fictives, joueurs, articles)

---

## 11. Hébergement OVH et déploiement

### Offre retenue : WebCloud Starter

> L'hébergement mutualisé OVH WebCloud Starter est plus contraignant qu'un VPS mais suffisant pour un club sportif. Les principales différences à anticiper :

| Élément | Détail |
|---|---|
| **PHP** | 8.2+ disponible, configuré via `.ovhconfig` à la racine |
| **MySQL** | Une base de données incluse |
| **SSH** | Accès SSH disponible (nécessaire pour les commandes artisan) |
| **Racine web** | Pointer vers le dossier `public/` de Laravel dans le manager OVH |
| **Node.js** | Non disponible — le build Vue/CSS se fait en local, seuls les fichiers compilés sont uploadés |
| **Redis** | Non disponible — cache via fichiers (driver `file`) |
| **Queue workers** | Pas de processus persistant — les jobs sont traités via un **cron OVH** toutes les minutes (`php artisan queue:work --stop-when-empty`) |
| **HTTPS** | Certificat Let's Encrypt inclus et renouvelé automatiquement |
| **SEO** | Natif — Laravel rend le HTML côté serveur |

### Implications techniques

- **Cache** : `CACHE_DRIVER=file` dans le `.env` de production (pas de Redis)
- **Emails** : configuration SMTP via un service externe (Brevo / Mailgun — offre gratuite suffisante pour un club)
- **Emails** (demandes d'essai, notifications) : envoyés via la queue traitée par le cron — délai max 1 minute acceptable
- **Sessions** : stockées en fichiers (driver `file`) ou en base MySQL

### Procédure de déploiement

1. En local : `npm run build` → compile les assets Vue dans `public/build/`
2. Upload par SSH/SFTP : code source + `public/build/` (sans `node_modules/`)
3. Sur OVH via SSH :
   ```bash
   composer install --no-dev --optimize-autoloader
   php artisan migrate --force
   php artisan storage:link
   php artisan config:cache
   php artisan route:cache
   ```
4. Configurer le cron OVH : `* * * * * php /path/to/artisan schedule:run`

> CI/CD via GitHub Actions (déclenchement automatique sur push) à prévoir en itération dédiée.

---

## 12. Ordre de développement suggéré (par itérations)

Chaque itération produit une version fonctionnelle et déployable.

1. **Socle local** : configuration WSL (PHP, MySQL, Node, Composer), mise en place Laravel + Inertia + Vue + Filament, authentification, système de rôles
2. **Contenu éditorial** : actualités, galerie, partenaires
3. **Club** : saisons, catégories, équipes, joueurs (+ import CSV), lieux, planning entraînements, infos pratiques (bureau, commissions, entraîneurs/arbitres)
4. **Calendrier & résultats** : intégration Scorenco par équipe, affiché sur les pages catégories
5. **Boutique & inscriptions** : articles HelloAsso, formulaire d'essai
6. **Déploiement OVH WebCloud** : configuration hébergement, mise en production, SMTP, cron
7. **Polish** : design final, responsive mobile, CI/CD GitHub Actions

---

## 13. Points ouverts (à préciser)

- [x] Nom de domaine : `tc2v-handball.fr`
- [ ] Identifiants et scope de l'API Scorenco pour ce club
- [ ] Logo haute résolution (à fournir)
- [x] Charte graphique — palette officielle :
  | Rôle | Pantone | HEX | RGB |
  |---|---|---|---|
  | Bleu principal | Pantone 540 C | `#003A5D` | (0, 58, 93) |
  | Bleu secondaire | Pantone 5405 C | `#5B7F95` | (91, 127, 149) |
  | Gris | Pantone 430 C | `#7C878E` | (124, 135, 142) |
  | Blanc | — | `#FFFFFF` | (255, 255, 255) |
- [ ] Contenu initial : textes de présentation, photos, liste des équipes

---

*Document rédigé le 25 avril 2026 — à compléter au fil des itérations.*
