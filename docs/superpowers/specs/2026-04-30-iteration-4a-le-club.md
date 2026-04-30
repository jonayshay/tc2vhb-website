# Spécification — Itération 4a : Module Le Club (Présentation + Staff)

**Date :** 30 avril 2026
**Statut :** Approuvée

---

## Objectif

Implémenter la première partie du module **Le Club** : page de présentation du club (contenu singleton géré dans Filament) et pages Entraîneurs / Arbitres (modèle `StaffMember` avec regroupement par catégorie).

---

## Périmètre

Ce module couvre :
- `ClubPresentation` — contenu singleton (titre, accroche, image, texte riche)
- `StaffMember` — entraîneurs et arbitres, regroupés par catégorie côté public

Hors périmètre : Bureau & CA, Commissions (itération 4b).

---

## Stack et dépendances

- **Laravel 12** + **Inertia.js v3** + **Vue.js 3** + **Filament 3** (socle existant)
- **TipTap** via `awcodes/filament-tiptap-editor` (déjà installé, utilisé pour Actualités)
- Aucune nouvelle dépendance

---

## Modèles de données

### Table `club_presentations`

| Colonne | Type | Contraintes | Description |
|---|---|---|---|
| `id` | bigint | PK, auto-increment | — |
| `title` | string | not null | Titre de la section |
| `accroche` | text | not null | Sous-titre / accroche |
| `featured_image` | string | nullable | Chemin relatif, disk `public` |
| `content` | longText | not null | Contenu riche (HTML TipTap) |
| `created_at` | timestamp | — | — |
| `updated_at` | timestamp | — | — |

**Règle :** un seul enregistrement en base, créé par seeder à la migration initiale (valeurs vides/placeholder).

### Table `staff_members`

| Colonne | Type | Contraintes | Description |
|---|---|---|---|
| `id` | bigint | PK, auto-increment | — |
| `name` | string | not null | Nom complet |
| `type` | enum(`entraineur`, `arbitre`) | not null | Type de rôle |
| `photo` | string | nullable | Chemin relatif, disk `public` |
| `bio` | text | nullable | Biographie courte |
| `categories` | JSON | not null, default `[]` | Slugs des catégories encadrées |
| `created_at` | timestamp | — | — |
| `updated_at` | timestamp | — | — |

### Catégories prédéfinies (liste ordonnée)

| Slug | Libellé | Genre |
|---|---|---|
| `baby` | Baby Hand | Mixte |
| `u7` | U7 | Mixte |
| `u9` | U9 | Mixte |
| `u11_m` | U11 Masculins | Masculin |
| `u11_f` | U11 Féminines | Féminin |
| `u13_m` | U13 Masculins | Masculin |
| `u13_f` | U13 Féminines | Féminin |
| `u15_m` | U15 Masculins | Masculin |
| `u15_f` | U15 Féminines | Féminin |
| `u18_m` | U18 Masculins | Masculin |
| `u18_f` | U18 Féminines | Féminin |
| `seniors_m` | Seniors Masculins | Masculin |
| `seniors_f` | Seniors Féminines | Féminin |
| `loisirs` | Loisirs | Mixte |

Cette liste est déclarée comme constante dans le modèle `StaffMember`. Un membre peut appartenir à plusieurs catégories et apparaît dans chaque groupe correspondant sur la page publique.

---

## Back-office Filament

### Page custom `ManageClubPresentation`

**Fichier :** `app/Filament/Pages/ManageClubPresentation.php`

- Étend `Filament\Pages\Page`, utilise le trait `HasForms`
- Charge toujours le seul enregistrement `ClubPresentation` existant
- Affiche directement le formulaire d'édition — pas de liste, pas de bouton "Créer"
- Accès : rôles `admin` et `super_admin`
- Navigation : icône `heroicon-o-document-text`, label "Présentation du club"

**Champs du formulaire :**
1. `title` — TextInput, requis
2. `accroche` — Textarea, requis
3. `featured_image` — FileUpload, nullable, `image/jpeg` + `image/png` + `image/webp`, dossier `club/`, disk `public`
4. `content` — TipTap, requis, `->columnSpanFull()`

### `StaffMemberResource`

**Fichier :** `app/Filament/Resources/StaffMemberResource.php`

Accès : rôles `admin` et `super_admin`.

**Formulaire :**
1. `name` — TextInput, requis
2. `type` — Select (`entraineur` → "Entraîneur", `arbitre` → "Arbitre"), requis
3. `photo` — FileUpload, nullable, `image/jpeg` + `image/png` + `image/webp`, `->image()`, `->maxSize(2048)`, dossier `staff/photos/`, disk `public`
4. `bio` — Textarea, nullable, `->columnSpanFull()`
5. `categories` — CheckboxList avec les 14 catégories dans l'ordre prédéfini

**Table :**
- Colonnes : photo (ImageColumn, `->circular()`), nom (searchable), type (badge : `primary` pour entraîneur, `warning` pour arbitre), catégories (TextColumn liste)
- Filtre : SelectFilter sur `type`
- Actions : EditAction, DeleteAction

**Pages :** ListStaffMembers (avec CreateAction), CreateStaffMember, EditStaffMember (avec DeleteAction).

---

## Backend Laravel

### Seeder `ClubPresentationSeeder`

Crée le seul enregistrement `ClubPresentation` avec des valeurs placeholder :
- `title` : "Présentation du club"
- `accroche` : "Bienvenue au TC2V Handball"
- `featured_image` : null
- `content` : `<p>À compléter.</p>`

Enregistré dans `DatabaseSeeder`.

### Contrôleur `LeClubController` (`app/Http/Controllers/LeClubController.php`)

**`index()`** — Retourne `Inertia::render('LeClub/Index')` sans données.

**`presentation()`** — Charge `ClubPresentation::firstOrFail()`, retourne `Inertia::render('LeClub/Presentation', ['presentation' => $presentation])`.

**`entraineurs()`** — Charge tous les `StaffMember` de type `entraineur`. Regroupe et trie par catégorie selon l'ordre de `StaffMember::CATEGORIES`. Retourne `Inertia::render('LeClub/Entraineurs', ['groupes' => $groupes])`.

**`arbitres()`** — Idem type `arbitre`. Retourne `Inertia::render('LeClub/Arbitres', ['groupes' => $groupes])`.

**Structure `$groupes` :**
```php
[
    ['categorie' => 'U13 Masculins', 'membres' => [...]],
    ['categorie' => 'U13 Féminines', 'membres' => [...]],
    // ...
]
// Seules les catégories ayant au moins un membre sont incluses.
```

### Routes (`routes/web.php`)

```php
Route::get('/le-club', [LeClubController::class, 'index'])->name('le-club.index');
Route::get('/le-club/presentation', [LeClubController::class, 'presentation'])->name('le-club.presentation');
Route::get('/le-club/entraineurs', [LeClubController::class, 'entraineurs'])->name('le-club.entraineurs');
Route::get('/le-club/arbitres', [LeClubController::class, 'arbitres'])->name('le-club.arbitres');
```

---

## Frontend Vue.js

### `resources/js/pages/LeClub/Index.vue`

- Pas de prop
- Affiche des liens de navigation vers les 3 sous-sections (Présentation, Entraîneurs, Arbitres)
- Page statique

### `resources/js/pages/LeClub/Presentation.vue`

- Prop : `presentation` (Object)
- Affiche : `<h1>` titre, accroche, image mise en avant (`/storage/{featured_image}`) si présente, contenu riche via `v-html`

### `resources/js/pages/LeClub/Entraineurs.vue`

- Prop : `groupes` (Array de `{ categorie: string, membres: StaffMember[] }`)
- Pour chaque groupe : titre de section (`<h2>`), grille de cartes membres
- Carte membre : photo (`/storage/{photo}`) ou placeholder initiale, nom, bio si présente
- Seules les catégories avec au moins un membre sont affichées (garanti côté PHP)

### `resources/js/pages/LeClub/Arbitres.vue`

- Structure identique à `Entraineurs.vue`
- Prop : `groupes` (même forme)

**Placeholder photo :** affiche la première lettre du nom dans un cercle coloré (CSS pur, pas d'image externe).

---

## Stockage des fichiers

| Type | Dossier | Disk |
|---|---|---|
| Image présentation | `storage/app/public/club/` | `public` |
| Photos staff | `storage/app/public/staff/photos/` | `public` |

---

## Tests

### `ClubPresentationPageTest` (Feature)
- La page `/le-club/presentation` retourne 200 avec le composant Inertia `LeClub/Presentation`
- La prop `presentation` contient les champs attendus

### `StaffMemberTest` (Unit)
- Un `StaffMember` peut être créé avec les champs requis
- `categories` est bien casté en array

### `StaffMemberObserverTest` — N/A (pas d'observer pour ce modèle)

### `LeClubControllerTest` (Feature)
- `/le-club` retourne 200 avec composant `LeClub/Index`
- `/le-club/presentation` retourne 200 avec composant `LeClub/Presentation` et prop `presentation`
- `/le-club/entraineurs` retourne 200 avec composant `LeClub/Entraineurs` et prop `groupes`
- `/le-club/arbitres` retourne 200 avec composant `LeClub/Arbitres` et prop `groupes`
- Les groupes sont triés selon l'ordre des catégories prédéfinies
- Les catégories sans membre n'apparaissent pas dans `groupes`

### `ManageClubPresentationTest` (Feature/Admin)
- Admin et super_admin peuvent accéder à la page Filament
- Utilisateur sans rôle est interdit

### `StaffMemberResourceTest` (Feature/Admin)
- Admin et super_admin peuvent accéder à `/admin/staff-members`
- Unauthenticated → redirect login
- Sans rôle → 403

---

## Ce qui est hors périmètre

- Bureau & CA (`BoardMember`, organigramme) — itération 4b
- Commissions (`Commission`, `CommissionMember`) — itération 4b
- Galerie photos
- Lien vers les équipes depuis les pages staff
