<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Alert;
use App\Models\CategoryBudget;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class AlertController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = Alert::query()
                     ->join('category_budget', 'alerts.category_budget_id', '=', 'category_budget.id')
                     ->join('budgets', 'category_budget.budget_id', '=', 'budgets.id')
                     ->where('budgets.utilisateur_id', Auth::id());
        
        // Filtrer par budget si spécifié
        if ($request->has('budget_id')) {
            $query->where('category_budget.budget_id', $request->budget_id);
        }
        
        // Filtrer par catégorie si spécifiée
        if ($request->has('category_id')) {
            $query->where('category_budget.category_id', $request->category_id);
        }
        
        // Filtrer par type si spécifié
        if ($request->has('type')) {
            $query->where('alerts.type', $request->type);
        }
        
        // Filtrer par statut d'activation
        if ($request->has('active')) {
            $query->where('alerts.active', $request->active == 'true');
        }
        
        $alerts = $query->select('alerts.*')
                        ->with(['categoryBudget.budget', 'categoryBudget.category'])
                        ->get();
        
        // Ajouter le statut de déclenchement pour chaque alerte
        $alerts->each(function ($alert) {
            $alert->is_triggered = $alert->isTriggered();
        });
        
        return response()->json($alerts);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'category_budget_id' => 'required|exists:category_budget,id',
            'type' => 'required|string|in:pourcentage,montant,reste',
            'seuil' => 'required|numeric|min:0',
            'active' => 'sometimes|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Erreur de validation',
                'errors' => $validator->errors()
            ], 422);
        }

        // Vérifier que l'utilisateur est propriétaire du budget associé à cette allocation
        $categoryBudget = CategoryBudget::with('budget')->findOrFail($request->category_budget_id);
        if ($categoryBudget->budget->utilisateur_id !== Auth::id()) {
            return response()->json([
                'message' => 'Vous n\'êtes pas autorisé à ajouter des alertes à ce budget',
            ], 403);
        }

        $alert = Alert::create([
            'category_budget_id' => $request->category_budget_id,
            'type' => $request->type,
            'seuil' => $request->seuil,
            'active' => $request->has('active') ? $request->active : true,
        ]);

        // Charger les relations pour la réponse
        $alert->load(['categoryBudget.budget', 'categoryBudget.category']);
        $alert->is_triggered = $alert->isTriggered();

        return response()->json([
            'message' => 'Alerte créée avec succès',
            'alert' => $alert
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $alert = Alert::with(['categoryBudget.budget', 'categoryBudget.category'])
                     ->findOrFail($id);
        
        // Vérifier que l'utilisateur est propriétaire du budget associé à cette alerte
        if ($alert->categoryBudget->budget->utilisateur_id !== Auth::id()) {
            return response()->json([
                'message' => 'Vous n\'êtes pas autorisé à consulter cette alerte',
            ], 403);
        }
        
        $alert->is_triggered = $alert->isTriggered();
        
        return response()->json($alert);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $alert = Alert::findOrFail($id);
        
        // Vérifier que l'utilisateur est propriétaire du budget associé à cette alerte
        if ($alert->categoryBudget->budget->utilisateur_id !== Auth::id()) {
            return response()->json([
                'message' => 'Vous n\'êtes pas autorisé à modifier cette alerte',
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'type' => 'sometimes|required|string|in:pourcentage,montant,reste',
            'seuil' => 'sometimes|required|numeric|min:0',
            'active' => 'sometimes|required|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Erreur de validation',
                'errors' => $validator->errors()
            ], 422);
        }

        if ($request->has('type')) {
            $alert->type = $request->type;
        }
        
        if ($request->has('seuil')) {
            $alert->seuil = $request->seuil;
        }
        
        if ($request->has('active')) {
            $alert->active = $request->active;
        }

        $alert->save();

        // Charger les relations pour la réponse
        $alert->load(['categoryBudget.budget', 'categoryBudget.category']);
        $alert->is_triggered = $alert->isTriggered();

        return response()->json([
            'message' => 'Alerte mise à jour avec succès',
            'alert' => $alert
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $alert = Alert::findOrFail($id);
        
        // Vérifier que l'utilisateur est propriétaire du budget associé à cette alerte
        if ($alert->categoryBudget->budget->utilisateur_id !== Auth::id()) {
            return response()->json([
                'message' => 'Vous n\'êtes pas autorisé à supprimer cette alerte',
            ], 403);
        }
        
        $alert->delete();

        return response()->json([
            'message' => 'Alerte supprimée avec succès'
        ]);
    }

    /**
     * Get triggered alerts for the user.
     */
    public function getTriggeredAlerts()
    {
        $alerts = Alert::where('active', true)
                     ->join('category_budget', 'alerts.category_budget_id', '=', 'category_budget.id')
                     ->join('budgets', 'category_budget.budget_id', '=', 'budgets.id')
                     ->where('budgets.utilisateur_id', Auth::id())
                     ->select('alerts.*')
                     ->with(['categoryBudget.budget', 'categoryBudget.category', 'categoryBudget.expenses'])
                     ->get();
        
        // Filtrer uniquement les alertes déclenchées
        $triggeredAlerts = $alerts->filter(function ($alert) {
            return $alert->isTriggered();
        });
        
        // Ajouter des informations supplémentaires pour chaque alerte
        $triggeredAlerts->each(function ($alert) {
            $categoryBudget = $alert->categoryBudget;
            $totalSpent = $categoryBudget->total_spent;
            $montantAlloue = $categoryBudget->montant_alloue;
            
            $alert->budget_name = $categoryBudget->budget->nom;
            $alert->category_name = $categoryBudget->category->nom;
            $alert->montant_alloue = $montantAlloue;
            $alert->total_spent = $totalSpent;
            $alert->remaining_amount = $montantAlloue - $totalSpent;
            $alert->percentage_spent = $montantAlloue > 0 ? ($totalSpent / $montantAlloue) * 100 : 0;
            
            switch ($alert->type) {
                case 'pourcentage':
                    $alert->message = "Vous avez dépensé " . number_format($alert->percentage_spent, 2) . "% de votre budget pour la catégorie " . $alert->category_name;
                    break;
                case 'montant':
                    $alert->message = "Vos dépenses pour la catégorie " . $alert->category_name . " ont atteint " . number_format($totalSpent, 2) . " €";
                    break;
                case 'reste':
                    $alert->message = "Il ne reste que " . number_format($alert->remaining_amount, 2) . " € dans votre budget pour la catégorie " . $alert->category_name;
                    break;
            }
        });
        
        return response()->json($triggeredAlerts->values());
    }
}