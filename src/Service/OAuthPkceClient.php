<?php

namespace App\Service;

use AdrienGras\PKCE\PKCEUtils;
use League\OAuth2\Client\Provider\GenericProvider;
use League\OAuth2\Client\Token\AccessTokenInterface;

class OAuthPkceClient
{
    protected const HELLO_ASSO_AUTHORIZE_URL = 'https://auth.helloasso-sandbox.com/authorize';
    protected const HELLO_ASSO_TOKEN_URL = 'https://api.helloasso-sandbox.com/oauth2/token';
    protected const HELLO_ASSO_RESOURCE_URL = 'https://api.helloasso-sandbox.com/oauth2/userinfo';
    protected const HELLO_ASSO_CLIENT_ID = '63b84a64921e41be8549aa3eced570e8';
    
    private GenericProvider $provider;

    public function __construct()
    {
        $this->provider = new GenericProvider([
            'clientId' => self::HELLO_ASSO_CLIENT_ID,
            'clientSecret' => '', // vide pour PKCE
            'redirectUri' => 'https://www.clubalpinlyon.top/mire-callback',
            'urlAuthorize' => self::HELLO_ASSO_AUTHORIZE_URL,
            'urlAccessToken' => self::HELLO_ASSO_TOKEN_URL,
            'urlResourceOwnerDetails' => self::HELLO_ASSO_RESOURCE_URL,
        ]);
    }

    public function getAuthorizationUrl(string &$codeVerifier): string
    {
        $options = [
            'code_challenge' => PKCEUtils::generateCodeChallenge($codeVerifier),
            'code_challenge_method' => 'S256',
        ];

        return $this->provider->getAuthorizationUrl($options);
    }

    public function getAccessToken(string $code, string $codeVerifier): AccessTokenInterface
    {
        return $this->provider->getAccessToken('authorization_code', [
            'code' => $code,
            'code_verifier' => $codeVerifier,
        ]);
    }
}
