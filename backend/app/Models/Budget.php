<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Budget extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'utilisateur_id',
        'nom',
        'date_debut',
        'date_fin',
        'montant_total',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'date_debut' => 'date',
        'date_fin' => 'date',
        'montant_total' => 'decimal:2',
    ];

    /**
     * Get the user that owns the budget.
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'utilisateur_id');
    }

    /**
     * Get the category budget records for the budget.
     */
    public function categoryBudgets()
    {
        return $this->hasMany(CategoryBudget::class);
    }

    /**
     * Get the categories for the budget.
     */
    public function categories()
    {
        return $this->belongsToMany(Category::class, 'category_budget')
                    ->withPivot('montant_alloue', 'pourcentage')
                    ->withTimestamps();
    }

    /**
     * Get all expenses for this budget across all categories.
     */
    public function expenses()
    {
        return $this->hasManyThrough(
            Expense::class,
            CategoryBudget::class,
            'budget_id', // Foreign key on category_budget table
            'category_budget_id', // Foreign key on expenses table
            'id', // Local key on budgets table
            'id' // Local key on category_budget table
        );
    }

    /**
     * Get the total amount spent for this budget.
     */
    public function getTotalSpentAttribute()
    {
        return $this->expenses()->sum('montant');
    }

    /**
     * Get the remaining amount for this budget.
     */
    public function getRemainingAmountAttribute()
    {
        return $this->montant_total - $this->total_spent;
    }
}