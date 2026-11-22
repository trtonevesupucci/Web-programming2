<?php

Flight::route('GET /external/recipes/random', function () {
    Flight::json(Flight::recipeService()->getRandomRecipe());
});

Flight::route('GET /external/recipes/search', function () {
    Flight::json(Flight::recipeService()->searchRecipes(Flight::request()->query['name'] ?? ''));
});

Flight::route('GET /external/recipes/@id', function ($id) {
    Flight::json(Flight::recipeService()->getRecipeById($id));
});
