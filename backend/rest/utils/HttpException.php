<?php

class HttpException extends Exception
{
    public static function badRequest($message)
    {
        return new self($message, 400);
    }

    public static function unauthorized($message = 'Unauthorized')
    {
        return new self($message, 401);
    }

    public static function forbidden($message = 'Access denied: insufficient privileges')
    {
        return new self($message, 403);
    }

    public static function notFound($message = 'Resource not found')
    {
        return new self($message, 404);
    }

    public static function unavailable($message)
    {
        return new self($message, 503);
    }
}
