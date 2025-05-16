<?php

namespace App\Repositories;

use App\Models\Categorie;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class CategorieRepository extends BaseRepository
{
    /**
     * CategorieRepository constructor.
     *
     * @param Categorie $model
     */
    public function __construct(Categorie $model)
    {
        parent::__construct($model);
    }

    /**
     * Récupérer toutes les catégories (globales et de l'utilisateur).
     *
     * @param int|null $userId
     * @return Collection
     */
    public function getAll(?int $userId = null): Collection
    {
        $query = $this->model->where('est_global', true);
        
        if ($userId) {
            $query->orWhere(function ($q) use ($userId) {
                $q->where('est_global', false)
                  ->where('utilisateur_id', $userId);
            });
        }
        
        return $query->orderBy('nom')->get();
    }

    /**
     * Récupérer les catégories d'un utilisateur spécifique.
     *
     * @param int $userId
     * @return Collection
     */
    public function getAllForUser(int $userId): Collection
    {
        return $this->model->where('utilisateur_id', $userId)
            ->where('est_global', false)
            ->orderBy('nom')
            ->get();
    }

    /**
     * Vérifier si une catégorie est utilisée.
     *
     * @param int $categorieId
     * @return bool
     */
    public function estUtilisee(int $categorieId): bool
    {
        // Vérifier si la catégorie est utilisée dans un budget
        $utiliseeDansBudget = DB::table('categorie_budget')
            ->where('categorie_id', $categorieId)
            ->exists();

        // Vérifier si la catégorie est utilisée dans un objectif
        $utiliseeDansObjectif = DB::table('objectifs')
            ->where('categorie_id', $categorieId)
            ->exists();

        return $utiliseeDansBudget || $utiliseeDansObjectif;
    }

    /**
     * Récupérer les catégories les plus utilisées par un utilisateur.
     *
     * @param int $userId
     * @param int $limit
     * @return Collection
     */
    public function getMostUsedByUser(int $userId, int $limit = 5): Collection
    {
        return $this->model
            ->select('categories.*', DB::raw('COUNT(depenses.id) as nb_depenses'))
            ->leftJoin('categorie_budget', 'categories.id', '=', 'categorie_budget.categorie_id')
            ->leftJoin('budgets', 'categorie_budget.budget_id', '=', 'budgets.id')
            ->leftJoin('depenses', 'categorie_budget.id', '=', 'depenses.categorie_budget_id')
            ->where('budgets.utilisateur_id', $userId)
            ->groupBy('categories.id')
            ->orderBy('nb_depenses', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * Récupérer les statistiques d'utilisation des catégories.
     *
     * @param int $userId
     * @param string|null $dateDebut
     * @param string|null $dateFin
     * @return array
     */
    public function getStatistiquesUtilisation(int $userId, ?string $dateDebut = null, ?string $dateFin = null): array
    {
        $query = $this->model
            ->select(
                'categories.id',
                'categories.nom',
                'categories.icone',
                DB::raw('SUM(depenses.montant) as total_depenses'),
                DB::raw('COUNT(depenses.id) as nb_depenses')
            )
            ->leftJoin('categorie_budget', 'categories.id', '=', 'categorie_budget.categorie_id')
            ->leftJoin('budgets', 'categorie_budget.budget_id', '=', 'budgets.id')
            ->leftJoin('depenses', 'categorie_budget.id', '=', 'depenses.categorie_budget_id')
            ->where('budgets.utilisateur_id', $userId)
            ->groupBy('categories.id');
        
        if ($dateDebut && $dateFin) {
            $query->whereBetween('depenses.date_depense', [$dateDebut, $dateFin]);
        }
        
        return $query->get()->toArray();
    }
}