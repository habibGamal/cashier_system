<?php

namespace App\Providers;

use Filament\Actions\Exports\Jobs\ExportCsv;
use App\Jobs\ExporterCsv;
use Filament\Auth\Http\Responses\LoginResponse;
use App\Http\Responses\CustomLoginResponse;
use App\Jobs\ImportCsv;
use App\Models\Product;
use App\Observers\ProductObserver;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Vite;
use Illuminate\Support\ServiceProvider;
use Filament\Actions\Imports\Jobs\ImportCsv as BaseImportCsv;
use Filament\Support\Facades\FilamentView;
use Filament\View\PanelsRenderHook;
use Illuminate\Contracts\View\View;
use Filament\Actions\Action;
use Illuminate\Support\Facades\Date;
use Carbon\Carbon;
use Illuminate\Support\Facades\URL;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(BaseImportCsv::class, ImportCsv::class);
        $this->app->bind(ExportCsv::class, ExporterCsv::class);
        $this->app->singleton(LoginResponse::class, CustomLoginResponse::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Vite::prefetch(concurrency: 3);

        // Register model observers
        Product::observe(ProductObserver::class);

        JsonResource::withoutWrapping();

        FilamentView::registerRenderHook(
            PanelsRenderHook::GLOBAL_SEARCH_AFTER,
            fn(): View => view('filament.global-actions'),
        );

        if (request()->isSecure() || request()->header('X-Forwarded-Proto') === 'https') {
            URL::forceScheme('https');
        }
    }
}
