<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\API\AuthController;
use App\Http\Controllers\API\UserController;
use App\Http\Controllers\API\AccountController;
use App\Http\Controllers\API\IncomeController;
use App\Http\Controllers\API\BudgetController;
use App\Http\Controllers\API\CategoryController;
use App\Http\Controllers\API\CategoryBudgetController;
use App\Http\Controllers\API\ExpenseController;
use App\Http\Controllers\API\GoalController;
use App\Http\Controllers\API\AlertController;
use App\Http\Controllers\API\StatisticsController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

// Routes publiques
Route::middleware('throttle:auth')->group(function () {
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login', [AuthController::class, 'login']);
    Route::post('/forgot-password', [AuthController::class, 'forgotPassword']);
    Route::post('/reset-password', [AuthController::class, 'resetPassword']);
});

// Routes protégées par authentification
Route::middleware('auth:sanctum')->group(function () {
    // Authentification et utilisateur
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/user', [UserController::class, 'show']);
    Route::put('/user', [UserController::class, 'update']);
    Route::post('/user/change-password', [UserController::class, 'changePassword']);
    
    // Comptes
    Route::apiResource('accounts', AccountController::class);
    Route::get('/accounts/{account}/transactions', [AccountController::class, 'transactions']);
    Route::post('/accounts/{account}/adjust-balance', [AccountController::class, 'adjustBalance']);
    
    // Revenus
    Route::apiResource('incomes', IncomeController::class);
    Route::get('/incomes/by-account/{account}', [IncomeController::class, 'byAccount']);
    Route::get('/incomes/by-period', [IncomeController::class, 'byPeriod']);
    
    // Budgets
    Route::apiResource('budgets', BudgetController::class);
    Route::get('/budgets/{budget}/expenses', [BudgetController::class, 'expenses']);
    Route::get('/budgets/current', [BudgetController::class, 'current']);
    Route::post('/budgets/{budget}/duplicate', [BudgetController::class, 'duplicate']);
    
    // Catégories
    Route::apiResource('categories', CategoryController::class);
    Route::delete('/categories/bulk-delete', [CategoryController::class, 'bulkDelete']);
    Route::put('/categories/update-default-percentages', [CategoryController::class, 'updateDefaultPercentages']);
    Route::post('/categories/import', [CategoryController::class, 'import']);
    Route::get('/categories/{category}/usage', [CategoryController::class, 'usage']);
    
    // Allocations de budget par catégorie
    Route::apiResource('category-budgets', CategoryBudgetController::class);
    Route::get('/category-budgets/by-budget/{budget}', [CategoryBudgetController::class, 'byBudget']);
    Route::get('/category-budgets/by-category/{category}', [CategoryBudgetController::class, 'byCategory']);
    
    // Dépenses
    Route::apiResource('expenses', ExpenseController::class);
    Route::get('/expenses/by-category/{categoryBudget}', [ExpenseController::class, 'byCategory']);
    Route::get('/expenses/by-budget/{budget}', [ExpenseController::class, 'byBudget']);
    Route::get('/expenses/by-period', [ExpenseController::class, 'byPeriod']);
    Route::post('/expenses/bulk-create', [ExpenseController::class, 'bulkCreate']);
    Route::post('/expenses/import', [ExpenseController::class, 'import']);
    
    // Objectifs
    Route::apiResource('goals', GoalController::class);
    Route::get('/goals/active', [GoalController::class, 'active']);
    Route::put('/goals/{goal}/mark-as-completed', [GoalController::class, 'markAsCompleted']);
    
    // Alertes
    Route::apiResource('alerts', AlertController::class);
    Route::get('/triggered-alerts', [AlertController::class, 'getTriggeredAlerts']);
    Route::put('/alerts/{alert}/toggle-active', [AlertController::class, 'toggleActive']);
    
    // Statistiques
    Route::get('/statistics/monthly', [StatisticsController::class, 'monthly']);
    Route::get('/statistics/yearly', [StatisticsController::class, 'yearly']);
    Route::get('/statistics/categories', [StatisticsController::class, 'categories']);
    Route::get('/statistics/trends', [StatisticsController::class, 'trends']);
    Route::get('/statistics/overview', [StatisticsController::class, 'overview']);
    Route::get('/statistics/expenses-by-month', [StatisticsController::class, 'expensesByMonth']);
    Route::get('/statistics/budget-performance', [StatisticsController::class, 'budgetPerformance']);
});