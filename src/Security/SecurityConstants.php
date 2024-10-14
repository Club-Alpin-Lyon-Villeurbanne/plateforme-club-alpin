<?php

namespace App\Security;

class SecurityConstants
{
    public const ADMIN_USERNAME = 'caflyon';
    public const CONTENT_MANAGER_USERNAME = 'content_manager';
    public const ROLE_ADMIN = 'ROLE_ADMIN';
    public const ROLE_CONTENT_MANAGER = 'ROLE_CONTENT_MANAGER';
    public const ROLE_USER = 'ROLE_USER';
    public const SESSION_USER_ROLE_KEY = 'user_role';
    public const CSRF_TOKEN_ID = 'admin_authenticate';
}