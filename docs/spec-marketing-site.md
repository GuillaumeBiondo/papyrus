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

## 7. Freemium — Architecture configurable (sans redéploiement)

### Principe fondamental

La maille freemium va changer souvent avant le lancement. Les limites ne doivent donc **jamais être codées en dur** dans la logique métier. Elles vivent en base de données, éditables depuis l'admin sans toucher au code.

### Structure en DB

```sql
-- Un plan = une ligne Stripe Product + une ligne locale avec ses features
CREATE TABLE plans (
    id           BIGSERIAL PRIMARY KEY,
    stripe_price_id_monthly  VARCHAR(100),   -- ex: price_xxxxx
    stripe_price_id_yearly   VARCHAR(100),
    name         VARCHAR(100) NOT NULL,      -- "Gratuit", "Pro"
    slug         VARCHAR(50) UNIQUE NOT NULL, -- "free", "pro"
    features     JSONB NOT NULL DEFAULT '{}',
    is_active    BOOLEAN DEFAULT TRUE,
    created_at   TIMESTAMP DEFAULT NOW(),
    updated_at   TIMESTAMP DEFAULT NOW()
);
```

### Le JSON `features` — toutes les limites en un seul endroit

```json
{
  "projects_max":           1,        // -1 = illimité
  "spell_check_per_month":  20,       // -1 = illimité
  "style_check_per_month":  20,
  "rewrite_per_month":      5,
  "repetition_analysis":    true,
  "chapter_analysis":       false,
  "character_stats":        false,
  "export":                 false,
  "priority_support":       false
}
```

Pour changer une limite : l'admin modifie ce JSON dans le panel → aucun déploiement.

### Le service qui vérifie les droits

```php
class PlanFeatureService
{
    public function can(User $user, string $feature): bool
    {
        $plan = $this->getPlan($user);
        $value = data_get($plan->features, $feature);

        if (is_bool($value)) return $value;
        if ($value === -1)   return true;   // illimité

        // Pour les quotas mensuels : comparer à l'usage du mois courant
        $used = $this->getMonthlyUsage($user, $feature);
        return $used < $value;
    }

    public function remaining(User $user, string $feature): int|null
    {
        $plan = $this->getPlan($user);
        $limit = data_get($plan->features, $feature);
        if ($limit === -1) return null; // null = illimité
        return max(0, $limit - $this->getMonthlyUsage($user, $feature));
    }
}
```

### Usage dans un controller

```php
// Dans SpellCheckController
public function check(Request $request)
{
    if (!$this->planFeatures->can($request->user(), 'spell_check_per_month')) {
        return response()->json([
            'error' => 'quota_exceeded',
            'upgrade_url' => route('billing.upgrade'),
        ], 403);
    }

    // ... logique de correction
    $this->planFeatures->incrementUsage($request->user(), 'spell_check_per_month');
}
```

### Suivi de l'usage mensuel

```sql
CREATE TABLE feature_usage (
    id         BIGSERIAL PRIMARY KEY,
    user_id    BIGINT REFERENCES users(id),
    feature    VARCHAR(100) NOT NULL,
    month      CHAR(7) NOT NULL,   -- ex: "2026-06"
    count      INT DEFAULT 0,
    UNIQUE(user_id, feature, month)
);
```

`UPSERT` à chaque utilisation, reset automatique car la clé inclut le mois.

### Panel admin — gestion des plans

```
Admin → Plans & Limites
  ├── Plan Gratuit
  │     └── [Formulaire structuré sur chaque feature du JSON]
  │           - Projets max : [input number, -1 = illimité]
  │           - Corrections ortho/mois : [input number, -1 = illimité]
  │           - Analyse chapitres : [toggle]
  │           ...
  └── Plan Pro
        └── [idem]
```

Le formulaire admin génère et sauvegarde le JSON. Pas besoin d'éditer du JSON brut.

### Catalogue des features connues à ce jour

À titre indicatif — tout peut changer sans déploiement :

| Clé feature | Type | Description |
|---|---|---|
| `projects_max` | nombre | Nb de projets actifs |
| `spell_check_per_month` | nombre | Corrections ortho/mois |
| `style_check_per_month` | nombre | Corrections style/mois |
| `rewrite_per_month` | nombre | Reformulations IA/mois |
| `repetition_analysis` | booléen | Analyse des répétitions |
| `chapter_analysis` | booléen | Synopsis auto + stats chapitre |
| `character_stats` | booléen | Ratios de présence personnages |
| `export` | booléen | Export du projet |
| `priority_support` | booléen | Support email prioritaire |

Ajouter une nouvelle feature = ajouter une clé dans le JSON des deux plans + un contrôle dans le code. **Pas de migration, pas de déploiement de config.**

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

## 9. RGPD — Conformité

### Pourquoi c'est critique pour Papyrus en particulier

Papyrus est un outil d'écriture. Les utilisateurs y stockent des manuscrits, des journaux intimes, des scénarios non publiés — **du contenu créatif hautement personnel**. En plus des données personnelles classiques (email, paiement), tu es dépositaire d'œuvres qui peuvent représenter des années de travail. Ça impose des obligations de sécurité et de transparence plus fortes que pour un simple outil SaaS.

Points spécifiques à clarifier publiquement :
- Le contenu de l'utilisateur lui appartient entièrement
- Le contenu **n'est pas utilisé pour entraîner des modèles d'IA**
- En cas de fermeture du service, l'utilisateur peut récupérer toutes ses données

---

### Registre des traitements (obligatoire RGPD Art. 30)

Document interne (pas public) qui liste chaque traitement. À tenir à jour.

| Traitement | Données | Base légale | Durée de conservation |
|---|---|---|---|
| Création de compte | Email, nom, mot de passe hashé | Contrat | Durée du compte + 3 ans |
| Contenu écrit | Textes, chapitres, projets | Contrat | Durée du compte + export possible |
| Abonnement & facturation | Nom, adresse, statut abonnement | Contrat + obligation légale | 10 ans (comptabilité) |
| Usage des features | Compteurs anonymisés par feature/mois | Intérêt légitime | 12 mois glissants |
| Logs de connexion | IP, date, user-agent | Intérêt légitime (sécurité) | 12 mois |
| Newsletter / blog | Email + consentement horodaté | Consentement | Jusqu'au désabonnement |
| Données IA (corrections) | Extraits de texte envoyés au modèle | Contrat | Non conservés (transit uniquement) |

---

### Droits des utilisateurs à implémenter (Art. 15–22)

Chaque droit doit avoir un endpoint API + une UI dans le profil utilisateur.

#### Droit d'accès & portabilité (Art. 15 & 20)
```
GET /api/user/data-export
→ génère un ZIP en tâche de fond (Laravel Job)
→ contient : profil, projets, chapitres, scènes, historique d'abonnement
→ notifie par email avec lien de téléchargement (valable 48h)
```

Format du ZIP :
```
export-papyrus-2026-06-01/
  profil.json             ← email, nom, date d'inscription, plan
  projets/
    mon-roman/
      projet.json         ← métadonnées
      chapitres/
        01-chapitre.md    ← contenu brut en markdown
  facturation.json        ← historique des paiements (sans données carte)
```

#### Droit à l'effacement (Art. 17) — "Supprimer mon compte"

C'est le plus complexe. Deux niveaux :

```php
// 1. Soft delete immédiat (compte désactivé, accès impossible)
$user->delete(); // Laravel soft delete

// 2. Hard delete différé après 30 jours (configurable dans settings)
// Un Job planifié via Laravel Scheduler purge les comptes soft-deleted > 30j
// Les données de facturation NE sont PAS supprimées (obligation légale 10 ans)
// → on anonymise : email → anon_xxxxx@deleted.papyrus.io, nom → "[Supprimé]"
```

Ce que l'utilisateur voit dans son profil :
```
[Supprimer mon compte]
  → Modal : "Vos projets seront supprimés dans 30 jours.
             Téléchargez vos données avant.
             Vos factures restent archivées 10 ans (obligation légale)."
  → Confirmation par email avec lien d'annulation (pendant 30 jours)
```

#### Droit de rectification (Art. 16)
L'utilisateur peut modifier email, nom depuis son profil — déjà prévu.
Si l'email change → re-vérification obligatoire.

#### Droit d'opposition au marketing (Art. 21)
- Case à décocher à l'inscription : "Recevoir les articles du blog et les nouveautés"
- Lien de désabonnement dans **chaque email marketing** (obligatoire)
- Géré via un champ `marketing_consent` + `marketing_consent_at` sur le user

---

### Cookies & consentement

Le site marketing (Nuxt) aura probablement Google Analytics ou équivalent.

**Règle CNIL :** les cookies non essentiels nécessitent un consentement **avant** leur dépôt — pas une simple bannière "en continuant vous acceptez".

```
Cookies strictement nécessaires (pas de consentement requis) :
  - Session / auth (Sanctum)
  - Préférences (langue, thème)
  - Panier / checkout Stripe

Cookies nécessitant consentement :
  - Analytics (Google Analytics, Plausible*, Matomo*)
  - Publicité (si un jour)

* Plausible et Matomo auto-hébergé sont exemptés de consentement
  si correctement configurés (CNIL délibération 2022)
```

**Recommandation :** utiliser **Plausible** (auto-hébergeable, conforme CNIL sans bandeau, open source) plutôt que Google Analytics. Ça évite tout le problème du bandeau cookies pour les analytics.

---

### Sous-traitants (Art. 28) — DPA à signer

Pour chaque prestataire qui traite des données personnelles pour ton compte :

| Prestataire | Données transmises | DPA disponible | Localisation |
|---|---|---|---|
| Stripe | Nom, email, adresse, statut paiement | ✓ (accepté aux CGU) | US + clauses SCCs EU |
| Prestataire IA (corrections) | Extraits de texte | À vérifier selon le choix | Variable |
| Hébergeur (OVH/Raspberry) | Toutes | N/A (auto-hébergé Pi) ou DPA OVH | FR/EU |

**Point critique sur l'IA :** si tu envoies des textes utilisateurs à un modèle externe (OpenAI, Anthropic, etc.) pour les corrections de style / reformulation, tu dois :
1. Le mentionner explicitement dans la politique de confidentialité
2. Préciser que les textes ne sont pas stockés ni utilisés pour l'entraînement
3. Vérifier que le prestataire IA a signé un DPA valable EU (OpenAI et Anthropic l'ont)

---

### Pages légales à créer sur le site marketing

| Page | Contenu minimum | Obligatoire |
|---|---|---|
| Politique de confidentialité | Registre synthétisé, droits, contacts, cookies | ✓ RGPD |
| CGU / CGV | Conditions d'utilisation, abonnement, remboursement | ✓ Loi française |
| Mentions légales | Éditeur, hébergeur, SIRET | ✓ LCEN (loi française) |
| Politique cookies | Types, durée, opt-out | ✓ CNIL |

Ces pages doivent être **accessibles depuis le footer** de chaque page.

Lien de contact RGPD dans la politique : une adresse email dédiée (`privacy@papyrus.io`) ou un formulaire de contact.

---

### Ce qu'on ajoute en DB

```sql
-- Sur la table users
ALTER TABLE users ADD COLUMN marketing_consent     BOOLEAN DEFAULT FALSE;
ALTER TABLE users ADD COLUMN marketing_consent_at  TIMESTAMP;
ALTER TABLE users ADD COLUMN deletion_requested_at TIMESTAMP;  -- soft delete demandé
ALTER TABLE users ADD COLUMN anonymized_at         TIMESTAMP;  -- hard delete effectué

-- Journal des demandes RGPD (pour preuve de traitement)
CREATE TABLE gdpr_requests (
    id           BIGSERIAL PRIMARY KEY,
    user_id      BIGINT REFERENCES users(id),
    type         VARCHAR(50) NOT NULL,  -- 'export' | 'deletion' | 'rectification' | 'opposition'
    status       VARCHAR(50) DEFAULT 'pending',  -- pending | processing | completed
    requested_at TIMESTAMP DEFAULT NOW(),
    completed_at TIMESTAMP,
    notes        TEXT
);
```

---

### Ce qu'on ajoute dans l'admin

```
Admin → RGPD
  ├── Demandes en attente
  │     ├── Exports à traiter (statut + téléchargement)
  │     └── Suppressions planifiées (avec possibilité de bloquer si litige)
  ├── Purge des comptes supprimés
  │     └── Liste des comptes en attente de hard delete + date prévue
  └── Paramètres
        └── Délai avant hard delete (défaut : 30 jours)
```

---

### Nouvelles routes Laravel

```
GET    /api/user/data-export            demander un export (génère un Job)
GET    /api/user/data-export/download   télécharger le ZIP (token signé, 48h)
DELETE /api/user/account                demander la suppression du compte
PUT    /api/user/consents               mettre à jour les consentements marketing

GET    /api/admin/gdpr/requests         [admin] liste des demandes RGPD
PUT    /api/admin/gdpr/requests/{id}    [admin] mettre à jour le statut
```

---

### Checklist avant lancement

- [ ] Politique de confidentialité rédigée et publiée
- [ ] CGU / CGV rédigées et publiées
- [ ] Mentions légales complètes (SIRET requis → micro-entreprise ou société)
- [ ] Endpoint export données opérationnel
- [ ] Flux suppression compte (soft → hard delete après X jours)
- [ ] Consentement marketing à l'inscription (case opt-in, pas opt-out)
- [ ] Lien désabonnement dans tous les emails marketing
- [ ] Analytics CNIL-compatible (Plausible recommandé)
- [ ] DPA Stripe vérifié
- [ ] DPA prestataire IA vérifié
- [ ] Adresse email privacy@ fonctionnelle

---

## 10. Décisions actées

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
