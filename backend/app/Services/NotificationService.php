<?php

namespace App\Services;

use App\Models\Alerte;
use App\Models\CategorieBudget;
use App\Repositories\AlerteRepository;
use App\Repositories\CategorieBudgetRepository;
use App\Repositories\DepenseRepository;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Log;
use Exception;

class NotificationService
{
    protected $alerteRepository;
    protected $categorieBudgetRepository;
    protected $depenseRepository;

    /**
     * NotificationService constructor.
     *
     * @param AlerteRepository $alerteRepository
     * @param CategorieBudgetRepository $categorieBudgetRepository
     * @param DepenseRepository $depenseRepository
     */
    public function __construct(
        AlerteRepository $alerteRepository,
        CategorieBudgetRepository $categorieBudgetRepository,
        DepenseRepository $depenseRepository
    ) {
        $this->alerteRepository = $alerteRepository;
        $this->categorieBudgetRepository = $categorieBudgetRepository;
        $this->depenseRepository = $depenseRepository;
    }

    /**
     * Vérifie si des alertes doivent être déclenchées pour une catégorie de budget.
     *
     * @param int $categorieBudgetId
     * @return array
     */
    public function verifierAlertes(int $categorieBudgetId): array
    {
        $categorieBudget = $this->categorieBudgetRepository->findOrFail($categorieBudgetId);
        $montantDepense = $this->calculerMontantDepense($categorieBudgetId);
        
        $alertes = $this->alerteRepository->getAlertesADeclencher(
            $categorieBudgetId,
            $montantDepense,
            $categorieBudget->montant_alloue
        );
        
        foreach ($alertes as $alerte) {
            $this->envoyerNotification($alerte, $categorieBudget, $montantDepense);
        }
        
        return $alertes->toArray();
    }

    /**
     * Récupère les alertes actives pour un utilisateur.
     *
     * @param int $userId
     * @return Collection
     */
    public function getAlertesActives(int $userId): Collection
    {
        return $this->alerteRepository->getActiveForUser($userId);
    }

    /**
     * Marque une alerte comme lue.
     *
     * @param Alerte $alerte
     * @return bool
     */
    public function marquerAlerteLue(Alerte $alerte): bool
    {
        $alerte->active = false;
        return $alerte->save();
    }

    /**
     * Calcule le montant total dépensé pour une catégorie de budget.
     *
     * @param int $categorieBudgetId
     * @return float
     */
    protected function calculerMontantDepense(int $categorieBudgetId): float
    {
        $depenses = $this->depenseRepository->findWhere(['categorie_budget_id' => $categorieBudgetId]);
        return $depenses->sum('montant');
    }

    /**
     * Envoie une notification à l'utilisateur.
     *
     * @param Alerte $alerte
     * @param CategorieBudget $categorieBudget
     * @param float $montantDepense
     * @return bool
     */
    protected function envoyerNotification(Alerte $alerte, CategorieBudget $categorieBudget, float $montantDepense): bool
    {
        try {
            // Charger les relations nécessaires
            $categorieBudget->load(['categorie', 'budget', 'budget.user']);
            $utilisateur = $categorieBudget->budget->user;
            
            if (!$utilisateur) {
                Log::error('Utilisateur non trouvé pour l\'alerte #' . $alerte->id);
                return false;
            }
            
            // Préparer le message
            $message = $this->preparerMessageAlerte($alerte, $categorieBudget, $montantDepense);
            
            // Envoyer selon les préférences de l'utilisateur
            $preferences = $utilisateur->preferences_notification ?? ['app' => true];
            
            if (isset($preferences['app']) && $preferences['app']) {
                // Enregistrer dans la table des notifications de l'application
                // Cette partie dépend de l'implémentation spécifique
                Log::info('Notification app envoyée: ' . $message);
            }
            
            if (isset($preferences['email']) && $preferences['email']) {
                // Envoyer un email
                // Cette partie dépend de l'implémentation spécifique
                Log::info('Notification email envoyée: ' . $message);
            }
            
            return true;
        } catch (Exception $e) {
            Log::error('Erreur lors de l\'envoi de la notification: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Prépare le message de l'alerte.
     *
     * @param Alerte $alerte
     * @param CategorieBudget $categorieBudget
     * @param float $montantDepense
     * @return string
     */
    protected function preparerMessageAlerte(Alerte $alerte, CategorieBudget $categorieBudget, float $montantDepense): string
    {
        $pourcentage = ($montantDepense / $categorieBudget->montant_alloue) * 100;
        $reste = $categorieBudget->montant_alloue - $montantDepense;
        
        if ($alerte->type === 'pourcentage') {
            return "Alerte: Vous avez dépensé " . number_format($pourcentage, 2) . "% de votre budget " .
                   $categorieBudget->categorie->nom . " (" . number_format($montantDepense, 2) . " € sur " .
                   number_format($categorieBudget->montant_alloue, 2) . " €). Il vous reste " . number_format($reste, 2) . " €.";
        } else {
            return "Alerte: Vos dépenses pour la catégorie " . $categorieBudget->categorie->nom .
                   " ont atteint " . number_format($montantDepense, 2) . " €, dépassant le seuil d'alerte de " .
                   number_format($alerte->seuil, 2) . " €.";
        }
    }

    /**
     * Vérifie si une catégorie budget appartient à l'utilisateur.
     *
     * @param int $categorieBudgetId
     * @param int $userId
     * @return bool
     * @throws Exception
     */
    public function verifierProprieteCategoriesBudget(int $categorieBudgetId, int $userId): bool
    {
        $categorieBudget = $this->categorieBudgetRepository->findOrFail($categorieBudgetId);
        $categorieBudget->load('budget');
        
        if ($categorieBudget->budget->utilisateur_id !== $userId) {
            throw new Exception('Cette catégorie de budget ne vous appartient pas.', 403);
        }
        
        return true;
    }
}