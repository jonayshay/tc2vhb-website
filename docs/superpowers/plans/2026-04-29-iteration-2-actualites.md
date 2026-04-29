# Module Actualités — Plan d'implémentation

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Implémenter le module Actualités : gestion éditoriale dans Filament (brouillon/publié/archivé), liste paginée et page de détail publiques, image à la une et contenu riche avec images inline via TipTap.

**Architecture:** Un modèle `News` avec observer pour la date de publication automatique. Un `NewsResource` Filament pour le back-office. Un `ActualitesController` Inertia avec deux pages Vue (`Index` et `Show`). Images stockées dans `storage/app/public/`.

**Tech Stack:** Laravel 12, Filament 3, `awcodes/filament-tiptap-editor`, Inertia.js v3, Vue.js 3, MySQL.

---

## Structure des fichiers

### À créer

| Fichier | Rôle |
|---|---|
| `database/migrations/*_create_news_table.php` | Table `news` |
| `app/Models/News.php` | Modèle Eloquent |
| `database/factories/NewsFactory.php` | Données de test |
| `app/Observers/NewsObserver.php` | Auto-renseigne `published_at` |
| `app/Filament/Resources/NewsResource.php` | Resource Filament (table + form) |
| `app/Filament/Resources/NewsResource/Pages/ListNews.php` | Page liste admin |
| `app/Filament/Resources/NewsResource/Pages/CreateNews.php` | Page création admin |
| `app/Filament/Resources/NewsResource/Pages/EditNews.php` | Page édition admin |
| `app/Http/Controllers/ActualitesController.php` | index() + show() |
| `resources/js/pages/Actualites/Index.vue` | Liste publique paginée |
| `resources/js/pages/Actualites/Show.vue` | Détail public |
| `tests/Unit/Observers/NewsObserverTest.php` | Tests unitaires observer |
| `tests/Feature/ActualitesControllerTest.php` | Tests controller public |
| `tests/Feature/Admin/NewsResourceTest.php` | Tests accès Filament |

### À modifier

| Fichier | Changement |
|---|---|
| `app/Providers/AppServiceProvider.php` | Enregistrer `NewsObserver` |
| `routes/web.php` | Ajouter routes `/actualites` et `/actualites/{slug}` |

---

### Task 1 : Installer awcodes/filament-tiptap-editor

**Files:**
- Modify: `composer.json` (via `composer require`)
- Create: `config/tiptap-editor.php` (via `vendor:publish`)

- [ ] **Step 1 : Installer le package**

```bash
composer require awcodes/filament-tiptap-editor
```

Résultat attendu : `Package manifest generated successfully.`

- [ ] **Step 2 : Publier la configuration**

```bash
php artisan vendor:publish --tag="tiptap-editor-config"
```

Résultat attendu : `Copied File [...] tiptap-editor.php`

- [ ] **Step 3 : Configurer le disk et répertoire d'upload**

Ouvrir `config/tiptap-editor.php` et s'assurer que les clés suivantes sont définies ainsi :

```php
'disk' => 'public',
'directory' => 'news',
'accepted_file_types' => ['image/jpeg', 'image/png', 'image/webp', 'image/gif'],
```

- [ ] **Step 4 : Vérifier que les tests existants passent encore**

```bash
php artisan test
```

Résultat attendu : tous les tests passent (aucune régression).

- [ ] **Step 5 : Commit**

```bash
git add composer.json composer.lock config/tiptap-editor.php
git commit -m "feat: install awcodes/filament-tiptap-editor"
```

---

### Task 2 : Migration, modèle News et factory

**Files:**
- Create: `database/migrations/*_create_news_table.php`
- Create: `app/Models/News.php`
- Create: `database/factories/NewsFactory.php`
- Create: `tests/Unit/Observers/NewsObserverTest.php` (dossier à créer si absent)
- Test: `tests/Feature/ActualitesControllerTest.php` (test partiel — uniquement la création en DB)

- [ ] **Step 1 : Créer le fichier de test**

Créer `tests/Unit/Models/NewsTest.php` (créer le dossier `tests/Unit/Models/` si absent) :

```php
<?php

namespace Tests\Unit\Models;

use App\Models\News;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NewsTest extends TestCase
{
    use RefreshDatabase;

    public function test_news_can_be_created_with_required_fields(): void
    {
        $news = News::factory()->create([
            'title' => 'Premier article',
            'slug' => 'premier-article',
            'content' => '<p>Contenu test</p>',
            'status' => 'draft',
        ]);

        $this->assertDatabaseHas('news', [
            'title' => 'Premier article',
            'slug' => 'premier-article',
            'status' => 'draft',
            'published_at' => null,
        ]);
    }

    public function test_published_at_is_cast_to_carbon(): void
    {
        $news = News::factory()->published()->create();

        $this->assertInstanceOf(\Illuminate\Support\Carbon::class, $news->published_at);
    }

    public function test_featured_image_is_nullable(): void
    {
        $news = News::factory()->create(['featured_image' => null]);

        $this->assertNull($news->featured_image);
    }
}
```

- [ ] **Step 2 : Lancer le test pour vérifier qu'il échoue**

```bash
php artisan test tests/Unit/Models/NewsTest.php
```

Résultat attendu : FAIL avec `Class "App\Models\News" not found`

- [ ] **Step 3 : Générer la migration et le modèle**

```bash
php artisan make:model News -mf
```

Cela crée `app/Models/News.php`, `database/migrations/*_create_news_table.php` et `database/factories/NewsFactory.php`.

- [ ] **Step 4 : Remplir la migration**

Ouvrir `database/migrations/*_create_news_table.php` et remplacer le contenu de `up()` :

```php
public function up(): void
{
    Schema::create('news', function (Blueprint $table) {
        $table->id();
        $table->string('title');
        $table->string('slug')->unique();
        $table->longText('content');
        $table->string('featured_image')->nullable();
        $table->enum('status', ['draft', 'published', 'archived'])->default('draft');
        $table->timestamp('published_at')->nullable();
        $table->timestamps();
    });
}
```

- [ ] **Step 5 : Remplir le modèle**

Remplacer le contenu de `app/Models/News.php` :

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class News extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'slug',
        'content',
        'featured_image',
        'status',
        'published_at',
    ];

    protected function casts(): array
    {
        return [
            'published_at' => 'datetime',
        ];
    }
}
```

- [ ] **Step 6 : Remplir la factory**

Remplacer le contenu de `database/factories/NewsFactory.php` :

```php
<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class NewsFactory extends Factory
{
    public function definition(): array
    {
        $title = $this->faker->sentence(6);

        return [
            'title' => $title,
            'slug' => Str::slug($title),
            'content' => '<p>' . implode('</p><p>', $this->faker->paragraphs(3)) . '</p>',
            'featured_image' => null,
            'status' => 'draft',
            'published_at' => null,
        ];
    }

    public function published(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'published',
            'published_at' => now(),
        ]);
    }

    public function archived(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'archived',
            'published_at' => now()->subDays(7),
        ]);
    }
}
```

- [ ] **Step 7 : Lancer le test pour vérifier qu'il passe**

```bash
php artisan test tests/Unit/Models/NewsTest.php
```

Résultat attendu : 3 tests passent (PASS).

- [ ] **Step 8 : Commit**

```bash
git add database/migrations app/Models/News.php database/factories/NewsFactory.php tests/Unit/Models/NewsTest.php
git commit -m "feat: modèle News avec migration et factory"
```

---

### Task 3 : Observer NewsObserver

**Files:**
- Create: `app/Observers/NewsObserver.php`
- Modify: `app/Providers/AppServiceProvider.php`
- Create: `tests/Unit/Observers/NewsObserverTest.php`

- [ ] **Step 1 : Créer le fichier de test**

Créer `tests/Unit/Observers/NewsObserverTest.php` (créer le dossier si absent) :

```php
<?php

namespace Tests\Unit\Observers;

use App\Models\News;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NewsObserverTest extends TestCase
{
    use RefreshDatabase;

    public function test_published_at_is_set_when_article_is_first_published(): void
    {
        $news = News::factory()->create(['status' => 'draft']);
        $this->assertNull($news->published_at);

        $news->update(['status' => 'published']);

        $this->assertNotNull($news->fresh()->published_at);
    }

    public function test_published_at_is_not_changed_when_article_is_republished(): void
    {
        $original = now()->subDay();
        $news = News::factory()->create([
            'status' => 'published',
            'published_at' => $original,
        ]);

        $news->update(['status' => 'draft']);
        $news->update(['status' => 'published']);

        $this->assertEquals(
            $original->toDateTimeString(),
            $news->fresh()->published_at->toDateTimeString()
        );
    }

    public function test_published_at_is_not_set_when_status_remains_draft(): void
    {
        $news = News::factory()->create(['status' => 'draft']);

        $news->update(['title' => 'Nouveau titre']);

        $this->assertNull($news->fresh()->published_at);
    }
}
```

- [ ] **Step 2 : Lancer le test pour vérifier qu'il échoue**

```bash
php artisan test tests/Unit/Observers/NewsObserverTest.php
```

Résultat attendu : 3 tests FAIL (l'observer n'existe pas, `published_at` n'est jamais défini automatiquement).

- [ ] **Step 3 : Générer l'observer**

```bash
php artisan make:observer NewsObserver --model=News
```

- [ ] **Step 4 : Remplir l'observer**

Remplacer le contenu de `app/Observers/NewsObserver.php` :

```php
<?php

namespace App\Observers;

use App\Models\News;

class NewsObserver
{
    public function saving(News $news): void
    {
        if ($news->status === 'published' && $news->published_at === null) {
            $news->published_at = now();
        }
    }
}
```

- [ ] **Step 5 : Enregistrer l'observer dans AppServiceProvider**

Modifier `app/Providers/AppServiceProvider.php` :

```php
<?php

namespace App\Providers;

use App\Models\News;
use App\Observers\NewsObserver;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void {}

    public function boot(): void
    {
        News::observe(NewsObserver::class);
    }
}
```

- [ ] **Step 6 : Lancer le test pour vérifier qu'il passe**

```bash
php artisan test tests/Unit/Observers/NewsObserverTest.php
```

Résultat attendu : 3 tests PASS.

- [ ] **Step 7 : Lancer tous les tests pour vérifier l'absence de régression**

```bash
php artisan test
```

Résultat attendu : tous les tests PASS.

- [ ] **Step 8 : Commit**

```bash
git add app/Observers/NewsObserver.php app/Providers/AppServiceProvider.php tests/Unit/Observers/NewsObserverTest.php
git commit -m "feat: observer NewsObserver — publication automatique"
```

---

### Task 4 : NewsResource Filament

**Files:**
- Create: `app/Filament/Resources/NewsResource.php`
- Create: `app/Filament/Resources/NewsResource/Pages/ListNews.php`
- Create: `app/Filament/Resources/NewsResource/Pages/CreateNews.php`
- Create: `app/Filament/Resources/NewsResource/Pages/EditNews.php`
- Create: `tests/Feature/Admin/NewsResourceTest.php`

- [ ] **Step 1 : Créer le fichier de test**

Créer `tests/Feature/Admin/NewsResourceTest.php` :

```php
<?php

namespace Tests\Feature\Admin;

use App\Models\News;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NewsResourceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
    }

    public function test_admin_can_access_news_list(): void
    {
        $user = User::factory()->create();
        $user->assignRole('admin');

        $this->actingAs($user)
            ->get('/admin/news')
            ->assertSuccessful();
    }

    public function test_super_admin_can_access_news_list(): void
    {
        $user = User::factory()->create();
        $user->assignRole('super_admin');

        $this->actingAs($user)
            ->get('/admin/news')
            ->assertSuccessful();
    }

    public function test_unauthenticated_user_cannot_access_news_list(): void
    {
        $this->get('/admin/news')->assertRedirect('/admin/login');
    }
}
```

- [ ] **Step 2 : Lancer le test pour vérifier qu'il échoue**

```bash
php artisan test tests/Feature/Admin/NewsResourceTest.php
```

Résultat attendu : FAIL (route `/admin/news` introuvable).

- [ ] **Step 3 : Créer le NewsResource**

Créer `app/Filament/Resources/NewsResource.php` :

```php
<?php

namespace App\Filament\Resources;

use App\Filament\Resources\NewsResource\Pages;
use App\Models\News;
use Awcodes\FilamentTiptapEditor\TiptapEditor;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Str;

class NewsResource extends Resource
{
    protected static ?string $model = News::class;
    protected static ?string $navigationIcon = 'heroicon-o-newspaper';
    protected static ?string $navigationLabel = 'Actualités';
    protected static ?string $modelLabel = 'Actualité';
    protected static ?string $pluralModelLabel = 'Actualités';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\TextInput::make('title')
                ->label('Titre')
                ->required()
                ->live(onBlur: true)
                ->afterStateUpdated(function (string $operation, $state, Set $set) {
                    if ($operation === 'create') {
                        $set('slug', Str::slug($state));
                    }
                }),

            Forms\Components\TextInput::make('slug')
                ->label('Slug (URL)')
                ->required()
                ->unique(ignoreRecord: true)
                ->disabled(fn (string $operation, ?News $record): bool =>
                    $operation === 'edit' && $record?->published_at !== null
                )
                ->dehydrated(),

            Forms\Components\Select::make('status')
                ->label('Statut')
                ->options([
                    'draft' => 'Brouillon',
                    'published' => 'Publié',
                    'archived' => 'Archivé',
                ])
                ->required()
                ->default('draft'),

            Forms\Components\FileUpload::make('featured_image')
                ->label('Image à la une')
                ->image()
                ->directory('news/featured')
                ->disk('public')
                ->nullable(),

            TiptapEditor::make('content')
                ->label('Contenu')
                ->required()
                ->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('title')
                    ->label('Titre')
                    ->searchable(),

                Tables\Columns\TextColumn::make('status')
                    ->label('Statut')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'draft' => 'gray',
                        'published' => 'success',
                        'archived' => 'danger',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'draft' => 'Brouillon',
                        'published' => 'Publié',
                        'archived' => 'Archivé',
                        default => $state,
                    }),

                Tables\Columns\TextColumn::make('published_at')
                    ->label('Publié le')
                    ->dateTime('d/m/Y')
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('Statut')
                    ->options([
                        'draft' => 'Brouillon',
                        'published' => 'Publié',
                        'archived' => 'Archivé',
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
            'index' => Pages\ListNews::route('/'),
            'create' => Pages\CreateNews::route('/create'),
            'edit' => Pages\EditNews::route('/{record}/edit'),
        ];
    }
}
```

- [ ] **Step 4 : Créer les pages Filament**

Créer `app/Filament/Resources/NewsResource/Pages/ListNews.php` :

```php
<?php

namespace App\Filament\Resources\NewsResource\Pages;

use App\Filament\Resources\NewsResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListNews extends ListRecords
{
    protected static string $resource = NewsResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\CreateAction::make()];
    }
}
```

Créer `app/Filament/Resources/NewsResource/Pages/CreateNews.php` :

```php
<?php

namespace App\Filament\Resources\NewsResource\Pages;

use App\Filament\Resources\NewsResource;
use Filament\Resources\Pages\CreateRecord;

class CreateNews extends CreateRecord
{
    protected static string $resource = NewsResource::class;
}
```

Créer `app/Filament/Resources/NewsResource/Pages/EditNews.php` :

```php
<?php

namespace App\Filament\Resources\NewsResource\Pages;

use App\Filament\Resources\NewsResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditNews extends EditRecord
{
    protected static string $resource = NewsResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\DeleteAction::make()];
    }
}
```

- [ ] **Step 5 : Lancer le test pour vérifier qu'il passe**

```bash
php artisan test tests/Feature/Admin/NewsResourceTest.php
```

Résultat attendu : 3 tests PASS.

- [ ] **Step 6 : Lancer tous les tests**

```bash
php artisan test
```

Résultat attendu : tous les tests PASS.

- [ ] **Step 7 : Commit**

```bash
git add app/Filament/Resources/ tests/Feature/Admin/NewsResourceTest.php
git commit -m "feat: NewsResource Filament — gestion des actualités"
```

---

### Task 5 : ActualitesController et routes

**Files:**
- Create: `app/Http/Controllers/ActualitesController.php`
- Modify: `routes/web.php`
- Create: `resources/js/pages/Actualites/Index.vue` (stub vide pour que l'assertion Inertia fonctionne)
- Create: `resources/js/pages/Actualites/Show.vue` (stub vide)
- Create: `tests/Feature/ActualitesControllerTest.php`

- [ ] **Step 1 : Créer le fichier de test**

Créer `tests/Feature/ActualitesControllerTest.php` :

```php
<?php

namespace Tests\Feature;

use App\Models\News;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class ActualitesControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_index_returns_only_published_articles(): void
    {
        News::factory()->published()->count(3)->create();
        News::factory()->create(['status' => 'draft']);
        News::factory()->archived()->create();

        $response = $this->get('/actualites');

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) =>
            $page->component('Actualites/Index')
                ->has('articles.data', 3)
        );
    }

    public function test_index_returns_articles_ordered_by_published_at_desc(): void
    {
        $old = News::factory()->published()->create(['published_at' => now()->subDays(5)]);
        $recent = News::factory()->published()->create(['published_at' => now()->subDay()]);

        $response = $this->get('/actualites');

        $response->assertInertia(fn (Assert $page) =>
            $page->component('Actualites/Index')
                ->where('articles.data.0.id', $recent->id)
                ->where('articles.data.1.id', $old->id)
        );
    }

    public function test_show_returns_published_article(): void
    {
        $article = News::factory()->published()->create(['slug' => 'mon-article']);

        $response = $this->get('/actualites/mon-article');

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) =>
            $page->component('Actualites/Show')
                ->where('article.slug', 'mon-article')
        );
    }

    public function test_show_returns_404_for_draft_article(): void
    {
        News::factory()->create(['slug' => 'brouillon', 'status' => 'draft']);

        $this->get('/actualites/brouillon')->assertNotFound();
    }

    public function test_show_returns_404_for_archived_article(): void
    {
        News::factory()->archived()->create(['slug' => 'archive']);

        $this->get('/actualites/archive')->assertNotFound();
    }

    public function test_show_returns_404_for_unknown_slug(): void
    {
        $this->get('/actualites/inexistant')->assertNotFound();
    }
}
```

- [ ] **Step 2 : Lancer le test pour vérifier qu'il échoue**

```bash
php artisan test tests/Feature/ActualitesControllerTest.php
```

Résultat attendu : FAIL avec `404 Not Found` ou `Route not found`.

- [ ] **Step 3 : Créer les stubs Vue (nécessaires pour que Inertia resolve le composant)**

Créer `resources/js/pages/Actualites/Index.vue` :

```vue
<template><div>Actualités</div></template>
```

Créer `resources/js/pages/Actualites/Show.vue` :

```vue
<template><div>Article</div></template>
```

- [ ] **Step 4 : Créer le controller**

Créer `app/Http/Controllers/ActualitesController.php` :

```php
<?php

namespace App\Http\Controllers;

use App\Models\News;
use Inertia\Inertia;
use Inertia\Response;

class ActualitesController extends Controller
{
    public function index(): Response
    {
        $articles = News::where('status', 'published')
            ->orderBy('published_at', 'desc')
            ->paginate(12);

        return Inertia::render('Actualites/Index', [
            'articles' => $articles,
        ]);
    }

    public function show(string $slug): Response
    {
        $article = News::where('slug', $slug)
            ->where('status', 'published')
            ->firstOrFail();

        return Inertia::render('Actualites/Show', [
            'article' => $article,
        ]);
    }
}
```

- [ ] **Step 5 : Ajouter les routes**

Modifier `routes/web.php` :

```php
<?php

use App\Http\Controllers\ActualitesController;
use App\Http\Controllers\HomeController;
use Illuminate\Support\Facades\Route;

Route::get('/', [HomeController::class, 'index']);

Route::get('/actualites', [ActualitesController::class, 'index'])->name('actualites.index');
Route::get('/actualites/{slug}', [ActualitesController::class, 'show'])->name('actualites.show');
```

- [ ] **Step 6 : Lancer le test pour vérifier qu'il passe**

```bash
php artisan test tests/Feature/ActualitesControllerTest.php
```

Résultat attendu : 6 tests PASS.

- [ ] **Step 7 : Lancer tous les tests**

```bash
php artisan test
```

Résultat attendu : tous les tests PASS.

- [ ] **Step 8 : Commit**

```bash
git add app/Http/Controllers/ActualitesController.php routes/web.php \
  resources/js/pages/Actualites/ tests/Feature/ActualitesControllerTest.php
git commit -m "feat: ActualitesController + routes + stubs Vue"
```

---

### Task 6 : Pages Vue Actualites/Index.vue et Show.vue

**Files:**
- Modify: `resources/js/pages/Actualites/Index.vue` (remplacer le stub)
- Modify: `resources/js/pages/Actualites/Show.vue` (remplacer le stub)

> Pas de test automatisé pour le rendu Vue — vérification manuelle dans le navigateur.

- [ ] **Step 1 : S'assurer que le serveur de dev tourne**

Dans deux terminaux séparés :

```bash
# Terminal 1
php artisan serve

# Terminal 2
npm run dev
```

- [ ] **Step 2 : Remplir Actualites/Index.vue**

Remplacer `resources/js/pages/Actualites/Index.vue` :

```vue
<script setup>
import { Link } from '@inertiajs/vue3'

defineProps({
    articles: Object,
})

function stripHtml(html) {
    const div = document.createElement('div')
    div.innerHTML = html
    return div.textContent || div.innerText || ''
}

function excerpt(content, length = 150) {
    const text = stripHtml(content).trim()
    return text.length > length ? text.slice(0, length).trimEnd() + '…' : text
}

function formatDate(dateStr) {
    return new Date(dateStr).toLocaleDateString('fr-FR', {
        year: 'numeric',
        month: 'long',
        day: 'numeric',
    })
}
</script>

<template>
    <div>
        <h1>Actualités</h1>

        <p v-if="articles.data.length === 0">
            Aucune actualité pour le moment.
        </p>

        <div v-else>
            <div>
                <article v-for="article in articles.data" :key="article.id">
                    <Link :href="`/actualites/${article.slug}`">
                        <img
                            v-if="article.featured_image"
                            :src="`/storage/${article.featured_image}`"
                            :alt="article.title"
                        />
                        <h2>{{ article.title }}</h2>
                    </Link>
                    <time>{{ formatDate(article.published_at) }}</time>
                    <p>{{ excerpt(article.content) }}</p>
                </article>
            </div>

            <nav v-if="articles.links && articles.links.length > 3">
                <template v-for="link in articles.links" :key="link.label">
                    <Link
                        v-if="link.url"
                        :href="link.url"
                        v-html="link.label"
                        :aria-current="link.active ? 'page' : undefined"
                    />
                    <span v-else v-html="link.label" aria-disabled="true" />
                </template>
            </nav>
        </div>
    </div>
</template>
```

- [ ] **Step 3 : Remplir Actualites/Show.vue**

Remplacer `resources/js/pages/Actualites/Show.vue` :

```vue
<script setup>
import { Link } from '@inertiajs/vue3'

defineProps({
    article: Object,
})

function formatDate(dateStr) {
    return new Date(dateStr).toLocaleDateString('fr-FR', {
        year: 'numeric',
        month: 'long',
        day: 'numeric',
    })
}
</script>

<template>
    <div>
        <Link href="/actualites">← Toutes les actualités</Link>

        <article>
            <img
                v-if="article.featured_image"
                :src="`/storage/${article.featured_image}`"
                :alt="article.title"
            />

            <h1>{{ article.title }}</h1>
            <time :datetime="article.published_at">
                {{ formatDate(article.published_at) }}
            </time>

            <div v-html="article.content" />
        </article>
    </div>
</template>
```

- [ ] **Step 4 : Créer un article de test dans Filament et vérifier**

Ouvrir `http://localhost:8000/admin` → Actualités → Créer un article.

- Renseigner un titre : l'URL doit se remplir automatiquement.
- Passer le statut à **Publié** → sauvegarder.
- Vérifier que `published_at` est bien renseigné dans la liste.
- Ouvrir `http://localhost:8000/actualites` → l'article doit apparaître.
- Cliquer sur l'article → la page de détail doit s'afficher avec le contenu.

- [ ] **Step 5 : Vérifier les cas limites**

- Créer un article en **Brouillon** → vérifier qu'il n'apparaît pas sur `/actualites`.
- Accéder directement à son URL `/actualites/{slug}` → doit retourner une page 404.
- Passer un article publié en **Archivé** → il disparaît de la liste et son URL retourne 404.
- Re-publier un article archivé → `published_at` doit rester inchangé (date d'origine conservée).

- [ ] **Step 6 : Lancer les tests complets une dernière fois**

```bash
php artisan test
```

Résultat attendu : tous les tests PASS.

- [ ] **Step 7 : Commit**

```bash
git add resources/js/pages/Actualites/
git commit -m "feat: pages Vue Actualites/Index et Show"
```
