<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Expense extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'category_budget_id',
        'description',
        'montant',
        'date_depense',
        'statut',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'montant' => 'decimal:2',
        'date_depense' => 'date',
    ];

    /**
     * Get the category budget that owns the expense.
     */
    public function categoryBudget()
    {
        return $this->belongsTo(CategoryBudget::class);
    }

    /**
     * Get the budget through the category budget.
     */
    public function budget()
    {
        return $this->hasOneThrough(
            Budget::class,
            CategoryBudget::class,
            'id', // Foreign key on the category_budget table
            'id', // Foreign key on the budgets table
            'category_budget_id', // Local key on the expenses table
            'budget_id' // Local key on the category_budget table
        );
    }

    /**
     * Get the category through the category budget.
     */
    public function category()
    {
        return $this->hasOneThrough(
            Category::class,
            CategoryBudget::class,
            'id', // Foreign key on the category_budget table
            'id', // Foreign key on the categories table
            'category_budget_id', // Local key on the expenses table
            'category_id' // Local key on the category_budget table
        );
    }
}