<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Alert extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'category_budget_id',
        'type',
        'seuil',
        'active',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'seuil' => 'decimal:2',
        'active' => 'boolean',
    ];

    /**
     * Get the category budget that owns the alert.
     */
    public function categoryBudget()
    {
        return $this->belongsTo(CategoryBudget::class);
    }

    /**
     * Check if the alert is triggered.
     */
    public function isTriggered()
    {
        if (!$this->active) {
            return false;
        }

        $categoryBudget = $this->categoryBudget;
        
        if (!$categoryBudget) {
            return false;
        }

        $totalSpent = $categoryBudget->totalSpent;
        $montantAlloue = $categoryBudget->montant_alloue;
        
        switch ($this->type) {
            case 'pourcentage':
                $spentPercentage = ($totalSpent / $montantAlloue) * 100;
                return $spentPercentage >= $this->seuil;
                
            case 'montant':
                return $totalSpent >= $this->seuil;
                
            case 'reste':
                $remaining = $montantAlloue - $totalSpent;
                return $remaining <= $this->seuil;
                
            default:
                return false;
        }
    }
}