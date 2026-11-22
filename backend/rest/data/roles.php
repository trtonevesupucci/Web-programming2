<?php

class Roles
{
    const ADMIN = 'admin';
    const CUSTOMER = 'customer';
    const STAFF = 'staff';

    public static function all()
    {
        return [self::ADMIN, self::CUSTOMER, self::STAFF];
    }
}
