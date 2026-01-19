<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Recipe;
use App\Models\Company;

class RecipePrintController extends Controller
{
    public function show(Recipe $recipe)
    {
        $mainCompany = Company::first();

        $recipe->load(['product', 'details.material', 'details.uom']);

        return view('recipe.print', [
            'mainCompany' => $mainCompany,
            'recipe' => $recipe,
        ]);
    }
}
