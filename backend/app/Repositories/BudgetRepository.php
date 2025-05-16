<?php

namespace App\Repositories;

use App\Models\Budget;
use Illuminate\Database\Eloquent\Collection;
use Carbon\Carbon;

class BudgetRepository extends BaseRepository
{
    /**
     * BudgetRepository constructor.
     *
     * @param Budget $model
     */
    public function __construct(Budget $model)
    {
        parent::__construct($model);
    }

    /**
     * Récupérer tous les budgets pour un utilisateur.
     *
     * @param int $userId
     * @param array $filter
     * @return Collection
     */
    public function getAllForUser(int $userId, array $filter = []): Collection
    {
        $query = $this->model->where('utilisateur_id', $userId);

        // Filtrer par période
        if (isset($filter['date_debut']) && isset($filter['date_fin'])) {
            $query->where(function ($q) use ($filter) {
                $q->whereBetween('date_debut', [$filter['date_debut'], $filter['date_fin']])
                  ->orWhereBetween('date_fin', [$filter['date_debut'], $filter['date_fin']]);
            });
        }

        // Filtrer par nom
        if (isset($filter['nom'])) {
            $query->where('nom', 'like', '%' . $filter['nom'] . '%');
        }

        return $query->with('categories')->orderBy('date_debut', 'desc')->get();
    }

    /**
     * Récupérer le budget actuel d'un utilisateur.
     *
     * @param int $userId
     * @return Budget|null
     */
    public function getBudgetActuel(int $userId): ?Budget
    {
        $now = Carbon::now()->format('Y-m-d');
        
        return $this->model->where('utilisateur_id', $userId)
            ->where('date_debut', '<=', $now)
            ->where('date_fin', '>=', $now)
            ->with(['categories', 'categories.depenses'])
            ->first();
    }

    /**
     * Récupérer les budgets pour une période donnée.
     *
     * @param int $userId
     * @param string $dateDebut
     * @param string $dateFin
     * @return Collection
     */
    public function getBudgetsPourPeriode(int $userId, string $dateDebut, string $dateFin): Collection
    {
        return $this->model->where('utilisateur_id', $userId)
            ->where(function ($query) use ($dateDebut, $dateFin) {
                $query->whereBetween('date_debut', [$dateDebut, $dateFin])
                      ->orWhereBetween('date_fin', [$dateDebut, $dateFin])
                      ->orWhere(function ($q) use ($dateDebut, $dateFin) {
                          $q->where('date_debut', '<=', $dateDebut)
                            ->where('date_fin', '>=', $dateFin);
                      });
            })
            ->with(['categories', 'categories.depenses'])
            ->get();
    }

    /**
     * Calculer le total des budgets pour un utilisateur.
     *
     * @param int $userId
     * @param string|null $dateDebut
     * @param string|null $dateFin
     * @return float
     */
    public function calculerTotalBudgets(int $userId, ?string $dateDebut = null, ?string $dateFin = null): float
    {
        $query = $this->model->where('utilisateur_id', $userId);
        
        if ($dateDebut && $dateFin) {
            $query->where(function ($q) use ($dateDebut, $dateFin) {
                $q->whereBetween('date_debut', [$dateDebut, $dateFin])
                  ->orWhereBetween('date_fin', [$dateDebut, $dateFin]);
            });
        }
        
        return $query->sum('montant_total');
    }

    /**
     * Récupérer les budgets pour une catégorie spécifique.
     *
     * @param int $userId
     * @param int $categorieId
     * @return Collection
     */
    public function getBudgetsPourCategorie(int $userId, int $categorieId): Collection
    {
        return $this->model->where('utilisateur_id', $userId)
            ->whereHas('categories', function ($query) use ($categorieId) {
                $query->where('categorie_id', $categorieId);
            })
            ->with(['categories' => function ($query) use ($categorieId) {
                $query->where('categorie_id', $categorieId);
            }])
            ->get();
    }
}