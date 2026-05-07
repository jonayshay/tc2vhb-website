# Iteration 5a — Module Équipes — Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Ajouter les pages publiques `/equipes` et `/equipes/{slug}` avec gestion Filament des saisons, catégories, équipes et joueurs, et import CSV depuis l'export fédération FFHandball.

**Architecture:** Quatre modèles liés (`Season` → `Category` → `Team` + `Player`). Un `SeasonObserver` garantit qu'une seule saison est courante à la fois. L'import CSV est encapsulé dans un `PlayerImportService` testé indépendamment de Filament. Le frontend suit les mêmes patterns que les pages LeClub existantes.

**Tech Stack:** Laravel 12, Filament 3, Inertia.js v3, Vue.js 3, PHPUnit, spatie/simple-excel

---

## File Structure

| Fichier | Action | Rôle |
|---|---|---|
| `database/migrations/2026_05_02_000001_create_seasons_table.php` | Créer | Table `seasons` |
| `database/migrations/2026_05_02_000002_create_categories_table.php` | Créer | Table `categories` |
| `database/migrations/2026_05_02_000003_create_teams_table.php` | Créer | Table `teams` |
| `database/migrations/2026_05_02_000004_create_players_table.php` | Créer | Table `players` |
| `app/Models/Season.php` | Créer | Modèle avec hasMany categories |
| `app/Models/Category.php` | Créer | Modèle avec slug auto, hasMany teams + players |
| `app/Models/Team.php` | Créer | Modèle avec belongsTo category |
| `app/Models/Player.php` | Créer | Modèle avec belongsTo category (nullable) |
| `database/factories/SeasonFactory.php` | Créer | Factory |
| `database/factories/CategoryFactory.php` | Créer | Factory |
| `database/factories/TeamFactory.php` | Créer | Factory |
| `database/factories/PlayerFactory.php` | Créer | Factory |
| `app/Observers/SeasonObserver.php` | Créer | Garantit un seul is_current=true |
| `app/Providers/AppServiceProvider.php` | Modifier | Enregistrer SeasonObserver |
| `tests/Unit/Models/SeasonTest.php` | Créer | Tests unitaires Season |
| `tests/Unit/Models/CategoryTest.php` | Créer | Tests unitaires Category |
| `tests/Unit/Models/TeamTest.php` | Créer | Tests unitaires Team |
| `tests/Unit/Models/PlayerTest.php` | Créer | Tests unitaires Player |
| `tests/Feature/SeasonObserverTest.php` | Créer | Tests observer |
| `app/Filament/Resources/SeasonResource.php` | Créer | Resource Filament + action setCurrent |
| `app/Filament/Resources/SeasonResource/Pages/ListSeasons.php` | Créer | Page liste |
| `app/Filament/Resources/SeasonResource/Pages/CreateSeason.php` | Créer | Page création |
| `app/Filament/Resources/SeasonResource/Pages/EditSeason.php` | Créer | Page édition |
| `app/Filament/Resources/CategoryResource.php` | Créer | Resource Filament |
| `app/Filament/Resources/CategoryResource/Pages/ListCategories.php` | Créer | Page liste |
| `app/Filament/Resources/CategoryResource/Pages/CreateCategory.php` | Créer | Page création |
| `app/Filament/Resources/CategoryResource/Pages/EditCategory.php` | Créer | Page édition |
| `tests/Feature/Admin/SeasonResourceTest.php` | Créer | Tests accès admin |
| `tests/Feature/Admin/CategoryResourceTest.php` | Créer | Tests accès admin |
| `app/Filament/Resources/TeamResource.php` | Créer | Resource Filament |
| `app/Filament/Resources/TeamResource/Pages/ListTeams.php` | Créer | Page liste |
| `app/Filament/Resources/TeamResource/Pages/CreateTeam.php` | Créer | Page création |
| `app/Filament/Resources/TeamResource/Pages/EditTeam.php` | Créer | Page édition |
| `app/Filament/Resources/PlayerResource.php` | Créer | Resource Filament |
| `app/Filament/Resources/PlayerResource/Pages/ListPlayers.php` | Créer | Page liste + import action |
| `app/Filament/Resources/PlayerResource/Pages/CreatePlayer.php` | Créer | Page création |
| `app/Filament/Resources/PlayerResource/Pages/EditPlayer.php` | Créer | Page édition |
| `tests/Feature/Admin/TeamResourceTest.php` | Créer | Tests accès admin |
| `tests/Feature/Admin/PlayerResourceTest.php` | Créer | Tests accès admin |
| `app/Services/PlayerImportService.php` | Créer | Logique d'import CSV |
| `tests/Feature/PlayerImportServiceTest.php` | Créer | Tests import |
| `app/Http/Controllers/EquipesController.php` | Créer | Méthodes index() et show() |
| `routes/web.php` | Modifier | 2 nouvelles routes |
| `tests/Feature/EquipesControllerTest.php` | Créer | Tests controller |
| `resources/js/pages/Equipes/Index.vue` | Créer | Page liste catégories |
| `resources/js/pages/Equipes/Show.vue` | Créer | Page catégorie (équipes + joueurs) |

---

## Task 1 : Season, Category, Team, Player — Migrations, Modèles, Factories, Observer

**Files:**
- Create: `database/migrations/2026_05_02_000001_create_seasons_table.php`
- Create: `database/migrations/2026_05_02_000002_create_categories_table.php`
- Create: `database/migrations/2026_05_02_000003_create_teams_table.php`
- Create: `database/migrations/2026_05_02_000004_create_players_table.php`
- Create: `app/Models/Season.php`
- Create: `app/Models/Category.php`
- Create: `app/Models/Team.php`
- Create: `app/Models/Player.php`
- Create: `database/factories/SeasonFactory.php`
- Create: `database/factories/CategoryFactory.php`
- Create: `database/factories/TeamFactory.php`
- Create: `database/factories/PlayerFactory.php`
- Create: `app/Observers/SeasonObserver.php`
- Modify: `app/Providers/AppServiceProvider.php`
- Create: `tests/Unit/Models/SeasonTest.php`
- Create: `tests/Unit/Models/CategoryTest.php`
- Create: `tests/Unit/Models/TeamTest.php`
- Create: `tests/Unit/Models/PlayerTest.php`
- Create: `tests/Feature/SeasonObserverTest.php`

- [ ] **Step 1 : Écrire les tests (doivent échouer)**

Créer `tests/Unit/Models/SeasonTest.php` :

```php
<?php

namespace Tests\Unit\Models;

use App\Models\Category;
use App\Models\Season;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SeasonTest extends TestCase
{
    use RefreshDatabase;

    public function test_season_can_be_created(): void
    {
        Season::factory()->create([
            'name' => '2026-2027',
            'is_current' => false,
        ]);

        $this->assertDatabaseHas('seasons', ['name' => '2026-2027']);
    }

    public function test_season_has_many_categories(): void
    {
        $season = Season::factory()->create();
        Category::factory()->create(['season_id' => $season->id]);
        Category::factory()->create(['season_id' => $season->id]);

        $this->assertCount(2, $season->categories);
    }
}
```

Créer `tests/Unit/Models/CategoryTest.php` :

```php
<?php

namespace Tests\Unit\Models;

use App\Models\Category;
use App\Models\Player;
use App\Models\Season;
use App\Models\Team;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CategoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_category_can_be_created(): void
    {
        $season = Season::factory()->create();
        Category::factory()->create([
            'season_id' => $season->id,
            'name'      => 'U13 Masculins',
            'gender'    => 'M',
        ]);

        $this->assertDatabaseHas('categories', ['name' => 'U13 Masculins']);
    }

    public function test_category_belongs_to_season(): void
    {
        $season = Season::factory()->create();
        $category = Category::factory()->create(['season_id' => $season->id]);

        $this->assertEquals($season->id, $category->season->id);
    }

    public function test_category_has_many_teams(): void
    {
        $category = Category::factory()->create();
        Team::factory()->create(['category_id' => $category->id]);
        Team::factory()->create(['category_id' => $category->id]);

        $this->assertCount(2, $category->teams);
    }

    public function test_category_has_many_players(): void
    {
        $category = Category::factory()->create();
        Player::factory()->create(['category_id' => $category->id]);
        Player::factory()->create(['category_id' => $category->id]);

        $this->assertCount(2, $category->players);
    }

    public function test_slug_is_auto_generated_from_name_when_empty(): void
    {
        $category = Category::factory()->create([
            'name' => 'U13 Masculins',
            'slug' => '',
        ]);

        $this->assertEquals('u13-masculins', $category->fresh()->slug);
    }
}
```

Créer `tests/Unit/Models/TeamTest.php` :

```php
<?php

namespace Tests\Unit\Models;

use App\Models\Category;
use App\Models\Team;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TeamTest extends TestCase
{
    use RefreshDatabase;

    public function test_team_can_be_created(): void
    {
        $category = Category::factory()->create();
        Team::factory()->create([
            'category_id' => $category->id,
            'name'        => 'Équipe 1',
        ]);

        $this->assertDatabaseHas('teams', ['name' => 'Équipe 1']);
    }

    public function test_team_belongs_to_category(): void
    {
        $category = Category::factory()->create();
        $team = Team::factory()->create(['category_id' => $category->id]);

        $this->assertEquals($category->id, $team->category->id);
    }

    public function test_photo_and_scorenco_id_are_nullable(): void
    {
        $team = Team::factory()->create(['photo' => null, 'scorenco_id' => null]);

        $this->assertNull($team->photo);
        $this->assertNull($team->scorenco_id);
    }
}
```

Créer `tests/Unit/Models/PlayerTest.php` :

```php
<?php

namespace Tests\Unit\Models;

use App\Models\Category;
use App\Models\Player;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PlayerTest extends TestCase
{
    use RefreshDatabase;

    public function test_player_can_be_created(): void
    {
        Player::factory()->create([
            'last_name'  => 'Dupont',
            'first_name' => 'Jean',
            'birth_date' => '2014-01-15',
        ]);

        $this->assertDatabaseHas('players', ['last_name' => 'Dupont']);
    }

    public function test_player_belongs_to_category(): void
    {
        $category = Category::factory()->create();
        $player = Player::factory()->create(['category_id' => $category->id]);

        $this->assertEquals($category->id, $player->category->id);
    }

    public function test_category_is_nullable(): void
    {
        $player = Player::factory()->create(['category_id' => null]);

        $this->assertNull($player->category_id);
    }

    public function test_optional_fields_are_nullable(): void
    {
        $player = Player::factory()->create([
            'photo'          => null,
            'gender'         => null,
            'license_number' => null,
        ]);

        $this->assertNull($player->photo);
        $this->assertNull($player->gender);
        $this->assertNull($player->license_number);
    }

    public function test_has_image_rights_defaults_to_false(): void
    {
        $player = Player::factory()->create();

        $this->assertFalse($player->has_image_rights);
    }
}
```

Créer `tests/Feature/SeasonObserverTest.php` :

```php
<?php

namespace Tests\Feature;

use App\Models\Season;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SeasonObserverTest extends TestCase
{
    use RefreshDatabase;

    public function test_when_season_set_as_current_others_become_not_current(): void
    {
        $first = Season::factory()->create(['is_current' => true]);
        $second = Season::factory()->create(['is_current' => false]);

        $second->update(['is_current' => true]);

        $this->assertFalse($first->fresh()->is_current);
        $this->assertTrue($second->fresh()->is_current);
    }

    public function test_is_current_false_does_not_affect_others(): void
    {
        $first = Season::factory()->create(['is_current' => true]);
        $second = Season::factory()->create(['is_current' => false]);

        $second->update(['name' => '2027-2028']);

        $this->assertTrue($first->fresh()->is_current);
    }

    public function test_updating_other_fields_does_not_affect_is_current(): void
    {
        $first = Season::factory()->create(['is_current' => true]);
        $first->update(['name' => 'Nouvelle saison']);

        $this->assertTrue($first->fresh()->is_current);
    }
}
```

- [ ] **Step 2 : Exécuter pour vérifier l'échec**

```bash
php artisan test tests/Unit/Models/SeasonTest.php tests/Unit/Models/CategoryTest.php tests/Unit/Models/TeamTest.php tests/Unit/Models/PlayerTest.php tests/Feature/SeasonObserverTest.php
```

Résultat attendu : FAIL — `Class "App\Models\Season" not found`

- [ ] **Step 3 : Créer les migrations**

Créer `database/migrations/2026_05_02_000001_create_seasons_table.php` :

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('seasons', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->date('starts_at')->nullable();
            $table->date('ends_at')->nullable();
            $table->boolean('is_current')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('seasons');
    }
};
```

Créer `database/migrations/2026_05_02_000002_create_categories_table.php` :

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('categories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('season_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('slug');
            $table->enum('gender', ['M', 'F', 'Mixte']);
            $table->integer('birth_year_min');
            $table->integer('birth_year_max');
            $table->timestamps();

            $table->unique(['slug', 'season_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('categories');
    }
};
```

Créer `database/migrations/2026_05_02_000003_create_teams_table.php` :

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('teams', function (Blueprint $table) {
            $table->id();
            $table->foreignId('category_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('photo')->nullable();
            $table->string('scorenco_id')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('teams');
    }
};
```

Créer `database/migrations/2026_05_02_000004_create_players_table.php` :

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('players', function (Blueprint $table) {
            $table->id();
            $table->foreignId('category_id')->nullable()->constrained()->nullOnDelete();
            $table->string('last_name');
            $table->string('first_name');
            $table->date('birth_date');
            $table->string('gender')->nullable();
            $table->string('license_number')->nullable();
            $table->string('photo')->nullable();
            $table->boolean('has_image_rights')->default(false);
            $table->timestamps();

            $table->unique(['last_name', 'first_name', 'birth_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('players');
    }
};
```

- [ ] **Step 4 : Créer les modèles**

Créer `app/Models/Season.php` :

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Season extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'starts_at',
        'ends_at',
        'is_current',
    ];

    protected $casts = [
        'starts_at'  => 'date',
        'ends_at'    => 'date',
        'is_current' => 'boolean',
    ];

    public function categories(): HasMany
    {
        return $this->hasMany(Category::class);
    }
}
```

Créer `app/Models/Category.php` :

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Category extends Model
{
    use HasFactory;

    protected $fillable = [
        'season_id',
        'name',
        'slug',
        'gender',
        'birth_year_min',
        'birth_year_max',
    ];

    protected static function booted(): void
    {
        static::creating(function (Category $category): void {
            if (empty($category->slug)) {
                $category->slug = Str::slug($category->name);
            }
        });
    }

    public function season(): BelongsTo
    {
        return $this->belongsTo(Season::class);
    }

    public function teams(): HasMany
    {
        return $this->hasMany(Team::class);
    }

    public function players(): HasMany
    {
        return $this->hasMany(Player::class);
    }
}
```

Créer `app/Models/Team.php` :

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Team extends Model
{
    use HasFactory;

    protected $fillable = [
        'category_id',
        'name',
        'photo',
        'scorenco_id',
    ];

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }
}
```

Créer `app/Models/Player.php` :

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Player extends Model
{
    use HasFactory;

    protected $fillable = [
        'category_id',
        'last_name',
        'first_name',
        'birth_date',
        'gender',
        'license_number',
        'photo',
        'has_image_rights',
    ];

    protected $casts = [
        'birth_date'       => 'date',
        'has_image_rights' => 'boolean',
    ];

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }
}
```

- [ ] **Step 5 : Créer les factories**

Créer `database/factories/SeasonFactory.php` :

```php
<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class SeasonFactory extends Factory
{
    public function definition(): array
    {
        $year = $this->faker->numberBetween(2024, 2030);

        return [
            'name'       => "{$year}-" . ($year + 1),
            'starts_at'  => "{$year}-09-01",
            'ends_at'    => ($year + 1) . '-06-30',
            'is_current' => false,
        ];
    }
}
```

Créer `database/factories/CategoryFactory.php` :

```php
<?php

namespace Database\Factories;

use App\Models\Season;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class CategoryFactory extends Factory
{
    public function definition(): array
    {
        $name = $this->faker->randomElement(['U9', 'U11', 'U13', 'U15', 'U17', 'Seniors'])
            . ' '
            . $this->faker->randomElement(['Masculins', 'Féminines', 'Mixte']);

        return [
            'season_id'      => Season::factory(),
            'name'           => $name,
            'slug'           => Str::slug($name),
            'gender'         => $this->faker->randomElement(['M', 'F', 'Mixte']),
            'birth_year_min' => 2010,
            'birth_year_max' => 2011,
        ];
    }
}
```

Créer `database/factories/TeamFactory.php` :

```php
<?php

namespace Database\Factories;

use App\Models\Category;
use Illuminate\Database\Eloquent\Factories\Factory;

class TeamFactory extends Factory
{
    public function definition(): array
    {
        return [
            'category_id' => Category::factory(),
            'name'        => 'Équipe ' . $this->faker->numberBetween(1, 3),
            'photo'       => null,
            'scorenco_id' => null,
        ];
    }
}
```

Créer `database/factories/PlayerFactory.php` :

```php
<?php

namespace Database\Factories;

use App\Models\Category;
use Illuminate\Database\Eloquent\Factories\Factory;

class PlayerFactory extends Factory
{
    public function definition(): array
    {
        return [
            'category_id'      => Category::factory(),
            'last_name'        => $this->faker->lastName(),
            'first_name'       => $this->faker->firstName(),
            'birth_date'       => $this->faker->dateTimeBetween('-20 years', '-8 years')->format('Y-m-d'),
            'gender'           => $this->faker->randomElement(['M', 'F']),
            'license_number'   => null,
            'photo'            => null,
            'has_image_rights' => false,
        ];
    }
}
```

- [ ] **Step 6 : Créer l'observer**

Créer `app/Observers/SeasonObserver.php` :

```php
<?php

namespace App\Observers;

use App\Models\Season;

class SeasonObserver
{
    public function updating(Season $season): void
    {
        if ($season->isDirty('is_current') && $season->is_current) {
            Season::where('id', '!=', $season->id)->update(['is_current' => false]);
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
use App\Models\Season;
use App\Observers\BoardMemberObserver;
use App\Observers\NewsObserver;
use App\Observers\PartnerObserver;
use App\Observers\SeasonObserver;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void {}

    public function boot(): void
    {
        BoardMember::observe(BoardMemberObserver::class);
        News::observe(NewsObserver::class);
        Partner::observe(PartnerObserver::class);
        Season::observe(SeasonObserver::class);
    }
}
```

- [ ] **Step 8 : Migrer et exécuter les tests**

```bash
php artisan migrate
php artisan test tests/Unit/Models/SeasonTest.php tests/Unit/Models/CategoryTest.php tests/Unit/Models/TeamTest.php tests/Unit/Models/PlayerTest.php tests/Feature/SeasonObserverTest.php
```

Résultat attendu : 14 tests PASS

- [ ] **Step 9 : Suite complète**

```bash
php artisan test
```

Résultat attendu : tous les tests passent

- [ ] **Step 10 : Commit**

```bash
git add database/migrations/2026_05_02_000001_create_seasons_table.php \
        database/migrations/2026_05_02_000002_create_categories_table.php \
        database/migrations/2026_05_02_000003_create_teams_table.php \
        database/migrations/2026_05_02_000004_create_players_table.php \
        app/Models/Season.php \
        app/Models/Category.php \
        app/Models/Team.php \
        app/Models/Player.php \
        database/factories/SeasonFactory.php \
        database/factories/CategoryFactory.php \
        database/factories/TeamFactory.php \
        database/factories/PlayerFactory.php \
        app/Observers/SeasonObserver.php \
        app/Providers/AppServiceProvider.php \
        tests/Unit/Models/SeasonTest.php \
        tests/Unit/Models/CategoryTest.php \
        tests/Unit/Models/TeamTest.php \
        tests/Unit/Models/PlayerTest.php \
        tests/Feature/SeasonObserverTest.php
git commit -m "feat: add Season, Category, Team, Player models, migrations, factories and SeasonObserver"
```

---

## Task 2 : SeasonResource + CategoryResource Filament

**Files:**
- Create: `app/Filament/Resources/SeasonResource.php`
- Create: `app/Filament/Resources/SeasonResource/Pages/ListSeasons.php`
- Create: `app/Filament/Resources/SeasonResource/Pages/CreateSeason.php`
- Create: `app/Filament/Resources/SeasonResource/Pages/EditSeason.php`
- Create: `app/Filament/Resources/CategoryResource.php`
- Create: `app/Filament/Resources/CategoryResource/Pages/ListCategories.php`
- Create: `app/Filament/Resources/CategoryResource/Pages/CreateCategory.php`
- Create: `app/Filament/Resources/CategoryResource/Pages/EditCategory.php`
- Create: `tests/Feature/Admin/SeasonResourceTest.php`
- Create: `tests/Feature/Admin/CategoryResourceTest.php`

- [ ] **Step 1 : Écrire les tests (doivent échouer)**

Créer `tests/Feature/Admin/SeasonResourceTest.php` :

```php
<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SeasonResourceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
    }

    public function test_admin_can_access_seasons_list(): void
    {
        $user = User::factory()->create();
        $user->assignRole('admin');

        $this->actingAs($user)->get('/admin/seasons')->assertSuccessful();
    }

    public function test_super_admin_can_access_seasons_list(): void
    {
        $user = User::factory()->create();
        $user->assignRole('super_admin');

        $this->actingAs($user)->get('/admin/seasons')->assertSuccessful();
    }

    public function test_unauthenticated_user_cannot_access_seasons_list(): void
    {
        $this->get('/admin/seasons')->assertRedirect('/admin/login');
    }

    public function test_authenticated_user_without_role_cannot_access_seasons_list(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get('/admin/seasons')->assertForbidden();
    }
}
```

Créer `tests/Feature/Admin/CategoryResourceTest.php` :

```php
<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CategoryResourceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
    }

    public function test_admin_can_access_categories_list(): void
    {
        $user = User::factory()->create();
        $user->assignRole('admin');

        $this->actingAs($user)->get('/admin/categories')->assertSuccessful();
    }

    public function test_super_admin_can_access_categories_list(): void
    {
        $user = User::factory()->create();
        $user->assignRole('super_admin');

        $this->actingAs($user)->get('/admin/categories')->assertSuccessful();
    }

    public function test_unauthenticated_user_cannot_access_categories_list(): void
    {
        $this->get('/admin/categories')->assertRedirect('/admin/login');
    }

    public function test_authenticated_user_without_role_cannot_access_categories_list(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get('/admin/categories')->assertForbidden();
    }
}
```

- [ ] **Step 2 : Exécuter pour vérifier l'échec**

```bash
php artisan test tests/Feature/Admin/SeasonResourceTest.php tests/Feature/Admin/CategoryResourceTest.php
```

Résultat attendu : FAIL — 404

- [ ] **Step 3 : Créer les pages Filament pour SeasonResource**

Créer `app/Filament/Resources/SeasonResource/Pages/ListSeasons.php` :

```php
<?php

namespace App\Filament\Resources\SeasonResource\Pages;

use App\Filament\Resources\SeasonResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListSeasons extends ListRecords
{
    protected static string $resource = SeasonResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\CreateAction::make()];
    }
}
```

Créer `app/Filament/Resources/SeasonResource/Pages/CreateSeason.php` :

```php
<?php

namespace App\Filament\Resources\SeasonResource\Pages;

use App\Filament\Resources\SeasonResource;
use Filament\Resources\Pages\CreateRecord;

class CreateSeason extends CreateRecord
{
    protected static string $resource = SeasonResource::class;
}
```

Créer `app/Filament/Resources/SeasonResource/Pages/EditSeason.php` :

```php
<?php

namespace App\Filament\Resources\SeasonResource\Pages;

use App\Filament\Resources\SeasonResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditSeason extends EditRecord
{
    protected static string $resource = SeasonResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\DeleteAction::make()];
    }
}
```

- [ ] **Step 4 : Créer SeasonResource**

Créer `app/Filament/Resources/SeasonResource.php` :

```php
<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SeasonResource\Pages;
use App\Models\Season;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class SeasonResource extends Resource
{
    protected static ?string $model = Season::class;
    protected static ?string $navigationIcon = 'heroicon-o-calendar';
    protected static ?string $navigationLabel = 'Saisons';
    protected static ?string $modelLabel = 'Saison';
    protected static ?string $pluralModelLabel = 'Saisons';
    protected static ?string $navigationGroup = 'Équipes';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\TextInput::make('name')
                ->label('Nom')
                ->required()
                ->placeholder('2026-2027'),

            Forms\Components\DatePicker::make('starts_at')
                ->label('Début'),

            Forms\Components\DatePicker::make('ends_at')
                ->label('Fin'),

            Forms\Components\Toggle::make('is_current')
                ->label('Saison courante'),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Nom')
                    ->searchable(),

                Tables\Columns\TextColumn::make('starts_at')
                    ->label('Début')
                    ->date('d/m/Y'),

                Tables\Columns\TextColumn::make('ends_at')
                    ->label('Fin')
                    ->date('d/m/Y'),

                Tables\Columns\IconColumn::make('is_current')
                    ->label('Courante')
                    ->boolean(),
            ])
            ->actions([
                Tables\Actions\Action::make('setCurrent')
                    ->label('Définir comme courante')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(fn (Season $record): bool => ! $record->is_current)
                    ->requiresConfirmation()
                    ->action(fn (Season $record) => $record->update(['is_current' => true])),

                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListSeasons::route('/'),
            'create' => Pages\CreateSeason::route('/create'),
            'edit'   => Pages\EditSeason::route('/{record}/edit'),
        ];
    }
}
```

- [ ] **Step 5 : Créer les pages Filament pour CategoryResource**

Créer `app/Filament/Resources/CategoryResource/Pages/ListCategories.php` :

```php
<?php

namespace App\Filament\Resources\CategoryResource\Pages;

use App\Filament\Resources\CategoryResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListCategories extends ListRecords
{
    protected static string $resource = CategoryResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\CreateAction::make()];
    }
}
```

Créer `app/Filament/Resources/CategoryResource/Pages/CreateCategory.php` :

```php
<?php

namespace App\Filament\Resources\CategoryResource\Pages;

use App\Filament\Resources\CategoryResource;
use Filament\Resources\Pages\CreateRecord;

class CreateCategory extends CreateRecord
{
    protected static string $resource = CategoryResource::class;
}
```

Créer `app/Filament/Resources/CategoryResource/Pages/EditCategory.php` :

```php
<?php

namespace App\Filament\Resources\CategoryResource\Pages;

use App\Filament\Resources\CategoryResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditCategory extends EditRecord
{
    protected static string $resource = CategoryResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\DeleteAction::make()];
    }
}
```

- [ ] **Step 6 : Créer CategoryResource**

Créer `app/Filament/Resources/CategoryResource.php` :

```php
<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CategoryResource\Pages;
use App\Models\Category;
use App\Models\Season;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Str;

class CategoryResource extends Resource
{
    protected static ?string $model = Category::class;
    protected static ?string $navigationIcon = 'heroicon-o-tag';
    protected static ?string $navigationLabel = 'Catégories';
    protected static ?string $modelLabel = 'Catégorie';
    protected static ?string $pluralModelLabel = 'Catégories';
    protected static ?string $navigationGroup = 'Équipes';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Select::make('season_id')
                ->label('Saison')
                ->options(Season::orderByDesc('name')->pluck('name', 'id'))
                ->required(),

            Forms\Components\Select::make('gender')
                ->label('Genre')
                ->options(['M' => 'Masculins', 'F' => 'Féminines', 'Mixte' => 'Mixte'])
                ->required(),

            Forms\Components\TextInput::make('name')
                ->label('Nom')
                ->required()
                ->live(debounce: 500)
                ->afterStateUpdated(function (Set $set, ?string $state, string $operation): void {
                    if ($operation === 'create') {
                        $set('slug', Str::slug($state ?? ''));
                    }
                }),

            Forms\Components\TextInput::make('slug')
                ->label('Slug (URL)')
                ->required(),

            Forms\Components\TextInput::make('birth_year_min')
                ->label('Année naissance min')
                ->numeric()
                ->required(),

            Forms\Components\TextInput::make('birth_year_max')
                ->label('Année naissance max')
                ->numeric()
                ->required(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('season.name')
                    ->label('Saison')
                    ->sortable(),

                Tables\Columns\TextColumn::make('name')
                    ->label('Nom')
                    ->searchable(),

                Tables\Columns\TextColumn::make('gender')
                    ->label('Genre'),

                Tables\Columns\TextColumn::make('birth_year_min')
                    ->label('Nés entre')
                    ->formatStateUsing(fn (Category $record): string => "{$record->birth_year_min}–{$record->birth_year_max}"),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('season_id')
                    ->label('Saison')
                    ->relationship('season', 'name'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListCategories::route('/'),
            'create' => Pages\CreateCategory::route('/create'),
            'edit'   => Pages\EditCategory::route('/{record}/edit'),
        ];
    }
}
```

- [ ] **Step 7 : Exécuter les tests**

```bash
php artisan test tests/Feature/Admin/SeasonResourceTest.php tests/Feature/Admin/CategoryResourceTest.php
```

Résultat attendu : 8 tests PASS

- [ ] **Step 8 : Suite complète**

```bash
php artisan test
```

Résultat attendu : tous les tests passent

- [ ] **Step 9 : Commit**

```bash
git add app/Filament/Resources/SeasonResource.php \
        "app/Filament/Resources/SeasonResource/Pages/ListSeasons.php" \
        "app/Filament/Resources/SeasonResource/Pages/CreateSeason.php" \
        "app/Filament/Resources/SeasonResource/Pages/EditSeason.php" \
        app/Filament/Resources/CategoryResource.php \
        "app/Filament/Resources/CategoryResource/Pages/ListCategories.php" \
        "app/Filament/Resources/CategoryResource/Pages/CreateCategory.php" \
        "app/Filament/Resources/CategoryResource/Pages/EditCategory.php" \
        tests/Feature/Admin/SeasonResourceTest.php \
        tests/Feature/Admin/CategoryResourceTest.php
git commit -m "feat: add SeasonResource and CategoryResource Filament"
```

---

## Task 3 : TeamResource + PlayerResource Filament

**Files:**
- Create: `app/Filament/Resources/TeamResource.php`
- Create: `app/Filament/Resources/TeamResource/Pages/ListTeams.php`
- Create: `app/Filament/Resources/TeamResource/Pages/CreateTeam.php`
- Create: `app/Filament/Resources/TeamResource/Pages/EditTeam.php`
- Create: `app/Filament/Resources/PlayerResource.php`
- Create: `app/Filament/Resources/PlayerResource/Pages/ListPlayers.php`
- Create: `app/Filament/Resources/PlayerResource/Pages/CreatePlayer.php`
- Create: `app/Filament/Resources/PlayerResource/Pages/EditPlayer.php`
- Create: `tests/Feature/Admin/TeamResourceTest.php`
- Create: `tests/Feature/Admin/PlayerResourceTest.php`

- [ ] **Step 1 : Écrire les tests (doivent échouer)**

Créer `tests/Feature/Admin/TeamResourceTest.php` :

```php
<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TeamResourceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
    }

    public function test_admin_can_access_teams_list(): void
    {
        $user = User::factory()->create();
        $user->assignRole('admin');

        $this->actingAs($user)->get('/admin/teams')->assertSuccessful();
    }

    public function test_super_admin_can_access_teams_list(): void
    {
        $user = User::factory()->create();
        $user->assignRole('super_admin');

        $this->actingAs($user)->get('/admin/teams')->assertSuccessful();
    }

    public function test_unauthenticated_user_cannot_access_teams_list(): void
    {
        $this->get('/admin/teams')->assertRedirect('/admin/login');
    }

    public function test_authenticated_user_without_role_cannot_access_teams_list(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get('/admin/teams')->assertForbidden();
    }
}
```

Créer `tests/Feature/Admin/PlayerResourceTest.php` :

```php
<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PlayerResourceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
    }

    public function test_admin_can_access_players_list(): void
    {
        $user = User::factory()->create();
        $user->assignRole('admin');

        $this->actingAs($user)->get('/admin/players')->assertSuccessful();
    }

    public function test_super_admin_can_access_players_list(): void
    {
        $user = User::factory()->create();
        $user->assignRole('super_admin');

        $this->actingAs($user)->get('/admin/players')->assertSuccessful();
    }

    public function test_unauthenticated_user_cannot_access_players_list(): void
    {
        $this->get('/admin/players')->assertRedirect('/admin/login');
    }

    public function test_authenticated_user_without_role_cannot_access_players_list(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get('/admin/players')->assertForbidden();
    }
}
```

- [ ] **Step 2 : Exécuter pour vérifier l'échec**

```bash
php artisan test tests/Feature/Admin/TeamResourceTest.php tests/Feature/Admin/PlayerResourceTest.php
```

Résultat attendu : FAIL — 404

- [ ] **Step 3 : Créer les pages Filament pour TeamResource**

Créer `app/Filament/Resources/TeamResource/Pages/ListTeams.php` :

```php
<?php

namespace App\Filament\Resources\TeamResource\Pages;

use App\Filament\Resources\TeamResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListTeams extends ListRecords
{
    protected static string $resource = TeamResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\CreateAction::make()];
    }
}
```

Créer `app/Filament/Resources/TeamResource/Pages/CreateTeam.php` :

```php
<?php

namespace App\Filament\Resources\TeamResource\Pages;

use App\Filament\Resources\TeamResource;
use Filament\Resources\Pages\CreateRecord;

class CreateTeam extends CreateRecord
{
    protected static string $resource = TeamResource::class;
}
```

Créer `app/Filament/Resources/TeamResource/Pages/EditTeam.php` :

```php
<?php

namespace App\Filament\Resources\TeamResource\Pages;

use App\Filament\Resources\TeamResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditTeam extends EditRecord
{
    protected static string $resource = TeamResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\DeleteAction::make()];
    }
}
```

- [ ] **Step 4 : Créer TeamResource**

Créer `app/Filament/Resources/TeamResource.php` :

```php
<?php

namespace App\Filament\Resources;

use App\Filament\Resources\TeamResource\Pages;
use App\Models\Category;
use App\Models\Team;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class TeamResource extends Resource
{
    protected static ?string $model = Team::class;
    protected static ?string $navigationIcon = 'heroicon-o-user-group';
    protected static ?string $navigationLabel = 'Équipes';
    protected static ?string $modelLabel = 'Équipe';
    protected static ?string $pluralModelLabel = 'Équipes';
    protected static ?string $navigationGroup = 'Équipes';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Select::make('category_id')
                ->label('Catégorie')
                ->options(Category::with('season')->get()->mapWithKeys(
                    fn (Category $c) => [$c->id => "{$c->season->name} — {$c->name}"]
                ))
                ->required(),

            Forms\Components\TextInput::make('name')
                ->label('Nom')
                ->required()
                ->placeholder('Équipe 1'),

            Forms\Components\FileUpload::make('photo')
                ->label('Photo d\'équipe')
                ->image()
                ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp'])
                ->maxSize(4096)
                ->directory('teams')
                ->disk('public')
                ->nullable(),

            Forms\Components\TextInput::make('scorenco_id')
                ->label('ID Scorenco')
                ->nullable(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\ImageColumn::make('photo')
                    ->label('Photo')
                    ->disk('public'),

                Tables\Columns\TextColumn::make('name')
                    ->label('Nom')
                    ->searchable(),

                Tables\Columns\TextColumn::make('category.name')
                    ->label('Catégorie')
                    ->sortable(),

                Tables\Columns\TextColumn::make('scorenco_id')
                    ->label('Scorenco ID')
                    ->placeholder('—'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('category_id')
                    ->label('Catégorie')
                    ->relationship('category', 'name'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListTeams::route('/'),
            'create' => Pages\CreateTeam::route('/create'),
            'edit'   => Pages\EditTeam::route('/{record}/edit'),
        ];
    }
}
```

- [ ] **Step 5 : Créer les pages Filament pour PlayerResource**

Créer `app/Filament/Resources/PlayerResource/Pages/ListPlayers.php` :

```php
<?php

namespace App\Filament\Resources\PlayerResource\Pages;

use App\Filament\Resources\PlayerResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListPlayers extends ListRecords
{
    protected static string $resource = PlayerResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\CreateAction::make()];
    }
}
```

Créer `app/Filament/Resources/PlayerResource/Pages/CreatePlayer.php` :

```php
<?php

namespace App\Filament\Resources\PlayerResource\Pages;

use App\Filament\Resources\PlayerResource;
use Filament\Resources\Pages\CreateRecord;

class CreatePlayer extends CreateRecord
{
    protected static string $resource = PlayerResource::class;
}
```

Créer `app/Filament/Resources/PlayerResource/Pages/EditPlayer.php` :

```php
<?php

namespace App\Filament\Resources\PlayerResource\Pages;

use App\Filament\Resources\PlayerResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditPlayer extends EditRecord
{
    protected static string $resource = PlayerResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\DeleteAction::make()];
    }
}
```

- [ ] **Step 6 : Créer PlayerResource**

Créer `app/Filament/Resources/PlayerResource.php` :

```php
<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PlayerResource\Pages;
use App\Models\Category;
use App\Models\Player;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class PlayerResource extends Resource
{
    protected static ?string $model = Player::class;
    protected static ?string $navigationIcon = 'heroicon-o-identification';
    protected static ?string $navigationLabel = 'Joueurs';
    protected static ?string $modelLabel = 'Joueur';
    protected static ?string $pluralModelLabel = 'Joueurs';
    protected static ?string $navigationGroup = 'Équipes';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Select::make('category_id')
                ->label('Catégorie')
                ->options(Category::with('season')->get()->mapWithKeys(
                    fn (Category $c) => [$c->id => "{$c->season->name} — {$c->name}"]
                ))
                ->nullable(),

            Forms\Components\TextInput::make('last_name')
                ->label('Nom')
                ->required(),

            Forms\Components\TextInput::make('first_name')
                ->label('Prénom')
                ->required(),

            Forms\Components\DatePicker::make('birth_date')
                ->label('Date de naissance')
                ->required(),

            Forms\Components\TextInput::make('gender')
                ->label('Sexe')
                ->placeholder('M ou F')
                ->nullable(),

            Forms\Components\TextInput::make('license_number')
                ->label('Numéro de licence')
                ->nullable(),

            Forms\Components\FileUpload::make('photo')
                ->label('Photo')
                ->image()
                ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp'])
                ->maxSize(2048)
                ->directory('players')
                ->disk('public')
                ->nullable(),

            Forms\Components\Toggle::make('has_image_rights')
                ->label('Droit à l\'image'),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('last_name')
                    ->label('Nom')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('first_name')
                    ->label('Prénom')
                    ->searchable(),

                Tables\Columns\TextColumn::make('birth_date')
                    ->label('Naissance')
                    ->date('d/m/Y'),

                Tables\Columns\TextColumn::make('category.name')
                    ->label('Catégorie')
                    ->placeholder('—'),

                Tables\Columns\IconColumn::make('has_image_rights')
                    ->label('Droit image')
                    ->boolean(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('category_id')
                    ->label('Catégorie')
                    ->relationship('category', 'name'),

                Tables\Filters\TernaryFilter::make('has_image_rights')
                    ->label('Droit à l\'image'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListPlayers::route('/'),
            'create' => Pages\CreatePlayer::route('/create'),
            'edit'   => Pages\EditPlayer::route('/{record}/edit'),
        ];
    }
}
```

- [ ] **Step 7 : Exécuter les tests**

```bash
php artisan test tests/Feature/Admin/TeamResourceTest.php tests/Feature/Admin/PlayerResourceTest.php
```

Résultat attendu : 8 tests PASS

- [ ] **Step 8 : Suite complète**

```bash
php artisan test
```

Résultat attendu : tous les tests passent

- [ ] **Step 9 : Commit**

```bash
git add app/Filament/Resources/TeamResource.php \
        "app/Filament/Resources/TeamResource/Pages/ListTeams.php" \
        "app/Filament/Resources/TeamResource/Pages/CreateTeam.php" \
        "app/Filament/Resources/TeamResource/Pages/EditTeam.php" \
        app/Filament/Resources/PlayerResource.php \
        "app/Filament/Resources/PlayerResource/Pages/ListPlayers.php" \
        "app/Filament/Resources/PlayerResource/Pages/CreatePlayer.php" \
        "app/Filament/Resources/PlayerResource/Pages/EditPlayer.php" \
        tests/Feature/Admin/TeamResourceTest.php \
        tests/Feature/Admin/PlayerResourceTest.php
git commit -m "feat: add TeamResource and PlayerResource Filament"
```

---

## Task 4 : PlayerImportService + Action Filament

**Files:**
- Create: `app/Services/PlayerImportService.php`
- Modify: `app/Filament/Resources/PlayerResource/Pages/ListPlayers.php`
- Create: `tests/Feature/PlayerImportServiceTest.php`

- [ ] **Step 1 : Installer spatie/simple-excel**

```bash
composer require spatie/simple-excel
```

Résultat attendu : package installé sans erreur

- [ ] **Step 2 : Écrire les tests (doivent échouer)**

Créer `tests/Feature/PlayerImportServiceTest.php` :

```php
<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Player;
use App\Models\Season;
use App\Services\PlayerImportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PlayerImportServiceTest extends TestCase
{
    use RefreshDatabase;

    private function writeCsv(string $content): string
    {
        $path = tempnam(sys_get_temp_dir(), 'csv_');
        file_put_contents($path, $content);
        return $path;
    }

    public function test_imports_player_and_assigns_category_by_birth_year(): void
    {
        $season = Season::factory()->create(['is_current' => true]);
        $category = Category::factory()->create([
            'season_id'      => $season->id,
            'gender'         => 'M',
            'birth_year_min' => 2014,
            'birth_year_max' => 2015,
        ]);

        $csv = "Nom;Prenom;Né(e) le;sexe;Numero Licence;DroitImage\n" .
               "Dupont;Jean;15/01/2014;M;123456;Oui\n";
        $path = $this->writeCsv($csv);

        $result = (new PlayerImportService())->import($path);

        $this->assertEquals(1, $result['imported']);
        $this->assertEquals(0, $result['skipped']);
        $this->assertEquals(0, $result['unmatched']);
        $this->assertDatabaseHas('players', [
            'last_name'        => 'Dupont',
            'first_name'       => 'Jean',
            'category_id'      => $category->id,
            'has_image_rights' => true,
        ]);
    }

    public function test_skips_duplicate_based_on_name_and_birth_date(): void
    {
        $season = Season::factory()->create(['is_current' => true]);
        Player::factory()->create([
            'last_name'  => 'Dupont',
            'first_name' => 'Jean',
            'birth_date' => '2014-01-15',
        ]);

        $csv = "Nom;Prenom;Né(e) le;sexe;Numero Licence;DroitImage\n" .
               "Dupont;Jean;15/01/2014;M;123456;Oui\n";
        $path = $this->writeCsv($csv);

        $result = (new PlayerImportService())->import($path);

        $this->assertEquals(0, $result['imported']);
        $this->assertEquals(1, $result['skipped']);
        $this->assertDatabaseCount('players', 1);
    }

    public function test_creates_player_with_null_category_when_no_match(): void
    {
        Season::factory()->create(['is_current' => true]);

        $csv = "Nom;Prenom;Né(e) le;sexe;Numero Licence;DroitImage\n" .
               "Martin;Alice;15/01/1980;F;654321;Non\n";
        $path = $this->writeCsv($csv);

        $result = (new PlayerImportService())->import($path);

        $this->assertEquals(1, $result['imported']);
        $this->assertEquals(0, $result['skipped']);
        $this->assertEquals(1, $result['unmatched']);
        $this->assertDatabaseHas('players', [
            'last_name'   => 'Martin',
            'category_id' => null,
        ]);
    }

    public function test_droit_image_oui_sets_has_image_rights_true(): void
    {
        Season::factory()->create(['is_current' => true]);

        $csv = "Nom;Prenom;Né(e) le;sexe;Numero Licence;DroitImage\n" .
               "A;B;01/01/2010;M;1;Oui\n" .
               "C;D;02/01/2010;M;2;Non\n" .
               "E;F;03/01/2010;M;3;O\n" .
               "G;H;04/01/2010;M;4;1\n";
        $path = $this->writeCsv($csv);

        (new PlayerImportService())->import($path);

        $this->assertTrue(Player::where('last_name', 'A')->first()->has_image_rights);
        $this->assertFalse(Player::where('last_name', 'C')->first()->has_image_rights);
        $this->assertTrue(Player::where('last_name', 'E')->first()->has_image_rights);
        $this->assertTrue(Player::where('last_name', 'G')->first()->has_image_rights);
    }

    public function test_female_player_is_assigned_to_female_category(): void
    {
        $season = Season::factory()->create(['is_current' => true]);
        $femCategory = Category::factory()->create([
            'season_id'      => $season->id,
            'gender'         => 'F',
            'birth_year_min' => 2012,
            'birth_year_max' => 2013,
        ]);
        $masCategory = Category::factory()->create([
            'season_id'      => $season->id,
            'gender'         => 'M',
            'birth_year_min' => 2012,
            'birth_year_max' => 2013,
        ]);

        $csv = "Nom;Prenom;Né(e) le;sexe;Numero Licence;DroitImage\n" .
               "Durand;Léa;10/05/2012;F;999;Non\n";
        $path = $this->writeCsv($csv);

        (new PlayerImportService())->import($path);

        $this->assertDatabaseHas('players', [
            'last_name'   => 'Durand',
            'category_id' => $femCategory->id,
        ]);
    }
}
```

- [ ] **Step 3 : Exécuter pour vérifier l'échec**

```bash
php artisan test tests/Feature/PlayerImportServiceTest.php
```

Résultat attendu : FAIL — `Class "App\Services\PlayerImportService" not found`

- [ ] **Step 4 : Créer PlayerImportService**

Créer `app/Services/PlayerImportService.php` :

```php
<?php

namespace App\Services;

use App\Models\Category;
use App\Models\Player;
use App\Models\Season;
use Carbon\Carbon;
use Spatie\SimpleExcel\SimpleExcelReader;

class PlayerImportService
{
    public function import(string $csvPath): array
    {
        $imported  = 0;
        $skipped   = 0;
        $unmatched = 0;

        SimpleExcelReader::create($csvPath, 'csv')
            ->useDelimiter(';')
            ->getRows()
            ->each(function (array $row) use (&$imported, &$skipped, &$unmatched): void {
                $lastName  = trim($row['Nom'] ?? '');
                $firstName = trim($row['Prenom'] ?? '');
                $rawDate   = trim($row['Né(e) le'] ?? '');
                $gender    = trim($row['sexe'] ?? '');
                $license   = trim($row['Numero Licence'] ?? '') ?: null;
                $droitImage = trim($row['DroitImage'] ?? '');

                if (empty($lastName) || empty($firstName) || empty($rawDate)) {
                    return;
                }

                $birthDate = Carbon::createFromFormat('d/m/Y', $rawDate);

                if (Player::where('last_name', $lastName)
                    ->where('first_name', $firstName)
                    ->where('birth_date', $birthDate->toDateString())
                    ->exists()
                ) {
                    $skipped++;
                    return;
                }

                $category = $this->findCategory($birthDate->year, $gender);

                if ($category === null) {
                    $unmatched++;
                }

                Player::create([
                    'category_id'      => $category?->id,
                    'last_name'        => $lastName,
                    'first_name'       => $firstName,
                    'birth_date'       => $birthDate->toDateString(),
                    'gender'           => $gender ?: null,
                    'license_number'   => $license,
                    'has_image_rights' => in_array(strtolower($droitImage), ['oui', 'o', '1'], true),
                ]);

                $imported++;
            });

        return [
            'imported'  => $imported,
            'skipped'   => $skipped,
            'unmatched' => $unmatched,
        ];
    }

    private function findCategory(int $birthYear, string $csvGender): ?Category
    {
        $genders = match (strtoupper(trim($csvGender))) {
            'M', 'MASCULIN'         => ['M', 'Mixte'],
            'F', 'FÉMININ', 'FEMININ' => ['F', 'Mixte'],
            default                 => ['Mixte'],
        };

        return Category::whereHas(
            'season',
            fn ($q) => $q->where('is_current', true)
        )
            ->where('birth_year_min', '<=', $birthYear)
            ->where('birth_year_max', '>=', $birthYear)
            ->whereIn('gender', $genders)
            ->first();
    }
}
```

- [ ] **Step 5 : Exécuter les tests**

```bash
php artisan test tests/Feature/PlayerImportServiceTest.php
```

Résultat attendu : 5 tests PASS

- [ ] **Step 6 : Ajouter l'action import dans ListPlayers**

Modifier `app/Filament/Resources/PlayerResource/Pages/ListPlayers.php` :

```php
<?php

namespace App\Filament\Resources\PlayerResource\Pages;

use App\Filament\Resources\PlayerResource;
use App\Services\PlayerImportService;
use Filament\Actions;
use Filament\Forms\Components\FileUpload;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;

class ListPlayers extends ListRecords
{
    protected static string $resource = PlayerResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('importCsv')
                ->label('Importer CSV')
                ->icon('heroicon-o-arrow-up-tray')
                ->form([
                    FileUpload::make('csv_file')
                        ->label('Fichier CSV (export FFHandball)')
                        ->acceptedFileTypes(['text/csv', 'text/plain', 'application/csv'])
                        ->required()
                        ->storeFiles(false),
                ])
                ->action(function (array $data): void {
                    $result = (new PlayerImportService())->import($data['csv_file']->getRealPath());

                    Notification::make()
                        ->title('Import terminé')
                        ->body("{$result['imported']} importés · {$result['skipped']} doublons ignorés · {$result['unmatched']} sans catégorie")
                        ->success()
                        ->send();
                }),

            Actions\CreateAction::make(),
        ];
    }
}
```

- [ ] **Step 7 : Suite complète**

```bash
php artisan test
```

Résultat attendu : tous les tests passent

- [ ] **Step 8 : Commit**

```bash
git add app/Services/PlayerImportService.php \
        "app/Filament/Resources/PlayerResource/Pages/ListPlayers.php" \
        tests/Feature/PlayerImportServiceTest.php
git commit -m "feat: add PlayerImportService with CSV import action in Filament"
```

---

## Task 5 : EquipesController + Routes

**Files:**
- Create: `app/Http/Controllers/EquipesController.php`
- Modify: `routes/web.php`
- Create: `tests/Feature/EquipesControllerTest.php`

- [ ] **Step 1 : Écrire les tests (doivent échouer)**

Créer `tests/Feature/EquipesControllerTest.php` :

```php
<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Player;
use App\Models\Season;
use App\Models\Team;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class EquipesControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_index_returns_404_when_no_current_season(): void
    {
        $this->get('/equipes')->assertNotFound();
    }

    public function test_index_returns_categories_of_current_season(): void
    {
        $season = Season::factory()->create(['is_current' => true]);
        Category::factory()->create(['season_id' => $season->id, 'name' => 'U13 Masculins']);

        $this->get('/equipes')
            ->assertOk()
            ->assertInertia(fn (Assert $page) =>
                $page->component('Equipes/Index')
                    ->has('categories', 1)
                    ->where('categories.0.name', 'U13 Masculins')
            );
    }

    public function test_index_does_not_return_categories_from_other_seasons(): void
    {
        $current = Season::factory()->create(['is_current' => true]);
        $other   = Season::factory()->create(['is_current' => false]);
        Category::factory()->create(['season_id' => $current->id, 'name' => 'U13 Masculins']);
        Category::factory()->create(['season_id' => $other->id, 'name' => 'U15 Féminines']);

        $this->get('/equipes')
            ->assertOk()
            ->assertInertia(fn (Assert $page) =>
                $page->component('Equipes/Index')
                    ->has('categories', 1)
                    ->where('categories.0.name', 'U13 Masculins')
            );
    }

    public function test_show_returns_category_with_teams_and_players(): void
    {
        $season   = Season::factory()->create(['is_current' => true]);
        $category = Category::factory()->create(['season_id' => $season->id, 'slug' => 'u13-masculins', 'name' => 'U13 Masculins']);
        Team::factory()->create(['category_id' => $category->id, 'name' => 'Équipe 1']);
        Player::factory()->create(['category_id' => $category->id, 'last_name' => 'Dupont']);

        $this->get('/equipes/u13-masculins')
            ->assertOk()
            ->assertInertia(fn (Assert $page) =>
                $page->component('Equipes/Show')
                    ->where('category.name', 'U13 Masculins')
                    ->has('teams', 1)
                    ->has('players', 1)
            );
    }

    public function test_show_returns_404_for_unknown_slug(): void
    {
        Season::factory()->create(['is_current' => true]);

        $this->get('/equipes/inconnu')->assertNotFound();
    }

    public function test_show_returns_404_for_category_from_other_season(): void
    {
        Season::factory()->create(['is_current' => true]);
        $other    = Season::factory()->create(['is_current' => false]);
        Category::factory()->create(['season_id' => $other->id, 'slug' => 'u13-masculins']);

        $this->get('/equipes/u13-masculins')->assertNotFound();
    }

    public function test_show_returns_404_when_no_current_season(): void
    {
        $this->get('/equipes/u13-masculins')->assertNotFound();
    }

    public function test_players_are_ordered_by_last_name_then_first_name(): void
    {
        $season   = Season::factory()->create(['is_current' => true]);
        $category = Category::factory()->create(['season_id' => $season->id, 'slug' => 'u13']);
        Player::factory()->create(['category_id' => $category->id, 'last_name' => 'Martin',  'first_name' => 'Zoé']);
        Player::factory()->create(['category_id' => $category->id, 'last_name' => 'Dupont',  'first_name' => 'Alice']);
        Player::factory()->create(['category_id' => $category->id, 'last_name' => 'Dupont',  'first_name' => 'Abel']);

        $this->get('/equipes/u13')
            ->assertOk()
            ->assertInertia(fn (Assert $page) =>
                $page->component('Equipes/Show')
                    ->where('players.0.last_name', 'Dupont')
                    ->where('players.0.first_name', 'Abel')
                    ->where('players.1.last_name', 'Dupont')
                    ->where('players.1.first_name', 'Zoé')
                    ->where('players.2.last_name', 'Martin')
            );
    }
}
```

- [ ] **Step 2 : Exécuter pour vérifier l'échec**

```bash
php artisan test tests/Feature/EquipesControllerTest.php
```

Résultat attendu : FAIL — 404 (routes non définies)

- [ ] **Step 3 : Créer EquipesController**

Créer `app/Http/Controllers/EquipesController.php` :

```php
<?php

namespace App\Http\Controllers;

use App\Models\Season;
use Inertia\Inertia;
use Inertia\Response;

class EquipesController extends Controller
{
    public function index(): Response
    {
        $season = Season::where('is_current', true)->firstOrFail();

        $categories = $season->categories()
            ->orderBy('gender')
            ->orderBy('name')
            ->get();

        return Inertia::render('Equipes/Index', [
            'categories' => $categories,
        ]);
    }

    public function show(string $slug): Response
    {
        $season   = Season::where('is_current', true)->firstOrFail();
        $category = $season->categories()->where('slug', $slug)->firstOrFail();

        return Inertia::render('Equipes/Show', [
            'category' => $category,
            'teams'    => $category->teams()->get(),
            'players'  => $category->players()
                ->orderBy('last_name')
                ->orderBy('first_name')
                ->get(),
        ]);
    }
}
```

- [ ] **Step 4 : Ajouter les routes**

Modifier `routes/web.php` :

```php
<?php

use App\Http\Controllers\ActualitesController;
use App\Http\Controllers\EquipesController;
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

Route::get('/equipes', [EquipesController::class, 'index'])->name('equipes.index');
Route::get('/equipes/{slug}', [EquipesController::class, 'show'])->name('equipes.show');
```

- [ ] **Step 5 : Exécuter les tests**

```bash
php artisan test tests/Feature/EquipesControllerTest.php
```

Résultat attendu : 8 tests PASS

- [ ] **Step 6 : Suite complète**

```bash
php artisan test
```

Résultat attendu : tous les tests passent

- [ ] **Step 7 : Commit**

```bash
git add app/Http/Controllers/EquipesController.php \
        routes/web.php \
        tests/Feature/EquipesControllerTest.php
git commit -m "feat: add EquipesController with index and show routes"
```

---

## Task 6 : Pages Vue

**Files:**
- Create: `resources/js/pages/Equipes/Index.vue`
- Create: `resources/js/pages/Equipes/Show.vue`

- [ ] **Step 1 : Vérifier la baseline PHP**

```bash
php artisan test
```

Résultat attendu : tous les tests passent

- [ ] **Step 2 : Créer Equipes/Index.vue**

Créer `resources/js/pages/Equipes/Index.vue` :

```vue
<script setup>
import { Link } from '@inertiajs/vue3';

defineProps({
    categories: {
        type: Array,
        required: true,
    },
});
</script>

<template>
    <div>
        <h1>Nos Équipes</h1>

        <p v-if="categories.length === 0">
            Aucune catégorie enregistrée pour la saison courante.
        </p>

        <div v-else class="categories-grid">
            <Link
                v-for="category in categories"
                :key="category.id"
                :href="`/equipes/${category.slug}`"
                class="category-card"
            >
                <span class="category-name">{{ category.name }}</span>
                <span class="category-gender">{{ category.gender }}</span>
            </Link>
        </div>
    </div>
</template>

<style scoped>
.categories-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 1rem;
    margin-top: 1.5rem;
}

@media (min-width: 768px) {
    .categories-grid {
        grid-template-columns: repeat(3, 1fr);
    }
}

@media (min-width: 1024px) {
    .categories-grid {
        grid-template-columns: repeat(4, 1fr);
    }
}

.category-card {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 0.5rem;
    padding: 1.5rem 1rem;
    border: 1px solid #e5e7eb;
    border-radius: 0.5rem;
    text-decoration: none;
    color: inherit;
    text-align: center;
    transition: border-color 0.15s;
}

.category-card:hover {
    border-color: #003A5D;
}

.category-name {
    font-weight: 600;
    font-size: 1rem;
}

.category-gender {
    font-size: 0.75rem;
    color: #7C878E;
    text-transform: uppercase;
    letter-spacing: 0.05em;
}
</style>
```

- [ ] **Step 3 : Créer Equipes/Show.vue**

Créer `resources/js/pages/Equipes/Show.vue` :

```vue
<script setup>
defineProps({
    category: {
        type: Object,
        required: true,
    },
    teams: {
        type: Array,
        required: true,
    },
    players: {
        type: Array,
        required: true,
    },
});
</script>

<template>
    <div>
        <h1>{{ category.name }}</h1>

        <section v-if="teams.length > 0" class="section">
            <h2>Équipes</h2>
            <div class="staff-grid">
                <div
                    v-for="team in teams"
                    :key="team.id"
                    class="staff-card"
                >
                    <img
                        v-if="team.photo"
                        :src="`/storage/${team.photo}`"
                        :alt="team.name"
                        class="team-photo"
                    />
                    <div v-else class="staff-avatar-placeholder">
                        {{ team.name.charAt(0).toUpperCase() }}
                    </div>
                    <p class="staff-name">{{ team.name }}</p>
                </div>
            </div>
        </section>

        <section class="section">
            <h2>Joueurs</h2>

            <p v-if="players.length === 0">
                Aucun joueur enregistré pour cette catégorie.
            </p>

            <div v-else class="staff-grid">
                <div
                    v-for="player in players"
                    :key="player.id"
                    class="staff-card"
                >
                    <img
                        v-if="player.photo && player.has_image_rights"
                        :src="`/storage/${player.photo}`"
                        :alt="`${player.first_name} ${player.last_name}`"
                        class="staff-photo"
                    />
                    <div v-else class="staff-avatar-placeholder">
                        {{ player.last_name.charAt(0).toUpperCase() }}
                    </div>
                    <p class="staff-name">{{ player.first_name }} {{ player.last_name }}</p>
                </div>
            </div>
        </section>
    </div>
</template>

<style scoped>
.section {
    margin-top: 2rem;
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

.staff-photo,
.team-photo {
    width: 80px;
    height: 80px;
    border-radius: 50%;
    object-fit: cover;
}

.team-photo {
    border-radius: 0.25rem;
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
    font-size: 0.875rem;
}
</style>
```

- [ ] **Step 4 : Suite complète**

```bash
php artisan test
```

Résultat attendu : tous les tests passent

- [ ] **Step 5 : Commit**

```bash
git add resources/js/pages/Equipes/Index.vue \
        resources/js/pages/Equipes/Show.vue
git commit -m "feat: add Equipes Vue pages (Index and Show)"
```

---

## Vérification finale

- [ ] **Lancer la suite complète**

```bash
php artisan test
```

Résultat attendu : tous les tests passent, aucune régression

- [ ] **Utiliser superpowers:finishing-a-development-branch pour finaliser**
