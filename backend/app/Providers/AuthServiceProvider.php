<?php

namespace App\Providers;

use App\Models\Budget;
use App\Models\Account;
use App\Models\Income;
use App\Models\Expense;
use App\Models\Goal;
use App\Policies\BudgetPolicy;
use App\Policies\AccountPolicy;
use App\Policies\IncomePolicy;
use App\Policies\ExpensePolicy;
use App\Policies\GoalPolicy;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Support\Facades\Gate;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The model to policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        Budget::class => BudgetPolicy::class,
        Account::class => AccountPolicy::class,
        Income::class => IncomePolicy::class,
        Expense::class => ExpensePolicy::class,
        Goal::class => GoalPolicy::class,
    ];

    /**
     * Register any authentication / authorization services.
     */
    public function boot(): void
    {
        $this->registerPolicies();

        // Définir la redirection pour la réinitialisation du mot de passe
        ResetPassword::createUrlUsing(function ($user, string $token) {
            return env('SPA_URL') . '/reset-password?token=' . $token . '&email=' . urlencode($user->email);
        });

        // Définir des gates pour les permissions supplémentaires
        Gate::define('view-statistics', function ($user) {
            return true; // Tous les utilisateurs peuvent voir leurs statistiques
        });

        Gate::define('bulk-delete-categories', function ($user) {
            return true; // Tous les utilisateurs peuvent supprimer des catégories en masse
        });
    }
}