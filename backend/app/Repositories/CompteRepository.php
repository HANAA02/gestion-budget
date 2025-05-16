<?php

namespace App\Repositories;

use App\Models\Compte;
use Illuminate\Database\Eloquent\Collection;

class CompteRepository extends BaseRepository
{
    /**
     * CompteRepository constructor.
     *
     * @param Compte $model
     */
    public function __construct(Compte $model)
    {
        parent::__construct($model);
    }

    /**
     * Récupérer tous les comptes d'un utilisateur.
     *
     * @param int $userId
     * @return Collection
     */
    public function getAllForUser(int $userId): Collection
    {
        return $this->model->where('utilisateur_id', $userId)
            ->orderBy('nom')
            ->get();
    }

    /**
     * Mettre à jour le solde d'un compte.
     *
     * @param Compte $compte
     * @param float $montant
     * @param string $type
     * @return Compte
     */
    public function updateSolde(Compte $compte, float $montant, string $type = 'credit'): Compte
    {
        if ($type === 'credit') {
            $compte->solde += $montant;
        } else {
            $compte->solde -= $montant;
        }
        
        $compte->save();
        return $compte;
    }

    /**
     * Récupérer le solde total des comptes d'un utilisateur.
     *
     * @param int $userId
     * @return float
     */
    public function getSoldeTotal(int $userId): float
    {
        return $this->model->where('utilisateur_id', $userId)
            ->sum('solde');
    }

    /**
     * Vérifier si un compte peut être supprimé.
     *
     * @param Compte $compte
     * @return bool
     */
    public function peutEtreSupprimer(Compte $compte): bool
    {
        // Vérifier si des revenus sont associés au compte
        $hasRevenus = $compte->revenus()->exists();
        
        // Vérifier si des dépenses sont associées au compte
        $hasDepenses = $compte->depenses()->exists();
        
        return !$hasRevenus && !$hasDepenses;
    }

    /**
     * Récupérer les mouvements d'un compte.
     *
     * @param int $compteId
     * @param string|null $dateDebut
     * @param string|null $dateFin
     * @return array
     */
    public function getMouvements(int $compteId, ?string $dateDebut = null, ?string $dateFin = null): array
    {
        $compte = $this->findOrFail($compteId);
        
        // Récupérer les revenus
        $revenus = $compte->revenus();
        if ($dateDebut && $dateFin) {
            $revenus->whereBetween('date_perception', [$dateDebut, $dateFin]);
        }
        $revenus = $revenus->get()->map(function ($revenu) {
            return [
                'date' => $revenu->date_perception,
                'description' => $revenu->source,
                'montant' => $revenu->montant,
                'type' => 'revenu'
            ];
        });
        
        // Récupérer les dépenses
        $depenses = $compte->depenses();
        if ($dateDebut && $dateFin) {
            $depenses->whereBetween('date_depense', [$dateDebut, $dateFin]);
        }
        $depenses = $depenses->get()->map(function ($depense) {
            return [
                'date' => $depense->date_depense,
                'description' => $depense->description,
                'montant' => -$depense->montant,
                'type' => 'depense'
            ];
        });
        
        // Combiner et trier par date
        $mouvements = $revenus->concat($depenses)->sortByDesc('date')->values()->toArray();
        
        return $mouvements;
    }
}