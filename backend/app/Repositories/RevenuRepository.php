<?php

namespace App\Repositories;

use App\Models\Revenu;
use Illuminate\Database\Eloquent\Collection;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class RevenuRepository extends BaseRepository
{
    /**
     * RevenuRepository constructor.
     *
     * @param Revenu $model
     */
    public function __construct(Revenu $model)
    {
        parent::__construct($model);
    }

    /**
     * Récupère tous les revenus d'un utilisateur avec filtres.
     *
     * @param int $userId
     * @param array $filtres
     * @return Collection
     */
    public function getAllForUser(int $userId, array $filtres = []): Collection
    {
        $query = $this->model->where('utilisateur_id', $userId);
        
        // Filtre par compte
        if (isset($filtres['compte_id'])) {
            $query->where('compte_id', $filtres['compte_id']);
        }
        
        // Filtre par date
        if (isset($filtres['date_debut'])) {
            $query->where('date_perception', '>=', $filtres['date_debut']);
        }
        
        if (isset($filtres['date_fin'])) {
            $query->where('date_perception', '<=', $filtres['date_fin']);
        }
        
        // Filtre par périodicité
        if (isset($filtres['periodicite'])) {
            $query->where('periodicite', $filtres['periodicite']);
        }
        
        return $query->with('compte')
            ->orderBy('date_perception', 'desc')
            ->get();
    }

    /**
     * Récupère les revenus pour une période donnée.
     *
     * @param int $userId
     * @param string $dateDebut
     * @param string $dateFin
     * @return Collection
     */
    public function getRevenusPourPeriode(int $userId, string $dateDebut, string $dateFin): Collection
    {
        return $this->model->where('utilisateur_id', $userId)
            ->whereBetween('date_perception', [$dateDebut, $dateFin])
            ->with('compte')
            ->get();
    }

    /**
     * Calcule le total des revenus pour un utilisateur pendant une période.
     *
     * @param int $userId
     * @param string $dateDebut
     * @param string $dateFin
     * @return float
     */
    public function getTotalForPeriod(int $userId, string $dateDebut, string $dateFin): float
    {
        return $this->model->where('utilisateur_id', $userId)
            ->whereBetween('date_perception', [$dateDebut, $dateFin])
            ->sum('montant');
    }

    /**
     * Récupère les statistiques des revenus.
     *
     * @param int $userId
     * @param int $periode
     * @return array
     */
    public function getStatistiques(int $userId, int $periode = 12): array
    {
        $result = [];
        $now = Carbon::now();
        
        for ($i = 0; $i < $periode; $i++) {
            $dateDebut = (clone $now)->subMonths($i)->startOfMonth();
            $dateFin = (clone $now)->subMonths($i)->endOfMonth();
            
            $revenus = $this->model->where('utilisateur_id', $userId)
                ->whereBetween('date_perception', [$dateDebut, $dateFin])
                ->sum('montant');
                
            $result[] = [
                'mois' => $dateDebut->format('Y-m'),
                'total' => $revenus
            ];
        }
        
        return array_reverse($result);
    }

    /**
     * Récupère la prévision des revenus pour les prochains mois.
     *
     * @param int $userId
     * @param int $mois
     * @return array
     */
    public function getPrevisions(int $userId, int $mois = 3): array
    {
        $result = [];
        $now = Carbon::now();
        
        // Récupérer les revenus récurrents
        $revenus = $this->model->where('utilisateur_id', $userId)
            ->whereIn('periodicite', ['mensuel', 'bimensuel', 'hebdomadaire'])
            ->get();
            
        for ($i = 0; $i < $mois; $i++) {
            $moisCourant = (clone $now)->addMonths($i);
            $total = 0;
            
            foreach ($revenus as $revenu) {
                if ($revenu->periodicite === 'mensuel') {
                    $total += $revenu->montant;
                } else if ($revenu->periodicite === 'bimensuel') {
                    $total += $revenu->montant * 2;
                } else if ($revenu->periodicite === 'hebdomadaire') {
                    // Approximativement 4.33 semaines par mois
                    $total += $revenu->montant * 4.33;
                }
            }
            
            $result[] = [
                'mois' => $moisCourant->format('Y-m'),
                'total' => $total
            ];
        }
        
        return $result;
    }

    /**
     * Clone un revenu récurrent pour le mois suivant.
     *
     * @param Revenu $revenu
     * @return Revenu
     */
    public function cloner(Revenu $revenu): Revenu
    {
        $nouveauRevenu = $revenu->replicate();
        
        if ($revenu->periodicite === 'mensuel') {
            $nouveauRevenu->date_perception = Carbon::parse($revenu->date_perception)->addMonth();
        } else if ($revenu->periodicite === 'hebdomadaire') {
            $nouveauRevenu->date_perception = Carbon::parse($revenu->date_perception)->addWeek();
        }
        
        $nouveauRevenu->save();
        
        return $nouveauRevenu;
    }
}