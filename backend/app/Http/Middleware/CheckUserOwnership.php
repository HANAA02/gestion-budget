<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class CheckUserOwnership
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $model = $request->route()->parameter(array_key_first($request->route()->parameters()));
        
        if (!$model) {
            return $next($request);
        }
        
        // Vérifie si le modèle a un utilisateur_id et s'il correspond à l'utilisateur connecté
        if (property_exists($model, 'utilisateur_id') && $model->utilisateur_id !== Auth::id()) {
            return response()->json([
                'message' => 'Vous n\'êtes pas autorisé à accéder à cette ressource.'
            ], 403);
        }
        
        // Vérifie si c'est un budget lié à une catégorie budget
        if (method_exists($model, 'budget') && $model->budget && $model->budget->utilisateur_id !== Auth::id()) {
            return response()->json([
                'message' => 'Vous n\'êtes pas autorisé à accéder à cette ressource.'
            ], 403);
        }
        
        // Vérifie si c'est une dépense liée à une catégorie budget
        if (method_exists($model, 'categorieBudget') && $model->categorieBudget && 
            $model->categorieBudget->budget && $model->categorieBudget->budget->utilisateur_id !== Auth::id()) {
            return response()->json([
                'message' => 'Vous n\'êtes pas autorisé à accéder à cette ressource.'
            ], 403);
        }
        
        return $next($request);
    }
}