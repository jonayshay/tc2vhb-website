# Spécification — Itération 4b : Module Le Club (Bureau & CA + Commissions)

**Date :** 1er mai 2026
**Statut :** Approuvée

---

## Objectif

Implémenter la deuxième partie du module **Le Club** : page Bureau & Conseil d'Administration (`BoardMember`, liste plate ordonnée) et pages Commissions (`Commission` + `CommissionMember` imbriqués).

---

## Périmètre

Ce module couvre :
- `BoardMember` — membres du bureau et CA, liste plate ordonnée avec drag-and-drop dans Filament
- `Commission` — commissions du club, ordonnées
- `CommissionMember` — membres d'une commission, gérés via Repeater dans le formulaire de leur commission

Hors périmètre : galerie photos, équipes, infos pratiques.

---

## Stack et dépendances

- **Laravel 12** + **Inertia.js v3** + **Vue.js 3** + **Filament 3** (socle existant)
- Aucune nouvelle dépendance

---

## Modèles de données

### Table `board_members`

| Colonne | Type | Contraintes | Description |
|---|---|---|---|
| `id` | bigint | PK, auto-increment | — |
| `name` | string | not null | Nom complet |
| `role` | string | not null | Rôle — ex. "Président", "Trésorier" |
| `bio` | text | nullable | Bio courte |
| `photo` | string | nullable | Chemin relatif, disk `public` |
| `sort_order` | int | not null, default 0 | Ordre d'affichage |
| `created_at` | timestamp | — | — |
| `updated_at` | timestamp | — | — |

### Table `commissions`

| Colonne | Type | Contraintes | Description |
|---|---|---|---|
| `id` | bigint | PK, auto-increment | — |
| `name` | string | not null | Nom de la commission |
| `description` | text | nullable | Description |
| `sort_order` | int | not null, default 0 | Ordre d'affichage |
| `created_at` | timestamp | — | — |
| `updated_at` | timestamp | — | — |

### Table `commission_members`

| Colonne | Type | Contraintes | Description |
|---|---|---|---|
| `id` | bigint | PK, auto-increment | — |
| `commission_id` | bigint | FK → commissions.id, cascade delete | — |
| `name` | string | not null | Nom complet |
| `role` | string | not null | Rôle au sein de la commission |
| `bio` | text | nullable | Bio courte |
| `photo` | string | nullable | Chemin relatif, disk `public` |
| `sort_order` | int | not null, default 0 | Ordre d'affichage |
| `created_at` | timestamp | — | — |
| `updated_at` | timestamp | — | — |

---

## Back-office Filament

### `BoardMemberResource`

**Fichier :** `app/Filament/Resources/BoardMemberResource.php`

Accès : rôles `admin` et `super_admin`.

**Formulaire :**
1. `name` — TextInput, requis
2. `role` — TextInput, requis
3. `bio` — Textarea, nullable, `->columnSpanFull()`
4. `photo` — FileUpload, nullable, `image/jpeg` + `image/png` + `image/webp`, `->image()`, `->maxSize(2048)`, dossier `board/`, disk `public`

**Table :**
- Colonnes : photo (ImageColumn, `->circular()`), nom (searchable), rôle, sort_order
- `->reorderable('sort_order')` pour le drag-and-drop
- Actions : EditAction, DeleteAction

**Pages :** ListBoardMembers (avec CreateAction), CreateBoardMember, EditBoardMember (avec DeleteAction).

**Observer `BoardMemberObserver`** : `creating` — si `sort_order === null || sort_order === 0`, auto-incrément à `BoardMember::max('sort_order') + 1`.

### `CommissionResource`

**Fichier :** `app/Filament/Resources/CommissionResource.php`

Accès : rôles `admin` et `super_admin`.

**Formulaire :**
1. `name` — TextInput, requis
2. `description` — Textarea, nullable, `->columnSpanFull()`
3. `members` — Repeater (`->relationship('members')->orderBy('sort_order')`), `->reorderableWithDragAndDrop()`, `->columnSpanFull()`, avec les champs :
   - `name` — TextInput, requis
   - `role` — TextInput, requis
   - `bio` — Textarea, nullable
   - `photo` — FileUpload, nullable, `image/jpeg` + `image/png` + `image/webp`, `->image()`, `->maxSize(2048)`, dossier `commissions/`, disk `public`

**Table :**
- Colonnes : nom (searchable), description (tronquée, `->limit(60)`), nombre de membres (`->counts('members')` → TextColumn)
- `->reorderable('sort_order')` pour le drag-and-drop
- Actions : EditAction, DeleteAction

**Pages :** ListCommissions (avec CreateAction), CreateCommission, EditCommission (avec DeleteAction).

**Pas d'observer** pour Commission ni CommissionMember — le sort_order des commissions est géré via drag-and-drop depuis la liste. Les membres sont créés via le Repeater et leur sort_order est géré par `->reorderableWithDragAndDrop()`.

---

## Backend Laravel

### Modèles

**`BoardMember`** (`app/Models/BoardMember.php`)
- `HasFactory`
- `$fillable` : `['name', 'role', 'bio', 'photo', 'sort_order']`

**`Commission`** (`app/Models/Commission.php`)
- `HasFactory`
- `$fillable` : `['name', 'description', 'sort_order']`
- Relation : `hasMany(CommissionMember::class)`

**`CommissionMember`** (`app/Models/CommissionMember.php`)
- `HasFactory`
- `$fillable` : `['commission_id', 'name', 'role', 'bio', 'photo', 'sort_order']`
- Relation : `belongsTo(Commission::class)`

### Observer `BoardMemberObserver`

**Fichier :** `app/Observers/BoardMemberObserver.php`

```php
public function creating(BoardMember $boardMember): void
{
    if ($boardMember->sort_order === null || $boardMember->sort_order === 0) {
        $boardMember->sort_order = (BoardMember::max('sort_order') ?? 0) + 1;
    }
}
```

Enregistré dans `AppServiceProvider::boot()`.

### Contrôleur `LeClubController`

**2 nouvelles méthodes** ajoutées au contrôleur existant `app/Http/Controllers/LeClubController.php` :

**`bureau()`**
```php
public function bureau(): Response
{
    $membres = BoardMember::orderBy('sort_order')->get();
    return Inertia::render('LeClub/Bureau', ['membres' => $membres]);
}
```

**`commissions()`**
```php
public function commissions(): Response
{
    $commissions = Commission::with(['members' => fn($q) => $q->orderBy('sort_order')])
        ->orderBy('sort_order')
        ->get();
    return Inertia::render('LeClub/Commissions', ['commissions' => $commissions]);
}
```

### Routes (`routes/web.php`)

Ajout de 2 routes au groupe existant `/le-club` :

```php
Route::get('/le-club/bureau', [LeClubController::class, 'bureau'])->name('le-club.bureau');
Route::get('/le-club/commissions', [LeClubController::class, 'commissions'])->name('le-club.commissions');
```

---

## Frontend Vue.js

### `resources/js/pages/LeClub/Index.vue` (mise à jour)

Ajout des liens vers `/le-club/bureau` et `/le-club/commissions` dans la nav existante.

### `resources/js/pages/LeClub/Bureau.vue`

- Prop : `membres` (Array)
- Message vide si `membres.length === 0`
- Grille responsive 2→3→4 colonnes (même pattern qu'`Entraineurs.vue`)
- Carte membre : photo (`/storage/{photo}`) ou placeholder initiale (premier caractère du nom, cercle #7C878E, 80×80px), nom, rôle, bio si présente

### `resources/js/pages/LeClub/Commissions.vue`

- Prop : `commissions` (Array de `{ id, name, description, members: [...] }`)
- Message vide si `commissions.length === 0`
- Pour chaque commission : `<h2>` nom, description si présente, grille de membres
- Grille membres : même structure que Bureau (photo/placeholder, nom, rôle, bio)

---

## Stockage des fichiers

| Type | Dossier | Disk |
|---|---|---|
| Photos bureau | `storage/app/public/board/` | `public` |
| Photos commissions | `storage/app/public/commissions/` | `public` |

---

## Tests

### `BoardMemberTest` (Unit)
- Un `BoardMember` peut être créé avec les champs requis
- `bio` et `photo` sont nullables

### `BoardMemberObserverTest` (Feature)
- `sort_order` est auto-incrémenté si non fourni
- `sort_order` est préservé si fourni

### `CommissionTest` (Unit)
- Une `Commission` peut être créée avec les champs requis
- La relation `hasMany(CommissionMember)` fonctionne

### `CommissionMemberTest` (Unit)
- Un `CommissionMember` peut être créé avec les champs requis
- La relation `belongsTo(Commission)` fonctionne
- Cascade delete : supprimer une Commission supprime ses members

### `BoardMemberResourceTest` (Feature/Admin)
- Admin et super_admin peuvent accéder à `/admin/board-members`
- Unauthenticated → redirect login
- Sans rôle → 403

### `CommissionResourceTest` (Feature/Admin)
- Admin et super_admin peuvent accéder à `/admin/commissions`
- Unauthenticated → redirect login
- Sans rôle → 403

### `LeClubControllerTest` — ajouts (Feature)
- `/le-club/bureau` retourne 200 avec composant `LeClub/Bureau` et prop `membres`
- `/le-club/commissions` retourne 200 avec composant `LeClub/Commissions` et prop `commissions`
- Les membres d'une commission sont triés par `sort_order`
- Les commissions sont triées par `sort_order`

---

## Ce qui est hors périmètre

- Lien entre BoardMember / CommissionMember et un compte User — non pertinent (pas de comptes individuels membres)
- Galerie photos, équipes, infos pratiques — itérations suivantes
