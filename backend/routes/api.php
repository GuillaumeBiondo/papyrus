<?php

use App\Http\Controllers\Api\Admin\ChangelogController as AdminChangelogController;
use App\Http\Controllers\Api\MaintenanceController;
use App\Http\Controllers\Api\ContentTypeController;
use App\Http\Controllers\Api\Admin\ContentTypeController as AdminContentTypeController;
use App\Http\Controllers\Api\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Api\Admin\SettingController as AdminSettingController;
use App\Http\Controllers\Api\Admin\UserController as AdminUserController;
use App\Http\Controllers\Api\AnnotationController;
use App\Http\Controllers\Api\ArcController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\BugReportController;
use App\Http\Controllers\Api\ActivityController;
use App\Http\Controllers\Api\AppConfigController;
use App\Http\Controllers\Api\CardController;
use App\Http\Controllers\Api\CardImageController;
use App\Http\Controllers\Api\FontController;
use App\Http\Controllers\Api\ProfileController;
use App\Http\Controllers\Api\SceneSnapshotController;
use App\Http\Controllers\Api\Admin\AvailableFontController;
use App\Http\Controllers\Api\ChangelogController;
use App\Http\Controllers\Api\ProjectExportController;
use App\Http\Controllers\Api\ChapterController;
use App\Http\Controllers\Api\KeywordController;
use App\Http\Controllers\Api\NotebookController;
use App\Http\Controllers\Api\NoteController;
use App\Http\Controllers\Api\TranscriptionController;
use App\Http\Controllers\Api\ProjectController;
use App\Http\Controllers\Api\SceneController;
use App\Http\Controllers\Api\AiController;
use App\Http\Controllers\Api\AiEnrichController;
use App\Http\Controllers\Api\Admin\AiEnrichController as AdminAiEnrichController;
use App\Http\Controllers\Api\Admin\AiVerificationController as AdminAiVerificationController;
use App\Http\Controllers\Api\Admin\AiStatsController as AdminAiStatsController;
use App\Http\Controllers\Api\Admin\WorkshopController as AdminWorkshopController;
use App\Http\Controllers\Api\Admin\GenreCategoryController as AdminGenreCategoryController;
use App\Http\Controllers\Api\GenreController;
use App\Http\Controllers\Api\TodoController;
use App\Http\Controllers\Api\EditionDocumentController;
use App\Http\Controllers\Api\EditionSettingsController;
use App\Http\Controllers\Api\EditionPresetController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Auth
|--------------------------------------------------------------------------
*/
Route::prefix('auth')->group(function () {
    Route::post('login', [AuthController::class, 'login']);
    Route::middleware('auth:sanctum')->group(function () {
        Route::post('logout', [AuthController::class, 'logout']);
        Route::get('me', [AuthController::class, 'me']);
        Route::put('password', [AuthController::class, 'updatePassword']);
    });
});

/*
|--------------------------------------------------------------------------
| Statut de maintenance (public, auth optionnelle via session stateful)
|--------------------------------------------------------------------------
*/
Route::get('v1/maintenance-status', [MaintenanceController::class, 'status']);

/*
|--------------------------------------------------------------------------
| API v1 — routes protégées
|--------------------------------------------------------------------------
*/
Route::prefix('v1')->middleware(['auth:sanctum', 'throttle:60,1', 'user_blocked', 'maintenance'])->group(function () {

    // Config publique (seuil snapshot, etc.)
    Route::get('config', [AppConfigController::class, 'index']);

    // Polices disponibles (liste publique des polices actives)
    Route::get('fonts', [FontController::class, 'index']);

    // Genres (liste publique pour le sélecteur de genres)
    Route::get('genres', [GenreController::class, 'index']);

    // Types de contenu (liste publique pour la création de projets)
    Route::get('content-types', [ContentTypeController::class, 'index']);

    // Snapshots de scènes
    Route::get('scenes/{scene}/snapshots',                    [SceneSnapshotController::class, 'index']);
    Route::post('scenes/{scene}/snapshots',                   [SceneSnapshotController::class, 'store']);
    Route::get('scenes/{scene}/snapshots/{snapshot}',         [SceneSnapshotController::class, 'show'])->name('snapshots.show');
    Route::post('scenes/{scene}/snapshots/{snapshot}/restore',[SceneSnapshotController::class, 'restore']);

    // Activité (grilles)
    Route::get('activity',                      [ActivityController::class, 'global']);
    Route::get('projects/{project}/activity',   [ActivityController::class, 'forProject']);

    // Profile
    Route::put('profile', [ProfileController::class, 'update']);
    Route::post('profile/avatar', [ProfileController::class, 'uploadAvatar'])->middleware('throttle:10,1');
    Route::delete('profile/avatar', [ProfileController::class, 'destroyAvatar']);
    Route::get('profile/avatar', [ProfileController::class, 'serveAvatar'])->name('profile.avatar');
    Route::put('profile/preferences', [ProfileController::class, 'updatePreferences']);

    // Bug report
    Route::post('bug-report', [BugReportController::class, 'store']);

    // Projects
    Route::get('projects', [ProjectController::class, 'index']);
    Route::post('projects', [ProjectController::class, 'store']);
    Route::get('projects/{project}', [ProjectController::class, 'show']);
    Route::put('projects/{project}', [ProjectController::class, 'update']);
    Route::delete('projects/{project}', [ProjectController::class, 'destroy']);
    Route::get('projects/{project}/export/{format}', [ProjectExportController::class, 'export']);

    // Members
    Route::get('projects/{project}/members', [ProjectController::class, 'members']);
    Route::post('projects/{project}/members', [ProjectController::class, 'inviteMember']);
    Route::put('projects/{project}/members/{user}', [ProjectController::class, 'updateMember']);
    Route::delete('projects/{project}/members/{user}', [ProjectController::class, 'removeMember']);

    // Rebuild index
    Route::post('projects/{project}/rebuild-index', [ProjectController::class, 'rebuildIndex'])
        ->middleware('throttle:10,1');

    // Édition
    Route::get('projects/{project}/edition/documents',         [EditionDocumentController::class, 'index']);
    Route::post('projects/{project}/edition/documents/toggle', [EditionDocumentController::class, 'toggle']);
    Route::get('edition/documents/{document}',                 [EditionDocumentController::class, 'show']);
    Route::put('edition/documents/{document}',                 [EditionDocumentController::class, 'update']);
    Route::get('projects/{project}/edition/settings',          [EditionSettingsController::class, 'show']);
    Route::put('projects/{project}/edition/settings',          [EditionSettingsController::class, 'update']);
    Route::get('edition/presets',                              [EditionPresetController::class, 'index']);
    Route::post('edition/presets',                             [EditionPresetController::class, 'store']);
    Route::put('edition/presets/{editionPreset}',              [EditionPresetController::class, 'update']);
    Route::delete('edition/presets/{editionPreset}',           [EditionPresetController::class, 'destroy']);

    // Arcs
    Route::get('projects/{project}/arcs', [ArcController::class, 'index']);
    Route::post('projects/{project}/arcs', [ArcController::class, 'store']);
    Route::get('arcs/{arc}', [ArcController::class, 'show']);
    Route::put('arcs/{arc}', [ArcController::class, 'update']);
    Route::delete('arcs/{arc}', [ArcController::class, 'destroy']);
    Route::post('projects/{project}/arcs/reorder', [ArcController::class, 'reorder']);
    Route::put('arcs/{arc}/summary', [ArcController::class, 'saveSummary']);
    Route::post('arcs/{arc}/summary/generate', [ArcController::class, 'generateSummary']);
    Route::get('arcs/{arc}/todos', [ArcController::class, 'todos']);
    Route::post('arcs/{arc}/todos', [ArcController::class, 'storeTodo']);

    // Chapters
    Route::get('arcs/{arc}/chapters', [ChapterController::class, 'index']);
    Route::post('arcs/{arc}/chapters', [ChapterController::class, 'store']);
    Route::get('chapters/{chapter}', [ChapterController::class, 'show']);
    Route::put('chapters/{chapter}', [ChapterController::class, 'update']);
    Route::delete('chapters/{chapter}', [ChapterController::class, 'destroy']);
    Route::post('chapters/reorder', [ChapterController::class, 'reorder']);
    Route::put('chapters/{chapter}/summary', [ChapterController::class, 'saveSummary']);
    Route::post('chapters/{chapter}/summary/generate', [ChapterController::class, 'generateSummary']);
    Route::get('chapters/{chapter}/todos', [ChapterController::class, 'todos']);
    Route::post('chapters/{chapter}/todos', [ChapterController::class, 'storeTodo']);

    // Todos (update / delete)
    Route::put('todos/{todo}', [TodoController::class, 'update']);
    Route::delete('todos/{todo}', [TodoController::class, 'destroy']);

    // Scenes
    Route::get('chapters/{chapter}/scenes', [SceneController::class, 'index']);
    Route::post('chapters/{chapter}/scenes', [SceneController::class, 'store']);
    Route::get('scenes/{scene}', [SceneController::class, 'show']);
    Route::put('scenes/{scene}', [SceneController::class, 'update']);
    Route::delete('scenes/{scene}', [SceneController::class, 'destroy']);
    Route::post('scenes/reorder', [SceneController::class, 'reorder']);

    // Cards
    Route::get('projects/{project}/cards', [CardController::class, 'index']);
    Route::post('projects/{project}/cards', [CardController::class, 'store']);
    Route::get('cards/{card}', [CardController::class, 'show']);
    Route::put('cards/{card}', [CardController::class, 'update']);
    Route::delete('cards/{card}', [CardController::class, 'destroy']);

    Route::put('cards/{card}/attributes', [CardController::class, 'updateAttributes']);
    Route::get('cards/{card}/links', [CardController::class, 'links']);
    Route::post('cards/{card}/links', [CardController::class, 'storeLink']);
    Route::put('cards/{card}/links/{link}', [CardController::class, 'updateLink']);
    Route::delete('cards/{card}/links/{link}', [CardController::class, 'destroyLink']);
    Route::post('cards/{card}/integrate-note', [CardController::class, 'integrateLoreNote']);

    // Card images
    Route::get('cards/{card}/images', [CardImageController::class, 'index']);
    Route::post('cards/{card}/images', [CardImageController::class, 'store'])
        ->middleware('throttle:20,1');
    Route::delete('cards/{card}/images/{image}', [CardImageController::class, 'destroy']);
    Route::put('cards/{card}/images/{image}/avatar', [CardImageController::class, 'setAvatar']);
    Route::get('cards/{card}/images/{image}/file', [CardImageController::class, 'serve'])
        ->name('cards.images.serve');

    Route::get('scenes/{scene}/cards', [CardController::class, 'sceneCards']);
    Route::get('scenes/{scene}/cards-by-keywords', [CardController::class, 'byKeywordsInScene']);
    Route::post('scenes/{scene}/cards/{card}', [CardController::class, 'attachToScene']);
    Route::delete('scenes/{scene}/cards/{card}', [CardController::class, 'detachFromScene']);

    // Notes
    Route::get('projects/{project}/notes', [NoteController::class, 'indexForProject']);
    Route::post('projects/{project}/notes', [NoteController::class, 'storeForProject']);
    Route::get('scenes/{scene}/notes', [NoteController::class, 'indexForScene']);
    Route::post('scenes/{scene}/notes', [NoteController::class, 'storeForScene']);
    Route::get('cards/{card}/notes', [NoteController::class, 'indexForCard']);
    Route::post('cards/{card}/notes', [NoteController::class, 'storeForCard']);
    Route::put('notes/{note}', [NoteController::class, 'update']);
    Route::delete('notes/{note}', [NoteController::class, 'destroy']);

    // Annotations
    Route::get('projects/{project}/annotations', [AnnotationController::class, 'indexForProject']);
    Route::get('scenes/{scene}/annotations', [AnnotationController::class, 'index'])
        ->name('scenes.annotations.index');
    Route::post('scenes/{scene}/annotations', [AnnotationController::class, 'store'])
        ->name('scenes.annotations.store');
    Route::put('annotations/{annotation}', [AnnotationController::class, 'update']);
    Route::delete('annotations/{annotation}', [AnnotationController::class, 'destroy']);
    Route::post('annotations/{annotation}/cards/{card}', [AnnotationController::class, 'linkCard']);
    Route::delete('annotations/{annotation}/cards/{card}', [AnnotationController::class, 'unlinkCard']);

    // Keywords
    Route::get('cards/{card}/keywords', [KeywordController::class, 'index']);
    Route::post('cards/{card}/keywords', [KeywordController::class, 'store']);
    Route::delete('cards/{card}/keywords/{keyword}', [KeywordController::class, 'destroy']);
    Route::get('cards/{card}/occurrences', [KeywordController::class, 'occurrences']);

    // Transcription STT
    Route::post('transcribe', [TranscriptionController::class, 'transcribe']);

    // Notebook
    Route::get('notebook', [NotebookController::class, 'index']);
    Route::post('notebook', [NotebookController::class, 'store']);
    Route::get('notebook/{notebookEntry}', [NotebookController::class, 'show']);
    Route::put('notebook/{notebookEntry}', [NotebookController::class, 'update']);
    Route::delete('notebook/{notebookEntry}', [NotebookController::class, 'destroy']);
    Route::post('notebook/{notebookEntry}/transfer', [NotebookController::class, 'transfer']);

    // Changelogs (user-facing)
    Route::get('changelogs', [ChangelogController::class, 'index']);
    Route::get('changelogs/unread', [ChangelogController::class, 'unread']);
    Route::post('changelogs/mark-all-read', [ChangelogController::class, 'markAllRead']);
    Route::post('changelogs/{changelog}/read', [ChangelogController::class, 'markRead']);

    // AI verification
    Route::get('ai/verifications', [AiController::class, 'verifications']);
    Route::post('ai/verify', [AiController::class, 'verify']);

    // AI enrichissement (dictionnaire)
    Route::get('ai/enrich-types', [AiEnrichController::class, 'types']);
    Route::post('ai/enrich', [AiEnrichController::class, 'enrich']);

    // Admin
    Route::prefix('admin')->middleware('admin')->group(function () {
        Route::get('dashboard', [AdminDashboardController::class, 'index']);

        Route::get('users', [AdminUserController::class, 'index']);
        Route::post('users', [AdminUserController::class, 'store']);
        Route::put('users/{user}/maintenance-bypass', [AdminUserController::class, 'updateMaintenanceBypass']);
        Route::put('users/{user}/block', [AdminUserController::class, 'updateBlockedStatus']);
        Route::put('users/{user}/premium-override', [AdminUserController::class, 'updatePremiumOverride']);

        Route::get('content-types', [AdminContentTypeController::class, 'index']);
        Route::post('content-types', [AdminContentTypeController::class, 'store']);
        Route::put('content-types/{contentType}', [AdminContentTypeController::class, 'update']);

        Route::get('changelogs', [AdminChangelogController::class, 'index']);
        Route::post('changelogs', [AdminChangelogController::class, 'store']);
        Route::post('changelogs/images', [AdminChangelogController::class, 'uploadImage']);
        Route::put('changelogs/{changelog}', [AdminChangelogController::class, 'update']);
        Route::delete('changelogs/{changelog}', [AdminChangelogController::class, 'destroy']);

        Route::get('settings', [AdminSettingController::class, 'index']);
        Route::put('settings/{key}', [AdminSettingController::class, 'update']);

        Route::get('fonts', [AvailableFontController::class, 'index']);
        Route::post('fonts', [AvailableFontController::class, 'store']);
        Route::put('fonts/reorder', [AvailableFontController::class, 'reorder']);
        Route::put('fonts/{font}', [AvailableFontController::class, 'update']);
        Route::delete('fonts/{font}', [AvailableFontController::class, 'destroy']);

        Route::get('ai-stats', [AdminAiStatsController::class, 'index']);

        Route::get('ai-verifications', [AdminAiVerificationController::class, 'index']);
        Route::post('ai-verifications', [AdminAiVerificationController::class, 'store']);
        Route::put('ai-verifications/reorder', [AdminAiVerificationController::class, 'reorder']);
        Route::put('ai-verifications/{aiVerification}', [AdminAiVerificationController::class, 'update']);
        Route::delete('ai-verifications/{aiVerification}', [AdminAiVerificationController::class, 'destroy']);

        Route::get('ai-enrich-types', [AdminAiEnrichController::class, 'index']);
        Route::post('ai-enrich-types', [AdminAiEnrichController::class, 'store']);
        Route::put('ai-enrich-types/reorder', [AdminAiEnrichController::class, 'reorder']);
        Route::put('ai-enrich-types/{aiEnrichType}', [AdminAiEnrichController::class, 'update']);
        Route::delete('ai-enrich-types/{aiEnrichType}', [AdminAiEnrichController::class, 'destroy']);

        Route::get('workshops', [AdminWorkshopController::class, 'index']);
        Route::put('workshops/reorder', [AdminWorkshopController::class, 'reorder']);
        Route::put('workshops/{workshop}', [AdminWorkshopController::class, 'update']);

        // Genre categories
        Route::get('genre-categories', [AdminGenreCategoryController::class, 'index']);
        Route::post('genre-categories', [AdminGenreCategoryController::class, 'store']);
        Route::put('genre-categories/reorder', [AdminGenreCategoryController::class, 'reorderCategories']);
        Route::put('genre-categories/{genreCategory}', [AdminGenreCategoryController::class, 'update']);
        Route::delete('genre-categories/{genreCategory}', [AdminGenreCategoryController::class, 'destroy']);
        // Genres within categories
        Route::post('genre-categories/{genreCategory}/genres', [AdminGenreCategoryController::class, 'storeGenre']);
        Route::put('genre-categories/{genreCategory}/genres/reorder', [AdminGenreCategoryController::class, 'reorderGenres']);
        Route::put('genre-categories/{genreCategory}/genres/{genre}', [AdminGenreCategoryController::class, 'updateGenre']);
        Route::delete('genre-categories/{genreCategory}/genres/{genre}', [AdminGenreCategoryController::class, 'destroyGenre']);
        // Proximity matrix
        Route::get('genre-proximity', [AdminGenreCategoryController::class, 'getProximity']);
        Route::put('genre-proximity', [AdminGenreCategoryController::class, 'updateProximity']);
    });
});
