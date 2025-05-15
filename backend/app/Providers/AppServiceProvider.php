<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Schema;
use Illuminate\Http\Resources\Json\JsonResource;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Définir la longueur par défaut des chaînes de caractères dans les migrations
        Schema::defaultStringLength(191);
        
        // Supprimer le wrapper data des ressources API
        JsonResource::withoutWrapping();
        
        // Définir le locale de l'application en français
        app()->setLocale('fr');
        
        // Définir les paramètres de pagination
        \Illuminate\Pagination\Paginator::defaultView('pagination::default');
        \Illuminate\Pagination\Paginator::defaultSimpleView('pagination::simple-default');
    }
}