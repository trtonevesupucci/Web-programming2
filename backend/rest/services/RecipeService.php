<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../utils/HttpException.php';
require_once __DIR__ . '/../utils/Logger.php';

class RecipeService
{
    const UNAVAILABLE_MESSAGE = 'Recipe inspiration is currently unavailable. Please try again later.';

    public function getRandomRecipe()
    {
        $response = $this->call('random.php');
        $meals = $response['meals'] ?? null;
        if (!is_array($meals) || count($meals) === 0) {
            Logger::error('EXTERNAL TheMealDB random.php returned no meals');
            throw HttpException::unavailable(self::UNAVAILABLE_MESSAGE);
        }
        return $this->formatMeal($meals[0]);
    }

    public function searchRecipes($name)
    {
        $name = trim((string) $name);
        if ($name === '') {
            throw HttpException::badRequest('Search term is required');
        }
        if (mb_strlen($name) > 50) {
            throw HttpException::badRequest('Search term must be at most 50 characters');
        }
        $response = $this->call('search.php', ['s' => $name]);
        $meals = $response['meals'] ?? [];
        return array_map([$this, 'formatMeal'], is_array($meals) ? $meals : []);
    }

    public function getRecipeById($id)
    {
        if (filter_var($id, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]) === false) {
            throw HttpException::badRequest('Invalid recipe ID');
        }
        $response = $this->call('lookup.php', ['i' => $id]);
        $meals = $response['meals'] ?? null;
        if (!is_array($meals) || count($meals) === 0) {
            throw HttpException::notFound('Recipe not found');
        }
        return $this->formatMeal($meals[0]);
    }

    private function call($endpoint, array $params = [])
    {
        $url = rtrim(Config::MEALDB_BASE_URL(), '/') . '/' . rawurlencode(Config::MEALDB_API_KEY()) . '/' . $endpoint;
        if (!empty($params)) {
            $url .= '?' . http_build_query($params);
        }

        $start = microtime(true);
        $curl = curl_init($url);
        curl_setopt_array($curl, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CONNECTTIMEOUT => Config::EXTERNAL_API_TIMEOUT(),
            CURLOPT_TIMEOUT => Config::EXTERNAL_API_TIMEOUT(),
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTPHEADER => ['Accept: application/json']
        ]);
        $body = curl_exec($curl);
        $status = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        $error = curl_error($curl);
        curl_close($curl);
        $duration = round((microtime(true) - $start) * 1000);

        if ($body === false) {
            Logger::error('EXTERNAL TheMealDB ' . $endpoint . ' failed: ' . $error . ' ' . $duration . 'ms');
            throw HttpException::unavailable(self::UNAVAILABLE_MESSAGE);
        }
        if ($status < 200 || $status >= 300) {
            Logger::error('EXTERNAL TheMealDB ' . $endpoint . ' returned HTTP ' . $status . ' ' . $duration . 'ms');
            throw HttpException::unavailable(self::UNAVAILABLE_MESSAGE);
        }

        $data = json_decode($body, true);
        if (!is_array($data)) {
            Logger::error('EXTERNAL TheMealDB ' . $endpoint . ' returned invalid JSON ' . $duration . 'ms');
            throw HttpException::unavailable(self::UNAVAILABLE_MESSAGE);
        }

        Logger::info('EXTERNAL TheMealDB ' . $endpoint . ' ' . $status . ' ' . $duration . 'ms');
        return $data;
    }

    private function formatMeal(array $meal)
    {
        $ingredients = [];
        for ($i = 1; $i <= 20; $i++) {
            $ingredient = trim((string) ($meal['strIngredient' . $i] ?? ''));
            if ($ingredient === '') {
                continue;
            }
            $ingredients[] = [
                'name' => $ingredient,
                'measure' => trim((string) ($meal['strMeasure' . $i] ?? ''))
            ];
        }

        return [
            'id' => (int) $meal['idMeal'],
            'name' => $meal['strMeal'] ?? '',
            'category' => $meal['strCategory'] ?? null,
            'area' => $meal['strArea'] ?? null,
            'instructions' => $meal['strInstructions'] ?? null,
            'thumbnail' => $meal['strMealThumb'] ?? null,
            'youtube' => ($meal['strYoutube'] ?? '') ?: null,
            'ingredients' => $ingredients
        ];
    }
}
