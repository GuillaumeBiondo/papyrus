<?php

use App\Http\Controllers\Api\AnnotationController;
use App\Http\Controllers\Api\ArcController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\BugReportController;
use App\Http\Controllers\Api\CardController;
use App\Http\Controllers\Api\ProjectExportController;
use App\Http\Controllers\Api\ChapterController;
use App\Http\Controllers\Api\KeywordController;
use App\Http\Controllers\Api\NotebookController;
use App\Http\Controllers\Api\NoteController;
use App\Http\Controllers\Api\TranscriptionController;
use App\Http\Controllers\Api\ProjectController;
use App\Http\Controllers\Api\SceneController;
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
| API v1 — routes protégées
|--------------------------------------------------------------------------
*/
Route::prefix('v1')->middleware(['auth:sanctum', 'throttle:60,1'])->group(function () {

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

    // Arcs
    Route::get('projects/{project}/arcs', [ArcController::class, 'index']);
    Route::post('projects/{project}/arcs', [ArcController::class, 'store']);
    Route::put('arcs/{arc}', [ArcController::class, 'update']);
    Route::delete('arcs/{arc}', [ArcController::class, 'destroy']);
    Route::post('projects/{project}/arcs/reorder', [ArcController::class, 'reorder']);

    // Chapters
    Route::get('arcs/{arc}/chapters', [ChapterController::class, 'index']);
    Route::post('arcs/{arc}/chapters', [ChapterController::class, 'store']);
    Route::put('chapters/{chapter}', [ChapterController::class, 'update']);
    Route::delete('chapters/{chapter}', [ChapterController::class, 'destroy']);
    Route::post('chapters/reorder', [ChapterController::class, 'reorder']);

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
    Route::delete('cards/{card}/links/{link}', [CardController::class, 'destroyLink']);

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
});
