<?php

namespace App\Repositories;

use App\Models\Alerte;
use Illuminate\Database\Eloquent\Collection;

class AlerteRepository extends BaseRepository
{
    /**
     * AlerteRepository constructor.
     *
     * @param Alerte $model
     */
    public function __construct(Alerte $model)
    {
        parent::__construct($model);
    }

    /**
     * Récupérer toutes les alertes pour un utilisateur.
     *
     * @param int $userId
     * @return Collection
     */
    public function getAllForUser(int $userId): Collection
    {
        return $this->model->whereHas('categorieBudget.budget', function ($query) use ($userId) {
            $query->where('utilisateur_id', $userId);
        })->with(['categorieBudget.categorie', 'categorieBudget.budget'])->get();
    }

    /**
     * Récupérer les alertes actives pour un utilisateur.
     *
     * @param int $userId
     * @return Collection
     */
    public function getActiveForUser(int $userId): Collection
    {
        return $this->model->where('active', true)
            ->whereHas('categorieBudget.budget', function ($query) use ($userId) {
                $query->where('utilisateur_id', $userId);
            })
            ->with(['categorieBudget.categorie', 'categorieBudget.budget'])
            ->get();
    }

    /**
     * Récupérer les alertes pour une catégorie de budget.
     *
     * @param int $categorieBudgetId
     * @return Collection
     */
    public function getForCategorieBudget(int $categorieBudgetId): Collection
    {
        return $this->model->where('categorie_budget_id', $categorieBudgetId)
            ->where('active', true)
            ->get();
    }

    /**
     * Désactiver une alerte.
     *
     * @param Alerte $alerte
     * @return Alerte
     */
    public function desactiver(Alerte $alerte): Alerte
    {
        return $this->update($alerte, ['active' => false]);
    }

    /**
     * Créer des alertes par défaut pour une catégorie de budget.
     *
     * @param int $categorieBudgetId
     * @param float $montantAlloue
     * @return void
     */
    public function creerAlertesParDefaut(int $categorieBudgetId, float $montantAlloue): void
    {
        // Alerte à 75% du budget
        $this->create([
            'categorie_budget_id' => $categorieBudgetId,
            'type' => 'pourcentage',
            'seuil' => 75,
            'active' => true
        ]);

        // Alerte à 90% du budget
        $this->create([
            'categorie_budget_id' => $categorieBudgetId,
            'type' => 'pourcentage',
            'seuil' => 90,
            'active' => true
        ]);
    }
}