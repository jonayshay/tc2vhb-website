# Spécification — Itération 2 : Module Actualités

**Date :** 29 avril 2026
**Statut :** Approuvée

---

## Objectif

Implémenter le module **Actualités** du site TC2V Handball : gestion éditoriale complète dans Filament (brouillon / publié / archivé), affichage public sous forme de liste paginée et de pages de détail, avec support d'une image à la une et d'un contenu riche avec images insérables.

---

## Périmètre

Ce module couvre uniquement les **actualités** (modèle `News`). Les modules galerie et partenaires feront l'objet d'itérations dédiées.

---

## Stack et dépendances

- **Laravel 12** + **Inertia.js v3** + **Vue.js 3** + **Filament 3** (socle existant)
- **`awcodes/filament-tiptap-editor`** — éditeur TipTap avec upload d'images inline (nouvelle dépendance Composer)

> Note : si le module galerie ou photos d'équipes justifie `spatie/laravel-medialibrary`, la migration des images du module actualités sera envisagée à ce moment-là.

---

## Modèle de données

### Table `news`

| Colonne | Type | Contraintes | Description |
|---|---|---|---|
| `id` | bigint | PK, auto-increment | — |
| `title` | string | not null | Titre de l'article |
| `slug` | string | unique, not null | URL (`/actualites/{slug}`), auto-généré depuis le titre |
| `content` | longText | not null | Contenu HTML produit par TipTap |
| `featured_image` | string | nullable | Chemin relatif vers l'image à la une |
| `status` | enum | not null, default `draft` | `draft` / `published` / `archived` |
| `published_at` | timestamp | nullable | Auto-renseigné à la première publication |
| `created_at` | timestamp | — | — |
| `updated_at` | timestamp | — | — |

### Règles métier

- Seuls les articles au statut `published` sont visibles sur le site public.
- Les articles `archived` ne sont plus visibles (ni dans la liste, ni en accès direct par slug) — une tentative d'accès retourne une 404.
- `published_at` est défini automatiquement lors du **premier** passage au statut `published` via un observer Eloquent (`NewsObserver`). Il n'est jamais modifié ensuite (republier un article déjà publié ne change pas la date).
- Le slug est auto-généré depuis le titre à la création (via `Str::slug()`). Il est modifiable manuellement dans Filament avant la première publication.

---

## Back-office Filament

### `NewsResource` (`app/Filament/Resources/NewsResource.php`)

**Accès :** rôles `admin` et `super_admin` uniquement (Filament shield ou politique de panel).

#### Vue liste (table)

| Colonne affichée | Détail |
|---|---|
| Titre | Lien vers le formulaire d'édition |
| Statut | Badge coloré : gris = brouillon, vert = publié, rouge = archivé |
| Date de publication | Formatée (`d/m/Y`), vide si brouillon |

Fonctionnalités :
- **Filtre** par statut (brouillon / publié / archivé)
- **Recherche** par titre
- **Actions de ligne** : éditer, supprimer (avec confirmation)

#### Formulaire création / édition

Champs dans l'ordre d'affichage :

1. **`title`** — `TextInput`, requis. Déclenche la génération du slug en live si le slug n'a pas encore été modifié manuellement.
2. **`slug`** — `TextInput`, requis, unique. Pré-rempli automatiquement, modifiable manuellement.
3. **`status`** — `Select`, options : Brouillon / Publié / Archivé. Valeur par défaut : Brouillon.
4. **`featured_image`** — `FileUpload`, accepte JPEG/PNG/WebP, stocké dans `storage/app/public/news/featured/`, nullable.
5. **`content`** — `TiptapEditor` (package `awcodes/filament-tiptap-editor`), requis. L'upload d'images inline est activé, stocké dans `storage/app/public/news/content/`.

---

## Backend Laravel

### Observer `NewsObserver` (`app/Observers/NewsObserver.php`)

- Écoute l'événement `saving` sur le modèle `News`.
- Si `status` passe à `published` **et** que `published_at` est null → définit `published_at = now()`.
- Enregistré dans `AppServiceProvider`.

### Controller `ActualitesController` (`app/Http/Controllers/ActualitesController.php`)

```php
// index() : articles publiés, triés par published_at DESC, paginés (12 par page)
// show(string $slug) : article publié par slug, 404 sinon
```

**`index()`**
- Récupère les `News` où `status = published`, triés par `published_at DESC`, paginés à 12 par page.
- Retourne `Inertia::render('Actualites/Index', ['articles' => $articles])`.

**`show(string $slug)`**
- Récupère le `News` par slug, vérifie que `status = published` (abort 404 sinon).
- Retourne `Inertia::render('Actualites/Show', ['article' => $article])`.

### Routes (`routes/web.php`)

```php
Route::get('/actualites', [ActualitesController::class, 'index'])->name('actualites.index');
Route::get('/actualites/{slug}', [ActualitesController::class, 'show'])->name('actualites.show');
```

---

## Frontend Vue.js

### `resources/js/pages/Actualites/Index.vue`

- **Layout :** `MainLayout.vue`
- **Affichage :** grille de cartes responsive (1 colonne mobile, 2-3 colonnes desktop)
- **Carte article :** image à la une (ou placeholder si absente), titre, date (`published_at`), début du contenu tronqué (~150 caractères, HTML strippé)
- **Lien :** chaque carte pointe vers `/actualites/{slug}` (lien Inertia)
- **Pagination :** composant de pagination Inertia (liens fournis par Laravel)

### `resources/js/pages/Actualites/Show.vue`

- **Layout :** `MainLayout.vue`
- **Affichage :**
  - Image à la une en en-tête de page (si présente)
  - Titre (`h1`)
  - Date de publication formatée
  - Contenu HTML rendu (`v-html`) — issu de TipTap, contenu interne contrôlé (pas de risque XSS tiers)
  - Lien "← Toutes les actualités" vers `/actualites`

---

## Stockage des images

| Type d'image | Chemin de stockage | Disk Laravel |
|---|---|---|
| Image à la une | `storage/app/public/news/featured/` | `public` |
| Images inline (contenu) | `storage/app/public/news/content/` | `public` |

Le lien symbolique `public/storage → storage/app/public` est déjà créé par `php artisan storage:link` (socle itération 1).

Les images sont servies directement par PHP (OVH) via leur URL publique.

---

## Tests

- **`NewsObserver`** : test unitaire vérifiant que `published_at` est défini à la première publication et non modifié lors d'une republication.
- **`ActualitesController`** : tests feature vérifiant :
  - La liste ne retourne que les articles `published`
  - Un slug d'article `draft` retourne 404
  - Un slug d'article `archived` retourne 404
  - Un slug valide retourne 200

---

## Ce qui est hors périmètre

- Catégories ou tags d'articles (pas dans la spec)
- Auteur affiché sur l'article (pas dans la spec)
- Commentaires
- Flux RSS
- Planification différée (publication à une date future)
- Migration vers `spatie/laravel-medialibrary` (réservé à l'itération galerie)
