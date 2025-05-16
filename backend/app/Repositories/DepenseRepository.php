<?php

namespace App\Repositories;

use App\Models\Depense;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class DepenseRepository extends BaseRepository
{
    /**
     * DepenseRepository constructor.
     *
     * @param Depense $model
     */
    public function __construct(Depense $model)
    {
        parent::__construct($model);
    }

    /**
     * Récupérer toutes les dépenses d'un utilisateur.
     *
     * @param int $userId
     * @param array $filtres
     * @return Collection
     */
    public function getAllForUser(int $userId, array $filtres = []): Collection
    {
        $query = $this->model->whereHas('categorieBudget.budget', function ($query) use ($userId) {
            $query->where('utilisateur_id', $userId);
        })->with(['categorieBudget.categorie', 'categorieBudget.budget']);
        
        // Filtre par compte
        if (isset($filtres['compte_id'])) {
            $query->where('compte_id', $filtres['compte_id']);
        }
        
        // Filtre par catégorie
        if (isset($filtres['categorie_id'])) {
            $query->whereHas('categorieBudget', function ($query) use ($filtres) {
                $query->where('categorie_id', $filtres['categorie_id']);
            });
        }
        
        // Filtre par date
        if (isset($filtres['date_debut']) && isset($filtres['date_fin'])) {
            $query->whereBetween('date_depense', [$filtres['date_debut'], $filtres['date_fin']]);
        }
        
        // Filtre par montant
        if (isset($filtres['montant_min']) && isset($filtres['montant_max'])) {
            $query->whereBetween('montant', [$filtres['montant_min'], $filtres['montant_max']]);
        }
        
        // Filtre par statut
        if (isset($filtres['statut'])) {
            $query->where('statut', $filtres['statut']);
        }
        
        // Filtre par description
        if (isset($filtres['recherche'])) {
            $query->where('description', 'like', '%' . $filtres['recherche'] . '%');
        }
        
        return $query->orderBy('date_depense', 'desc')->get();
    }

    /**
     * Récupérer les dépenses pour une catégorie budget.
     *
     * @param int $categorieBudgetId
     * @return Collection
     */
    public function getForCategorieBudget(int $categorieBudgetId): Collection
    {
        return $this->model->where('categorie_budget_id', $categorieBudgetId)
            ->orderBy('date_depense', 'desc')
            ->get();
    }

    /**
     * Récupérer les dépenses par catégorie.
     *
     * @param int $userId
     * @param string|null $dateDebut
     * @param string|null $dateFin
     * @return array
     */
    public function getByCategorie(int $userId, ?string $dateDebut = null, ?string $dateFin = null): array
    {
        $query = DB::table('depenses')
            ->select(
                'categories.id',
                'categories.nom',
                'categories.icone',
                DB::raw('SUM(depenses.montant) as total')
            )
            ->join('categorie_budget', 'depenses.categorie_budget_id', '=', 'categorie_budget.id')
            ->join('categories', 'categorie_budget.categorie_id', '=', 'categories.id')
            ->join('budgets', 'categorie_budget.budget_id', '=', 'budgets.id')
            ->where('budgets.utilisateur_id', $userId);
        
        if ($dateDebut && $dateFin) {
            $query->whereBetween('depenses.date_depense', [$dateDebut, $dateFin]);
        }
        
        return $query->groupBy('categories.id')
            ->orderBy('total', 'desc')
            ->get()
            ->toArray();
    }

    /**
     * Calculer le total des dépenses pour une période donnée.
     *
     * @param int $userId
     * @param string|null $dateDebut
     * @param string|null $dateFin
     * @return float
     */
    public function calculerTotalPeriode(int $userId, ?string $dateDebut = null, ?string $dateFin = null): float
    {
        $query = $this->model->whereHas('categorieBudget.budget', function ($query) use ($userId) {
            $query->where('utilisateur_id', $userId);
        });
        
        if ($dateDebut && $dateFin) {
            $query->whereBetween('date_depense', [$dateDebut, $dateFin]);
        }
        
        return $query->sum('montant');
    }

    /**
     * Récupérer les dépenses récentes.
     *
     * @param int $userId
     * @param int $limit
     * @return Collection
     */
    public function getRecentes(int $userId, int $limit = 5): Collection
    {
        return $this->model->whereHas('categorieBudget.budget', function ($query) use ($userId) {
            $query->where('utilisateur_id', $userId);
        })
        ->with(['categorieBudget.categorie'])
        ->orderBy('date_depense', 'desc')
        ->limit($limit)
        ->get();
    }

    /**
     * Récupérer les dépenses par jour pour une période donnée.
     *
     * @param int $userId
     * @param string $dateDebut
     * @param string $dateFin
     * @return array
     */
    public function getParJour(int $userId, string $dateDebut, string $dateFin): array
    {
        return DB::table('depenses')
            ->select(
                DB::raw('DATE(date_depense) as date'),
                DB::raw('SUM(montant) as total')
            )
            ->join('categorie_budget', 'depenses.categorie_budget_id', '=', 'categorie_budget.id')
            ->join('budgets', 'categorie_budget.budget_id', '=', 'budgets.id')
            ->where('budgets.utilisateur_id', $userId)
            ->whereBetween('date_depense', [$dateDebut, $dateFin])
            ->groupBy(DB::raw('DATE(date_depense)'))
            ->orderBy('date')
            ->get()
            ->toArray();
    }
}