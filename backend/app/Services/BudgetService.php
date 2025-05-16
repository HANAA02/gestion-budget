<?php

namespace App\Services;

use App\Models\Budget;
use App\Models\Categorie;
use App\Models\CategorieBudget;
use App\Repositories\BudgetRepository;
use App\Repositories\CategorieRepository;
use App\Repositories\CategorieBudgetRepository;
use App\Repositories\CompteRepository;
use App\Repositories\DepenseRepository;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Exception;

class BudgetService
{
    protected $budgetRepository;
    protected $categorieRepository;
    protected $categorieBudgetRepository;
    protected $compteRepository;
    protected $depenseRepository;

    /**
     * BudgetService constructor.
     *
     * @param BudgetRepository $budgetRepository
     * @param CategorieRepository $categorieRepository
     * @param CategorieBudgetRepository $categorieBudgetRepository
     * @param CompteRepository $compteRepository
     * @param DepenseRepository $depenseRepository
     */
    public function __construct(
        BudgetRepository $budgetRepository,
        CategorieRepository $categorieRepository,
        CategorieBudgetRepository $categorieBudgetRepository,
        CompteRepository $compteRepository,
        DepenseRepository $depenseRepository
    ) {
        $this->budgetRepository = $budgetRepository;
        $this->categorieRepository = $categorieRepository;
        $this->categorieBudgetRepository = $categorieBudgetRepository;
        $this->compteRepository = $compteRepository;
        $this->depenseRepository = $depenseRepository;
    }

    /**
     * Crée un nouveau budget avec ses catégories.
     *
     * @param array $budgetData
     * @param array $categoriesData
     * @return Budget
     */
    public function creerBudget(array $budgetData, array $categoriesData = []): Budget
    {
        DB::beginTransaction();
        
        try {
            // Créer le budget
            $budget = $this->budgetRepository->create($budgetData);
            
            // Si aucune catégorie n'est spécifiée, utiliser les catégories par défaut
            if (empty($categoriesData)) {
                $categoriesData = $this->getCategoriesParDefaut($budget->montant_total);
            }
            
            // Créer les relations catégorie-budget
            foreach ($categoriesData as $categorieData) {
                $this->ajouterCategorieBudget(
                    $budget->id,
                    $categorieData['categorie_id'],
                    $categorieData['montant_alloue'],
                    $categorieData['pourcentage']
                );
            }
            
            DB::commit();
            
            return $budget->fresh()->load('categories.categorie');
        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Met à jour un budget existant avec ses catégories.
     *
     * @param Budget $budget
     * @param array $budgetData
     * @param array $categoriesData
     * @return Budget
     */
    public function mettreAJourBudget(Budget $budget, array $budgetData, array $categoriesData = []): Budget
    {
        DB::beginTransaction();
        
        try {
            // Mettre à jour le budget
            $budget = $this->budgetRepository->update($budget, $budgetData);
            
            if (!empty($categoriesData)) {
                // Supprimer les catégories existantes si de nouvelles sont fournies
                $budget->categories()->delete();
                
                // Ajouter les nouvelles catégories
                foreach ($categoriesData as $categorieData) {
                    $this->ajouterCategorieBudget(
                        $budget->id,
                        $categorieData['categorie_id'],
                        $categorieData['montant_alloue'],
                        $categorieData['pourcentage']
                    );
                }
            }
            
            DB::commit();
            
            return $budget->fresh()->load('categories.categorie');
        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Ajoute une catégorie à un budget.
     *
     * @param int $budgetId
     * @param int $categorieId
     * @param float $montantAlloue
     * @param float $pourcentage
     * @return CategorieBudget
     */
    public function ajouterCategorieBudget(
        int $budgetId,
        int $categorieId,
        float $montantAlloue,
        float $pourcentage
    ): CategorieBudget {
        return $this->categorieBudgetRepository->create([
            'budget_id' => $budgetId,
            'categorie_id' => $categorieId,
            'montant_alloue' => $montantAlloue,
            'pourcentage' => $pourcentage
        ]);
    }

    /**
     * Met à jour une relation catégorie-budget.
     *
     * @param CategorieBudget $categorieBudget
     * @param array $data
     * @return CategorieBudget
     */
    public function mettreAJourCategorieBudget(CategorieBudget $categorieBudget, array $data): CategorieBudget
    {
        // Si on change le montant, ajuster le pourcentage
        if (isset($data['montant_alloue']) && !isset($data['pourcentage'])) {
            $budget = $this->budgetRepository->find($categorieBudget->budget_id);
            if ($budget->montant_total > 0) {
                $data['pourcentage'] = ($data['montant_alloue'] / $budget->montant_total) * 100;
            }
        }
        
        // Si on change le pourcentage, ajuster le montant
        if (isset($data['pourcentage']) && !isset($data['montant_alloue'])) {
            $budget = $this->budgetRepository->find($categorieBudget->budget_id);
            $data['montant_alloue'] = ($data['pourcentage'] / 100) * $budget->montant_total;
        }
        
        return $this->categorieBudgetRepository->update($categorieBudget, $data);
    }

    /**
     * Rééquilibre les pourcentages de toutes les catégories d'un budget.
     *
     * @param int $budgetId
     * @return array
     */
    public function reequilibrerCategories(int $budgetId): array
    {
        $budget = $this->budgetRepository->findOrFail($budgetId);
        $categoriesBudget = $this->categorieBudgetRepository->getAllForBudget($budgetId);
        
        $totalMontants = $categoriesBudget->sum('montant_alloue');
        
        DB::beginTransaction();
        
        try {
            foreach ($categoriesBudget as $categorieBudget) {
                $pourcentage = ($categorieBudget->montant_alloue / $totalMontants) * 100;
                
                $this->categorieBudgetRepository->update($categorieBudget, [
                    'pourcentage' => $pourcentage
                ]);
            }
            
            DB::commit();
            
            return $this->categorieBudgetRepository->getAllForBudget($budgetId)->toArray();
        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Récupère le budget actuel d'un utilisateur.
     *
     * @param int $userId
     * @return Budget|null
     */
    public function getBudgetActuel(int $userId): ?Budget
    {
        return $this->budgetRepository->getBudgetActuel($userId);
    }

    /**
     * Calcule les statistiques pour un budget.
     *
     * @param Budget $budget
     * @return array
     */
    public function calculerStatistiques(Budget $budget): array
    {
        $budget->load(['categories.categorie', 'categories.depenses']);
        
        $totalAlloue = $budget->montant_total;
        $totalDepense = 0;
        $categoriesStats = [];
        
        foreach ($budget->categories as $categorieBudget) {
            $montantDepense = $categorieBudget->depenses->sum('montant');
            $totalDepense += $montantDepense;
            $pourcentageUtilise = $categorieBudget->montant_alloue > 0 
                ? ($montantDepense / $categorieBudget->montant_alloue) * 100 
                : 0;
                
            $categoriesStats[] = [
                'id' => $categorieBudget->id,
                'categorie_id' => $categorieBudget->categorie_id,
                'nom' => $categorieBudget->categorie->nom,
                'icone' => $categorieBudget->categorie->icone,
                'montant_alloue' => $categorieBudget->montant_alloue,
                'montant_depense' => $montantDepense,
                'reste' => $categorieBudget->montant_alloue - $montantDepense,
                'pourcentage_utilise' => $pourcentageUtilise,
                'pourcentage_du_budget' => $categorieBudget->pourcentage
            ];
        }
        
        return [
            'id' => $budget->id,
            'nom' => $budget->nom,
            'date_debut' => $budget->date_debut,
            'date_fin' => $budget->date_fin,
            'montant_total' => $totalAlloue,
            'total_depense' => $totalDepense,
            'reste' => $totalAlloue - $totalDepense,
            'pourcentage_utilise' => $totalAlloue > 0 ? ($totalDepense / $totalAlloue) * 100 : 0,
            'categories' => $categoriesStats
        ];
    }

    /**
     * Calcule les statistiques pour une catégorie de budget.
     *
     * @param CategorieBudget $categorieBudget
     * @return array
     */
    public function calculerStatistiquesCategorieBudget(CategorieBudget $categorieBudget): array
    {
        $categorieBudget->load(['depenses']);
        
        $montantDepense = $categorieBudget->depenses->sum('montant');
        $pourcentageUtilise = $categorieBudget->montant_alloue > 0 
            ? ($montantDepense / $categorieBudget->montant_alloue) * 100 
            : 0;
            
        return [
            'montant_alloue' => $categorieBudget->montant_alloue,
            'montant_depense' => $montantDepense,
            'reste' => $categorieBudget->montant_alloue - $montantDepense,
            'pourcentage_utilise' => $pourcentageUtilise,
            'nombre_depenses' => $categorieBudget->depenses->count()
        ];
    }

    /**
     * Vérifie si une catégorie de budget appartient à l'utilisateur.
     *
     * @param int $categorieBudgetId
     * @param int $userId
     * @return bool
     * @throws Exception
     */
    public function verifierProprieteCategoriesBudget(int $categorieBudgetId, int $userId): bool
    {
        $categorieBudget = $this->categorieBudgetRepository->findOrFail($categorieBudgetId);
        $budget = $this->budgetRepository->findOrFail($categorieBudget->budget_id);
        
        if ($budget->utilisateur_id !== $userId) {
            throw new Exception('Cette catégorie de budget ne vous appartient pas.', 403);
        }
        
        return true;
    }

    /**
     * Vérifie si un compte appartient à l'utilisateur.
     *
     * @param int $compteId
     * @param int $userId
     * @return bool
     * @throws Exception
     */
    public function verifierProprieteCompte(int $compteId, int $userId): bool
    {
        if (!$this->compteRepository->appartientAUtilisateur($compteId, $userId)) {
            throw new Exception('Ce compte ne vous appartient pas.', 403);
        }
        
        return true;
    }

    /**
     * Crée un budget automatiquement à partir d'un revenu.
     *
     * @param int $userId
     * @param float $montant
     * @param string $datePerception
     * @return Budget
     */
    public function creerBudgetAutomatique(int $userId, float $montant, string $datePerception): Budget
    {
        $datePerception = Carbon::parse($datePerception);
        $dateDebut = $datePerception->copy()->startOfMonth();
        $dateFin = $datePerception->copy()->endOfMonth();
        
        $budgetData = [
            'utilisateur_id' => $userId,
            'nom' => 'Budget ' . $datePerception->format('F Y'),
            'date_debut' => $dateDebut->format('Y-m-d'),
            'date_fin' => $dateFin->format('Y-m-d'),
            'montant_total' => $montant
        ];
        
        return $this->creerBudget($budgetData);
    }

    /**
     * Récupère les catégories par défaut avec leurs pourcentages.
     *
     * @param float $montantTotal
     * @return array
     */
    protected function getCategoriesParDefaut(float $montantTotal): array
    {
        // Récupérer les catégories par défaut
        $categories = Categorie::where('est_global', true)
            ->where('pourcentage_defaut', '>', 0)
            ->get();
            
        $result = [];
        
        foreach ($categories as $categorie) {
            $montantAlloue = ($categorie->pourcentage_defaut / 100) * $montantTotal;
            
            $result[] = [
                'categorie_id' => $categorie->id,
                'montant_alloue' => $montantAlloue,
                'pourcentage' => $categorie->pourcentage_defaut
            ];
        }
        
        return $result;
    }
}