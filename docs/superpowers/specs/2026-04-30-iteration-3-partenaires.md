# Spécification — Itération 3 : Module Partenaires

**Date :** 30 avril 2026
**Statut :** Approuvée

---

## Objectif

Implémenter le module **Partenaires** du site TC2V Handball : gestion des sponsors et partenaires dans Filament avec réordonnancement visuel par drag-and-drop, et affichage public sur une page dédiée.

---

## Périmètre

Ce module couvre uniquement les **partenaires** (modèle `Partner`). Pas de niveaux de partenariat, pas de statut publié/archivé — tous les partenaires créés sont visibles publiquement. Pour retirer un partenaire, on le supprime.

---

## Stack et dépendances

- **Laravel 12** + **Inertia.js v3** + **Vue.js 3** + **Filament 3** (socle existant)
- Aucune nouvelle dépendance — le drag-and-drop est natif dans Filament 3 via `->reorderable()`.

---

## Modèle de données

### Table `partners`

| Colonne | Type | Contraintes | Description |
|---|---|---|---|
| `id` | bigint | PK, auto-increment | — |
| `name` | string | not null | Nom du partenaire ou sponsor |
| `logo` | string | nullable | Chemin relatif vers le logo (disk `public`) |
| `url` | string | nullable | URL du site du partenaire |
| `description` | text | nullable | Description ou slogan |
| `sort_order` | integer | not null, default 0 | Ordre d'affichage sur le site public |
| `created_at` | timestamp | — | — |
| `updated_at` | timestamp | — | — |

### Règles métier

- Tous les partenaires enregistrés sont affichés publiquement, triés par `sort_order` ASC.
- `sort_order` est pré-rempli automatiquement à la création : valeur = nombre de partenaires existants + 1 (le nouveau partenaire arrive en dernier).
- Le réordonnancement dans Filament met à jour `sort_order` en base via le mécanisme natif Filament (`->reorderable('sort_order')`).
- Les logos acceptent JPEG, PNG, WebP et SVG (formats courants pour les logos de sponsors).

---

## Back-office Filament

### `PartnerResource` (`app/Filament/Resources/PartnerResource.php`)

**Accès :** rôles `admin` et `super_admin` uniquement.

#### Vue liste (table)

| Colonne affichée | Détail |
|---|---|
| Logo | Miniature (`->circular()` ou carré) |
| Nom | Texte |
| URL | Lien cliquable (`->url()`) |
| Ordre | Numérique |

Fonctionnalités :
- **Drag-and-drop** via `->reorderable('sort_order')` — glisser les lignes pour modifier l'ordre, sauvegarde automatique en base
- **Actions de ligne** : éditer, supprimer (avec confirmation)

#### Formulaire création / édition

Champs dans l'ordre d'affichage :

1. **`name`** — `TextInput`, requis.
2. **`logo`** — `FileUpload`, nullable. Accepte `image/jpeg`, `image/png`, `image/webp`, `image/svg+xml`. Stocké dans `storage/app/public/partners/logos/`, disk `public`.
3. **`url`** — `TextInput`, nullable, validation URL.
4. **`description`** — `Textarea`, nullable.
5. **`sort_order`** — `TextInput` numérique, pré-rempli automatiquement (observer ou mutateur — voir section backend).

---

## Backend Laravel

### Observer `PartnerObserver` (`app/Observers/PartnerObserver.php`)

- Écoute l'événement `creating` sur le modèle `Partner`.
- Si `sort_order` est null ou 0 → définit `sort_order = Partner::max('sort_order') + 1`.
- Enregistré dans `AppServiceProvider`.

### Controller `PartenairesController` (`app/Http/Controllers/PartenairesController.php`)

**`index()`**
- Récupère tous les `Partner` triés par `sort_order ASC`.
- Retourne `Inertia::render('Partenaires', ['partenaires' => $partenaires])`.

### Route (`routes/web.php`)

```php
Route::get('/partenaires', [PartenairesController::class, 'index'])->name('partenaires.index');
```

---

## Frontend Vue.js

### `resources/js/pages/Partenaires.vue`

- **Layout :** `MainLayout.vue` (appliqué via `app.js`)
- **Prop :** `partenaires` (Array)
- **Affichage :** grille responsive (2-4 colonnes selon la taille d'écran)
- **Carte partenaire :**
  - Logo (balise `<img>` avec `/storage/{logo}`) si présent, sinon nom seul
  - Nom du partenaire
  - Description (si présente)
  - Lien cliquable vers `url` (attribut `target="_blank" rel="noopener noreferrer"`) si présente
- **Message vide** si aucun partenaire enregistré

---

## Stockage des logos

| Type | Chemin | Disk |
|---|---|---|
| Logos partenaires | `storage/app/public/partners/logos/` | `public` |

Le lien symbolique `public/storage → storage/app/public` est déjà en place (socle itération 1).

---

## Tests

- **`PartnerObserver`** : test unitaire vérifiant que `sort_order` est auto-incrémenté à la création.
- **`PartenairesController`** : tests feature vérifiant :
  - La page retourne 200 avec le composant Inertia `Partenaires`
  - Les partenaires sont triés par `sort_order` ASC
  - La page fonctionne sans partenaires (tableau vide)

---

## Ce qui est hors périmètre

- Niveaux de partenariat (non demandé)
- Statut publié/archivé (non demandé — suppression directe)
- Page de détail par partenaire
- Spatie Media Library (réservé au module galerie)
