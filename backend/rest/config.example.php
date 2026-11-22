<?php

class Config
{
    public static function DB_HOST()
    {
        return '127.0.0.1';
    }

    public static function DB_PORT()
    {
        return 3306;
    }

    public static function DB_NAME()
    {
        return 'restaurant_db';
    }

    public static function DB_USER()
    {
        return 'your_db_user';
    }

    public static function DB_PASSWORD()
    {
        return 'your_db_password';
    }

    public static function JWT_SECRET()
    {
        return 'change_me_to_a_long_random_secret';
    }

    public static function MEALDB_BASE_URL()
    {
        return 'https://www.themealdb.com/api/json/v1/';
    }

    public static function MEALDB_API_KEY()
    {
        return 'your_mealdb_api_key';
    }

    public static function EXTERNAL_API_TIMEOUT()
    {
        return 5;
    }

    public static function LOG_DIR()
    {
        return __DIR__ . '/../logs';
    }
}
