# Rest API

## Authentification

### Générer un token

`POST` `/api/login_check`

Body : 

```json
{
  "username": "email@example.com",
  "password": "plain-password"
}
```

Response payload :

`HTTP 200 OK`

```json
{
	"token": "xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx"
}
```

Errors :
- `HTTP 401 Unauthorized` si erreur d'authentification
- `HTTP 403 Forbidden` si accès refusé


