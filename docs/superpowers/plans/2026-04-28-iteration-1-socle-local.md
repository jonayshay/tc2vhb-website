# Itération 1 — Socle local : Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Mettre en place un environnement Laravel + Inertia.js + Vue.js + Filament fonctionnel en local sur WSL, avec authentification admin-only et système de rôles Spatie.

**Architecture:** Laravel sert à la fois le site public (Inertia/Vue) et le back-office Filament depuis le même projet. L'authentification est réservée aux administrateurs (panel Filament). Spatie laravel-permission gère deux rôles : `super_admin` et `admin`. Le site public est entièrement accessible sans connexion.

**Tech Stack:** PHP 8.2+, Laravel 11, Inertia.js 2, Vue.js 3, Vite, Filament 3, Spatie laravel-permission 6, MySQL 8, Mailpit

---

## Fichiers créés / modifiés

| Fichier | Rôle |
|---|---|
| `bootstrap/app.php` | Enregistrement du middleware Inertia |
| `app/Http/Middleware/HandleInertiaRequests.php` | Middleware Inertia (données partagées) |
| `app/Http/Controllers/HomeController.php` | Controller page d'accueil |
| `app/Models/User.php` | Modèle User — HasRoles + FilamentUser |
| `app/Providers/Filament/AdminPanelProvider.php` | Config panel Filament (généré par artisan) |
| `resources/views/app.blade.php` | Template racine Inertia |
| `resources/js/app.js` | Point d'entrée Inertia + Vue |
| `resources/js/layouts/MainLayout.vue` | Layout principal public |
| `resources/js/pages/Home.vue` | Page d'accueil |
| `vite.config.js` | Config Vite + plugin Vue |
| `routes/web.php` | Route `/` |
| `database/seeders/RolesAndPermissionsSeeder.php` | Création des rôles Spatie |
| `database/seeders/AdminUserSeeder.php` | Création de l'utilisateur admin de test |
| `database/seeders/DatabaseSeeder.php` | Orchestration des seeders |
| `tests/Feature/HomePageTest.php` | Test : GET / retourne le composant Inertia Home |
| `tests/Feature/Admin/AdminAccessTest.php` | Tests : accès Filament selon rôle |
| `tests/Feature/Admin/RolesTest.php` | Tests : attribution et vérification des rôles |

> Les tests utilisent SQLite en mémoire (configuration par défaut de `phpunit.xml` en Laravel 11) — pas besoin de base de données de test séparée.

---

### Task 1 : WSL — Dépendances système

**Files:** aucun fichier de projet — configuration système uniquement

- [ ] **Step 1 : Ajouter le dépôt PHP et mettre à jour les paquets**

```bash
sudo add-apt-repository ppa:ondrej/php -y
sudo apt update && sudo apt upgrade -y
```

- [ ] **Step 2 : Installer PHP 8.2 et ses extensions Laravel**

```bash
sudo apt install -y php8.2 php8.2-cli php8.2-fpm php8.2-mysql php8.2-xml \
  php8.2-mbstring php8.2-curl php8.2-zip php8.2-gd php8.2-bcmath \
  php8.2-intl php8.2-sqlite3
php -v
```

Résultat attendu : `PHP 8.2.x (cli)`

- [ ] **Step 3 : Installer MySQL 8**

```bash
sudo apt install -y mysql-server
sudo service mysql start
```

Créer un utilisateur applicatif dédié :

```bash
sudo mysql <<'EOF'
CREATE USER 'tc2v'@'localhost' IDENTIFIED BY 'tc2v_password';
GRANT ALL PRIVILEGES ON *.* TO 'tc2v'@'localhost' WITH GRANT OPTION;
FLUSH PRIVILEGES;
EOF
```

Pour démarrer MySQL automatiquement à chaque session WSL :

```bash
echo "sudo service mysql start 2>/dev/null" >> ~/.bashrc
```

- [ ] **Step 4 : Installer Composer**

```bash
curl -sS https://getcomposer.org/installer | php
sudo mv composer.phar /usr/local/bin/composer
composer --version
```

Résultat attendu : `Composer version 2.x.x`

- [ ] **Step 5 : Installer Node.js LTS via nvm**

```bash
curl -o- https://raw.githubusercontent.com/nvm-sh/nvm/v0.39.7/install.sh | bash
source ~/.bashrc
nvm install --lts
node --version && npm --version
```

Résultat attendu : `v20.x.x` / `10.x.x`

---

### Task 2 : Scaffolding Laravel dans le projet existant

**Files:** tous les fichiers Laravel créés dans `~/projects/tc2v-hb/`

Le répertoire contient déjà `.git` et `docs/`. On installe Laravel dans un dossier temporaire puis on fusionne.

- [ ] **Step 1 : Créer le projet Laravel dans un dossier temporaire**

```bash
composer create-project laravel/laravel /tmp/tc2v-laravel --prefer-dist
```

- [ ] **Step 2 : Fusionner dans le repo existant**

```bash
rsync -av /tmp/tc2v-laravel/ ~/projects/tc2v-hb/
rm -rf /tmp/tc2v-laravel
```

Vérifier que `docs/` est toujours présent et que `artisan` est à la racine :

```bash
ls ~/projects/tc2v-hb/artisan ~/projects/tc2v-hb/docs/
```

- [ ] **Step 3 : Installer les dépendances**

```bash
cd ~/projects/tc2v-hb
composer install
npm install
```

- [ ] **Step 4 : Vérifier l'installation Laravel**

```bash
php artisan --version
```

Résultat attendu : `Laravel Framework 11.x.x`

- [ ] **Step 5 : Commit initial Laravel**

```bash
git add .
git commit -m "feat: scaffolding Laravel 11"
```

---

### Task 3 : Configurer la base de données MySQL

**Files:** `.env`

- [ ] **Step 1 : Créer la base de données**

```bash
mysql -u tc2v -ptc2v_password -e \
  "CREATE DATABASE IF NOT EXISTS tc2v_handball CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
```

- [ ] **Step 2 : Configurer .env**

```bash
cp .env.example .env
php artisan key:generate
```

Modifier les variables suivantes dans `.env` :

```dotenv
APP_NAME="TC2V Handball"
APP_URL=http://localhost:8000

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=tc2v_handball
DB_USERNAME=tc2v
DB_PASSWORD=tc2v_password
```

- [ ] **Step 3 : Lancer les migrations**

```bash
php artisan migrate
```

Résultat attendu : tables `users`, `password_reset_tokens`, `sessions`, `jobs`, `cache` créées sans erreur.

- [ ] **Step 4 : Vérifier**

```bash
mysql -u tc2v -ptc2v_password tc2v_handball -e "SHOW TABLES;"
```

---

### Task 4 : Installer et configurer Inertia.js (côté Laravel)

**Files:**
- Create: `app/Http/Middleware/HandleInertiaRequests.php`
- Create: `resources/views/app.blade.php`
- Modify: `bootstrap/app.php`

- [ ] **Step 1 : Installer le package Inertia pour Laravel**

```bash
composer require inertiajs/inertia-laravel
```

- [ ] **Step 2 : Générer le middleware Inertia**

```bash
php artisan inertia:middleware
```

Cela génère `app/Http/Middleware/HandleInertiaRequests.php` avec la méthode `share()` pour passer des données globales aux pages Vue.

- [ ] **Step 3 : Enregistrer le middleware dans bootstrap/app.php**

Dans `bootstrap/app.php`, ajouter dans le callback `withMiddleware` :

```php
->withMiddleware(function (Middleware $middleware) {
    $middleware->web(append: [
        \App\Http\Middleware\HandleInertiaRequests::class,
    ]);
})
```

- [ ] **Step 4 : Créer le template racine Blade**

Créer `resources/views/app.blade.php` :

```html
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>TC2V Handball</title>
        @vite(['resources/css/app.css', 'resources/js/app.js'])
        @inertiaHead
    </head>
    <body class="antialiased">
        @inertia
    </body>
</html>
```

- [ ] **Step 5 : Commit**

```bash
git add app/Http/Middleware/HandleInertiaRequests.php \
  resources/views/app.blade.php bootstrap/app.php \
  composer.json composer.lock
git commit -m "feat: install and configure Inertia.js middleware"
```

---

### Task 5 : Installer et configurer Vue.js + Vite

**Files:**
- Modify: `vite.config.js`
- Modify: `resources/js/app.js`
- Create: `resources/js/layouts/MainLayout.vue`

- [ ] **Step 1 : Installer les packages npm**

```bash
npm install @inertiajs/vue3 vue @vitejs/plugin-vue
```

- [ ] **Step 2 : Configurer vite.config.js**

Remplacer le contenu de `vite.config.js` :

```javascript
import { defineConfig } from 'vite'
import laravel from 'laravel-vite-plugin'
import vue from '@vitejs/plugin-vue'

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.js'],
            refresh: true,
        }),
        vue({
            template: {
                transformAssetUrls: {
                    base: null,
                    includeAbsolute: false,
                },
            },
        }),
    ],
})
```

- [ ] **Step 3 : Configurer le point d'entrée Inertia (resources/js/app.js)**

Remplacer le contenu de `resources/js/app.js` :

```javascript
import { createApp, h } from 'vue'
import { createInertiaApp } from '@inertiajs/vue3'
import MainLayout from './layouts/MainLayout.vue'

createInertiaApp({
    resolve: name => {
        const pages = import.meta.glob('./pages/**/*.vue', { eager: true })
        const page = pages[`./pages/${name}.vue`]
        page.default.layout = page.default.layout || MainLayout
        return page
    },
    setup({ el, App, props, plugin }) {
        createApp({ render: () => h(App, props) })
            .use(plugin)
            .mount(el)
    },
})
```

- [ ] **Step 4 : Créer le layout principal**

Créer `resources/js/layouts/MainLayout.vue` :

```vue
<template>
    <div>
        <header>
            <nav>
                <a href="/">TC2V Handball</a>
            </nav>
        </header>
        <main>
            <slot />
        </main>
        <footer>
            <p>TC2V Handball — Triel, Chanteloup, Vernouillet, Verneuil</p>
        </footer>
    </div>
</template>
```

- [ ] **Step 5 : Commit**

```bash
git add vite.config.js resources/js/app.js \
  resources/js/layouts/MainLayout.vue \
  package.json package-lock.json
git commit -m "feat: install and configure Vue.js 3 + Vite frontend"
```

---

### Task 6 : Page d'accueil — TDD

**Files:**
- Create: `tests/Feature/HomePageTest.php`
- Create: `app/Http/Controllers/HomeController.php`
- Create: `resources/js/pages/Home.vue`
- Modify: `routes/web.php`

- [ ] **Step 1 : Écrire le test (il doit échouer)**

Créer `tests/Feature/HomePageTest.php` :

```php
<?php

namespace Tests\Feature;

use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class HomePageTest extends TestCase
{
    public function test_home_page_returns_inertia_response(): void
    {
        $response = $this->get('/');

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) =>
            $page->component('Home')
        );
    }
}
```

- [ ] **Step 2 : Lancer le test — vérifier qu'il échoue**

```bash
php artisan test tests/Feature/HomePageTest.php
```

Résultat attendu : FAIL — la route `/` retourne une vue Blade par défaut, pas Inertia.

- [ ] **Step 3 : Créer HomeController**

Créer `app/Http/Controllers/HomeController.php` :

```php
<?php

namespace App\Http\Controllers;

use Inertia\Inertia;
use Inertia\Response;

class HomeController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Home');
    }
}
```

- [ ] **Step 4 : Mettre à jour routes/web.php**

Remplacer le contenu de `routes/web.php` :

```php
<?php

use App\Http\Controllers\HomeController;
use Illuminate\Support\Facades\Route;

Route::get('/', [HomeController::class, 'index']);
```

- [ ] **Step 5 : Créer la page Vue Home**

Créer `resources/js/pages/Home.vue` :

```vue
<template>
    <div>
        <h1>Bienvenue au TC2V Handball</h1>
        <p>Triel, Chanteloup, Vernouillet, Verneuil — Yvelines</p>
    </div>
</template>
```

- [ ] **Step 6 : Lancer le test — vérifier qu'il passe**

```bash
php artisan test tests/Feature/HomePageTest.php
```

Résultat attendu : PASS

- [ ] **Step 7 : Vérifier dans le navigateur**

```bash
# Terminal 1
php artisan serve

# Terminal 2
npm run dev
```

Ouvrir `http://localhost:8000` — la page s'affiche avec le layout (header TC2V Handball, contenu, footer).

- [ ] **Step 8 : Commit**

```bash
git add tests/Feature/HomePageTest.php \
  app/Http/Controllers/HomeController.php \
  resources/js/pages/Home.vue routes/web.php
git commit -m "feat: add home page with Inertia + Vue (TDD)"
```

---

### Task 7 : Installer Filament

**Files:**
- Create (auto): `app/Providers/Filament/AdminPanelProvider.php`
- Modify (auto): `bootstrap/providers.php`

- [ ] **Step 1 : Installer Filament**

```bash
composer require filament/filament:"^3.2" -W
```

- [ ] **Step 2 : Initialiser le panel admin**

```bash
php artisan filament:install --panels
```

Quand demandé : accepter l'ID de panel par défaut (`admin`). Cela génère `app/Providers/Filament/AdminPanelProvider.php` et enregistre le provider dans `bootstrap/providers.php`.

- [ ] **Step 3 : Lancer les migrations Filament**

```bash
php artisan migrate
```

- [ ] **Step 4 : Vérifier l'accès au panel**

```bash
php artisan serve
```

Ouvrir `http://localhost:8000/admin` — la page de login Filament doit s'afficher.

- [ ] **Step 5 : Commit**

```bash
git add app/Providers/Filament/ bootstrap/providers.php \
  composer.json composer.lock
git commit -m "feat: install Filament admin panel"
```

---

### Task 8 : Installer Spatie laravel-permission + configurer User

**Files:**
- Create (auto): `config/permission.php`
- Create (auto): `database/migrations/..._create_permission_tables.php`
- Modify: `app/Models/User.php`

- [ ] **Step 1 : Installer le package**

```bash
composer require spatie/laravel-permission
```

- [ ] **Step 2 : Publier la configuration et les migrations**

```bash
php artisan vendor:publish --provider="Spatie\Permission\PermissionServiceProvider"
```

- [ ] **Step 3 : Lancer les migrations**

```bash
php artisan migrate
```

Résultat attendu : tables `roles`, `permissions`, `model_has_roles`, `model_has_permissions`, `role_has_permissions` créées.

- [ ] **Step 4 : Mettre à jour app/Models/User.php**

Remplacer le contenu de `app/Models/User.php` :

```php
<?php

namespace App\Models;

use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable implements FilamentUser
{
    use HasFactory, Notifiable, HasRoles;

    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function canAccessPanel(Panel $panel): bool
    {
        return $this->hasAnyRole(['admin', 'super_admin']);
    }
}
```

- [ ] **Step 5 : Commit**

```bash
git add app/Models/User.php config/permission.php \
  composer.json composer.lock
git commit -m "feat: install Spatie laravel-permission, User implements FilamentUser + HasRoles"
```

---

### Task 9 : Seeders — rôles et utilisateur admin

**Files:**
- Create: `database/seeders/RolesAndPermissionsSeeder.php`
- Create: `database/seeders/AdminUserSeeder.php`
- Modify: `database/seeders/DatabaseSeeder.php`

- [ ] **Step 1 : Créer RolesAndPermissionsSeeder**

Créer `database/seeders/RolesAndPermissionsSeeder.php` :

```php
<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RolesAndPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        Role::firstOrCreate(['name' => 'super_admin', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
    }
}
```

- [ ] **Step 2 : Créer AdminUserSeeder**

Créer `database/seeders/AdminUserSeeder.php` :

```php
<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        $user = User::firstOrCreate(
            ['email' => 'admin@tc2v-handball.fr'],
            [
                'name' => 'Super Admin',
                'password' => bcrypt('password'),
                'email_verified_at' => now(),
            ]
        );
        $user->assignRole('super_admin');
    }
}
```

- [ ] **Step 3 : Mettre à jour DatabaseSeeder.php**

Remplacer le contenu de `database/seeders/DatabaseSeeder.php` :

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
        ]);
    }
}
```

- [ ] **Step 4 : Lancer les seeders**

```bash
php artisan db:seed
```

Résultat attendu : seeders exécutés sans erreur.

- [ ] **Step 5 : Vérifier en base**

```bash
mysql -u tc2v -ptc2v_password tc2v_handball \
  -e "SELECT name FROM roles; SELECT email FROM users;"
```

Résultat attendu : rôles `super_admin` et `admin`, utilisateur `admin@tc2v-handball.fr`.

- [ ] **Step 6 : Commit**

```bash
git add database/seeders/
git commit -m "feat: add roles and admin user seeders"
```

---

### Task 10 : Tests — accès admin et rôles

**Files:**
- Create: `tests/Feature/Admin/AdminAccessTest.php`
- Create: `tests/Feature/Admin/RolesTest.php`

- [ ] **Step 1 : Créer tests/Feature/Admin/AdminAccessTest.php**

```php
<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminAccessTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
    }

    public function test_unauthenticated_user_is_redirected_to_login(): void
    {
        $this->get('/admin')->assertRedirect('/admin/login');
    }

    public function test_admin_user_can_access_panel(): void
    {
        $user = User::factory()->create();
        $user->assignRole('admin');

        $this->actingAs($user)
            ->followingRedirects()
            ->get('/admin')
            ->assertSuccessful();
    }

    public function test_super_admin_can_access_panel(): void
    {
        $user = User::factory()->create();
        $user->assignRole('super_admin');

        $this->actingAs($user)
            ->followingRedirects()
            ->get('/admin')
            ->assertSuccessful();
    }

    public function test_user_without_role_cannot_access_panel(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get('/admin')->assertForbidden();
    }
}
```

- [ ] **Step 2 : Créer tests/Feature/Admin/RolesTest.php**

```php
<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RolesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
    }

    public function test_user_can_be_assigned_admin_role(): void
    {
        $user = User::factory()->create();
        $user->assignRole('admin');

        $this->assertTrue($user->hasRole('admin'));
        $this->assertFalse($user->hasRole('super_admin'));
    }

    public function test_user_can_be_assigned_super_admin_role(): void
    {
        $user = User::factory()->create();
        $user->assignRole('super_admin');

        $this->assertTrue($user->hasRole('super_admin'));
    }

    public function test_user_can_hold_multiple_roles(): void
    {
        $user = User::factory()->create();
        $user->assignRole('admin');
        $user->assignRole('super_admin');

        $this->assertTrue($user->hasRole('admin'));
        $this->assertTrue($user->hasRole('super_admin'));
    }

    public function test_user_without_role_cannot_access_panel(): void
    {
        $user = User::factory()->create();

        $this->assertFalse($user->hasAnyRole(['admin', 'super_admin']));
    }
}
```

- [ ] **Step 3 : Lancer tous les tests**

```bash
php artisan test
```

Résultat attendu : tous les tests PASS.

> Si `test_user_without_role_cannot_access_panel` retourne 302 au lieu de 403, Filament redirige plutôt que de rejeter directement. Remplacer `assertForbidden()` par `assertRedirect()` dans `AdminAccessTest`.

- [ ] **Step 4 : Commit**

```bash
git add tests/Feature/Admin/
git commit -m "test: admin access and roles feature tests"
```

---

### Task 11 : Installer Mailpit (capture d'emails locale)

**Files:**
- Modify: `.env`
- Modify: `.env.example`

- [ ] **Step 1 : Télécharger et installer Mailpit**

```bash
curl -sSL https://raw.githubusercontent.com/axllent/mailpit/develop/install.sh | sudo bash
mailpit --version
```

- [ ] **Step 2 : Lancer Mailpit en arrière-plan**

```bash
mailpit &
```

Interface web disponible à `http://localhost:8025`.

- [ ] **Step 3 : Configurer .env pour Mailpit**

Dans `.env`, remplacer la section MAIL :

```dotenv
MAIL_MAILER=smtp
MAIL_HOST=localhost
MAIL_PORT=1025
MAIL_USERNAME=null
MAIL_PASSWORD=null
MAIL_ENCRYPTION=null
MAIL_FROM_ADDRESS="noreply@tc2v-handball.fr"
MAIL_FROM_NAME="TC2V Handball"
```

- [ ] **Step 4 : Vérifier la capture d'emails**

```bash
php artisan tinker --execute="Mail::raw('Test Mailpit', fn(\$m) => \$m->to('test@example.com')->subject('Test'));"
```

Ouvrir `http://localhost:8025` — l'email doit apparaître dans Mailpit.

- [ ] **Step 5 : Documenter dans .env.example**

Mettre à jour la section MAIL dans `.env.example` pour documenter la config Mailpit locale :

```dotenv
MAIL_MAILER=smtp
MAIL_HOST=localhost
MAIL_PORT=1025
MAIL_USERNAME=null
MAIL_PASSWORD=null
MAIL_ENCRYPTION=null
MAIL_FROM_ADDRESS="noreply@tc2v-handball.fr"
MAIL_FROM_NAME="TC2V Handball"
```

- [ ] **Step 6 : Commit**

```bash
git add .env.example
git commit -m "docs: document Mailpit config in .env.example"
```

---

### Task 12 : Vérification finale

**Files:** aucun

- [ ] **Step 1 : Lancer la suite de tests complète**

```bash
php artisan test
```

Résultat attendu : tous les tests PASS, aucun WARNING.

- [ ] **Step 2 : Vérifier le site public**

```bash
# Terminal 1
php artisan serve

# Terminal 2
npm run dev
```

Ouvrir `http://localhost:8000` — page d'accueil visible avec header, contenu et footer.

- [ ] **Step 3 : Vérifier le back-office Filament**

Ouvrir `http://localhost:8000/admin` → page de login Filament.
Se connecter avec `admin@tc2v-handball.fr` / `password`.
Résultat attendu : accès au dashboard Filament sans erreur.

- [ ] **Step 4 : Commit de clôture de l'itération**

```bash
git add .
git status
git commit -m "feat: iteration 1 complete — socle local Laravel + Inertia + Vue + Filament + Spatie"
```

> `.env` ne doit jamais être commité — il est dans `.gitignore`. Seul `.env.example` (sans credentials réels) est versionné.
