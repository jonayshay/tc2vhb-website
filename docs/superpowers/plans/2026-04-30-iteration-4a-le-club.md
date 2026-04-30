# Iteration 4a — Module Le Club (Présentation + Staff) — Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Ajouter le module Le Club : page de présentation du club (contenu singleton géré dans Filament) et pages Entraîneurs / Arbitres (modèle `StaffMember` groupé par catégorie prédéfinie).

**Architecture:** `ClubPresentation` est un singleton Eloquent édité via une page Filament custom (`ManageClubPresentation`). `StaffMember` est un modèle standard avec une colonne JSON `categories` (liste prédéfinie ordonnée de 14 slugs). Le regroupement par catégorie est calculé côté PHP dans `LeClubController` avant d'être passé à Inertia.

**Tech Stack:** Laravel 12, Filament 3, Inertia.js v3, Vue.js 3, PHPUnit, TipTap (awcodes/filament-tiptap-editor déjà installé)

---

## File Structure

| Fichier | Action | Rôle |
|---|---|---|
| `database/migrations/2026_04_30_000001_create_club_presentations_table.php` | Créer | Table `club_presentations` |
| `app/Models/ClubPresentation.php` | Créer | Modèle singleton |
| `database/factories/ClubPresentationFactory.php` | Créer | Factory pour tests |
| `database/seeders/ClubPresentationSeeder.php` | Créer | Seed de l'enregistrement unique |
| `database/seeders/DatabaseSeeder.php` | Modifier | Enregistrer ClubPresentationSeeder |
| `tests/Unit/Models/ClubPresentationTest.php` | Créer | Tests unitaires du modèle |
| `app/Filament/Pages/ManageClubPresentation.php` | Créer | Page Filament custom (singleton edit) |
| `resources/views/filament/pages/manage-club-presentation.blade.php` | Créer | Vue Blade de la page Filament |
| `tests/Feature/Admin/ManageClubPresentationTest.php` | Créer | Tests accès admin |
| `database/migrations/2026_04_30_000002_create_staff_members_table.php` | Créer | Table `staff_members` |
| `app/Models/StaffMember.php` | Créer | Modèle avec constante CATEGORIES et cast JSON |
| `database/factories/StaffMemberFactory.php` | Créer | Factory avec états `entraineur()` / `arbitre()` |
| `tests/Unit/Models/StaffMemberTest.php` | Créer | Tests unitaires du modèle |
| `app/Filament/Resources/StaffMemberResource.php` | Créer | Resource Filament (form + table) |
| `app/Filament/Resources/StaffMemberResource/Pages/ListStaffMembers.php` | Créer | Page liste |
| `app/Filament/Resources/StaffMemberResource/Pages/CreateStaffMember.php` | Créer | Page création |
| `app/Filament/Resources/StaffMemberResource/Pages/EditStaffMember.php` | Créer | Page édition |
| `tests/Feature/Admin/StaffMemberResourceTest.php` | Créer | Tests accès admin |
| `app/Http/Controllers/LeClubController.php` | Créer | 4 méthodes : index, presentation, entraineurs, arbitres |
| `routes/web.php` | Modifier | 4 routes /le-club/* |
| `tests/Feature/LeClubControllerTest.php` | Créer | Tests feature du contrôleur |
| `resources/js/pages/LeClub/Index.vue` | Créer | Landing page navigation |
| `resources/js/pages/LeClub/Presentation.vue` | Créer | Page présentation du club |
| `resources/js/pages/LeClub/Entraineurs.vue` | Créer | Page entraîneurs groupés |
| `resources/js/pages/LeClub/Arbitres.vue` | Créer | Page arbitres groupés |

---

## Task 1 : ClubPresentation — Migration, Modèle, Factory, Seeder

**Files:**
- Create: `database/migrations/2026_04_30_000001_create_club_presentations_table.php`
- Create: `app/Models/ClubPresentation.php`
- Create: `database/factories/ClubPresentationFactory.php`
- Create: `database/seeders/ClubPresentationSeeder.php`
- Modify: `database/seeders/DatabaseSeeder.php`
- Create: `tests/Unit/Models/ClubPresentationTest.php`

- [ ] **Step 1 : Écrire le test (doit échouer)**

Créer `tests/Unit/Models/ClubPresentationTest.php` :

```php
<?php

namespace Tests\Unit\Models;

use App\Models\ClubPresentation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ClubPresentationTest extends TestCase
{
    use RefreshDatabase;

    public function test_club_presentation_can_be_created(): void
    {
        ClubPresentation::factory()->create([
            'title' => 'Notre club',
            'accroche' => 'Un club passionné',
            'content' => '<p>Contenu test</p>',
        ]);

        $this->assertDatabaseHas('club_presentations', [
            'title' => 'Notre club',
            'accroche' => 'Un club passionné',
            'featured_image' => null,
        ]);
    }

    public function test_featured_image_is_nullable(): void
    {
        $presentation = ClubPresentation::factory()->create(['featured_image' => null]);

        $this->assertNull($presentation->featured_image);
    }
}
```

- [ ] **Step 2 : Exécuter pour vérifier l'échec**

```bash
php artisan test tests/Unit/Models/ClubPresentationTest.php
```

Résultat attendu : FAIL — `Class "App\Models\ClubPresentation" not found`

- [ ] **Step 3 : Créer la migration**

Créer `database/migrations/2026_04_30_000001_create_club_presentations_table.php` :

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('club_presentations', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('accroche');
            $table->string('featured_image')->nullable();
            $table->longText('content');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('club_presentations');
    }
};
```

- [ ] **Step 4 : Créer le modèle**

Créer `app/Models/ClubPresentation.php` :

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ClubPresentation extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'accroche',
        'featured_image',
        'content',
    ];
}
```

- [ ] **Step 5 : Créer la factory**

Créer `database/factories/ClubPresentationFactory.php` :

```php
<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class ClubPresentationFactory extends Factory
{
    public function definition(): array
    {
        return [
            'title' => 'Présentation du club',
            'accroche' => $this->faker->sentence(),
            'featured_image' => null,
            'content' => '<p>' . $this->faker->paragraph() . '</p>',
        ];
    }
}
```

- [ ] **Step 6 : Créer le seeder**

Créer `database/seeders/ClubPresentationSeeder.php` :

```php
<?php

namespace Database\Seeders;

use App\Models\ClubPresentation;
use Illuminate\Database\Seeder;

class ClubPresentationSeeder extends Seeder
{
    public function run(): void
    {
        ClubPresentation::firstOrCreate(
            ['id' => 1],
            [
                'title' => 'Présentation du club',
                'accroche' => 'Bienvenue au TC2V Handball',
                'featured_image' => null,
                'content' => '<p>À compléter.</p>',
            ]
        );
    }
}
```

- [ ] **Step 7 : Enregistrer le seeder dans DatabaseSeeder**

Modifier `database/seeders/DatabaseSeeder.php` :

```php
<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            RolesAndPermissionsSeeder::class,
            AdminUserSeeder::class,
            ClubPresentationSeeder::class,
        ]);
    }
}
```

- [ ] **Step 8 : Exécuter la migration et les tests**

```bash
php artisan migrate
php artisan test tests/Unit/Models/ClubPresentationTest.php
```

Résultat attendu : 2 tests PASS

- [ ] **Step 9 : Suite complète**

```bash
php artisan test
```

Résultat attendu : tous les tests passent

- [ ] **Step 10 : Commit**

```bash
git add database/migrations/2026_04_30_000001_create_club_presentations_table.php \
        app/Models/ClubPresentation.php \
        database/factories/ClubPresentationFactory.php \
        database/seeders/ClubPresentationSeeder.php \
        database/seeders/DatabaseSeeder.php \
        tests/Unit/Models/ClubPresentationTest.php
git commit -m "feat: add ClubPresentation model, migration, factory and seeder"
```

---

## Task 2 : Page Filament ManageClubPresentation

**Files:**
- Create: `app/Filament/Pages/ManageClubPresentation.php`
- Create: `resources/views/filament/pages/manage-club-presentation.blade.php`
- Create: `tests/Feature/Admin/ManageClubPresentationTest.php`

- [ ] **Step 1 : Écrire le test (doit échouer)**

Créer `tests/Feature/Admin/ManageClubPresentationTest.php` :

```php
<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ManageClubPresentationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
    }

    public function test_admin_can_access_club_presentation_page(): void
    {
        $user = User::factory()->create();
        $user->assignRole('admin');

        $this->actingAs($user)
            ->get('/admin/club-presentation')
            ->assertSuccessful();
    }

    public function test_super_admin_can_access_club_presentation_page(): void
    {
        $user = User::factory()->create();
        $user->assignRole('super_admin');

        $this->actingAs($user)
            ->get('/admin/club-presentation')
            ->assertSuccessful();
    }

    public function test_unauthenticated_user_cannot_access_club_presentation_page(): void
    {
        $this->get('/admin/club-presentation')->assertRedirect('/admin/login');
    }

    public function test_authenticated_user_without_role_cannot_access_club_presentation_page(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get('/admin/club-presentation')
            ->assertForbidden();
    }
}
```

- [ ] **Step 2 : Exécuter pour vérifier l'échec**

```bash
php artisan test tests/Feature/Admin/ManageClubPresentationTest.php
```

Résultat attendu : FAIL — 404 (page inexistante)

- [ ] **Step 3 : Créer le répertoire et la page Filament**

Créer `app/Filament/Pages/ManageClubPresentation.php` :

```php
<?php

namespace App\Filament\Pages;

use App\Models\ClubPresentation;
use FilamentTiptapEditor\TiptapEditor;
use Filament\Forms;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;

class ManageClubPresentation extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-document-text';
    protected static ?string $navigationLabel = 'Présentation du club';
    protected static ?string $title = 'Présentation du club';
    protected static string $slug = 'club-presentation';
    protected static string $view = 'filament.pages.manage-club-presentation';

    public ?array $data = [];

    public function mount(): void
    {
        $presentation = ClubPresentation::firstOrCreate(
            ['id' => 1],
            [
                'title' => 'Présentation du club',
                'accroche' => 'Bienvenue au TC2V Handball',
                'featured_image' => null,
                'content' => '<p>À compléter.</p>',
            ]
        );

        $this->form->fill($presentation->toArray());
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('title')
                    ->label('Titre')
                    ->required(),

                Forms\Components\Textarea::make('accroche')
                    ->label('Accroche')
                    ->required()
                    ->columnSpanFull(),

                Forms\Components\FileUpload::make('featured_image')
                    ->label('Image mise en avant')
                    ->image()
                    ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp'])
                    ->maxSize(2048)
                    ->directory('club')
                    ->disk('public')
                    ->nullable(),

                TiptapEditor::make('content')
                    ->label('Contenu')
                    ->required()
                    ->columnSpanFull(),
            ])
            ->statePath('data');
    }

    public function save(): void
    {
        $data = $this->form->getState();

        ClubPresentation::first()->update($data);

        Notification::make()
            ->title('Enregistré avec succès')
            ->success()
            ->send();
    }
}
```

- [ ] **Step 4 : Créer la vue Blade**

Créer `resources/views/filament/pages/manage-club-presentation.blade.php` :

```blade
<x-filament-panels::page>
    <form wire:submit.prevent="save">
        {{ $this->form }}

        <div class="mt-6">
            <x-filament::button type="submit">
                Enregistrer
            </x-filament::button>
        </div>
    </form>

    <x-filament-actions::modals />
</x-filament-panels::page>
```

- [ ] **Step 5 : Exécuter les tests**

```bash
php artisan test tests/Feature/Admin/ManageClubPresentationTest.php
```

Résultat attendu : 4 tests PASS

- [ ] **Step 6 : Suite complète**

```bash
php artisan test
```

Résultat attendu : tous les tests passent

- [ ] **Step 7 : Commit**

```bash
git add app/Filament/Pages/ManageClubPresentation.php \
        resources/views/filament/pages/manage-club-presentation.blade.php \
        tests/Feature/Admin/ManageClubPresentationTest.php
git commit -m "feat: add ManageClubPresentation Filament page (singleton edit)"
```

---

## Task 3 : StaffMember — Migration, Modèle, Factory

**Files:**
- Create: `database/migrations/2026_04_30_000002_create_staff_members_table.php`
- Create: `app/Models/StaffMember.php`
- Create: `database/factories/StaffMemberFactory.php`
- Create: `tests/Unit/Models/StaffMemberTest.php`

- [ ] **Step 1 : Écrire le test (doit échouer)**

Créer `tests/Unit/Models/StaffMemberTest.php` :

```php
<?php

namespace Tests\Unit\Models;

use App\Models\StaffMember;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StaffMemberTest extends TestCase
{
    use RefreshDatabase;

    public function test_staff_member_can_be_created_with_required_fields(): void
    {
        StaffMember::factory()->create([
            'name' => 'Jean Dupont',
            'type' => 'entraineur',
            'categories' => ['u13_m', 'u15_m'],
        ]);

        $this->assertDatabaseHas('staff_members', [
            'name' => 'Jean Dupont',
            'type' => 'entraineur',
        ]);
    }

    public function test_categories_is_cast_to_array(): void
    {
        $member = StaffMember::factory()->create(['categories' => ['u13_m', 'u15_m']]);

        $this->assertIsArray($member->fresh()->categories);
        $this->assertContains('u13_m', $member->fresh()->categories);
        $this->assertContains('u15_m', $member->fresh()->categories);
    }

    public function test_optional_fields_are_nullable(): void
    {
        $member = StaffMember::factory()->create([
            'photo' => null,
            'bio' => null,
        ]);

        $this->assertNull($member->photo);
        $this->assertNull($member->bio);
    }

    public function test_categories_constant_contains_14_entries(): void
    {
        $this->assertCount(14, StaffMember::CATEGORIES);
    }

    public function test_categories_constant_is_in_correct_order(): void
    {
        $keys = array_keys(StaffMember::CATEGORIES);
        $this->assertEquals('baby', $keys[0]);
        $this->assertEquals('u7', $keys[1]);
        $this->assertEquals('loisirs', $keys[13]);
    }
}
```

- [ ] **Step 2 : Exécuter pour vérifier l'échec**

```bash
php artisan test tests/Unit/Models/StaffMemberTest.php
```

Résultat attendu : FAIL — `Class "App\Models\StaffMember" not found`

- [ ] **Step 3 : Créer la migration**

Créer `database/migrations/2026_04_30_000002_create_staff_members_table.php` :

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('staff_members', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->enum('type', ['entraineur', 'arbitre']);
            $table->string('photo')->nullable();
            $table->text('bio')->nullable();
            $table->json('categories')->default('[]');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('staff_members');
    }
};
```

- [ ] **Step 4 : Créer le modèle**

Créer `app/Models/StaffMember.php` :

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StaffMember extends Model
{
    use HasFactory;

    const CATEGORIES = [
        'baby'      => 'Baby Hand',
        'u7'        => 'U7',
        'u9'        => 'U9',
        'u11_m'     => 'U11 Masculins',
        'u11_f'     => 'U11 Féminines',
        'u13_m'     => 'U13 Masculins',
        'u13_f'     => 'U13 Féminines',
        'u15_m'     => 'U15 Masculins',
        'u15_f'     => 'U15 Féminines',
        'u18_m'     => 'U18 Masculins',
        'u18_f'     => 'U18 Féminines',
        'seniors_m' => 'Seniors Masculins',
        'seniors_f' => 'Seniors Féminines',
        'loisirs'   => 'Loisirs',
    ];

    protected $fillable = [
        'name',
        'type',
        'photo',
        'bio',
        'categories',
    ];

    protected function casts(): array
    {
        return [
            'categories' => 'array',
        ];
    }
}
```

- [ ] **Step 5 : Créer la factory**

Créer `database/factories/StaffMemberFactory.php` :

```php
<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class StaffMemberFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => $this->faker->name(),
            'type' => $this->faker->randomElement(['entraineur', 'arbitre']),
            'photo' => null,
            'bio' => $this->faker->sentence(),
            'categories' => ['u13_m'],
        ];
    }

    public function entraineur(): static
    {
        return $this->state(['type' => 'entraineur']);
    }

    public function arbitre(): static
    {
        return $this->state(['type' => 'arbitre']);
    }
}
```

- [ ] **Step 6 : Exécuter la migration et les tests**

```bash
php artisan migrate
php artisan test tests/Unit/Models/StaffMemberTest.php
```

Résultat attendu : 5 tests PASS

- [ ] **Step 7 : Suite complète**

```bash
php artisan test
```

Résultat attendu : tous les tests passent

- [ ] **Step 8 : Commit**

```bash
git add database/migrations/2026_04_30_000002_create_staff_members_table.php \
        app/Models/StaffMember.php \
        database/factories/StaffMemberFactory.php \
        tests/Unit/Models/StaffMemberTest.php
git commit -m "feat: add StaffMember model, migration and factory"
```

---

## Task 4 : StaffMemberResource Filament

**Files:**
- Create: `app/Filament/Resources/StaffMemberResource.php`
- Create: `app/Filament/Resources/StaffMemberResource/Pages/ListStaffMembers.php`
- Create: `app/Filament/Resources/StaffMemberResource/Pages/CreateStaffMember.php`
- Create: `app/Filament/Resources/StaffMemberResource/Pages/EditStaffMember.php`
- Create: `tests/Feature/Admin/StaffMemberResourceTest.php`

- [ ] **Step 1 : Écrire le test (doit échouer)**

Créer `tests/Feature/Admin/StaffMemberResourceTest.php` :

```php
<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StaffMemberResourceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
    }

    public function test_admin_can_access_staff_members_list(): void
    {
        $user = User::factory()->create();
        $user->assignRole('admin');

        $this->actingAs($user)
            ->get('/admin/staff-members')
            ->assertSuccessful();
    }

    public function test_super_admin_can_access_staff_members_list(): void
    {
        $user = User::factory()->create();
        $user->assignRole('super_admin');

        $this->actingAs($user)
            ->get('/admin/staff-members')
            ->assertSuccessful();
    }

    public function test_unauthenticated_user_cannot_access_staff_members_list(): void
    {
        $this->get('/admin/staff-members')->assertRedirect('/admin/login');
    }

    public function test_authenticated_user_without_role_cannot_access_staff_members_list(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get('/admin/staff-members')
            ->assertForbidden();
    }
}
```

- [ ] **Step 2 : Exécuter pour vérifier l'échec**

```bash
php artisan test tests/Feature/Admin/StaffMemberResourceTest.php
```

Résultat attendu : FAIL — 404

- [ ] **Step 3 : Créer les pages Filament**

Créer `app/Filament/Resources/StaffMemberResource/Pages/ListStaffMembers.php` :

```php
<?php

namespace App\Filament\Resources\StaffMemberResource\Pages;

use App\Filament\Resources\StaffMemberResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListStaffMembers extends ListRecords
{
    protected static string $resource = StaffMemberResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\CreateAction::make()];
    }
}
```

Créer `app/Filament/Resources/StaffMemberResource/Pages/CreateStaffMember.php` :

```php
<?php

namespace App\Filament\Resources\StaffMemberResource\Pages;

use App\Filament\Resources\StaffMemberResource;
use Filament\Resources\Pages\CreateRecord;

class CreateStaffMember extends CreateRecord
{
    protected static string $resource = StaffMemberResource::class;
}
```

Créer `app/Filament/Resources/StaffMemberResource/Pages/EditStaffMember.php` :

```php
<?php

namespace App\Filament\Resources\StaffMemberResource\Pages;

use App\Filament\Resources\StaffMemberResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditStaffMember extends EditRecord
{
    protected static string $resource = StaffMemberResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\DeleteAction::make()];
    }
}
```

- [ ] **Step 4 : Créer StaffMemberResource**

Créer `app/Filament/Resources/StaffMemberResource.php` :

```php
<?php

namespace App\Filament\Resources;

use App\Filament\Resources\StaffMemberResource\Pages;
use App\Models\StaffMember;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class StaffMemberResource extends Resource
{
    protected static ?string $model = StaffMember::class;
    protected static ?string $navigationIcon = 'heroicon-o-users';
    protected static ?string $navigationLabel = 'Staff';
    protected static ?string $modelLabel = 'Membre du staff';
    protected static ?string $pluralModelLabel = 'Staff';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\TextInput::make('name')
                ->label('Nom')
                ->required(),

            Forms\Components\Select::make('type')
                ->label('Type')
                ->options([
                    'entraineur' => 'Entraîneur',
                    'arbitre' => 'Arbitre',
                ])
                ->required(),

            Forms\Components\FileUpload::make('photo')
                ->label('Photo')
                ->image()
                ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp'])
                ->maxSize(2048)
                ->directory('staff/photos')
                ->disk('public')
                ->nullable(),

            Forms\Components\Textarea::make('bio')
                ->label('Bio')
                ->columnSpanFull()
                ->nullable(),

            Forms\Components\CheckboxList::make('categories')
                ->label('Catégories')
                ->options(StaffMember::CATEGORIES)
                ->columns(2)
                ->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\ImageColumn::make('photo')
                    ->label('Photo')
                    ->disk('public')
                    ->circular(),

                Tables\Columns\TextColumn::make('name')
                    ->label('Nom')
                    ->searchable(),

                Tables\Columns\TextColumn::make('type')
                    ->label('Type')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'entraineur' => 'primary',
                        'arbitre' => 'warning',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'entraineur' => 'Entraîneur',
                        'arbitre' => 'Arbitre',
                        default => $state,
                    }),

                Tables\Columns\TextColumn::make('categories')
                    ->label('Catégories')
                    ->formatStateUsing(fn (array $state): string =>
                        implode(', ', array_map(
                            fn ($slug) => StaffMember::CATEGORIES[$slug] ?? $slug,
                            $state
                        ))
                    ),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('type')
                    ->label('Type')
                    ->options([
                        'entraineur' => 'Entraîneur',
                        'arbitre' => 'Arbitre',
                    ]),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListStaffMembers::route('/'),
            'create' => Pages\CreateStaffMember::route('/create'),
            'edit' => Pages\EditStaffMember::route('/{record}/edit'),
        ];
    }
}
```

- [ ] **Step 5 : Exécuter les tests**

```bash
php artisan test tests/Feature/Admin/StaffMemberResourceTest.php
```

Résultat attendu : 4 tests PASS

- [ ] **Step 6 : Suite complète**

```bash
php artisan test
```

Résultat attendu : tous les tests passent

- [ ] **Step 7 : Commit**

```bash
git add app/Filament/Resources/StaffMemberResource.php \
        "app/Filament/Resources/StaffMemberResource/Pages/ListStaffMembers.php" \
        "app/Filament/Resources/StaffMemberResource/Pages/CreateStaffMember.php" \
        "app/Filament/Resources/StaffMemberResource/Pages/EditStaffMember.php" \
        tests/Feature/Admin/StaffMemberResourceTest.php
git commit -m "feat: add StaffMemberResource Filament with category filter"
```

---

## Task 5 : LeClubController + Routes

**Files:**
- Create: `app/Http/Controllers/LeClubController.php`
- Modify: `routes/web.php`
- Create: `tests/Feature/LeClubControllerTest.php`

- [ ] **Step 1 : Écrire les tests (doivent échouer)**

Créer `tests/Feature/LeClubControllerTest.php` :

```php
<?php

namespace Tests\Feature;

use App\Models\ClubPresentation;
use App\Models\StaffMember;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class LeClubControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_index_retourne_la_page_navigation(): void
    {
        $this->get('/le-club')
            ->assertOk()
            ->assertInertia(fn (Assert $page) =>
                $page->component('LeClub/Index')
            );
    }

    public function test_presentation_retourne_le_contenu_du_club(): void
    {
        ClubPresentation::factory()->create([
            'title' => 'Notre club',
            'accroche' => 'Un club passionné',
        ]);

        $this->get('/le-club/presentation')
            ->assertOk()
            ->assertInertia(fn (Assert $page) =>
                $page->component('LeClub/Presentation')
                    ->where('presentation.title', 'Notre club')
                    ->where('presentation.accroche', 'Un club passionné')
            );
    }

    public function test_entraineurs_retourne_les_membres_de_type_entraineur(): void
    {
        StaffMember::factory()->entraineur()->create(['name' => 'Coach A', 'categories' => ['u13_m']]);
        StaffMember::factory()->arbitre()->create(['name' => 'Arbitre B', 'categories' => ['u13_m']]);

        $this->get('/le-club/entraineurs')
            ->assertOk()
            ->assertInertia(fn (Assert $page) =>
                $page->component('LeClub/Entraineurs')
                    ->has('groupes', 1)
                    ->where('groupes.0.membres.0.name', 'Coach A')
            );
    }

    public function test_entraineurs_sont_groupes_dans_lordre_des_categories(): void
    {
        StaffMember::factory()->entraineur()->create(['categories' => ['u15_m']]);
        StaffMember::factory()->entraineur()->create(['categories' => ['u13_m']]);

        $this->get('/le-club/entraineurs')
            ->assertOk()
            ->assertInertia(fn (Assert $page) =>
                $page->component('LeClub/Entraineurs')
                    ->where('groupes.0.categorie', 'U13 Masculins')
                    ->where('groupes.1.categorie', 'U15 Masculins')
            );
    }

    public function test_categories_sans_membres_nexistent_pas_dans_groupes(): void
    {
        StaffMember::factory()->entraineur()->create(['categories' => ['seniors_m']]);

        $this->get('/le-club/entraineurs')
            ->assertOk()
            ->assertInertia(fn (Assert $page) =>
                $page->component('LeClub/Entraineurs')
                    ->has('groupes', 1)
                    ->where('groupes.0.categorie', 'Seniors Masculins')
            );
    }

    public function test_entraineurs_retourne_groupes_vide_sans_membres(): void
    {
        $this->get('/le-club/entraineurs')
            ->assertOk()
            ->assertInertia(fn (Assert $page) =>
                $page->component('LeClub/Entraineurs')
                    ->where('groupes', [])
            );
    }

    public function test_arbitres_retourne_les_membres_de_type_arbitre(): void
    {
        StaffMember::factory()->arbitre()->create(['name' => 'Arbitre C', 'categories' => ['seniors_m']]);
        StaffMember::factory()->entraineur()->create(['name' => 'Coach D', 'categories' => ['seniors_m']]);

        $this->get('/le-club/arbitres')
            ->assertOk()
            ->assertInertia(fn (Assert $page) =>
                $page->component('LeClub/Arbitres')
                    ->has('groupes', 1)
                    ->where('groupes.0.membres.0.name', 'Arbitre C')
            );
    }
}
```

- [ ] **Step 2 : Exécuter pour vérifier l'échec**

```bash
php artisan test tests/Feature/LeClubControllerTest.php
```

Résultat attendu : FAIL — 404 (routes inconnues)

- [ ] **Step 3 : Créer le contrôleur**

Créer `app/Http/Controllers/LeClubController.php` :

```php
<?php

namespace App\Http\Controllers;

use App\Models\ClubPresentation;
use App\Models\StaffMember;
use Illuminate\Support\Collection;
use Inertia\Inertia;
use Inertia\Response;

class LeClubController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('LeClub/Index');
    }

    public function presentation(): Response
    {
        $presentation = ClubPresentation::firstOrFail();

        return Inertia::render('LeClub/Presentation', [
            'presentation' => $presentation,
        ]);
    }

    public function entraineurs(): Response
    {
        $membres = StaffMember::where('type', 'entraineur')->get();

        return Inertia::render('LeClub/Entraineurs', [
            'groupes' => $this->grouperParCategorie($membres),
        ]);
    }

    public function arbitres(): Response
    {
        $membres = StaffMember::where('type', 'arbitre')->get();

        return Inertia::render('LeClub/Arbitres', [
            'groupes' => $this->grouperParCategorie($membres),
        ]);
    }

    private function grouperParCategorie(Collection $membres): array
    {
        $groupes = [];

        foreach (array_keys(StaffMember::CATEGORIES) as $slug) {
            $membresCategorie = $membres->filter(
                fn (StaffMember $m) => in_array($slug, $m->categories ?? [])
            );

            if ($membresCategorie->isNotEmpty()) {
                $groupes[] = [
                    'categorie' => StaffMember::CATEGORIES[$slug],
                    'membres' => $membresCategorie->values(),
                ];
            }
        }

        return $groupes;
    }
}
```

- [ ] **Step 4 : Ajouter les routes**

Modifier `routes/web.php` :

```php
<?php

use App\Http\Controllers\ActualitesController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\LeClubController;
use App\Http\Controllers\PartenairesController;
use Illuminate\Support\Facades\Route;

Route::get('/', [HomeController::class, 'index']);

Route::get('/actualites', [ActualitesController::class, 'index'])->name('actualites.index');
Route::get('/actualites/{slug}', [ActualitesController::class, 'show'])->name('actualites.show');

Route::get('/partenaires', [PartenairesController::class, 'index'])->name('partenaires.index');

Route::get('/le-club', [LeClubController::class, 'index'])->name('le-club.index');
Route::get('/le-club/presentation', [LeClubController::class, 'presentation'])->name('le-club.presentation');
Route::get('/le-club/entraineurs', [LeClubController::class, 'entraineurs'])->name('le-club.entraineurs');
Route::get('/le-club/arbitres', [LeClubController::class, 'arbitres'])->name('le-club.arbitres');
```

- [ ] **Step 5 : Exécuter les tests**

```bash
php artisan test tests/Feature/LeClubControllerTest.php
```

Résultat attendu : 7 tests PASS

- [ ] **Step 6 : Suite complète**

```bash
php artisan test
```

Résultat attendu : tous les tests passent

- [ ] **Step 7 : Commit**

```bash
git add app/Http/Controllers/LeClubController.php \
        routes/web.php \
        tests/Feature/LeClubControllerTest.php
git commit -m "feat: add LeClubController with category grouping and routes"
```

---

## Task 6 : Pages Vue

**Files:**
- Create: `resources/js/pages/LeClub/Index.vue`
- Create: `resources/js/pages/LeClub/Presentation.vue`
- Create: `resources/js/pages/LeClub/Entraineurs.vue`
- Create: `resources/js/pages/LeClub/Arbitres.vue`

- [ ] **Step 1 : Vérifier que les tests PHP passent toujours (baseline)**

```bash
php artisan test
```

Résultat attendu : tous les tests passent (les tests Inertia vérifient le payload JSON, pas les fichiers Vue)

- [ ] **Step 2 : Créer LeClub/Index.vue**

Créer `resources/js/pages/LeClub/Index.vue` :

```vue
<script setup>
</script>

<template>
    <div>
        <h1>Le Club</h1>

        <nav class="club-nav">
            <a href="/le-club/presentation">Présentation du club</a>
            <a href="/le-club/entraineurs">Entraîneurs</a>
            <a href="/le-club/arbitres">Arbitres</a>
        </nav>
    </div>
</template>

<style scoped>
.club-nav {
    display: flex;
    flex-direction: column;
    gap: 1rem;
    margin-top: 2rem;
}

.club-nav a {
    padding: 1rem;
    border: 1px solid #e5e7eb;
    border-radius: 0.5rem;
    text-decoration: none;
    color: inherit;
}
</style>
```

- [ ] **Step 3 : Créer LeClub/Presentation.vue**

Créer `resources/js/pages/LeClub/Presentation.vue` :

```vue
<script setup>
defineProps({
    presentation: {
        type: Object,
        required: true,
    },
});
</script>

<template>
    <div>
        <h1>{{ presentation.title }}</h1>

        <p class="accroche">{{ presentation.accroche }}</p>

        <img
            v-if="presentation.featured_image"
            :src="`/storage/${presentation.featured_image}`"
            :alt="presentation.title"
            class="featured-image"
        />

        <div class="content" v-html="presentation.content" />
    </div>
</template>

<style scoped>
.accroche {
    font-size: 1.125rem;
    color: #6b7280;
    margin-bottom: 1.5rem;
}

.featured-image {
    width: 100%;
    max-height: 400px;
    object-fit: cover;
    border-radius: 0.5rem;
    margin-bottom: 1.5rem;
}

.content {
    line-height: 1.75;
}
</style>
```

- [ ] **Step 4 : Créer LeClub/Entraineurs.vue**

Créer `resources/js/pages/LeClub/Entraineurs.vue` :

```vue
<script setup>
defineProps({
    groupes: {
        type: Array,
        required: true,
    },
});
</script>

<template>
    <div>
        <h1>Nos Entraîneurs</h1>

        <p v-if="groupes.length === 0">
            Aucun entraîneur enregistré pour le moment.
        </p>

        <div v-for="groupe in groupes" :key="groupe.categorie" class="groupe">
            <h2>{{ groupe.categorie }}</h2>

            <div class="staff-grid">
                <div
                    v-for="membre in groupe.membres"
                    :key="membre.id"
                    class="staff-card"
                >
                    <img
                        v-if="membre.photo"
                        :src="`/storage/${membre.photo}`"
                        :alt="membre.name"
                        class="staff-photo"
                    />
                    <div v-else class="staff-avatar-placeholder">
                        {{ membre.name.charAt(0).toUpperCase() }}
                    </div>

                    <p class="staff-name">{{ membre.name }}</p>
                    <p v-if="membre.bio" class="staff-bio">{{ membre.bio }}</p>
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>
.groupe {
    margin-bottom: 3rem;
}

.staff-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 1.5rem;
    margin-top: 1rem;
}

@media (min-width: 768px) {
    .staff-grid {
        grid-template-columns: repeat(3, 1fr);
    }
}

@media (min-width: 1024px) {
    .staff-grid {
        grid-template-columns: repeat(4, 1fr);
    }
}

.staff-card {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 0.5rem;
    padding: 1rem;
    border: 1px solid #e5e7eb;
    border-radius: 0.5rem;
    text-align: center;
}

.staff-photo {
    width: 80px;
    height: 80px;
    border-radius: 50%;
    object-fit: cover;
}

.staff-avatar-placeholder {
    width: 80px;
    height: 80px;
    border-radius: 50%;
    background-color: #7C878E;
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 2rem;
    font-weight: 600;
}

.staff-name {
    font-weight: 600;
}

.staff-bio {
    font-size: 0.875rem;
    color: #6b7280;
}
</style>
```

- [ ] **Step 5 : Créer LeClub/Arbitres.vue**

Créer `resources/js/pages/LeClub/Arbitres.vue` :

```vue
<script setup>
defineProps({
    groupes: {
        type: Array,
        required: true,
    },
});
</script>

<template>
    <div>
        <h1>Nos Arbitres</h1>

        <p v-if="groupes.length === 0">
            Aucun arbitre enregistré pour le moment.
        </p>

        <div v-for="groupe in groupes" :key="groupe.categorie" class="groupe">
            <h2>{{ groupe.categorie }}</h2>

            <div class="staff-grid">
                <div
                    v-for="membre in groupe.membres"
                    :key="membre.id"
                    class="staff-card"
                >
                    <img
                        v-if="membre.photo"
                        :src="`/storage/${membre.photo}`"
                        :alt="membre.name"
                        class="staff-photo"
                    />
                    <div v-else class="staff-avatar-placeholder">
                        {{ membre.name.charAt(0).toUpperCase() }}
                    </div>

                    <p class="staff-name">{{ membre.name }}</p>
                    <p v-if="membre.bio" class="staff-bio">{{ membre.bio }}</p>
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>
.groupe {
    margin-bottom: 3rem;
}

.staff-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 1.5rem;
    margin-top: 1rem;
}

@media (min-width: 768px) {
    .staff-grid {
        grid-template-columns: repeat(3, 1fr);
    }
}

@media (min-width: 1024px) {
    .staff-grid {
        grid-template-columns: repeat(4, 1fr);
    }
}

.staff-card {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 0.5rem;
    padding: 1rem;
    border: 1px solid #e5e7eb;
    border-radius: 0.5rem;
    text-align: center;
}

.staff-photo {
    width: 80px;
    height: 80px;
    border-radius: 50%;
    object-fit: cover;
}

.staff-avatar-placeholder {
    width: 80px;
    height: 80px;
    border-radius: 50%;
    background-color: #7C878E;
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 2rem;
    font-weight: 600;
}

.staff-name {
    font-weight: 600;
}

.staff-bio {
    font-size: 0.875rem;
    color: #6b7280;
}
</style>
```

- [ ] **Step 6 : Vérifier la suite complète**

```bash
php artisan test
```

Résultat attendu : tous les tests passent

- [ ] **Step 7 : Commit**

```bash
git add resources/js/pages/LeClub/Index.vue \
        resources/js/pages/LeClub/Presentation.vue \
        resources/js/pages/LeClub/Entraineurs.vue \
        resources/js/pages/LeClub/Arbitres.vue
git commit -m "feat: add LeClub Vue pages (Index, Presentation, Entraineurs, Arbitres)"
```

---

## Vérification finale

- [ ] **Lancer la suite complète**

```bash
php artisan test
```

Résultat attendu : tous les tests passent (aucune régression)

- [ ] **Utiliser superpowers:finishing-a-development-branch pour finaliser**
