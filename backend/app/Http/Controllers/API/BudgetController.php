<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Budget;
use App\Models\CategoryBudget;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class BudgetController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = Budget::where('utilisateur_id', Auth::id());
        
        // Filtrer par date si spécifiée
        if ($request->has('date_debut') && $request->has('date_fin')) {
            $query->where(function ($q) use ($request) {
                $q->whereBetween('date_debut', [$request->date_debut, $request->date_fin])
                  ->orWhereBetween('date_fin', [$request->date_debut, $request->date_fin]);
            });
        }
        
        $budgets = $query->with('categories')->orderBy('date_debut', 'desc')->get();
        
        return response()->json($budgets);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'nom' => 'required|string|max:100',
            'date_debut' => 'required|date',
            'date_fin' => 'required|date|after:date_debut',
            'montant_total' => 'required|numeric|min:0',
            'categories' => 'required|array',
            'categories.*.id' => 'required|exists:categories,id',
            'categories.*.pourcentage' => 'required|numeric|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Erreur de validation',
                'errors' => $validator->errors()
            ], 422);
        }

        // Vérifier que la somme des pourcentages est égale à 100
        $totalPercentage = array_sum(array_column($request->categories, 'pourcentage'));
        if (abs($totalPercentage - 100) > 0.01) {
            return response()->json([
                'message' => 'La somme des pourcentages doit être égale à 100%',
            ], 422);
        }

        DB::beginTransaction();

        try {
            // Créer le budget
            $budget = Budget::create([
                'utilisateur_id' => Auth::id(),
                'nom' => $request->nom,
                'date_debut' => $request->date_debut,
                'date_fin' => $request->date_fin,
                'montant_total' => $request->montant_total,
            ]);

            // Créer les allocations par catégorie
            foreach ($request->categories as $categoryData) {
                $montantAlloue = ($categoryData['pourcentage'] / 100) * $request->montant_total;
                
                CategoryBudget::create([
                    'budget_id' => $budget->id,
                    'category_id' => $categoryData['id'],
                    'montant_alloue' => $montantAlloue,
                    'pourcentage' => $categoryData['pourcentage'],
                ]);
            }

            DB::commit();

            // Charger les relations pour la réponse
            $budget->load('categories');

            return response()->json([
                'message' => 'Budget créé avec succès',
                'budget' => $budget
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            
            return response()->json([
                'message' => 'Erreur lors de la création du budget',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $budget = Budget::where('utilisateur_id', Auth::id())
                        ->with(['categories', 'categoryBudgets.expenses'])
                        ->findOrFail($id);
        
        // Calculer les statistiques pour chaque catégorie
        $categoryStats = [];
        foreach ($budget->categoryBudgets as $categoryBudget) {
            $categoryStats[] = [
                'category_id' => $categoryBudget->category_id,
                'category_name' => $categoryBudget->category->nom,
                'montant_alloue' => $categoryBudget->montant_alloue,
                'pourcentage' => $categoryBudget->pourcentage,
                'total_spent' => $categoryBudget->total_spent,
                'remaining_amount' => $categoryBudget->remaining_amount,
                'percentage_spent' => $categoryBudget->percentage_spent,
            ];
        }
        
        // Ajouter les statistiques à la réponse
        $budgetData = $budget->toArray();
        $budgetData['statistics'] = [
            'total_spent' => $budget->total_spent,
            'remaining_amount' => $budget->remaining_amount,
            'categories' => $categoryStats
        ];
        
        return response()->json($budgetData);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $budget = Budget::where('utilisateur_id', Auth::id())
                        ->findOrFail($id);

        $validator = Validator::make($request->all(), [
            'nom' => 'sometimes|required|string|max:100',
            'date_debut' => 'sometimes|required|date',
            'date_fin' => 'sometimes|required|date|after:date_debut',
            'montant_total' => 'sometimes|required|numeric|min:0',
            'categories' => 'sometimes|required|array',
            'categories.*.id' => 'required_with:categories|exists:categories,id',
            'categories.*.pourcentage' => 'required_with:categories|numeric|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Erreur de validation',
                'errors' => $validator->errors()
            ], 422);
        }

        // Vérifier que la somme des pourcentages est égale à 100
        if ($request->has('categories')) {
            $totalPercentage = array_sum(array_column($request->categories, 'pourcentage'));
            if (abs($totalPercentage - 100) > 0.01) {
                return response()->json([
                    'message' => 'La somme des pourcentages doit être égale à 100%',
                ], 422);
            }
        }

        DB::beginTransaction();

        try {
            // Mettre à jour le budget
            if ($request->has('nom')) {
                $budget->nom = $request->nom;
            }
            
            if ($request->has('date_debut')) {
                $budget->date_debut = $request->date_debut;
            }
            
            if ($request->has('date_fin')) {
                $budget->date_fin = $request->date_fin;
            }
            
            if ($request->has('montant_total')) {
                $budget->montant_total = $request->montant_total;
            }
            
            $budget->save();

            // Mettre à jour les allocations par catégorie si nécessaire
            if ($request->has('categories') && $request->has('montant_total')) {
                // Supprimer les anciennes allocations
                CategoryBudget::where('budget_id', $budget->id)->delete();
                
                // Créer les nouvelles allocations
                foreach ($request->categories as $categoryData) {
                    $montantAlloue = ($categoryData['pourcentage'] / 100) * $request->montant_total;
                    
                    CategoryBudget::create([
                        'budget_id' => $budget->id,
                        'category_id' => $categoryData['id'],
                        'montant_alloue' => $montantAlloue,
                        'pourcentage' => $categoryData['pourcentage'],
                    ]);
                }
            } else if ($request->has('categories')) {
                // Mettre à jour les pourcentages sans changer le montant total
                // Supprimer les anciennes allocations
                CategoryBudget::where('budget_id', $budget->id)->delete();
                
                // Créer les nouvelles allocations
                foreach ($request->categories as $categoryData) {
                    $montantAlloue = ($categoryData['pourcentage'] / 100) * $budget->montant_total;
                    
                    CategoryBudget::create([
                        'budget_id' => $budget->id,
                        'category_id' => $categoryData['id'],
                        'montant_alloue' => $montantAlloue,
                        'pourcentage' => $categoryData['pourcentage'],
                    ]);
                }
            } else if ($request->has('montant_total')) {
                // Mettre à jour les montants alloués sans changer les pourcentages
                $categoryBudgets = CategoryBudget::where('budget_id', $budget->id)->get();
                
                foreach ($categoryBudgets as $categoryBudget) {
                    $montantAlloue = ($categoryBudget->pourcentage / 100) * $request->montant_total;
                    $categoryBudget->montant_alloue = $montantAlloue;
                    $categoryBudget->save();
                }
            }

            DB::commit();

            // Charger les relations pour la réponse
            $budget->load('categories');

            return response()->json([
                'message' => 'Budget mis à jour avec succès',
                'budget' => $budget
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            
            return response()->json([
                'message' => 'Erreur lors de la mise à jour du budget',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $budget = Budget::where('utilisateur_id', Auth::id())
                        ->findOrFail($id);
        
        // Vérifier si le budget a des dépenses
        $hasExpenses = $budget->expenses()->exists();
        
        if ($hasExpenses) {
            return response()->json([
                'message' => 'Impossible de supprimer ce budget car il contient des dépenses',
            ], 422);
        }

        DB::beginTransaction();

        try {
            // Supprimer les allocations par catégorie
            CategoryBudget::where('budget_id', $budget->id)->delete();
            
            // Supprimer le budget
            $budget->delete();

            DB::commit();

            return response()->json([
                'message' => 'Budget supprimé avec succès'
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            
            return response()->json([
                'message' => 'Erreur lors de la suppression du budget',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get monthly statistics for the user.
     */
    public function monthlyStatistics(Request $request)
    {
        // Déterminer l'année et le mois (par défaut, l'année et le mois en cours)
        $year = $request->year ?? date('Y');
        $month = $request->month ?? date('m');
        
        // Récupérer le budget du mois spécifié
        $budget = Budget::where('utilisateur_id', Auth::id())
                        ->whereYear('date_debut', '<=', $year)
                        ->whereMonth('date_debut', '<=', $month)
                        ->whereYear('date_fin', '>=', $year)
                        ->whereMonth('date_fin', '>=', $month)
                        ->with(['categories', 'categoryBudgets.expenses' => function ($query) use ($year, $month) {
                            $query->whereYear('date_depense', $year)
                                  ->whereMonth('date_depense', $month);
                        }])
                        ->first();
        
        if (!$budget) {
            return response()->json([
                'message' => 'Aucun budget trouvé pour cette période',
            ], 404);
        }
        
        // Calculer les statistiques
        $totalBudget = $budget->montant_total;
        $totalSpent = 0;
        $categoriesStats = [];
        
        foreach ($budget->categoryBudgets as $categoryBudget) {
            $categorySpent = $categoryBudget->expenses->sum('montant');
            $totalSpent += $categorySpent;
            
            $categoriesStats[] = [
                'category_id' => $categoryBudget->category_id,
                'category_name' => $categoryBudget->category->nom,
                'budget_amount' => $categoryBudget->montant_alloue,
                'spent_amount' => $categorySpent,
                'remaining_amount' => $categoryBudget->montant_alloue - $categorySpent,
                'percentage_spent' => $categoryBudget->montant_alloue > 0 ? ($categorySpent / $categoryBudget->montant_alloue) * 100 : 0,
            ];
        }
        
        // Obtenir les dépenses par jour pour le mois
        $startDate = "$year-$month-01";
        $endDate = date('Y-m-t', strtotime($startDate));
        
        $dailyExpenses = DB::table('expenses')
                            ->join('category_budget', 'expenses.category_budget_id', '=', 'category_budget.id')
                            ->join('budgets', 'category_budget.budget_id', '=', 'budgets.id')
                            ->where('budgets.utilisateur_id', Auth::id())
                            ->whereBetween('expenses.date_depense', [$startDate, $endDate])
                            ->select(DB::raw('DATE(expenses.date_depense) as date'), DB::raw('SUM(expenses.montant) as total'))
                            ->groupBy('date')
                            ->orderBy('date')
                            ->get();
        
        return response()->json([
            'budget' => [
                'id' => $budget->id,
                'nom' => $budget->nom,
                'date_debut' => $budget->date_debut,
                'date_fin' => $budget->date_fin,
                'montant_total' => $totalBudget,
            ],
            'statistics' => [
                'total_budget' => $totalBudget,
                'total_spent' => $totalSpent,
                'remaining_amount' => $totalBudget - $totalSpent,
                'percentage_spent' => $totalBudget > 0 ? ($totalSpent / $totalBudget) * 100 : 0,
                'categories' => $categoriesStats,
                'daily_expenses' => $dailyExpenses,
            ],
            'period' => [
                'year' => $year,
                'month' => $month,
            ],
        ]);
    }

    /**
     * Get yearly statistics for the user.
     */
    public function yearlyStatistics(Request $request)
    {
        // Déterminer l'année (par défaut, l'année en cours)
        $year = $request->year ?? date('Y');
        
        // Récupérer les budgets de l'année spécifiée
        $budgets = Budget::where('utilisateur_id', Auth::id())
                        ->whereYear('date_debut', $year)
                        ->orWhereYear('date_fin', $year)
                        ->with(['categories', 'categoryBudgets.expenses' => function ($query) use ($year) {
                            $query->whereYear('date_depense', $year);
                        }])
                        ->get();
        
        if ($budgets->isEmpty()) {
            return response()->json([
                'message' => 'Aucun budget trouvé pour cette année',
            ], 404);
        }
        
        // Calculer les statistiques mensuelles
      
        $monthlyStats = [];
        
        for ($month = 1; $month <= 12; $month++) {
            $startDate = "$year-" . sprintf("%02d", $month) . "-01";
            $endDate = date('Y-m-t', strtotime($startDate));
            
            // Trouver le budget applicable pour ce mois
            $applicableBudget = $budgets->first(function ($budget) use ($startDate, $endDate) {
                return $budget->date_debut <= $endDate && $budget->date_fin >= $startDate;
            });
            
            if (!$applicableBudget) {
                $monthlyStats[$month] = [
                    'budget_amount' => 0,
                    'spent_amount' => 0,
                    'month' => $month,
                ];
                continue;
            }
            
            // Calculer les dépenses pour ce mois
            $totalSpent = 0;
            
            foreach ($applicableBudget->categoryBudgets as $categoryBudget) {
                $monthlyExpenses = $categoryBudget->expenses->filter(function ($expense) use ($month) {
                    return date('n', strtotime($expense->date_depense)) == $month;
                });
                
                $totalSpent += $monthlyExpenses->sum('montant');
            }
            
            $monthlyStats[$month] = [
                'budget_amount' => $applicableBudget->montant_total,
                'spent_amount' => $totalSpent,
                'month' => $month,
            ];
        }
        
        // Calculer les statistiques par catégorie pour l'année
        $categoryStats = [];
        $categories = [];
        
        foreach ($budgets as $budget) {
            foreach ($budget->categoryBudgets as $categoryBudget) {
                $categoryId = $categoryBudget->category_id;
                $categoryName = $categoryBudget->category->nom;
                
                if (!isset($categories[$categoryId])) {
                    $categories[$categoryId] = $categoryName;
                }
                
                $yearExpenses = $categoryBudget->expenses->filter(function ($expense) use ($year) {
                    return date('Y', strtotime($expense->date_depense)) == $year;
                });
                
                $spentAmount = $yearExpenses->sum('montant');
                
                if (!isset($categoryStats[$categoryId])) {
                    $categoryStats[$categoryId] = [
                        'category_id' => $categoryId,
                        'category_name' => $categoryName,
                        'spent_amount' => $spentAmount,
                    ];
                } else {
                    $categoryStats[$categoryId]['spent_amount'] += $spentAmount;
                }
            }
        }
        
        // Obtenir le total des dépenses pour l'année
        $totalYearlySpent = array_sum(array_column($categoryStats, 'spent_amount'));
        
        // Calculer le pourcentage de dépenses pour chaque catégorie
        foreach ($categoryStats as &$stat) {
            $stat['percentage'] = $totalYearlySpent > 0 ? ($stat['spent_amount'] / $totalYearlySpent) * 100 : 0;
        }
        
        return response()->json([
            'year' => $year,
            'total_spent' => $totalYearlySpent,
            'monthly_statistics' => array_values($monthlyStats),
            'category_statistics' => array_values($categoryStats),
        ]);
    }
}