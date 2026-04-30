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
