# Iteration 3 — Module Partenaires — Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Ajouter le module Partenaires — gestion des sponsors dans Filament avec réordonnancement drag-and-drop, et affichage public sur `/partenaires`.

**Architecture:** Modèle `Partner` avec `sort_order` géré par un observer (`creating`) et mis à jour via le mécanisme natif Filament (`->reorderable('sort_order')`). Affichage public via `PartenairesController` → Inertia → `Partenaires.vue`.

**Tech Stack:** Laravel 12, Filament 3, Inertia.js v3, Vue.js 3, PHPUnit

---

## File Structure

| Fichier | Action | Rôle |
|---|---|---|
| `database/migrations/2026_04_30_000000_create_partners_table.php` | Créer | Table `partners` |
| `app/Models/Partner.php` | Créer | Modèle Eloquent |
| `database/factories/PartnerFactory.php` | Créer | Factory pour tests |
| `tests/Unit/Models/PartnerTest.php` | Créer | Tests unitaires du modèle |
| `app/Observers/PartnerObserver.php` | Créer | Auto-incrémente `sort_order` à la création |
| `app/Providers/AppServiceProvider.php` | Modifier | Enregistrer `PartnerObserver` |
| `tests/Unit/Observers/PartnerObserverTest.php` | Créer | Tests de l'observer |
| `app/Filament/Resources/PartnerResource.php` | Créer | Resource Filament (form + table + drag-and-drop) |
| `app/Filament/Resources/PartnerResource/Pages/ListPartners.php` | Créer | Page liste Filament |
| `app/Filament/Resources/PartnerResource/Pages/CreatePartner.php` | Créer | Page création Filament |
| `app/Filament/Resources/PartnerResource/Pages/EditPartner.php` | Créer | Page édition Filament |
| `tests/Feature/Admin/PartnerResourceTest.php` | Créer | Tests accès admin Filament |
| `app/Http/Controllers/PartenairesController.php` | Créer | Contrôleur public |
| `routes/web.php` | Modifier | Route `/partenaires` |
| `tests/Feature/PartenairesControllerTest.php` | Créer | Tests du contrôleur public |
| `resources/js/pages/Partenaires.vue` | Créer | Page Vue publique |

---

## Task 1 : Migration, modèle et factory

**Files:**
- Create: `database/migrations/2026_04_30_000000_create_partners_table.php`
- Create: `app/Models/Partner.php`
- Create: `database/factories/PartnerFactory.php`
- Create: `tests/Unit/Models/PartnerTest.php`

- [ ] **Step 1 : Écrire le test du modèle (doit échouer)**

Créer `tests/Unit/Models/PartnerTest.php` :

```php
<?php

namespace Tests\Unit\Models;

use App\Models\Partner;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PartnerTest extends TestCase
{
    use RefreshDatabase;

    public function test_partner_can_be_created_with_required_fields(): void
    {
        $partner = Partner::factory()->create([
            'name' => 'Sponsor Principal',
            'sort_order' => 1,
        ]);

        $this->assertDatabaseHas('partners', [
            'name' => 'Sponsor Principal',
            'sort_order' => 1,
        ]);
    }

    public function test_optional_fields_are_nullable(): void
    {
        $partner = Partner::factory()->create([
            'logo' => null,
            'url' => null,
            'description' => null,
        ]);

        $this->assertNull($partner->logo);
        $this->assertNull($partner->url);
        $this->assertNull($partner->description);
    }
}
```

- [ ] **Step 2 : Exécuter le test pour vérifier qu'il échoue**

```bash
php artisan test tests/Unit/Models/PartnerTest.php
```

Résultat attendu : FAIL — `Class "App\Models\Partner" not found` ou `Table partners doesn't exist`

- [ ] **Step 3 : Créer la migration**

Créer `database/migrations/2026_04_30_000000_create_partners_table.php` :

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('partners', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('logo')->nullable();
            $table->string('url')->nullable();
            $table->text('description')->nullable();
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('partners');
    }
};
```

- [ ] **Step 4 : Créer le modèle**

Créer `app/Models/Partner.php` :

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Partner extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'logo',
        'url',
        'description',
        'sort_order',
    ];
}
```

- [ ] **Step 5 : Créer la factory**

Créer `database/factories/PartnerFactory.php` :

```php
<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class PartnerFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => $this->faker->company(),
            'logo' => null,
            'url' => $this->faker->url(),
            'description' => $this->faker->sentence(),
            'sort_order' => $this->faker->numberBetween(1, 100),
        ];
    }
}
```

- [ ] **Step 6 : Exécuter la migration et les tests**

```bash
php artisan migrate
php artisan test tests/Unit/Models/PartnerTest.php
```

Résultat attendu : 2 tests, 2 assertions — PASS

- [ ] **Step 7 : Commit**

```bash
git add database/migrations/2026_04_30_000000_create_partners_table.php \
        app/Models/Partner.php \
        database/factories/PartnerFactory.php \
        tests/Unit/Models/PartnerTest.php
git commit -m "feat: add Partner model, migration and factory"
```

---

## Task 2 : Observer PartnerObserver

**Files:**
- Create: `app/Observers/PartnerObserver.php`
- Modify: `app/Providers/AppServiceProvider.php`
- Create: `tests/Unit/Observers/PartnerObserverTest.php`

- [ ] **Step 1 : Écrire le test de l'observer (doit échouer)**

Créer `tests/Unit/Observers/PartnerObserverTest.php` :

```php
<?php

namespace Tests\Unit\Observers;

use App\Models\Partner;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PartnerObserverTest extends TestCase
{
    use RefreshDatabase;

    public function test_sort_order_is_auto_incremented_on_creation(): void
    {
        Partner::factory()->create(['sort_order' => 1]);
        Partner::factory()->create(['sort_order' => 2]);

        $partner = Partner::create(['name' => 'Nouveau Partenaire']);

        $this->assertEquals(3, $partner->fresh()->sort_order);
    }

    public function test_sort_order_is_set_to_1_when_no_partners_exist(): void
    {
        $partner = Partner::create(['name' => 'Premier Partenaire']);

        $this->assertEquals(1, $partner->fresh()->sort_order);
    }

    public function test_sort_order_is_not_overridden_when_explicitly_set(): void
    {
        Partner::factory()->create(['sort_order' => 5]);

        $partner = Partner::create(['name' => 'Partenaire Positionné', 'sort_order' => 10]);

        $this->assertEquals(10, $partner->fresh()->sort_order);
    }
}
```

- [ ] **Step 2 : Exécuter les tests pour vérifier qu'ils échouent**

```bash
php artisan test tests/Unit/Observers/PartnerObserverTest.php
```

Résultat attendu : FAIL — `test_sort_order_is_auto_incremented_on_creation` échoue (sort_order reste 0 ou null)

- [ ] **Step 3 : Créer l'observer**

Créer `app/Observers/PartnerObserver.php` :

```php
<?php

namespace App\Observers;

use App\Models\Partner;

class PartnerObserver
{
    public function creating(Partner $partner): void
    {
        if (empty($partner->sort_order)) {
            $partner->sort_order = Partner::max('sort_order') + 1;
        }
    }
}
```

- [ ] **Step 4 : Enregistrer l'observer dans AppServiceProvider**

Modifier `app/Providers/AppServiceProvider.php` :

```php
<?php

namespace App\Providers;

use App\Models\News;
use App\Models\Partner;
use App\Observers\NewsObserver;
use App\Observers\PartnerObserver;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void {}

    public function boot(): void
    {
        News::observe(NewsObserver::class);
        Partner::observe(PartnerObserver::class);
    }
}
```

- [ ] **Step 5 : Exécuter les tests**

```bash
php artisan test tests/Unit/Observers/PartnerObserverTest.php
```

Résultat attendu : 3 tests, 3 assertions — PASS

- [ ] **Step 6 : Vérifier que les tests existants passent toujours**

```bash
php artisan test
```

Résultat attendu : tous les tests passent

- [ ] **Step 7 : Commit**

```bash
git add app/Observers/PartnerObserver.php \
        app/Providers/AppServiceProvider.php \
        tests/Unit/Observers/PartnerObserverTest.php
git commit -m "feat: add PartnerObserver to auto-increment sort_order on creation"
```

---

## Task 3 : PartnerResource Filament

**Files:**
- Create: `app/Filament/Resources/PartnerResource.php`
- Create: `app/Filament/Resources/PartnerResource/Pages/ListPartners.php`
- Create: `app/Filament/Resources/PartnerResource/Pages/CreatePartner.php`
- Create: `app/Filament/Resources/PartnerResource/Pages/EditPartner.php`
- Create: `tests/Feature/Admin/PartnerResourceTest.php`

- [ ] **Step 1 : Écrire les tests d'accès Filament (doivent échouer)**

Créer `tests/Feature/Admin/PartnerResourceTest.php` :

```php
<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PartnerResourceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
    }

    public function test_admin_can_access_partners_list(): void
    {
        $user = User::factory()->create();
        $user->assignRole('admin');

        $this->actingAs($user)
            ->get('/admin/partners')
            ->assertSuccessful();
    }

    public function test_super_admin_can_access_partners_list(): void
    {
        $user = User::factory()->create();
        $user->assignRole('super_admin');

        $this->actingAs($user)
            ->get('/admin/partners')
            ->assertSuccessful();
    }

    public function test_unauthenticated_user_cannot_access_partners_list(): void
    {
        $this->get('/admin/partners')->assertRedirect('/admin/login');
    }

    public function test_authenticated_user_without_role_cannot_access_partners_list(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get('/admin/partners')
            ->assertForbidden();
    }
}
```

- [ ] **Step 2 : Exécuter les tests pour vérifier qu'ils échouent**

```bash
php artisan test tests/Feature/Admin/PartnerResourceTest.php
```

Résultat attendu : FAIL — `/admin/partners` retourne 404 (resource non existante)

- [ ] **Step 3 : Créer les pages Filament**

Créer `app/Filament/Resources/PartnerResource/Pages/ListPartners.php` :

```php
<?php

namespace App\Filament\Resources\PartnerResource\Pages;

use App\Filament\Resources\PartnerResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListPartners extends ListRecords
{
    protected static string $resource = PartnerResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\CreateAction::make()];
    }
}
```

Créer `app/Filament/Resources/PartnerResource/Pages/CreatePartner.php` :

```php
<?php

namespace App\Filament\Resources\PartnerResource\Pages;

use App\Filament\Resources\PartnerResource;
use Filament\Resources\Pages\CreateRecord;

class CreatePartner extends CreateRecord
{
    protected static string $resource = PartnerResource::class;
}
```

Créer `app/Filament/Resources/PartnerResource/Pages/EditPartner.php` :

```php
<?php

namespace App\Filament\Resources\PartnerResource\Pages;

use App\Filament\Resources\PartnerResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditPartner extends EditRecord
{
    protected static string $resource = PartnerResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\DeleteAction::make()];
    }
}
```

- [ ] **Step 4 : Créer PartnerResource**

Créer `app/Filament/Resources/PartnerResource.php` :

```php
<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PartnerResource\Pages;
use App\Models\Partner;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class PartnerResource extends Resource
{
    protected static ?string $model = Partner::class;
    protected static ?string $navigationIcon = 'heroicon-o-building-office';
    protected static ?string $navigationLabel = 'Partenaires';
    protected static ?string $modelLabel = 'Partenaire';
    protected static ?string $pluralModelLabel = 'Partenaires';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\TextInput::make('name')
                ->label('Nom')
                ->required(),

            Forms\Components\FileUpload::make('logo')
                ->label('Logo')
                ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp', 'image/svg+xml'])
                ->directory('partners/logos')
                ->disk('public')
                ->nullable(),

            Forms\Components\TextInput::make('url')
                ->label('Site web')
                ->url()
                ->nullable(),

            Forms\Components\Textarea::make('description')
                ->label('Description')
                ->nullable(),

            Forms\Components\TextInput::make('sort_order')
                ->label('Ordre')
                ->numeric()
                ->default(fn () => Partner::max('sort_order') + 1),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->reorderable('sort_order')
            ->columns([
                Tables\Columns\ImageColumn::make('logo')
                    ->label('Logo')
                    ->disk('public')
                    ->circular(),

                Tables\Columns\TextColumn::make('name')
                    ->label('Nom')
                    ->searchable(),

                Tables\Columns\TextColumn::make('url')
                    ->label('URL')
                    ->url(fn (?string $state): ?string => $state)
                    ->openUrlInNewTab(),

                Tables\Columns\TextColumn::make('sort_order')
                    ->label('Ordre')
                    ->sortable(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPartners::route('/'),
            'create' => Pages\CreatePartner::route('/create'),
            'edit' => Pages\EditPartner::route('/{record}/edit'),
        ];
    }
}
```

- [ ] **Step 5 : Exécuter les tests**

```bash
php artisan test tests/Feature/Admin/PartnerResourceTest.php
```

Résultat attendu : 4 tests, 4 assertions — PASS

- [ ] **Step 6 : Vérifier que tous les tests passent**

```bash
php artisan test
```

Résultat attendu : tous les tests passent

- [ ] **Step 7 : Commit**

```bash
git add app/Filament/Resources/PartnerResource.php \
        app/Filament/Resources/PartnerResource/Pages/ListPartners.php \
        app/Filament/Resources/PartnerResource/Pages/CreatePartner.php \
        app/Filament/Resources/PartnerResource/Pages/EditPartner.php \
        tests/Feature/Admin/PartnerResourceTest.php
git commit -m "feat: add PartnerResource Filament with drag-and-drop reordering"
```

---

## Task 4 : PartenairesController + route

**Files:**
- Create: `app/Http/Controllers/PartenairesController.php`
- Modify: `routes/web.php`
- Create: `tests/Feature/PartenairesControllerTest.php`

- [ ] **Step 1 : Écrire les tests du contrôleur (doivent échouer)**

Créer `tests/Feature/PartenairesControllerTest.php` :

```php
<?php

namespace Tests\Feature;

use App\Models\Partner;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class PartenairesControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_page_returns_200_with_inertia_component(): void
    {
        $response = $this->get('/partenaires');

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) =>
            $page->component('Partenaires')
        );
    }

    public function test_partenaires_are_sorted_by_sort_order_asc(): void
    {
        $third = Partner::factory()->create(['name' => 'C', 'sort_order' => 3]);
        $first = Partner::factory()->create(['name' => 'A', 'sort_order' => 1]);
        $second = Partner::factory()->create(['name' => 'B', 'sort_order' => 2]);

        $response = $this->get('/partenaires');

        $response->assertInertia(fn (Assert $page) =>
            $page->component('Partenaires')
                ->where('partenaires.0.id', $first->id)
                ->where('partenaires.1.id', $second->id)
                ->where('partenaires.2.id', $third->id)
        );
    }

    public function test_page_works_with_no_partners(): void
    {
        $response = $this->get('/partenaires');

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) =>
            $page->component('Partenaires')
                ->where('partenaires', [])
        );
    }
}
```

- [ ] **Step 2 : Exécuter les tests pour vérifier qu'ils échouent**

```bash
php artisan test tests/Feature/PartenairesControllerTest.php
```

Résultat attendu : FAIL — `test_page_returns_200_with_inertia_component` retourne 404 (route inconnue)

- [ ] **Step 3 : Créer le contrôleur**

Créer `app/Http/Controllers/PartenairesController.php` :

```php
<?php

namespace App\Http\Controllers;

use App\Models\Partner;
use Inertia\Inertia;
use Inertia\Response;

class PartenairesController extends Controller
{
    public function index(): Response
    {
        $partenaires = Partner::orderBy('sort_order')->get();

        return Inertia::render('Partenaires', [
            'partenaires' => $partenaires,
        ]);
    }
}
```

- [ ] **Step 4 : Ajouter la route**

Modifier `routes/web.php` :

```php
<?php

use App\Http\Controllers\ActualitesController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\PartenairesController;
use Illuminate\Support\Facades\Route;

Route::get('/', [HomeController::class, 'index']);

Route::get('/actualites', [ActualitesController::class, 'index'])->name('actualites.index');
Route::get('/actualites/{slug}', [ActualitesController::class, 'show'])->name('actualites.show');

Route::get('/partenaires', [PartenairesController::class, 'index'])->name('partenaires.index');
```

- [ ] **Step 5 : Exécuter les tests**

```bash
php artisan test tests/Feature/PartenairesControllerTest.php
```

Résultat attendu : 3 tests, 3+ assertions — PASS

- [ ] **Step 6 : Vérifier que tous les tests passent**

```bash
php artisan test
```

Résultat attendu : tous les tests passent

- [ ] **Step 7 : Commit**

```bash
git add app/Http/Controllers/PartenairesController.php \
        routes/web.php \
        tests/Feature/PartenairesControllerTest.php
git commit -m "feat: add PartenairesController and public route"
```

---

## Task 5 : Page Vue Partenaires.vue

**Files:**
- Create: `resources/js/pages/Partenaires.vue`

- [ ] **Step 1 : Créer le composant Vue**

Créer `resources/js/pages/Partenaires.vue` :

```vue
<template>
    <div>
        <h1>Nos Partenaires</h1>

        <p v-if="partenaires.length === 0">
            Aucun partenaire pour le moment.
        </p>

        <div v-else class="partners-grid">
            <div
                v-for="partner in partenaires"
                :key="partner.id"
                class="partner-card"
            >
                <a
                    v-if="partner.url"
                    :href="partner.url"
                    target="_blank"
                    rel="noopener noreferrer"
                >
                    <img
                        v-if="partner.logo"
                        :src="`/storage/${partner.logo}`"
                        :alt="partner.name"
                    />
                    <span v-else>{{ partner.name }}</span>
                </a>
                <template v-else>
                    <img
                        v-if="partner.logo"
                        :src="`/storage/${partner.logo}`"
                        :alt="partner.name"
                    />
                    <span v-else>{{ partner.name }}</span>
                </template>

                <p class="partner-name">{{ partner.name }}</p>
                <p v-if="partner.description" class="partner-description">
                    {{ partner.description }}
                </p>
            </div>
        </div>
    </div>
</template>

<script setup>
defineProps({
    partenaires: {
        type: Array,
        required: true,
    },
});
</script>

<style scoped>
.partners-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 1.5rem;
}

@media (min-width: 768px) {
    .partners-grid {
        grid-template-columns: repeat(3, 1fr);
    }
}

@media (min-width: 1024px) {
    .partners-grid {
        grid-template-columns: repeat(4, 1fr);
    }
}

.partner-card {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 0.5rem;
    padding: 1rem;
    border: 1px solid #e5e7eb;
    border-radius: 0.5rem;
}

.partner-card img {
    max-width: 120px;
    max-height: 80px;
    object-fit: contain;
}

.partner-name {
    font-weight: 600;
    text-align: center;
}

.partner-description {
    font-size: 0.875rem;
    color: #6b7280;
    text-align: center;
}
</style>
```

- [ ] **Step 2 : Vérifier que les tests du contrôleur passent toujours**

Les tests Inertia vérifient le composant côté PHP (nom du composant dans la réponse JSON). Le fichier Vue n'est pas requis pour que les tests passent — mais vérifier qu'aucune régression n'est introduite :

```bash
php artisan test
```

Résultat attendu : tous les tests passent

- [ ] **Step 3 : Commit**

```bash
git add resources/js/pages/Partenaires.vue
git commit -m "feat: add Partenaires public page (Vue component)"
```

---

## Vérification finale

- [ ] **Lancer la suite complète de tests**

```bash
php artisan test
```

Résultat attendu : tous les tests passent (aucune régression)

- [ ] **Utiliser superpowers:finishing-a-development-branch pour finaliser**
