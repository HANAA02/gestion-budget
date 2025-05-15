<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Services\BudgetCalculationService;
use App\Services\AlertService;
use App\Services\StatisticsService;

class BudgetServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Enregistrer les services spécifiques à la gestion de budget
        $this->app->singleton(BudgetCalculationService::class, function ($app) {
            return new BudgetCalculationService();
        });

        $this->app->singleton(AlertService::class, function ($app) {
            return new AlertService();
        });

        $this->app->singleton(StatisticsService::class, function ($app) {
            return new StatisticsService();
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Enregistrer des macros personnalisées pour les modèles de budget
        \App\Models\Budget::macro('getTotalExpenses', function () {
            return $this->expenses()->sum('montant');
        });

        \App\Models\Budget::macro('getRemainingBudget', function () {
            return $this->montant_total - $this->getTotalExpenses();
        });

        \App\Models\Budget::macro('getSpentPercentage', function () {
            return $this->montant_total > 0 
                ? ($this->getTotalExpenses() / $this->montant_total) * 100 
                : 0;
        });
    }
}