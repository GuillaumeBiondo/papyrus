<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class BetaReaderScope
{
    private const ALLOWED_PATHS = [
        'api/v1/projects/*/chapters',
        'api/v1/chapters/*/scenes',
        'api/v1/scenes/*',
        'api/v1/scenes/*/annotations',
        'api/v1/annotations/*',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        $project = $request->route('project');

        if ($project && $request->user()?->hasRoleInProject('beta_reader', $project)) {
            if (! $request->isMethod('GET')) {
                // beta_reader peut seulement créer ses propres annotations
                $isAnnotationStore = $request->isMethod('POST')
                    && $request->routeIs('scenes.annotations.store');

                if (! $isAnnotationStore) {
                    abort(403, 'Accès refusé aux bêta-lecteurs.');
                }
            }
        }

        return $next($request);
    }
}
