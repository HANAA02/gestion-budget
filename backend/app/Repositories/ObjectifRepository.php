<?php

namespace App\Repositories;

use App\Models\Objectif;
use Illuminate\Database\Eloquent\Collection;
use Carbon\Carbon;

class ObjectifRepository extends BaseRepository
{
    /**
     * ObjectifRepository constructor.
     *
     * @param Objectif $model
     */
    public function __construct(Objectif $model)
    {
        parent::__construct($model);
    }

    /**
     * Récupérer tous les objectifs d'un utilisateur.
     *
     * @param int $userId
     * @param array $filtres
     * @return Collection
     */
    public function getAllForUser(int $userId, array $filtres = []): Collection
    {
        $query = $this->model->where('utilisateur_id', $userId);
        
        // Filtre par catégorie
        if (isset($filtres['categorie_id'])) {
            $query->where('categorie_id', $filtres['categorie_id']);
        }
        
        // Filtre par statut
        if (isset($filtres['statut'])) {
            $query->where('statut', $filtres['statut']);
        }
        
        // Filtre par date de fin
        if (isset($filtres['date_fin'])) {
            $query->where('date_fin', '<=', $filtres['date_fin']);
        }
        
        return $query->with('categorie')->orderBy('date_fin')->get();
    }

    /**
     * Récupérer les objectifs actifs.
     *
     * @param int $userId
     * @return Collection
     */
    public function getObjectifsActifs(int $userId): Collection
    {
        $now = Carbon::now()->format('Y-m-d');
        
        return $this->model->where('utilisateur_id', $userId)
            ->where('statut', 'en cours')
            ->where('date_debut', '<=', $now)
            ->where('date_fin', '>=', $now)
            ->with('categorie')
            ->get();
    }

    /**
     * Récupérer les objectifs par catégorie.
     *
     * @param int $userId
     * @param int $categorieId
     * @return Collection
     */
    public function getParCategorie(int $userId, int $categorieId): Collection
    {
        return $this->model->where('utilisateur_id', $userId)
            ->where('categorie_id', $categorieId)
            ->with('categorie')
            ->orderBy('date_fin')
            ->get();
    }

    /**
     * Mettre à jour le statut d'un objectif.
     *
     * @param Objectif $objectif
     * @param string $statut
     * @return Objectif
     */
    public function updateStatut(Objectif $objectif, string $statut): Objectif
    {
        return $this->update($objectif, ['statut' => $statut]);
    }

    /**
     * Récupérer les objectifs à venir.
     *
     * @param int $userId
     * @param int $jours
     * @return Collection
     */
    public function getObjectifsAVenir(int $userId, int $jours = 30): Collection
    {
        $now = Carbon::now();
        $futur = $now->copy()->addDays($jours)->format('Y-m-d');
        
        return $this->model->where('utilisateur_id', $userId)
            ->where('statut', 'en cours')
            ->whereBetween('date_fin', [$now->format('Y-m-d'), $futur])
            ->with('categorie')
            ->orderBy('date_fin')
            ->get();
    }

    /**
     * Récupérer les objectifs expirés.
     *
     * @param int $userId
     * @return Collection
     */
    public function getObjectifsExpires(int $userId): Collection
    {
        $now = Carbon::now()->format('Y-m-d');
        
        return $this->model->where('utilisateur_id', $userId)
            ->where('statut', 'en cours')
            ->where('date_fin', '<', $now)
            ->with('categorie')
            ->orderBy('date_fin', 'desc')
            ->get();
    }
}