# Papyrus — Spec technique backend

**Laravel 13 · PHP 8.4 · API JSON · PostgreSQL**
Version 1.0

---

## 1. Stack et structure

### 1.1 Stack technique

- PHP 8.4 + Laravel 13
- PostgreSQL 17 (Raspberry Pi 5)
- **Laravel Sanctum** — auth SPA (cookie httpOnly, jamais localStorage)
- **Laravel API Resources** — sérialisation JSON
- **Laravel Queues** — driver `database`, pour `RebuildKeywordIndex`
- **Laravel Policies** — autorisation par ressource
- **spatie/laravel-permission** — rôles `owner`, `co_author`, `beta_reader`

### 1.2 Notes Laravel 13

- **`routes/api.php` non inclus par défaut** — générer via `php artisan install:api`
- **`AuthorizesRequests` retiré du Controller de base** — ajouter explicitement le trait dans `app/Http/Controllers/Controller.php` :
  ```php
  abstract class Controller
  {
      use \Illuminate\Foundation\Auth\Access\AuthorizesRequests;
  }
  ```
- **Sanctum SPA** — activer via `$middleware->statefulApi()` dans `bootstrap/app.php` (remplace l'enregistrement manuel des middlewares Sanctum)

### 1.3 Structure des dossiers

```
app/
  Http/
    Controllers/Api/       # un controller par ressource
    Requests/              # Form Requests (validation)
    Resources/             # API Resources & Collections
    Middleware/            # BetaReaderScope
  Models/
  Policies/                # une Policy par model sensible
  Jobs/                    # RebuildKeywordIndex
  Services/                # KeywordScanner, SceneWordCounter
routes/
  api.php
```

### 1.4 Auth — Sanctum SPA

Le frontend Vue envoie `GET /sanctum/csrf-cookie` avant le login, puis `POST /api/auth/login`. La session est stockée en cookie httpOnly.

| Endpoint | Description |
|---|---|
| `GET /sanctum/csrf-cookie` | Initialise la session CSRF |
| `POST /api/auth/login` | Credentials → session cookie |
| `GET /api/auth/me` | Utilisateur courant + rôles |
| `POST /api/auth/logout` | Invalide la session |

---

## 2. Migrations

> Toutes les clés primaires sont des **UUID** (`$table->uuid('id')->primary()`).

### 2.1 `users`

| Colonne | Type | Contrainte |
|---|---|---|
| `id` | uuid PK | not null |
| `name` | string(100) | not null |
| `email` | string(150) | unique, not null |
| `password` | string | bcrypt |
| `timestamps` | | |

### 2.2 `projects`

| Colonne | Type | Notes |
|---|---|---|
| `id` | uuid PK | |
| `owner_id` | uuid FK → users | |
| `title` | string(200) | |
| `genre` | string(100) | nullable |
| `color` | string(7) | nullable, hex couleur carte |
| `target_words` | integer | default 80000 |
| `timestamps` | | |

### 2.3 `project_users` — pivot rôles

| Colonne | Type | Notes |
|---|---|---|
| `project_id` | uuid FK | |
| `user_id` | uuid FK | |
| `role` | enum | `owner\|co_author\|beta_reader` |

> Clé primaire composite `(project_id, user_id)`.

### 2.4 `chapters`

| Colonne | Type | Notes |
|---|---|---|
| `id` | uuid PK | |
| `project_id` | uuid FK | |
| `title` | string(200) | |
| `order` | integer | default 0, tri drag & drop |
| `timestamps` | | |

### 2.5 `scenes`

| Colonne | Type | Notes |
|---|---|---|
| `id` | uuid PK | |
| `chapter_id` | uuid FK | |
| `title` | string(200) | |
| `content` | longText | nullable, Markdown |
| `status` | enum | `idea\|draft\|revised\|final` |
| `order` | integer | default 0 |
| `word_count` | integer | calculé à la sauvegarde via Observer |
| `timestamps` | | |

### 2.6 `cards`

| Colonne | Type | Notes |
|---|---|---|
| `id` | uuid PK | |
| `project_id` | uuid FK | |
| `type` | string(50) | `character\|place\|event\|object\|theme` — extensible |
| `title` | string(200) | |
| `timestamps` | | |

### 2.7 `card_attributes`

| Colonne | Type | Notes |
|---|---|---|
| `id` | uuid PK | |
| `card_id` | uuid FK | |
| `key` | string(100) | ex: `age`, `description`, `arc_narratif` |
| `value` | json | cast `array` dans le model |

### 2.8 `card_links`

| Colonne | Type | Notes |
|---|---|---|
| `id` | uuid PK | |
| `card_id` | uuid FK | fiche source |
| `linked_card_id` | uuid FK | fiche cible |
| `label` | string(100) | nullable, ex: "alliée", "ennemi de" |

### 2.9 `scene_cards` — pivot scène ↔ fiche

| Colonne | Type |
|---|---|
| `scene_id` | uuid FK |
| `card_id` | uuid FK |

> Clé primaire composite `(scene_id, card_id)`.

### 2.10 `notes` — polymorphe

| Colonne | Type | Notes |
|---|---|---|
| `id` | uuid PK | |
| `noteable_type` | string | `App\Models\Scene` ou `App\Models\Card` |
| `noteable_id` | uuid | |
| `body` | text | |
| `timestamps` | | |

> Index composite obligatoire sur `(noteable_type, noteable_id)`.

### 2.11 `annotations`

| Colonne | Type | Notes |
|---|---|---|
| `id` | uuid PK | |
| `scene_id` | uuid FK | |
| `user_id` | uuid FK | |
| `anchor_start` | integer | nullable, position caractère |
| `anchor_end` | integer | nullable |
| `body` | text | |
| `type` | enum | `inline\|global` |
| `timestamps` | | |

### 2.12 `annotation_cards` — pivot annotation ↔ fiche

| Colonne | Type | Notes |
|---|---|---|
| `annotation_id` | uuid FK | |
| `card_id` | uuid FK | |

> Accessible uniquement aux rôles `owner` et `co_author` (Policy).

### 2.13 `card_keywords`

| Colonne | Type | Notes |
|---|---|---|
| `id` | uuid PK | |
| `card_id` | uuid FK | |
| `keyword` | string(100) | |
| `case_sensitive` | boolean | default false |

### 2.14 `keyword_occurrences`

| Colonne | Type | Notes |
|---|---|---|
| `id` | uuid PK | |
| `card_keyword_id` | uuid FK | |
| `scene_id` | uuid FK | |
| `position_start` | integer | |
| `position_end` | integer | |
| `context_excerpt` | string(300) | ~150 chars autour de l'occurrence |
| `computed_at` | timestamp | |

> Table entièrement recalculée par le job — pas de mise à jour partielle.

### 2.15 `notebook_entries` — carnet global

| Colonne | Type | Notes |
|---|---|---|
| `id` | uuid PK | |
| `user_id` | uuid FK | |
| `title` | string(200) | nullable |
| `body` | text | |
| `project_id` | uuid FK | nullable, rattachement optionnel |
| `timestamps` | | |

---

## 3. Models Eloquent

### 3.1 Relations clés

```php
// Project
public function owner(): BelongsTo          // → User
public function members(): BelongsToMany    // → User (via project_users)
public function chapters(): HasMany
public function cards(): HasMany
public function notebookEntries(): HasMany

// Chapter
public function project(): BelongsTo
public function scenes(): HasMany

// Scene
public function chapter(): BelongsTo
public function cards(): BelongsToMany      // via scene_cards
public function annotations(): HasMany
public function notes(): MorphMany
public function keywordOccurrences(): HasManyThrough  // via card_keywords

// Card
public function project(): BelongsTo
public function attributes(): HasMany       // card_attributes
public function links(): HasMany            // card_links (source)
public function linkedBy(): HasMany         // card_links (cible)
public function keywords(): HasMany         // card_keywords
public function scenes(): BelongsToMany     // via scene_cards
public function notes(): MorphMany

// Annotation
public function scene(): BelongsTo
public function user(): BelongsTo
public function cards(): BelongsToMany      // annotation_cards (auteur uniquement)
```

### 3.2 Observer — SceneObserver

```php
// App\Observers\SceneObserver
public function saving(Scene $scene): void
{
    if ($scene->isDirty('content')) {
        $scene->word_count = str_word_count(strip_tags($scene->content));
    }
}
```

> Enregistré dans `AppServiceProvider::boot()`.

---

## 4. Routes API

Toutes les routes sont préfixées `/api/v1` et protégées par `auth:sanctum` sauf mention contraire.

### 4.1 Auth

```
POST   /api/auth/login
POST   /api/auth/logout
GET    /api/auth/me
```

### 4.2 Projects

```
GET    /api/v1/projects                    index — projets de l'utilisateur
POST   /api/v1/projects                    store
GET    /api/v1/projects/{project}          show
PUT    /api/v1/projects/{project}          update
DELETE /api/v1/projects/{project}          destroy

GET    /api/v1/projects/{project}/members  liste des membres
POST   /api/v1/projects/{project}/members  inviter (email + role)
PUT    /api/v1/projects/{project}/members/{user}   changer le rôle
DELETE /api/v1/projects/{project}/members/{user}   retirer
```

### 4.3 Chapters & Scenes

```
GET    /api/v1/projects/{project}/chapters
POST   /api/v1/projects/{project}/chapters
PUT    /api/v1/chapters/{chapter}
DELETE /api/v1/chapters/{chapter}
POST   /api/v1/chapters/reorder            body: [{ id, order }]

GET    /api/v1/chapters/{chapter}/scenes
POST   /api/v1/chapters/{chapter}/scenes
GET    /api/v1/scenes/{scene}
PUT    /api/v1/scenes/{scene}
DELETE /api/v1/scenes/{scene}
POST   /api/v1/scenes/reorder
```

### 4.4 Cards

```
GET    /api/v1/projects/{project}/cards           ?type=character&q=aldric
POST   /api/v1/projects/{project}/cards
GET    /api/v1/cards/{card}
PUT    /api/v1/cards/{card}
DELETE /api/v1/cards/{card}

PUT    /api/v1/cards/{card}/attributes            remplace tous les attributs
GET    /api/v1/cards/{card}/links
POST   /api/v1/cards/{card}/links
DELETE /api/v1/cards/{card}/links/{link}

GET    /api/v1/scenes/{scene}/cards               fiches liées à la scène
POST   /api/v1/scenes/{scene}/cards/{card}        attacher
DELETE /api/v1/scenes/{scene}/cards/{card}        détacher
```

### 4.5 Notes & Annotations

```
GET    /api/v1/scenes/{scene}/notes
POST   /api/v1/scenes/{scene}/notes
PUT    /api/v1/notes/{note}
DELETE /api/v1/notes/{note}

GET    /api/v1/cards/{card}/notes
POST   /api/v1/cards/{card}/notes

GET    /api/v1/scenes/{scene}/annotations
POST   /api/v1/scenes/{scene}/annotations
PUT    /api/v1/annotations/{annotation}
DELETE /api/v1/annotations/{annotation}

POST   /api/v1/annotations/{annotation}/cards/{card}   lier fiche (owner/co_author)
DELETE /api/v1/annotations/{annotation}/cards/{card}
```

### 4.6 Keywords & Référentiel

```
GET    /api/v1/cards/{card}/keywords
POST   /api/v1/cards/{card}/keywords
DELETE /api/v1/keywords/{keyword}

GET    /api/v1/cards/{card}/occurrences         liste depuis keyword_occurrences
POST   /api/v1/projects/{project}/rebuild-index dispatch RebuildKeywordIndex
```

### 4.7 Carnet

```
GET    /api/v1/notebook                    ?project_id=&free=true
POST   /api/v1/notebook
GET    /api/v1/notebook/{entry}
PUT    /api/v1/notebook/{entry}
DELETE /api/v1/notebook/{entry}
```

---

## 5. Policies

Une Policy par resource sensible. Le middleware `BetaReaderScope` bloque l'accès aux fiches, notes d'auteur et carnet pour les `beta_reader`.

| Policy | owner | co_author | beta_reader |
|---|---|---|---|
| `ProjectPolicy` | CRUD | read + update | read only |
| `ScenePolicy` | CRUD | CRUD | read only |
| `CardPolicy` | CRUD | CRUD | ✗ |
| `AnnotationPolicy` (auteur) | CRUD | CRUD | ✗ |
| `AnnotationPolicy` (bêta) | CRUD | CRUD | own only |
| `NotePolicy` | CRUD | CRUD | ✗ |
| `NotebookPolicy` | CRUD | ✗ | ✗ |

### BetaReaderScope middleware

```php
// App\Http\Middleware\BetaReaderScope
public function handle(Request $request, Closure $next): Response
{
    $project = $request->route('project');
    if ($project && $request->user()->hasRoleInProject('beta_reader', $project)) {
        if ($request->isMethod('GET') && !$this->isAllowedForBeta($request)) {
            abort(403);
        }
        if (!$request->isMethod('GET')) {
            abort(403);
        }
    }
    return $next($request);
}
```

---

## 6. Job — RebuildKeywordIndex

```php
// App\Jobs\RebuildKeywordIndex
class RebuildKeywordIndex implements ShouldQueue
{
    public function __construct(public Project $project) {}

    public function handle(): void
    {
        // 1. Supprimer les occurrences existantes du projet
        KeywordOccurrence::whereHas('cardKeyword.card', fn($q) =>
            $q->where('project_id', $this->project->id)
        )->delete();

        // 2. Charger tous les mots-clés du projet
        $keywords = CardKeyword::whereHas('card', fn($q) =>
            $q->where('project_id', $this->project->id)
        )->with('card')->get();

        // 3. Charger toutes les scènes du projet
        $scenes = Scene::whereHas('chapter', fn($q) =>
            $q->where('project_id', $this->project->id)
        )->get();

        // 4. Scanner
        $occurrences = [];
        foreach ($scenes as $scene) {
            foreach ($keywords as $kw) {
                $occurrences = array_merge(
                    $occurrences,
                    app(KeywordScanner::class)->scan($scene, $kw)
                );
            }
        }

        // 5. Insérer en bulk
        KeywordOccurrence::insert($occurrences);
    }
}
```

### Service KeywordScanner

```php
// App\Services\KeywordScanner
public function scan(Scene $scene, CardKeyword $keyword): array
{
    $content = strip_tags($scene->content ?? '');
    $search  = $keyword->case_sensitive
        ? $keyword->keyword
        : mb_strtolower($keyword->keyword);
    $haystack = $keyword->case_sensitive
        ? $content
        : mb_strtolower($content);

    $offset = 0;
    $results = [];

    while (($pos = mb_strpos($haystack, $search, $offset)) !== false) {
        $start   = max(0, $pos - 80);
        $excerpt = mb_substr($content, $start, 160);

        $results[] = [
            'id'                => Str::uuid(),
            'card_keyword_id'   => $keyword->id,
            'scene_id'          => $scene->id,
            'position_start'    => $pos,
            'position_end'      => $pos + mb_strlen($search),
            'context_excerpt'   => $excerpt,
            'computed_at'       => now(),
        ];

        $offset = $pos + 1;
    }

    return $results;
}
```

> Le job est dispatchable via `POST /api/v1/projects/{project}/rebuild-index` (owner/co_author uniquement). Sur le Pi 5, le queue worker tourne en tant que service systemd.

---

## 7. API Resources

Chaque resource expose uniquement les champs nécessaires au frontend. Exemple :

```php
// App\Http\Resources\SceneResource
public function toArray(Request $request): array
{
    return [
        'id'         => $this->id,
        'title'      => $this->title,
        'content'    => $this->content,
        'status'     => $this->status,
        'order'      => $this->order,
        'word_count' => $this->word_count,
        'cards'      => CardResource::collection($this->whenLoaded('cards')),
        'annotations'=> AnnotationResource::collection($this->whenLoaded('annotations')),
        'notes'      => NoteResource::collection($this->whenLoaded('notes')),
        'updated_at' => $this->updated_at,
    ];
}
```

> Utiliser `whenLoaded()` systématiquement pour éviter le N+1. Charger les relations explicitement dans les controllers via `->load()` ou `->with()`.

---

## 8. Validation — Form Requests

Les Form Requests sont organisées par namespace correspondant à leur ressource :

```
app/Http/Requests/
  Project/    StoreProjectRequest, UpdateProjectRequest
  Chapter/    StoreChapterRequest, UpdateChapterRequest
  Scene/      StoreSceneRequest, UpdateSceneRequest
  Card/       StoreCardRequest, UpdateCardRequest
  Note/       StoreNoteRequest, UpdateNoteRequest
  Annotation/ StoreAnnotationRequest, UpdateAnnotationRequest
  Keyword/    StoreKeywordRequest
  Notebook/   StoreNotebookEntryRequest, UpdateNotebookEntryRequest
```

```php
// App\Http\Requests\Scene\StoreSceneRequest
public function rules(): array
{
    return [
        'title'   => ['required', 'string', 'max:200'],
        'content' => ['nullable', 'string'],
        'status'  => ['sometimes', Rule::in(['idea','draft','revised','final'])],
        'order'   => ['sometimes', 'integer', 'min:0'],
    ];
}

// App\Http\Requests\Card\StoreCardRequest
public function rules(): array
{
    return [
        'type'       => ['required', 'string', 'max:50'],
        'title'      => ['required', 'string', 'max:200'],
        'attributes' => ['sometimes', 'array'],
        'attributes.*.key'   => ['required', 'string', 'max:100'],
        'attributes.*.value' => ['nullable'],
    ];
}
```

---

## 9. Configuration queue worker (Pi 5)

### systemd service

```ini
# /etc/systemd/system/papyrus-worker.service
[Unit]
Description=Papyrus Queue Worker
After=network.target

[Service]
User=www-data
WorkingDirectory=/var/www/papyrus/backend
ExecStart=php artisan queue:work database --sleep=3 --tries=3 --timeout=120
Restart=on-failure
RestartSec=5s

[Install]
WantedBy=multi-user.target
```

```bash
sudo systemctl enable papyrus-worker
sudo systemctl start papyrus-worker
```

### Horizon (optionnel, recommandé)

Si la charge du carnet ou du référentiel augmente, remplacer le worker systemd par **Laravel Horizon** pour le monitoring des queues via une UI web.

---

## 10. Gestion des utilisateurs

Pas de registration publique — Papyrus est une app privée. Les comptes sont créés par l'administrateur via la commande artisan :

```bash
php artisan papyrus:create-user
```

La commande demande interactivement le nom, l'email et le mot de passe. Elle valide que l'email est unique et que le mot de passe fait au moins 8 caractères.

> **Option B (future)** — un flow d'invitation depuis l'UI (owner invite par email + rôle depuis les paramètres projet) pourra être ajouté ultérieurement.

---

## 11. Points d'attention

- **N+1** — utiliser `->with()` dans tous les index controllers. Activer `Model::preventLazyLoading()` en développement.
- **UUID partout** — cohérence avec le frontend Vue (pas de séquences prévisibles exposées).
- **Soft deletes** — à activer sur `projects`, `scenes`, `cards` et `notebook_entries` pour permettre une corbeille.
- **Rate limiting** — appliquer `throttle:60,1` sur toutes les routes API, `throttle:10,1` sur le rebuild-index.
- **CORS** — configurer `config/cors.php` pour autoriser uniquement l'origine du frontend Vue (localhost en dev, domaine Pi en prod).
- **Pagination** — tous les endpoints `index` retournent une `ResourceCollection` paginée (15 items par défaut), jamais de collection entière.
