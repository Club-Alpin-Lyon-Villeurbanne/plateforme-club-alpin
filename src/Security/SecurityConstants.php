<?php

namespace App\Security;

class SecurityConstants
{
    public const ADMIN_USERNAME = 'caflyon';
    public const CONTENT_MANAGER_USERNAME = 'admin_contenu';

    public const CSRF_ADMIN_TOKEN_ID = 'admin_authenticate';

    public const ROLE_ADMIN = 'ROLE_ADMIN';
    public const ROLE_CONTENT_MANAGER = 'ROLE_CONTENT_MANAGER';
    public const ROLE_USER = 'ROLE_USER';

    public const SESSION_USER_ROLE_KEY = 'user_role';
}
