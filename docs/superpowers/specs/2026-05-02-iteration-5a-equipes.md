# Spécification — Itération 5a : Module Équipes

**Date :** 2 mai 2026
**Périmètre :** Saisons, catégories, équipes, joueurs (+ import CSV) — pages publiques `/equipes` et `/equipes/:slug`
**Hors périmètre :** Intégration Scorenco (itération 6), Venue + TrainingSession (itération 5b), formulaire d'essai

---

## Objectif

Permettre aux visiteurs de consulter les catégories de la saison courante, les équipes et les joueurs de chaque catégorie. Permettre aux admins de gérer saisons, catégories, équipes et joueurs via Filament, avec import CSV depuis l'export fédération FFHandball.

---

## Modèles de données

### `Season` — `seasons`

| Colonne | Type | Notes |
|---|---|---|
| `id` | bigint PK | |
| `name` | string | Ex : "2026-2027" |
| `starts_at` | date | |
| `ends_at` | date | |
| `is_current` | boolean | default false — un seul true à la fois |
| `timestamps` | | |

Un observer `SeasonObserver` écoute `updating` : quand `is_current` passe à `true`, il met `is_current = false` sur toutes les autres saisons.

### `Category` — `categories`

| Colonne | Type | Notes |
|---|---|---|
| `id` | bigint PK | |
| `season_id` | FK → seasons | cascade delete |
| `name` | string | Ex : "U13 Masculins" |
| `slug` | string | unique, auto-généré depuis `name` |
| `gender` | enum | `M`, `F`, `Mixte` |
| `birth_year_min` | integer | Année de naissance min éligible |
| `birth_year_max` | integer | Année de naissance max éligible |
| `timestamps` | | |

### `Team` — `teams`

| Colonne | Type | Notes |
|---|---|---|
| `id` | bigint PK | |
| `category_id` | FK → categories | cascade delete |
| `name` | string | Ex : "Équipe 1" |
| `photo` | string | nullable, chemin stockage public |
| `scorenco_id` | string | nullable, pour itération 6 |
| `timestamps` | | |

### `Player` — `players`

| Colonne | Type | Notes |
|---|---|---|
| `id` | bigint PK | |
| `category_id` | FK → categories | nullable, setNull on delete |
| `last_name` | string | |
| `first_name` | string | |
| `birth_date` | date | |
| `gender` | string | nullable, valeur brute depuis CSV |
| `license_number` | string | nullable |
| `photo` | string | nullable, chemin stockage public |
| `has_image_rights` | boolean | default false |
| `timestamps` | | |

Contrainte unique : `(last_name, first_name, birth_date)`.

---

## Relations

```
Season ──hasMany──► Category ──hasMany──► Team
                        │
                        └──hasMany──► Player
```

- `Season::categories()` → hasMany Category
- `Category::teams()` → hasMany Team
- `Category::players()` → hasMany Player, orderBy last_name
- `Category::season()` → belongsTo Season
- `Team::category()` → belongsTo Category
- `Player::category()` → belongsTo Category (nullable)

---

## Observer

**`SeasonObserver`** — méthode `updating` :

```php
public function updating(Season $season): void
{
    if ($season->isDirty('is_current') && $season->is_current) {
        Season::where('id', '!=', $season->id)->update(['is_current' => false]);
    }
}
```

Enregistré dans `AppServiceProvider::boot()`.

---

## Filament

Quatre resources dans le groupe de navigation **"Équipes"**.

### `SeasonResource`

- Liste : colonnes name, starts_at, ends_at, badge is_current
- Action de table **"Définir comme saison courante"** (visible uniquement si `!is_current`) → `$record->update(['is_current' => true])`
- Formulaire : name (required), starts_at, ends_at, is_current (toggle)

### `CategoryResource`

- Liste : colonnes name, gender, birth_year_min–birth_year_max, saison (relation)
- Filtre par saison (SelectFilter)
- Formulaire : season_id (select required), name (required), slug (auto depuis name, modifiable), gender (select M/F/Mixte required), birth_year_min (required), birth_year_max (required)
- Le slug est généré automatiquement avec `Str::slug($name)` lors de la création si laissé vide

### `TeamResource`

- Liste : colonnes name, category (relation), scorenco_id
- Filtre par catégorie (SelectFilter)
- Formulaire : category_id (select required), name (required), photo (FileUpload, directory: `teams`, disk: `public`), scorenco_id (nullable)

### `PlayerResource`

- Liste : colonnes last_name, first_name, birth_date, gender, category (relation), has_image_rights (badge)
- Filtres : catégorie (SelectFilter), has_image_rights (TernaryFilter)
- Recherche : last_name, first_name
- Formulaire : category_id (select nullable), last_name (required), first_name (required), birth_date (required), gender (nullable), license_number (nullable), photo (FileUpload, directory: `players`, disk: `public`), has_image_rights (Toggle)
- **Header action "Importer CSV"** → voir section Import CSV

---

## Import CSV

### Déclenchement

Bouton "Importer CSV" dans le header de `PlayerResource::ListPlayers`. Ouvre un modal avec un `FileUpload` (`.csv` uniquement). À la soumission, la méthode `handle()` de l'action traite le fichier.

### Format CSV attendu

Séparateur `;`. Colonnes utilisées : `Nom`, `Prenom`, `Né(e) le`, `sexe`, `Numero Licence`, `DroitImage`. Les autres colonnes sont ignorées.

### Algorithme

```
Pour chaque ligne du CSV :
  1. Extraire last_name=Nom, first_name=Prenom, birth_date=Né(e) le,
     gender=sexe, license_number=Numero Licence, droitImage=DroitImage

  2. Vérifier doublon : Player::where(last_name, first_name, birth_date)->exists()
     → si oui : $skipped++ ; continuer

  3. Calculer category_id :
     - Extraire birth_year depuis birth_date
     - Chercher Category de la saison courante (is_current=true)
       où birth_year_min <= birth_year <= birth_year_max
       ET gender correspond (M/F) ou genre = Mixte
     → si trouvée : $category_id = $found->id
     → sinon : $category_id = null ; $unmatched++

  4. has_image_rights : DroitImage in ['Oui', 'O', '1', 'oui'] → true, sinon false

  5. Player::create([...])
     $imported++

Afficher notification : "$imported importés · $skipped doublons ignorés · $unmatched sans catégorie"
```

### Correspondance genre CSV → catégorie

| Valeur CSV `sexe` | Catégories éligibles |
|---|---|
| `M` ou `Masculin` | gender = `M` ou `Mixte` |
| `F` ou `Féminin` | gender = `F` ou `Mixte` |
| Autre / vide | gender = `Mixte` uniquement |

---

## Controller et routes

### `EquipesController`

```php
public function index(): Response
{
    $saison = Season::where('is_current', true)->firstOrFail();
    $categories = $saison->categories()->orderBy('gender')->orderBy('name')->get();

    return Inertia::render('Equipes/Index', [
        'categories' => $categories,
    ]);
}

public function show(string $slug): Response
{
    $saison = Season::where('is_current', true)->firstOrFail();
    $category = $saison->categories()->where('slug', $slug)->firstOrFail();

    $teams = $category->teams()->get();
    $players = $category->players()->orderBy('last_name')->orderBy('first_name')->get();

    return Inertia::render('Equipes/Show', [
        'category' => $category,
        'teams'    => $teams,
        'players'  => $players,
    ]);
}
```

### Routes (`routes/web.php`)

```php
Route::get('/equipes', [EquipesController::class, 'index'])->name('equipes.index');
Route::get('/equipes/{slug}', [EquipesController::class, 'show'])->name('equipes.show');
```

---

## Pages Vue

### `Equipes/Index.vue`

Props : `categories` (Array)

Affiche une grille de cartes — une par catégorie. Chaque carte affiche le nom de la catégorie, le genre (badge), et un lien vers `/equipes/{slug}`. Message vide si aucune catégorie.

### `Equipes/Show.vue`

Props : `category` (Object), `teams` (Array), `players` (Array)

Structure :
1. `<h1>` avec `category.name`
2. Section **Équipes** : grille de cartes `Team` — photo (ou placeholder), nom de l'équipe
3. Section **Joueurs** : grille de cartes `Player` — si `has_image_rights = true` et `photo` présente → `<img>` depuis `/storage/...` ; sinon → avatar placeholder initial. Nom affiché sous la photo.

Style cohérent avec `Bureau.vue` et `Commissions.vue` (staff-card, staff-photo, staff-avatar-placeholder).

---

## Tests

### Unitaires

- `SeasonTest` : création, flag is_current
- `CategoryTest` : création, relation season, relation players
- `TeamTest` : création, relation category
- `PlayerTest` : création, champs nullable, has_image_rights

### Feature — Observer

- `SeasonObserverTest` : quand une saison devient is_current, les autres passent à false

### Feature — Import CSV

- `PlayerImportTest` :
  - Import de lignes valides → joueurs créés avec bonne catégorie
  - Doublon → ignoré, pas de doublon en base
  - Joueur sans catégorie correspondante → category_id null
  - DroitImage "Oui" → has_image_rights true ; "Non" → false

### Feature — Controller

- `EquipesControllerTest` :
  - index() : 404 si pas de saison courante ; retourne les catégories de la saison courante
  - show() : 404 si slug inconnu ; retourne category + teams + players ; players ordonnés par nom
  - show() : 404 si catégorie d'une autre saison

### Feature — Admin

- `SeasonResourceTest` : accès admin/super_admin, refus sans rôle
- `CategoryResourceTest` : idem
- `TeamResourceTest` : idem
- `PlayerResourceTest` : idem
