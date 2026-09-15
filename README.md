# phoneHub — Panel interne Cloud Phones

Plateforme Laravel (Blade + Livewire + MySQL) pour piloter tes Cloud Phones via l’API GeeLark. La clé API reste côté serveur.

## Stack

- Laravel 13 + Livewire 4
- Blade + Tailwind CSS 4
- MySQL
- Client HTTP vers `https://openapi.geelark.com`

## Installation (WAMP)

1. Créer la base (déjà faite si tu as suivi le setup) :

```sql
CREATE DATABASE geelark CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

2. Configurer `.env` :

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_DATABASE=geelark
DB_USERNAME=root
DB_PASSWORD=

GEELARK_API_TOKEN=ton_token
GEELARK_AUTH_MODE=token
GEELARK_DEMO_MODE=false
```

Sans token, `GEELARK_DEMO_MODE=true` simule les réponses API.

### Auth admin

```text
URL      : /login
Email    : admin@phonehub.local
Password : password
```

Toutes les pages du panel sont protégées par `auth`.

3. Installer et migrer :

```bash
composer install
npm install && npm run build
php artisan migrate --seed
php artisan serve
```

Ouvre `http://127.0.0.1:8000`.

## Fonctionnalités

| Action | Description |
|--------|-------------|
| Liste phones | Recherche, filtre statut, tags/groupes/proxy |
| Créer | `POST /open/v1/phone/addNew` |
| Start / Stop / Restart | API start & stop |
| Supprimer | Stop puis delete |
| Apps | Install via `app/install` + historique local |
| Screenshots | `screenShot` + `screenShot/result` |
| Automations | Lancement de tâches + journal MySQL |
| Sync | Import depuis `phone/list` |
| Dashboard | Fleet status, uptime, health |

## Schéma MySQL

- `phones` — miroir local + métadonnées GeeLark
- `phone_apps` — apps installées
- `automations` — journal des tâches

## Sécurité

Ne jamais exposer `GEELARK_API_TOKEN` / `GEELARK_API_KEY` dans le front. Toutes les actions passent par `App\Services\GeeLarkApiService` et `PhoneManager`.

Auth alternative (signature) :

```env
GEELARK_AUTH_MODE=key
GEELARK_APP_ID=...
GEELARK_API_KEY=...
```

## Doc API

[GeeLark OpenAPI](https://github.com/GeeLark/geelark-openapi)
