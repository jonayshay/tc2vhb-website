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
