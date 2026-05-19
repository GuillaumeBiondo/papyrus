# Papyrus — Specs Marketing Site

> Version 2 — conseils techniques détaillés

---

## 1. Architecture — Un container de plus, pas d'accès DB direct

### Ce qu'on ajoute

```
┌─────────────────────────────────────────────────────┐
│  Raspberry Pi (Nginx)                               │
│                                                     │
│  app.papyrus.io        → Vue.js (dist static)       │
│  papyrus.io            → Nuxt 3 (nouveau)  ← NEW    │
│  api.papyrus.io        → Laravel (existant)         │
│                              │                      │
│                         PostgreSQL                  │
└─────────────────────────────────────────────────────┘
```

**Recommandation : le site marketing ne touche jamais directement la DB.**

Ta logique "API pour les users, direct pour le reste" est compréhensible mais risquée :
- La DB devient une interface publique implicite → tout changement de schéma casse les deux projets en même temps
- Les credentials DB dans un deuxième container = surface d'attaque supplémentaire
- Laravel a déjà toute la logique de validation, autorisation, et transformation

**Ce qu'on fait à la place :** ajouter des routes dédiées dans le backend Laravel existant, prefixées `/api/site/` (ou `/api/web/`). Le site Nuxt parle uniquement à ces endpoints. C'est 2-3 controllers de plus, pas un projet séparé.

```
Nuxt (marketing site)
    ↕ HTTP/JSON
Laravel (backend existant) — nouvelles routes /api/site/*
    ↕
PostgreSQL
```

### Stack recommandée pour le site marketing

**Nuxt 3** — cohérent avec Vue.js déjà maîtrisé, SSR pour le SEO, markdown natif avec `@nuxt/content`.

```
marketing/              ← nouveau dossier dans le repo (ou repo séparé)
  nuxt.config.ts
  content/              ← articles markdown
    articles/
      mon-article.md
  pages/
    index.vue           ← landing page
    tarifs.vue
    tutoriels/
    blog/
  components/
  server/               ← API routes Nuxt si besoin de proxy
```

### Mode maintenance indépendant

Dans le backend Laravel, un middleware `SiteMaintenanceMiddleware` vérifie un flag en cache/config.
Le site Nuxt retourne une page 503 dédiée sans toucher l'app principale.

```php
// Route Laravel
Route::middleware('site.maintenance')->prefix('api/site')->group(function () {
    Route::get('/articles', [SiteArticleController::class, 'index']);
    // ...
});
```

Un toggle dans le panel admin suffit (`config('site.maintenance') → true`).

---

## 2. Rôle Webmaster

### Intégration avec spatie/laravel-permission

Tu as déjà `owner`, `co_author`, `beta_reader` (rôles par projet). Le rôle `webmaster` est un **rôle global système**, pas par projet.

```php
// Migration
// spatie permet les rôles globaux (sans guard spécifique)
Role::create(['name' => 'webmaster', 'guard_name' => 'web']);

// Dans le panel admin : toggle
$user->assignRole('webmaster');
$user->removeRole('webmaster');
```

### Ce qu'un webmaster peut faire

| Action | Webmaster | Admin |
|---|---|---|
| Créer / modifier un article | ✓ | ✓ |
| Publier / dépublier | ✓ | ✓ |
| Gérer les images | ✓ | ✓ |
| Mode maintenance du site | ✗ | ✓ |
| Gérer les tarifs & promos | ✗ | ✓ |
| Gérer les tokens | ✗ | ✓ |
| Gérer les utilisateurs | ✗ | ✓ |

```php
// Policy article
class SiteArticlePolicy
{
    public function create(User $user): bool
    {
        return $user->hasRole(['admin', 'webmaster']);
    }

    public function publish(User $user, Article $article): bool
    {
        return $user->hasRole(['admin', 'webmaster']);
    }
}
```

---

## 3. Paiement — Stripe + Laravel Cashier

### Pourquoi Stripe

Comparatif pour 9€/mois, marché français/EU :

| | Stripe | Paddle | Lemon Squeezy |
|---|---|---|---|
| Frais par transaction | 1.4% + 0.25€ (EU) → ~0.38€ | 5% + 0.50€ → ~0.95€ | 5% + 0.50€ → ~0.95€ |
| Sur 9€/mois | **perd ~4.2%** | perd ~10.5% | perd ~10.5% |
| Gestion TVA/VAT EU | Via Stripe Tax (addon) | Inclus (MoR) | Inclus (MoR) |
| Intégration Laravel | **Laravel Cashier** (officiel) | Manuel | Manuel |
| Export compta | CSV, FEC, Pennylane | Basique | Basique |
| Codes promo natifs | ✓ | ✓ | ✓ |
| Portal client (annulation self-service) | ✓ | ✓ | ✓ |

**Verdict : Stripe.**
- Les frais sont 2.5× inférieurs à Paddle
- Laravel Cashier (`laravel/cashier-stripe`) gère les abonnements, les portals clients, les webhooks, les trials — tout est documenté et maintenu par Laravel
- Stripe Tax gère la TVA EU automatiquement (sinon tu dois facturer la TVA du pays de l'acheteur — obligation légale depuis 2015)

### Modèle de prix à 9€ TTC

```
9€ TTC/mois   = 7.50€ HT (TVA 20%)
85€ TTC/an    = 70.83€ HT  → équivaut à 7.08€/mois (-21%, soit ~2.5 mois offerts)
```

### Ce que Stripe gère nativement (sans code custom)

- Abonnements mensuels / annuels
- Codes promo (% ou montant fixe, durée limitée, usage max)
- Périodes de réduction (ex: -50% pendant 3 mois)
- Trials (ex: 14 jours gratuits avant débit)
- Portal client (l'utilisateur gère lui-même : annulation, changement de plan, téléchargement des factures)
- Webhooks → Laravel écoute et met à jour le statut abonnement en DB

### Installation

```bash
composer require laravel/cashier
php artisan vendor:publish --tag="cashier-migrations"
php artisan migrate
```

```php
// User model
use Laravel\Cashier\Billable;

class User extends Authenticatable
{
    use Billable;
}
```

### Données à collecter pour la transaction

Stripe demande très peu côté client : juste l'email + le moyen de paiement (géré par Stripe.js / Stripe Elements dans le browser, les numéros de carte ne transitent jamais par ton serveur).

Pour la facture conforme :
- Prénom / Nom ou Raison sociale
- Adresse (pour TVA EU)
- Numéro de TVA (si B2B, pour autoliquidation)

---

## 4. Section Admin — Gestion des tarifs & promos

### Ce que l'admin peut faire

```
Panel Admin → Abonnements
  ├── Plans actifs (synchronisés avec Stripe Products/Prices)
  │     ├── Modifier le prix affiché (cosmétique) → màj Stripe via API
  │     └── Activer / désactiver un plan
  ├── Promotions
  │     ├── Créer un code promo → POST /stripe/coupons
  │     │     - Type : % ou montant fixe
  │     │     - Durée : once / repeating (N mois) / forever
  │     │     - Date d'expiration
  │     │     - Nombre d'utilisations max
  │     └── Liste des codes actifs / consommés
  └── Abonnés
        ├── Liste avec statut Stripe (active, past_due, canceled)
        └── Actions : annuler, offrir extension, changer de plan
```

**Important :** les prix "réels" vivent dans Stripe. L'admin Papyrus appelle l'API Stripe pour les créer/modifier. Ne jamais dupliquer les prix uniquement en DB locale — Stripe est la source de vérité.

```php
// Créer un coupon depuis l'admin
$stripe->coupons->create([
    'percent_off'       => 20,
    'duration'          => 'repeating',
    'duration_in_months'=> 3,
    'max_redemptions'   => 100,
    'redeem_by'         => strtotime('2026-12-31'),
]);
```

---

## 5. Tokens d'accès temporaires (Agents Hermes)

### Ce que c'est

Un système de tokens API à durée de vie configurable, créés depuis l'admin, pour des agents automatisés (tests, scripts, agents IA Hermes) qui doivent s'authentifier sans compte utilisateur.

### Implémentation avec Sanctum

Sanctum supporte déjà les tokens API (mode token, distinct du mode SPA cookie).

```php
// Migration complémentaire
Schema::table('personal_access_tokens', function (Blueprint $table) {
    // Sanctum a déjà expires_at depuis Laravel 10
    // On ajoute juste des métadonnées
    $table->string('description')->nullable();
    $table->foreignId('created_by')->nullable()->constrained('users');
});
```

```php
// Dans l'admin : créer un token
$token = $targetUser->createToken(
    name: $request->description,
    abilities: ['site:read', 'api:agent'],
    expiresAt: now()->addDays($request->validity_days)
);
// Afficher $token->plainTextToken UNE SEULE FOIS à l'admin
```

### Panel admin — gestion des tokens

```
Admin → Tokens Hermes
  ├── Créer un token
  │     - Description (ex: "Agent test staging")
  │     - Validité (jours) — valeur par défaut configurable dans les settings
  │     - Abilities (cases à cocher)
  ├── Liste des tokens actifs (description, expiration, dernier usage)
  └── Révoquer un token
```

### Paramètre global configurable

```php
// Dans la table settings ou config/site.php
'hermes_token_default_validity_days' => 30,
```

Modifiable depuis le panel admin sans déploiement.

---

## 6. Blog — Éditeur Markdown avec gestion des images

### Éditeur recommandé : Toast UI Editor

**Pourquoi :** vrai éditeur markdown (pas juste un textarea), double vue (édition + preview), gestion d'images native, léger, bien maintenu, licence MIT.

Alternatives :
- **TipTap** : plus puissant, peut écrire du markdown, excellent si tu veux du WYSIWYG pur — mais plus complexe à configurer pour du markdown natif
- **EasyMDE** : simple et efficace si tu veux rester proche du markdown brut

### Gestion des images

Les images s'uploadent via un endpoint Laravel dédié, stockées localement (ou S3-compatible/MinIO si tu veux du cloud self-hosted).

```php
// Route
Route::post('/api/site/articles/upload-image', [SiteImageController::class, 'store'])
    ->middleware(['auth:sanctum', 'role:admin|webmaster']);

// Controller
public function store(Request $request): JsonResponse
{
    $path = $request->file('image')->store('site/articles', 'public');
    return response()->json(['url' => Storage::url($path)]);
}
```

L'éditeur Toast UI appelle cet endpoint au drop/paste d'image et insère le markdown `![alt](url)` automatiquement.

### Structure d'un article en DB

```sql
CREATE TABLE site_articles (
    id              BIGSERIAL PRIMARY KEY,
    author_id       BIGINT REFERENCES users(id),
    slug            VARCHAR(255) UNIQUE NOT NULL,
    title           VARCHAR(500) NOT NULL,
    excerpt         TEXT,
    body            TEXT NOT NULL,            -- markdown brut
    body_html       TEXT,                     -- compilé au save (cache)
    cover_image     VARCHAR(500),
    category        VARCHAR(100),
    tags            JSONB DEFAULT '[]',
    instagram_text  TEXT,                     -- version condensée Instagram
    status          VARCHAR(20) DEFAULT 'draft',  -- draft | published | archived
    published_at    TIMESTAMP,
    created_at      TIMESTAMP DEFAULT NOW(),
    updated_at      TIMESTAMP DEFAULT NOW()
);
```

### Mode maintenance du blog indépendant

```php
// settings table (clé/valeur générique)
// site_blog_maintenance = true/false

// Middleware
class BlogMaintenanceMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        if (Setting::get('site_blog_maintenance') && !auth()->user()?->hasRole('admin|webmaster')) {
            return response()->json(['message' => 'Blog en maintenance'], 503);
        }
        return $next($request);
    }
}
```

---

## 7. Freemium — Définir les limites

La question clé : qu'est-ce qui est gratuit vs payant ?

**Proposition :**

| Fonctionnalité | Gratuit | Pro (9€/mois) |
|---|---|---|
| Projets | 1 | Illimité |
| Corrections ortho/style | 20/mois | Illimité |
| Reformulation IA | 5/mois | Illimité |
| Analyse répétitions | ✓ | ✓ |
| Analyse chapitres (synopsis auto) | ✗ | ✓ |
| Stats personnages | ✗ | ✓ |
| Export | ✗ | ✓ |
| Support | ✗ | Email prioritaire |

**Principe :** le gratuit doit être suffisant pour qu'un auteur débutant ou occasionnel s'en satisfasse, et frustrant juste assez pour qu'un auteur sérieux upgrade.

---

## 8. Schéma des nouvelles routes Laravel

```
POST   /api/auth/*                          existant (Sanctum)

GET    /api/site/articles                   liste publique
GET    /api/site/articles/{slug}            article public
POST   /api/site/articles                   [webmaster|admin] créer
PUT    /api/site/articles/{id}              [webmaster|admin] modifier
DELETE /api/site/articles/{id}              [admin] supprimer
POST   /api/site/articles/upload-image      [webmaster|admin] upload image

GET    /api/site/pricing                    plans publics
GET    /api/admin/pricing                   [admin] gestion plans Stripe
POST   /api/admin/coupons                   [admin] créer coupon
GET    /api/admin/coupons                   [admin] liste coupons
DELETE /api/admin/coupons/{id}              [admin] révoquer coupon

GET    /api/admin/tokens                    [admin] liste tokens Hermes
POST   /api/admin/tokens                    [admin] créer token
DELETE /api/admin/tokens/{id}              [admin] révoquer token

POST   /api/billing/subscribe               créer abonnement Stripe
POST   /api/billing/portal                  redirect portal Stripe
POST   /api/billing/webhook                 webhook Stripe (non authentifié, signé)

PUT    /api/admin/settings                  [admin] paramètres globaux (maintenance, validity_days, etc.)
```

---

## 9. Décisions actées

| Sujet | Décision |
|---|---|
| Architecture | Container Nuxt 3 séparé, API Laravel uniquement (pas d'accès DB direct) |
| Rôles | Rôle `webmaster` global via spatie/laravel-permission |
| Paiement | **Stripe + Laravel Cashier** |
| Prix | 9€ TTC/mois · 85€ TTC/an |
| Éditeur blog | Toast UI Editor (ou TipTap) |
| Tokens | Sanctum token API + champ `expires_at` natif |
| Images | Upload Laravel → stockage local ou MinIO |
| Maintenance blog | Middleware + toggle dans table settings |

---

## 10. Ordre d'implémentation suggéré

1. **DB** : migrations `site_articles`, `settings`, rôle `webmaster`
2. **Backend Laravel** : routes `/api/site/*` + `/api/admin/*` + policies
3. **Stripe** : installer Cashier, configurer webhooks, créer les Products/Prices
4. **Admin panel** : section abonnements, codes promo, tokens Hermes, toggle maintenance
5. **Nuxt** : landing page, blog (lecture articles via API), tarifs
6. **Éditeur blog** : intégrer Toast UI Editor dans le panel admin existant
7. **Auth cross-site** : SSO ou lien direct `app.papyrus.io` depuis le site marketing
