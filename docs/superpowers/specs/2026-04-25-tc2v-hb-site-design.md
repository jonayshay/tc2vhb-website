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

- **Les membres et parents actuels** : retrouver les informations pratiques (planning, lieux, convocations, résultats)
- **Les personnes souhaitant rejoindre le club** : découvrir le club, les équipes, s'inscrire

Le site doit pouvoir être **géré au quotidien par des responsables du club sans compétences techniques**, via un espace d'administration clair et simple. Les tâches plus complexes (configuration, intégrations) seront assurées par un technicien.

---

## 2. Stack technique choisie

> **C'est quoi une "stack technique" ?** C'est l'ensemble des outils et langages utilisés pour construire le site. Chaque outil a un rôle précis.

| Composant | Outil choisi | Rôle |
|---|---|---|
| **Frontend** (ce que l'utilisateur voit) | Angular | Application web moderne, rapide, dans le navigateur |
| **Backend** (le cerveau côté serveur) | Laravel (PHP) | Gère les données, la logique, les emails, l'API |
| **Back-office** (interface d'administration) | Filament | Interface admin pour les gestionnaires du club |
| **Base de données** | MySQL | Stockage de toutes les données du club |
| **Hébergement** | OVH (VPS ou mutualisé) | Serveurs français, conformes RGPD |
| **Paiements** | HelloAsso | Plateforme française pour les associations, sans commission |
| **Résultats / Calendrier** | Scorenco (API) | Service spécialisé handball, synchronisé automatiquement |

### Pourquoi Angular + Laravel ?

- **Angular** génère des fichiers statiques (HTML/CSS/JS) déposés sur le serveur : pas besoin de Node.js en production, compatible hébergement OVH standard.
- **Laravel** est le framework PHP le plus populaire au monde : robuste, bien documenté, très adapté aux projets associatifs avec beaucoup de logique métier (convocations, équipes, notifications).
- **Filament** est un back-office "clé en main" qui s'intègre à Laravel : les administrateurs du club ont une interface soignée sans qu'on ait besoin de la coder from scratch.

---

## 3. Architecture générale

```
┌──────────────────────────────────────────────────────────┐
│                    Hébergement OVH                       │
│                                                          │
│  ┌───────────────────┐    ┌────────────────────────────┐ │
│  │  Site public      │    │  Serveur backend           │ │
│  │  tc2v-hb.fr       │◄──►│  api.tc2v-hb.fr            │ │
│  │  Angular (SPA)    │    │  Laravel + Filament admin  │ │
│  └───────────────────┘    └─────────────┬──────────────┘ │
│                                         │                │
│                               ┌─────────▼──────────┐     │
│                               │    Base MySQL       │     │
│                               └────────────────────┘     │
└──────────────────────────────────────────────────────────┘
          │                            │
          ▼                            ▼
    Scorenco API               HelloAsso
    (résultats, matchs)        (inscriptions, boutique)
```

> **SPA (Single Page Application)** : le site Angular se charge une seule fois, puis navigue sans rechargement de page — comme une application mobile dans le navigateur. C'est plus rapide et fluide pour l'utilisateur.

**Deux sous-domaines** sur le même hébergement OVH :
- `tc2v-hb.fr` → site public Angular
- `api.tc2v-hb.fr` → API Laravel + back-office Filament à `/admin`

---

## 4. Modules fonctionnels

Le site est découpé en **modules indépendants**, développés et mis en ligne par itérations successives. Chaque module fait l'objet d'une spécification détaillée à part.

| Module | Public | Membre connecté | Admin Filament |
|---|---|---|---|
| **Actualités** | Lecture | — | Créer / modifier / supprimer |
| **Présentation des équipes** | Lecture | — | Gérer équipes et joueurs |
| **Calendrier des matchs** | Via Scorenco | — | — |
| **Résultats** | Via Scorenco | — | — |
| **Planning des entraînements** | Lecture | — | Gérer les créneaux |
| **Lieux d'entraînement** | Lecture (carte) | — | Gérer les gymnases |
| **Convocations** | — | Voir et répondre | Créer et envoyer |
| **Galerie photos** | Lecture | — | Upload et organisation |
| **Boutique** | Voir + lien HelloAsso | — | Gérer les articles |
| **Partenaires / sponsors** | Lecture | — | Gérer les partenaires |
| **Inscription au club** | Lien HelloAsso | — | — |

### Module convocations — approche envisagée

> Ce module est le plus complexe. Son fonctionnement précis sera affiné lors de son itération de développement dédiée.

Inspiré de **SportEasy** (outil de gestion d'équipes sportives) dans une version simplifiée :

- Un coach ou admin crée une convocation (match ou entraînement) pour une équipe
- Les joueurs concernés reçoivent un **email avec lien de réponse** (présent / absent / incertain)
- Le coach voit en temps réel le **récap des réponses** dans son espace admin
- Les joueurs accèdent à leurs convocations depuis leur **espace membre** sur le site

---

## 5. Structure du frontend Angular

> **Angular** est un framework JavaScript développé par Google. Il permet de créer des interfaces web organisées en composants réutilisables, comme des "briques" qu'on assemble.

```
src/app/
├── core/               ← Authentification, intercepteurs réseau, services partagés
├── shared/             ← Composants réutilisables (header, footer, boutons, cartes...)
├── pages/
│   ├── home/           ← Page d'accueil
│   ├── actualites/     ← Liste et détail des articles
│   ├── equipes/        ← Présentation des équipes
│   ├── calendrier/     ← Matchs (données Scorenco)
│   ├── planning/       ← Entraînements
│   ├── boutique/       ← Articles + liens HelloAsso
│   ├── galerie/        ← Photos
│   ├── partenaires/    ← Sponsors
│   └── espace-membre/  ← Convocations, profil joueur (accès restreint)
└── app.routes.ts       ← Configuration de la navigation
```

- Chaque page est chargée à la demande (**lazy-loading**) : le navigateur ne télécharge que ce dont il a besoin.
- L'`espace-membre` est protégé par un **guard** : seul un joueur connecté peut y accéder.

---

## 6. Structure du backend Laravel

> **Laravel** est un framework PHP. PHP est le langage de programmation le plus utilisé pour les sites web côté serveur. Laravel en est la version la plus moderne et structurée.

### Organisation du code

```
app/
├── Http/Controllers/Api/   ← Points d'entrée de l'API (un par module)
├── Http/Requests/          ← Validation des données reçues
├── Http/Resources/         ← Mise en forme des réponses JSON
├── Models/                 ← Représentation des données (voir section suivante)
├── Notifications/          ← Emails automatiques (convocations, etc.)
├── Services/
│   └── ScorencoService.php ← Connexion à l'API Scorenco + mise en cache
└── Filament/Resources/     ← Écrans du back-office admin
```

### Authentification

**Laravel Sanctum** gère la connexion des membres : après identification (email + mot de passe), l'utilisateur reçoit un **token** (clé secrète temporaire) que Angular utilise pour toutes ses requêtes sécurisées.

---

## 7. Les modèles de données (Eloquent)

> **C'est quoi un modèle de données ?** C'est la représentation en code d'une "table" dans la base de données. Par exemple, un modèle `User` correspond à la table des utilisateurs. **Eloquent** est le système Laravel qui fait le lien entre le code PHP et la base MySQL — il permet d'écrire `User::find(1)` plutôt que de rédiger du SQL brut.

> **C'est quoi une "relation" entre modèles ?** Comme dans Excel, une ligne d'une table peut être liée à une ligne d'une autre table. Par exemple, un `Player` appartient à une `Team`, et une `Team` a plusieurs `Player`.

### Modèles prévus

| Modèle | Table MySQL | Description |
|---|---|---|
| `User` | `users` | Tous les utilisateurs : joueurs et admins. Contient email, mot de passe, rôle. |
| `Team` | `teams` | Une équipe du club (ex: Seniors Masculins, U13 Féminines…). |
| `Player` | `players` | Lien entre un `User` et une `Team` — un joueur peut appartenir à plusieurs équipes. |
| `News` | `news` | Un article d'actualité : titre, contenu, date, image. |
| `Photo` | `photos` | Une photo de galerie, associée à un album ou une catégorie. |
| `Partner` | `partners` | Un sponsor ou partenaire : logo, nom, lien, niveau de partenariat. |
| `Product` | `products` | Un article de la boutique : nom, description, image, lien HelloAsso. |
| `TrainingSession` | `training_sessions` | Un créneau d'entraînement récurrent ou ponctuel : jour, heure, équipe, lieu. |
| `Venue` | `venues` | Un lieu d'entraînement ou de match : nom, adresse, coordonnées GPS. |
| `Convocation` | `convocations` | Une convocation pour un match ou entraînement : équipe, date, lieu, type. |
| `ConvocationResponse` | `convocation_responses` | La réponse d'un joueur à une convocation : présent / absent / incertain. |

### Schéma des relations principales

```
User ──────────── Player ──────────── Team
                                        │
                               TrainingSession ──── Venue
                                        │
                                  Convocation ──── ConvocationResponse
                                                          │
                                                        User (joueur)
```

---

## 8. Intégration Scorenco

> **Scorenco** est une plateforme spécialisée dans la gestion des clubs de handball. Elle fournit une API (interface de communication entre services) qui permet de récupérer automatiquement les calendriers de matchs et les résultats.

- Laravel interroge l'API Scorenco périodiquement et **met les données en cache** (stockage temporaire) pour éviter de la solliciter à chaque visite du site.
- Le frontend Angular affiche ces données comme n'importe quelle autre donnée — transparent pour l'utilisateur.
- L'intégration exacte (identifiants, fréquence de synchro) sera précisée lors de l'itération du module calendrier.

---

## 9. Intégration HelloAsso

> **HelloAsso** est une plateforme française dédiée aux associations. Elle gère les paiements en ligne sans commission pour l'association. Elle propose une API et des widgets intégrables.

Deux usages prévus :
1. **Inscriptions au club** : lien ou widget HelloAsso intégré dans une page dédiée
2. **Boutique** : chaque article renvoie vers une campagne HelloAsso (vente en ligne)

---

## 10. Hébergement et déploiement

| Élément | Détail |
|---|---|
| **Fournisseur** | OVH ou équivalent français |
| **Serveur Laravel** | VPS Ubuntu avec PHP 8.2+, MySQL, Apache ou Nginx |
| **Site Angular** | Fichiers statiques dans `public_html/`, servis par Apache |
| **Déploiement** | Manuel dans un premier temps (FTP/SSH), CI/CD GitHub Actions à prévoir |
| **HTTPS** | Certificat Let's Encrypt (gratuit, renouvelé automatiquement) |

---

## 11. Ordre de développement suggéré (par itérations)

Chaque itération produit une version fonctionnelle et déployable.

1. **Socle** : mise en place Laravel + Angular + Filament, hébergement OVH, authentification
2. **Contenu éditorial** : actualités, galerie, partenaires
3. **Club** : équipes, joueurs, lieux, planning entraînements
4. **Calendrier & résultats** : intégration Scorenco
5. **Espace membre** : connexion joueurs, profil
6. **Convocations** : création, envoi email, réponses, récap
7. **Boutique** : articles + intégration HelloAsso
8. **Inscriptions** : formulaire + lien HelloAsso
9. **Polish** : design final, responsive mobile, SEO, déploiement continu

---

## 12. Points ouverts (à préciser)

- [ ] Nom de domaine définitif (`tc2v-hb.fr` ou autre)
- [ ] Type d'hébergement OVH retenu (mutualisé avec SSH ou VPS)
- [ ] Identifiants et scope de l'API Scorenco pour ce club
- [ ] Fonctionnement détaillé des convocations (notifications SMS ? application mobile ?)
- [ ] Charte graphique : couleurs officielles du club, logo haute résolution
- [ ] Contenu initial : textes de présentation, photos, liste des équipes

---

*Document rédigé le 25 avril 2026 — à compléter au fil des itérations.*
