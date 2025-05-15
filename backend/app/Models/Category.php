<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Category extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'nom',
        'description',
        'icone',
        'pourcentage_defaut',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'pourcentage_defaut' => 'decimal:2',
    ];

    /**
     * Get the budgets for the category.
     */
    public function budgets()
    {
        return $this->belongsToMany(Budget::class, 'category_budget')
                    ->withPivot('montant_alloue', 'pourcentage')
                    ->withTimestamps();
    }

    /**
     * Get the category budget records for the category.
     */
    public function categoryBudgets()
    {
        return $this->hasMany(CategoryBudget::class);
    }

    /**
     * Get the goals for the category.
     */
    public function goals()
    {
        return $this->hasMany(Goal::class);
    }
}