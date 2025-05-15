<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Goal extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'utilisateur_id',
        'category_id',
        'titre',
        'description',
        'montant_cible',
        'date_debut',
        'date_fin',
        'statut',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'montant_cible' => 'decimal:2',
        'date_debut' => 'date',
        'date_fin' => 'date',
    ];

    /**
     * Get the user that owns the goal.
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'utilisateur_id');
    }

    /**
     * Get the category that owns the goal.
     */
    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    /**
     * Calculate progress percentage for the goal.
     */
    public function getProgressPercentageAttribute()
    {
        // Cette méthode devrait être implémentée selon la logique de votre application
        // Par exemple, en fonction des dépenses économisées pour ce but
        
        // Exemple simple:
        $totalDays = $this->date_debut->diffInDays($this->date_fin);
        $daysElapsed = $this->date_debut->diffInDays(now());
        
        if ($totalDays <= 0) {
            return 0;
        }
        
        $percentage = min(100, max(0, ($daysElapsed / $totalDays) * 100));
        
        return round($percentage, 2);
    }
}