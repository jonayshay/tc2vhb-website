# Iteration 4b — Module Le Club (Bureau & CA + Commissions) — Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Ajouter les pages Bureau & CA et Commissions au module Le Club, avec gestion Filament drag-and-drop pour l'ordre d'affichage.

**Architecture:** `BoardMember` est une liste plate ordonnée gérée via `BoardMemberResource` (drag-and-drop, observer pour auto-incrément). `Commission` a une relation `hasMany CommissionMember` ; les membres sont gérés via un Repeater dans le formulaire Filament de leur commission. Deux nouvelles méthodes dans `LeClubController` existant ; deux nouvelles pages Vue.

**Tech Stack:** Laravel 12, Filament 3, Inertia.js v3, Vue.js 3, PHPUnit

---

## File Structure

| Fichier | Action | Rôle |
|---|---|---|
| `database/migrations/2026_05_01_000001_create_board_members_table.php` | Créer | Table `board_members` |
| `app/Models/BoardMember.php` | Créer | Modèle |
| `database/factories/BoardMemberFactory.php` | Créer | Factory |
| `app/Observers/BoardMemberObserver.php` | Créer | Auto-incrément sort_order |
| `app/Providers/AppServiceProvider.php` | Modifier | Enregistrer BoardMemberObserver |
| `tests/Unit/Models/BoardMemberTest.php` | Créer | Tests unitaires modèle |
| `tests/Feature/BoardMemberObserverTest.php` | Créer | Tests observer |
| `app/Filament/Resources/BoardMemberResource.php` | Créer | Resource Filament |
| `app/Filament/Resources/BoardMemberResource/Pages/ListBoardMembers.php` | Créer | Page liste |
| `app/Filament/Resources/BoardMemberResource/Pages/CreateBoardMember.php` | Créer | Page création |
| `app/Filament/Resources/BoardMemberResource/Pages/EditBoardMember.php` | Créer | Page édition |
| `tests/Feature/Admin/BoardMemberResourceTest.php` | Créer | Tests accès admin |
| `database/migrations/2026_05_01_000002_create_commissions_table.php` | Créer | Table `commissions` |
| `database/migrations/2026_05_01_000003_create_commission_members_table.php` | Créer | Table `commission_members` |
| `app/Models/Commission.php` | Créer | Modèle avec hasMany |
| `app/Models/CommissionMember.php` | Créer | Modèle avec belongsTo |
| `database/factories/CommissionFactory.php` | Créer | Factory |
| `database/factories/CommissionMemberFactory.php` | Créer | Factory |
| `tests/Unit/Models/CommissionTest.php` | Créer | Tests unitaires |
| `tests/Unit/Models/CommissionMemberTest.php` | Créer | Tests unitaires + cascade |
| `app/Filament/Resources/CommissionResource.php` | Créer | Resource Filament avec Repeater |
| `app/Filament/Resources/CommissionResource/Pages/ListCommissions.php` | Créer | Page liste |
| `app/Filament/Resources/CommissionResource/Pages/CreateCommission.php` | Créer | Page création |
| `app/Filament/Resources/CommissionResource/Pages/EditCommission.php` | Créer | Page édition |
| `tests/Feature/Admin/CommissionResourceTest.php` | Créer | Tests accès admin |
| `app/Http/Controllers/LeClubController.php` | Modifier | Ajouter bureau() et commissions() |
| `routes/web.php` | Modifier | 2 nouvelles routes |
| `tests/Feature/LeClubControllerTest.php` | Modifier | 4 nouveaux tests |
| `resources/js/pages/LeClub/Bureau.vue` | Créer | Page bureau public |
| `resources/js/pages/LeClub/Commissions.vue` | Créer | Page commissions public |
| `resources/js/pages/LeClub/Index.vue` | Modifier | Ajouter 2 liens nav |

---

## Task 1 : BoardMember — Migration, Modèle, Factory, Observer

**Files:**
- Create: `database/migrations/2026_05_01_000001_create_board_members_table.php`
- Create: `app/Models/BoardMember.php`
- Create: `database/factories/BoardMemberFactory.php`
- Create: `app/Observers/BoardMemberObserver.php`
- Modify: `app/Providers/AppServiceProvider.php`
- Create: `tests/Unit/Models/BoardMemberTest.php`
- Create: `tests/Feature/BoardMemberObserverTest.php`

- [ ] **Step 1 : Écrire les tests (doivent échouer)**

Créer `tests/Unit/Models/BoardMemberTest.php` :

```php
<?php

namespace Tests\Unit\Models;

use App\Models\BoardMember;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BoardMemberTest extends TestCase
{
    use RefreshDatabase;

    public function test_board_member_can_be_created_with_required_fields(): void
    {
        BoardMember::factory()->create([
            'name' => 'Jean Martin',
            'role' => 'Président',
        ]);

        $this->assertDatabaseHas('board_members', [
            'name' => 'Jean Martin',
            'role' => 'Président',
        ]);
    }

    public function test_bio_and_photo_are_nullable(): void
    {
        $member = BoardMember::factory()->create([
            'bio' => null,
            'photo' => null,
        ]);

        $this->assertNull($member->bio);
        $this->assertNull($member->photo);
    }
}
```

Créer `tests/Feature/BoardMemberObserverTest.php` :

```php
<?php

namespace Tests\Feature;

use App\Models\BoardMember;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BoardMemberObserverTest extends TestCase
{
    use RefreshDatabase;

    public function test_sort_order_is_auto_incremented_when_not_provided(): void
    {
        $first = BoardMember::factory()->create(['sort_order' => 0]);
        $second = BoardMember::factory()->create(['sort_order' => 0]);

        $this->assertEquals(1, $first->fresh()->sort_order);
        $this->assertEquals(2, $second->fresh()->sort_order);
    }

    public function test_sort_order_is_preserved_when_explicitly_set(): void
    {
        $member = BoardMember::factory()->create(['sort_order' => 5]);

        $this->assertEquals(5, $member->fresh()->sort_order);
    }
}
```

- [ ] **Step 2 : Exécuter pour vérifier l'échec**

```bash
php artisan test tests/Unit/Models/BoardMemberTest.php tests/Feature/BoardMemberObserverTest.php
```

Résultat attendu : FAIL — `Class "App\Models\BoardMember" not found`

- [ ] **Step 3 : Créer la migration**

Créer `database/migrations/2026_05_01_000001_create_board_members_table.php` :

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('board_members', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('role');
            $table->text('bio')->nullable();
            $table->string('photo')->nullable();
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('board_members');
    }
};
```

- [ ] **Step 4 : Créer le modèle**

Créer `app/Models/BoardMember.php` :

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BoardMember extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'role',
        'bio',
        'photo',
        'sort_order',
    ];
}
```

- [ ] **Step 5 : Créer la factory**

Créer `database/factories/BoardMemberFactory.php` :

```php
<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class BoardMemberFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => $this->faker->name(),
            'role' => $this->faker->randomElement(['Président', 'Vice-Président', 'Trésorier', 'Secrétaire', 'Membre CA']),
            'bio' => $this->faker->sentence(),
            'photo' => null,
            'sort_order' => 0,
        ];
    }
}
```

- [ ] **Step 6 : Créer l'observer**

Créer `app/Observers/BoardMemberObserver.php` :

```php
<?php

namespace App\Observers;

use App\Models\BoardMember;

class BoardMemberObserver
{
    public function creating(BoardMember $boardMember): void
    {
        if ($boardMember->sort_order === null || $boardMember->sort_order === 0) {
            $boardMember->sort_order = (BoardMember::max('sort_order') ?? 0) + 1;
        }
    }
}
```

- [ ] **Step 7 : Enregistrer l'observer dans AppServiceProvider**

Modifier `app/Providers/AppServiceProvider.php` :

```php
<?php

namespace App\Providers;

use App\Models\BoardMember;
use App\Models\News;
use App\Models\Partner;
use App\Observers\BoardMemberObserver;
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
        BoardMember::observe(BoardMemberObserver::class);
    }
}
```

- [ ] **Step 8 : Migrer et exécuter les tests**

```bash
php artisan migrate
php artisan test tests/Unit/Models/BoardMemberTest.php tests/Feature/BoardMemberObserverTest.php
```

Résultat attendu : 4 tests PASS

- [ ] **Step 9 : Suite complète**

```bash
php artisan test
```

Résultat attendu : tous les tests passent

- [ ] **Step 10 : Commit**

```bash
git add database/migrations/2026_05_01_000001_create_board_members_table.php \
        app/Models/BoardMember.php \
        database/factories/BoardMemberFactory.php \
        app/Observers/BoardMemberObserver.php \
        app/Providers/AppServiceProvider.php \
        tests/Unit/Models/BoardMemberTest.php \
        tests/Feature/BoardMemberObserverTest.php
git commit -m "feat: add BoardMember model, migration, factory and observer"
```

---

## Task 2 : BoardMemberResource Filament

**Files:**
- Create: `app/Filament/Resources/BoardMemberResource.php`
- Create: `app/Filament/Resources/BoardMemberResource/Pages/ListBoardMembers.php`
- Create: `app/Filament/Resources/BoardMemberResource/Pages/CreateBoardMember.php`
- Create: `app/Filament/Resources/BoardMemberResource/Pages/EditBoardMember.php`
- Create: `tests/Feature/Admin/BoardMemberResourceTest.php`

- [ ] **Step 1 : Écrire le test (doit échouer)**

Créer `tests/Feature/Admin/BoardMemberResourceTest.php` :

```php
<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BoardMemberResourceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
    }

    public function test_admin_can_access_board_members_list(): void
    {
        $user = User::factory()->create();
        $user->assignRole('admin');

        $this->actingAs($user)
            ->get('/admin/board-members')
            ->assertSuccessful();
    }

    public function test_super_admin_can_access_board_members_list(): void
    {
        $user = User::factory()->create();
        $user->assignRole('super_admin');

        $this->actingAs($user)
            ->get('/admin/board-members')
            ->assertSuccessful();
    }

    public function test_unauthenticated_user_cannot_access_board_members_list(): void
    {
        $this->get('/admin/board-members')->assertRedirect('/admin/login');
    }

    public function test_authenticated_user_without_role_cannot_access_board_members_list(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get('/admin/board-members')
            ->assertForbidden();
    }
}
```

- [ ] **Step 2 : Exécuter pour vérifier l'échec**

```bash
php artisan test tests/Feature/Admin/BoardMemberResourceTest.php
```

Résultat attendu : FAIL — 404

- [ ] **Step 3 : Créer les pages Filament**

Créer `app/Filament/Resources/BoardMemberResource/Pages/ListBoardMembers.php` :

```php
<?php

namespace App\Filament\Resources\BoardMemberResource\Pages;

use App\Filament\Resources\BoardMemberResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListBoardMembers extends ListRecords
{
    protected static string $resource = BoardMemberResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\CreateAction::make()];
    }
}
```

Créer `app/Filament/Resources/BoardMemberResource/Pages/CreateBoardMember.php` :

```php
<?php

namespace App\Filament\Resources\BoardMemberResource\Pages;

use App\Filament\Resources\BoardMemberResource;
use Filament\Resources\Pages\CreateRecord;

class CreateBoardMember extends CreateRecord
{
    protected static string $resource = BoardMemberResource::class;
}
```

Créer `app/Filament/Resources/BoardMemberResource/Pages/EditBoardMember.php` :

```php
<?php

namespace App\Filament\Resources\BoardMemberResource\Pages;

use App\Filament\Resources\BoardMemberResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditBoardMember extends EditRecord
{
    protected static string $resource = BoardMemberResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\DeleteAction::make()];
    }
}
```

- [ ] **Step 4 : Créer BoardMemberResource**

Créer `app/Filament/Resources/BoardMemberResource.php` :

```php
<?php

namespace App\Filament\Resources;

use App\Filament\Resources\BoardMemberResource\Pages;
use App\Models\BoardMember;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class BoardMemberResource extends Resource
{
    protected static ?string $model = BoardMember::class;
    protected static ?string $navigationIcon = 'heroicon-o-user-group';
    protected static ?string $navigationLabel = 'Bureau & CA';
    protected static ?string $modelLabel = 'Membre du bureau';
    protected static ?string $pluralModelLabel = 'Bureau & CA';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\TextInput::make('name')
                ->label('Nom')
                ->required(),

            Forms\Components\TextInput::make('role')
                ->label('Rôle')
                ->required(),

            Forms\Components\Textarea::make('bio')
                ->label('Bio')
                ->columnSpanFull()
                ->nullable(),

            Forms\Components\FileUpload::make('photo')
                ->label('Photo')
                ->image()
                ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp'])
                ->maxSize(2048)
                ->directory('board')
                ->disk('public')
                ->nullable(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->reorderable('sort_order')
            ->columns([
                Tables\Columns\ImageColumn::make('photo')
                    ->label('Photo')
                    ->disk('public')
                    ->circular(),

                Tables\Columns\TextColumn::make('name')
                    ->label('Nom')
                    ->searchable(),

                Tables\Columns\TextColumn::make('role')
                    ->label('Rôle'),

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
            'index' => Pages\ListBoardMembers::route('/'),
            'create' => Pages\CreateBoardMember::route('/create'),
            'edit' => Pages\EditBoardMember::route('/{record}/edit'),
        ];
    }
}
```

- [ ] **Step 5 : Exécuter les tests**

```bash
php artisan test tests/Feature/Admin/BoardMemberResourceTest.php
```

Résultat attendu : 4 tests PASS

- [ ] **Step 6 : Suite complète**

```bash
php artisan test
```

Résultat attendu : tous les tests passent

- [ ] **Step 7 : Commit**

```bash
git add app/Filament/Resources/BoardMemberResource.php \
        "app/Filament/Resources/BoardMemberResource/Pages/ListBoardMembers.php" \
        "app/Filament/Resources/BoardMemberResource/Pages/CreateBoardMember.php" \
        "app/Filament/Resources/BoardMemberResource/Pages/EditBoardMember.php" \
        tests/Feature/Admin/BoardMemberResourceTest.php
git commit -m "feat: add BoardMemberResource Filament with drag-and-drop reordering"
```

---

## Task 3 : Commission + CommissionMember — Migrations, Modèles, Factories

**Files:**
- Create: `database/migrations/2026_05_01_000002_create_commissions_table.php`
- Create: `database/migrations/2026_05_01_000003_create_commission_members_table.php`
- Create: `app/Models/Commission.php`
- Create: `app/Models/CommissionMember.php`
- Create: `database/factories/CommissionFactory.php`
- Create: `database/factories/CommissionMemberFactory.php`
- Create: `tests/Unit/Models/CommissionTest.php`
- Create: `tests/Unit/Models/CommissionMemberTest.php`

- [ ] **Step 1 : Écrire les tests (doivent échouer)**

Créer `tests/Unit/Models/CommissionTest.php` :

```php
<?php

namespace Tests\Unit\Models;

use App\Models\Commission;
use App\Models\CommissionMember;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CommissionTest extends TestCase
{
    use RefreshDatabase;

    public function test_commission_can_be_created_with_required_fields(): void
    {
        Commission::factory()->create(['name' => 'Commission sportive']);

        $this->assertDatabaseHas('commissions', ['name' => 'Commission sportive']);
    }

    public function test_description_is_nullable(): void
    {
        $commission = Commission::factory()->create(['description' => null]);

        $this->assertNull($commission->description);
    }

    public function test_commission_has_many_members(): void
    {
        $commission = Commission::factory()->create();
        CommissionMember::factory()->create(['commission_id' => $commission->id]);
        CommissionMember::factory()->create(['commission_id' => $commission->id]);

        $this->assertCount(2, $commission->members);
    }
}
```

Créer `tests/Unit/Models/CommissionMemberTest.php` :

```php
<?php

namespace Tests\Unit\Models;

use App\Models\Commission;
use App\Models\CommissionMember;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CommissionMemberTest extends TestCase
{
    use RefreshDatabase;

    public function test_commission_member_can_be_created_with_required_fields(): void
    {
        $commission = Commission::factory()->create();

        CommissionMember::factory()->create([
            'commission_id' => $commission->id,
            'name' => 'Marie Dupont',
            'role' => 'Présidente',
        ]);

        $this->assertDatabaseHas('commission_members', [
            'name' => 'Marie Dupont',
            'role' => 'Présidente',
        ]);
    }

    public function test_commission_member_belongs_to_commission(): void
    {
        $commission = Commission::factory()->create();
        $member = CommissionMember::factory()->create(['commission_id' => $commission->id]);

        $this->assertEquals($commission->id, $member->commission->id);
    }

    public function test_deleting_commission_cascades_to_members(): void
    {
        $commission = Commission::factory()->create();
        CommissionMember::factory()->create(['commission_id' => $commission->id]);

        $commission->delete();

        $this->assertDatabaseEmpty('commission_members');
    }
}
```

- [ ] **Step 2 : Exécuter pour vérifier l'échec**

```bash
php artisan test tests/Unit/Models/CommissionTest.php tests/Unit/Models/CommissionMemberTest.php
```

Résultat attendu : FAIL — `Class "App\Models\Commission" not found`

- [ ] **Step 3 : Créer la migration commissions**

Créer `database/migrations/2026_05_01_000002_create_commissions_table.php` :

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('commissions', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('commissions');
    }
};
```

- [ ] **Step 4 : Créer la migration commission_members**

Créer `database/migrations/2026_05_01_000003_create_commission_members_table.php` :

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('commission_members', function (Blueprint $table) {
            $table->id();
            $table->foreignId('commission_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('role');
            $table->text('bio')->nullable();
            $table->string('photo')->nullable();
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('commission_members');
    }
};
```

- [ ] **Step 5 : Créer le modèle Commission**

Créer `app/Models/Commission.php` :

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Commission extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'sort_order',
    ];

    public function members(): HasMany
    {
        return $this->hasMany(CommissionMember::class)->orderBy('sort_order');
    }
}
```

- [ ] **Step 6 : Créer le modèle CommissionMember**

Créer `app/Models/CommissionMember.php` :

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CommissionMember extends Model
{
    use HasFactory;

    protected $fillable = [
        'commission_id',
        'name',
        'role',
        'bio',
        'photo',
        'sort_order',
    ];

    public function commission(): BelongsTo
    {
        return $this->belongsTo(Commission::class);
    }
}
```

- [ ] **Step 7 : Créer les factories**

Créer `database/factories/CommissionFactory.php` :

```php
<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class CommissionFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => $this->faker->words(3, true),
            'description' => $this->faker->sentence(),
            'sort_order' => 0,
        ];
    }
}
```

Créer `database/factories/CommissionMemberFactory.php` :

```php
<?php

namespace Database\Factories;

use App\Models\Commission;
use Illuminate\Database\Eloquent\Factories\Factory;

class CommissionMemberFactory extends Factory
{
    public function definition(): array
    {
        return [
            'commission_id' => Commission::factory(),
            'name' => $this->faker->name(),
            'role' => $this->faker->randomElement(['Président(e)', 'Vice-Président(e)', 'Secrétaire', 'Membre']),
            'bio' => $this->faker->sentence(),
            'photo' => null,
            'sort_order' => 0,
        ];
    }
}
```

- [ ] **Step 8 : Migrer et exécuter les tests**

```bash
php artisan migrate
php artisan test tests/Unit/Models/CommissionTest.php tests/Unit/Models/CommissionMemberTest.php
```

Résultat attendu : 6 tests PASS

- [ ] **Step 9 : Suite complète**

```bash
php artisan test
```

Résultat attendu : tous les tests passent

- [ ] **Step 10 : Commit**

```bash
git add database/migrations/2026_05_01_000002_create_commissions_table.php \
        database/migrations/2026_05_01_000003_create_commission_members_table.php \
        app/Models/Commission.php \
        app/Models/CommissionMember.php \
        database/factories/CommissionFactory.php \
        database/factories/CommissionMemberFactory.php \
        tests/Unit/Models/CommissionTest.php \
        tests/Unit/Models/CommissionMemberTest.php
git commit -m "feat: add Commission and CommissionMember models, migrations and factories"
```

---

## Task 4 : CommissionResource Filament

**Files:**
- Create: `app/Filament/Resources/CommissionResource.php`
- Create: `app/Filament/Resources/CommissionResource/Pages/ListCommissions.php`
- Create: `app/Filament/Resources/CommissionResource/Pages/CreateCommission.php`
- Create: `app/Filament/Resources/CommissionResource/Pages/EditCommission.php`
- Create: `tests/Feature/Admin/CommissionResourceTest.php`

- [ ] **Step 1 : Écrire le test (doit échouer)**

Créer `tests/Feature/Admin/CommissionResourceTest.php` :

```php
<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CommissionResourceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
    }

    public function test_admin_can_access_commissions_list(): void
    {
        $user = User::factory()->create();
        $user->assignRole('admin');

        $this->actingAs($user)
            ->get('/admin/commissions')
            ->assertSuccessful();
    }

    public function test_super_admin_can_access_commissions_list(): void
    {
        $user = User::factory()->create();
        $user->assignRole('super_admin');

        $this->actingAs($user)
            ->get('/admin/commissions')
            ->assertSuccessful();
    }

    public function test_unauthenticated_user_cannot_access_commissions_list(): void
    {
        $this->get('/admin/commissions')->assertRedirect('/admin/login');
    }

    public function test_authenticated_user_without_role_cannot_access_commissions_list(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get('/admin/commissions')
            ->assertForbidden();
    }
}
```

- [ ] **Step 2 : Exécuter pour vérifier l'échec**

```bash
php artisan test tests/Feature/Admin/CommissionResourceTest.php
```

Résultat attendu : FAIL — 404

- [ ] **Step 3 : Créer les pages Filament**

Créer `app/Filament/Resources/CommissionResource/Pages/ListCommissions.php` :

```php
<?php

namespace App\Filament\Resources\CommissionResource\Pages;

use App\Filament\Resources\CommissionResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListCommissions extends ListRecords
{
    protected static string $resource = CommissionResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\CreateAction::make()];
    }
}
```

Créer `app/Filament/Resources/CommissionResource/Pages/CreateCommission.php` :

```php
<?php

namespace App\Filament\Resources\CommissionResource\Pages;

use App\Filament\Resources\CommissionResource;
use Filament\Resources\Pages\CreateRecord;

class CreateCommission extends CreateRecord
{
    protected static string $resource = CommissionResource::class;
}
```

Créer `app/Filament/Resources/CommissionResource/Pages/EditCommission.php` :

```php
<?php

namespace App\Filament\Resources\CommissionResource\Pages;

use App\Filament\Resources\CommissionResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditCommission extends EditRecord
{
    protected static string $resource = CommissionResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\DeleteAction::make()];
    }
}
```

- [ ] **Step 4 : Créer CommissionResource**

Créer `app/Filament/Resources/CommissionResource.php` :

```php
<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CommissionResource\Pages;
use App\Models\Commission;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class CommissionResource extends Resource
{
    protected static ?string $model = Commission::class;
    protected static ?string $navigationIcon = 'heroicon-o-user-circle';
    protected static ?string $navigationLabel = 'Commissions';
    protected static ?string $modelLabel = 'Commission';
    protected static ?string $pluralModelLabel = 'Commissions';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\TextInput::make('name')
                ->label('Nom')
                ->required(),

            Forms\Components\Textarea::make('description')
                ->label('Description')
                ->columnSpanFull()
                ->nullable(),

            Forms\Components\Repeater::make('members')
                ->label('Membres')
                ->relationship('members')
                ->reorderableWithDragAndDrop()
                ->columnSpanFull()
                ->schema([
                    Forms\Components\TextInput::make('name')
                        ->label('Nom')
                        ->required(),

                    Forms\Components\TextInput::make('role')
                        ->label('Rôle')
                        ->required(),

                    Forms\Components\Textarea::make('bio')
                        ->label('Bio')
                        ->nullable(),

                    Forms\Components\FileUpload::make('photo')
                        ->label('Photo')
                        ->image()
                        ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp'])
                        ->maxSize(2048)
                        ->directory('commissions')
                        ->disk('public')
                        ->nullable(),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->reorderable('sort_order')
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Nom')
                    ->searchable(),

                Tables\Columns\TextColumn::make('description')
                    ->label('Description')
                    ->limit(60),

                Tables\Columns\TextColumn::make('members_count')
                    ->label('Membres')
                    ->counts('members'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCommissions::route('/'),
            'create' => Pages\CreateCommission::route('/create'),
            'edit' => Pages\EditCommission::route('/{record}/edit'),
        ];
    }
}
```

- [ ] **Step 5 : Exécuter les tests**

```bash
php artisan test tests/Feature/Admin/CommissionResourceTest.php
```

Résultat attendu : 4 tests PASS

- [ ] **Step 6 : Suite complète**

```bash
php artisan test
```

Résultat attendu : tous les tests passent

- [ ] **Step 7 : Commit**

```bash
git add app/Filament/Resources/CommissionResource.php \
        "app/Filament/Resources/CommissionResource/Pages/ListCommissions.php" \
        "app/Filament/Resources/CommissionResource/Pages/CreateCommission.php" \
        "app/Filament/Resources/CommissionResource/Pages/EditCommission.php" \
        tests/Feature/Admin/CommissionResourceTest.php
git commit -m "feat: add CommissionResource Filament with member Repeater"
```

---

## Task 5 : LeClubController + Routes

**Files:**
- Modify: `app/Http/Controllers/LeClubController.php`
- Modify: `routes/web.php`
- Modify: `tests/Feature/LeClubControllerTest.php`

- [ ] **Step 1 : Écrire les tests (doivent échouer)**

Ajouter ces 4 méthodes à la fin de `tests/Feature/LeClubControllerTest.php` (avant le `}` fermant la classe) :

```php
    public function test_bureau_retourne_les_membres_du_bureau(): void
    {
        BoardMember::factory()->create(['name' => 'Jean Martin', 'sort_order' => 1]);
        BoardMember::factory()->create(['name' => 'Marie Dupont', 'sort_order' => 2]);

        $this->get('/le-club/bureau')
            ->assertOk()
            ->assertInertia(fn (Assert $page) =>
                $page->component('LeClub/Bureau')
                    ->has('membres', 2)
                    ->where('membres.0.name', 'Jean Martin')
                    ->where('membres.1.name', 'Marie Dupont')
            );
    }

    public function test_bureau_retourne_membres_vide_sans_donnees(): void
    {
        $this->get('/le-club/bureau')
            ->assertOk()
            ->assertInertia(fn (Assert $page) =>
                $page->component('LeClub/Bureau')
                    ->where('membres', [])
            );
    }

    public function test_commissions_retourne_les_commissions_avec_membres(): void
    {
        $commission = Commission::factory()->create(['name' => 'Commission sportive', 'sort_order' => 1]);
        CommissionMember::factory()->create([
            'commission_id' => $commission->id,
            'name' => 'Alice',
            'sort_order' => 1,
        ]);

        $this->get('/le-club/commissions')
            ->assertOk()
            ->assertInertia(fn (Assert $page) =>
                $page->component('LeClub/Commissions')
                    ->has('commissions', 1)
                    ->where('commissions.0.name', 'Commission sportive')
                    ->has('commissions.0.members', 1)
                    ->where('commissions.0.members.0.name', 'Alice')
            );
    }

    public function test_commissions_sont_triees_par_sort_order(): void
    {
        Commission::factory()->create(['name' => 'Commission B', 'sort_order' => 2]);
        Commission::factory()->create(['name' => 'Commission A', 'sort_order' => 1]);

        $this->get('/le-club/commissions')
            ->assertOk()
            ->assertInertia(fn (Assert $page) =>
                $page->component('LeClub/Commissions')
                    ->where('commissions.0.name', 'Commission A')
                    ->where('commissions.1.name', 'Commission B')
            );
    }
```

Ajouter également les imports manquants en haut du fichier `tests/Feature/LeClubControllerTest.php` :

```php
use App\Models\BoardMember;
use App\Models\Commission;
use App\Models\CommissionMember;
```

- [ ] **Step 2 : Exécuter pour vérifier l'échec**

```bash
php artisan test tests/Feature/LeClubControllerTest.php
```

Résultat attendu : les 6 tests existants passent, les 4 nouveaux FAIL — 404

- [ ] **Step 3 : Ajouter les méthodes au contrôleur**

Modifier `app/Http/Controllers/LeClubController.php` — ajouter les imports et les deux méthodes :

```php
<?php

namespace App\Http\Controllers;

use App\Models\BoardMember;
use App\Models\ClubPresentation;
use App\Models\Commission;
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

    public function bureau(): Response
    {
        $membres = BoardMember::orderBy('sort_order')->get();

        return Inertia::render('LeClub/Bureau', [
            'membres' => $membres,
        ]);
    }

    public function commissions(): Response
    {
        $commissions = Commission::with(['members' => fn ($q) => $q->orderBy('sort_order')])
            ->orderBy('sort_order')
            ->get();

        return Inertia::render('LeClub/Commissions', [
            'commissions' => $commissions,
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

Modifier `routes/web.php` — ajouter les 2 routes à la fin :

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
Route::get('/le-club/bureau', [LeClubController::class, 'bureau'])->name('le-club.bureau');
Route::get('/le-club/commissions', [LeClubController::class, 'commissions'])->name('le-club.commissions');
```

- [ ] **Step 5 : Exécuter les tests**

```bash
php artisan test tests/Feature/LeClubControllerTest.php
```

Résultat attendu : 10 tests PASS (6 existants + 4 nouveaux)

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
git commit -m "feat: add bureau and commissions methods to LeClubController"
```

---

## Task 6 : Pages Vue

**Files:**
- Create: `resources/js/pages/LeClub/Bureau.vue`
- Create: `resources/js/pages/LeClub/Commissions.vue`
- Modify: `resources/js/pages/LeClub/Index.vue`

- [ ] **Step 1 : Vérifier la baseline PHP**

```bash
php artisan test
```

Résultat attendu : tous les tests passent

- [ ] **Step 2 : Créer LeClub/Bureau.vue**

Créer `resources/js/pages/LeClub/Bureau.vue` :

```vue
<script setup>
defineProps({
    membres: {
        type: Array,
        required: true,
    },
});
</script>

<template>
    <div>
        <h1>Bureau & Conseil d'Administration</h1>

        <p v-if="membres.length === 0">
            Aucun membre enregistré pour le moment.
        </p>

        <div v-else class="staff-grid">
            <div
                v-for="membre in membres"
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
                <p class="staff-role">{{ membre.role }}</p>
                <p v-if="membre.bio" class="staff-bio">{{ membre.bio }}</p>
            </div>
        </div>
    </div>
</template>

<style scoped>
.staff-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 1.5rem;
    margin-top: 1.5rem;
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

.staff-role {
    font-size: 0.875rem;
    color: #003A5D;
    font-weight: 500;
}

.staff-bio {
    font-size: 0.875rem;
    color: #6b7280;
}
</style>
```

- [ ] **Step 3 : Créer LeClub/Commissions.vue**

Créer `resources/js/pages/LeClub/Commissions.vue` :

```vue
<script setup>
defineProps({
    commissions: {
        type: Array,
        required: true,
    },
});
</script>

<template>
    <div>
        <h1>Nos Commissions</h1>

        <p v-if="commissions.length === 0">
            Aucune commission enregistrée pour le moment.
        </p>

        <div v-for="commission in commissions" :key="commission.id" class="commission">
            <h2>{{ commission.name }}</h2>

            <p v-if="commission.description" class="commission-description">
                {{ commission.description }}
            </p>

            <div v-if="commission.members.length > 0" class="staff-grid">
                <div
                    v-for="membre in commission.members"
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
                    <p class="staff-role">{{ membre.role }}</p>
                    <p v-if="membre.bio" class="staff-bio">{{ membre.bio }}</p>
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>
.commission {
    margin-bottom: 3rem;
}

.commission-description {
    color: #6b7280;
    margin-bottom: 1rem;
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

.staff-role {
    font-size: 0.875rem;
    color: #003A5D;
    font-weight: 500;
}

.staff-bio {
    font-size: 0.875rem;
    color: #6b7280;
}
</style>
```

- [ ] **Step 4 : Mettre à jour LeClub/Index.vue**

Remplacer `resources/js/pages/LeClub/Index.vue` :

```vue
<script setup>
</script>

<template>
    <div>
        <h1>Le Club</h1>

        <nav class="club-nav">
            <a href="/le-club/presentation">Présentation du club</a>
            <a href="/le-club/bureau">Bureau & Conseil d'Administration</a>
            <a href="/le-club/commissions">Commissions</a>
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

- [ ] **Step 5 : Suite complète**

```bash
php artisan test
```

Résultat attendu : tous les tests passent

- [ ] **Step 6 : Commit**

```bash
git add resources/js/pages/LeClub/Bureau.vue \
        resources/js/pages/LeClub/Commissions.vue \
        resources/js/pages/LeClub/Index.vue
git commit -m "feat: add Bureau and Commissions Vue pages, update LeClub navigation"
```

---

## Vérification finale

- [ ] **Lancer la suite complète**

```bash
php artisan test
```

Résultat attendu : tous les tests passent, aucune régression

- [ ] **Utiliser superpowers:finishing-a-development-branch pour finaliser**
