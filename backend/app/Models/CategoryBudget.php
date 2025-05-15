<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CategoryBudget extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'category_budget';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'budget_id',
        'category_id',
        'montant_alloue',
        'pourcentage',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'montant_alloue' => 'decimal:2',
        'pourcentage' => 'decimal:2',
    ];

    /**
     * Get the budget that owns the category budget.
     */
    public function budget()
    {
        return $this->belongsTo(Budget::class);
    }

    /**
     * Get the category that owns the category budget.
     */
    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    /**
     * Get the expenses for the category budget.
     */
    public function expenses()
    {
        return $this->hasMany(Expense::class);
    }

    /**
     * Get the alerts for the category budget.
     */
    public function alerts()
    {
        return $this->hasMany(Alert::class);
    }

    /**
     * Get the total amount spent for this category budget.
     */
    public function getTotalSpentAttribute()
    {
        return $this->expenses()->sum('montant');
    }

    /**
     * Get the remaining amount for this category budget.
     */
    public function getRemainingAmountAttribute()
    {
        return $this->montant_alloue - $this->total_spent;
    }

    /**
     * Get the percentage spent for this category budget.
     */
    public function getPercentageSpentAttribute()
    {
        if ($this->montant_alloue <= 0) {
            return 0;
        }
        
        return ($this->total_spent / $this->montant_alloue) * 100;
    }
}