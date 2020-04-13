<?php


namespace ProxyBank\Models\Security;


abstract class AuthenticationStrategy
{
    /**
     * Authentication with login, password and a specific cookie.
     */
    const LOGIN_PASSWORD_COOKIE = 0;
}
