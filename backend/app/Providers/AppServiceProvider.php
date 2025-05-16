<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Schema;

use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Pagination\Paginator;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Tu peux enregistrer ici des services ou des dépendances
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Définir la longueur par défaut des chaînes pour les anciennes versions de MySQL
        Schema::defaultStringLength(191);

        // Supprimer l'enveloppe "data" autour des ressources JSON
        JsonResource::withoutWrapping();

        // Définir la langue de l'application en français
        app()->setLocale('fr');

        // Utiliser les vues de pagination Bootstrap (ou autres si tu as personnalisé)
        Paginator::defaultView('pagination::default');
        Paginator::defaultSimpleView('pagination::simple-default');
    }
}
