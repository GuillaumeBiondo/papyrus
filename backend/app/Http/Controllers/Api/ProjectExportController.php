<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Project;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;
use ZipArchive;

class ProjectExportController extends Controller
{
    public function export(Request $request, Project $project, string $format): StreamedResponse
    {
        $this->authorize('view', $project);

        $project->load(['arcs.chapters.scenes' => fn ($q) => $q->orderBy('order')]);

        return match ($format) {
            'txt'  => $this->exportText($project, false),
            'md'   => $this->exportText($project, true),
            'zip'  => $this->exportZip($project),
            default => abort(400, 'Format non supporté.'),
        };
    }

    private function exportText(Project $project, bool $markdown): StreamedResponse
    {
        $ext      = $markdown ? 'md' : 'txt';
        $filename = Str::slug($project->title) . '.' . $ext;
        $content  = $markdown
            ? "# {$project->title}\n\n"
            : strtoupper($project->title) . "\n" . str_repeat('=', mb_strlen($project->title)) . "\n\n";

        foreach ($project->arcs->sortBy('order') as $arc) {
            $content .= $markdown
                ? "## {$arc->title}\n\n"
                : "\n=== {$arc->title} ===\n\n";

            foreach ($arc->chapters->sortBy('order') as $chapter) {
                $content .= $markdown
                    ? "### {$chapter->title}\n\n"
                    : "--- {$chapter->title} ---\n\n";

                foreach ($chapter->scenes->sortBy('order') as $scene) {
                    $content .= $markdown
                        ? "#### {$scene->title}\n\n"
                        : "[{$scene->title}]\n\n";

                    $content .= $this->htmlToText($scene->content ?? '') . "\n\n";
                }
            }
        }

        return response()->streamDownload(
            fn () => print($content),
            $filename,
            ['Content-Type' => $markdown ? 'text/markdown' : 'text/plain'],
        );
    }

    private function exportZip(Project $project): StreamedResponse
    {
        $tmpFile = tempnam(sys_get_temp_dir(), 'papyrus_export_');
        $zip     = new ZipArchive();
        $zip->open($tmpFile, ZipArchive::OVERWRITE);

        $root = Str::slug($project->title);

        foreach ($project->arcs->sortBy('order') as $arcIdx => $arc) {
            $arcDir = sprintf('%s/%02d-%s', $root, $arcIdx + 1, Str::slug($arc->title));

            foreach ($arc->chapters->sortBy('order') as $chIdx => $chapter) {
                $chDir = sprintf('%s/%02d-%s', $arcDir, $chIdx + 1, Str::slug($chapter->title));

                foreach ($chapter->scenes->sortBy('order') as $scIdx => $scene) {
                    $name    = sprintf('%02d-%s.txt', $scIdx + 1, Str::slug($scene->title));
                    $content = "[{$scene->title}]\n\n" . $this->htmlToText($scene->content ?? '');
                    $zip->addFromString("{$chDir}/{$name}", $content);
                }
            }
        }

        $zip->close();

        $filename = Str::slug($project->title) . '.zip';
        $response = response()->streamDownload(function () use ($tmpFile) {
            readfile($tmpFile);
            @unlink($tmpFile);
        }, $filename, ['Content-Type' => 'application/zip']);

        return $response;
    }

    private function htmlToText(string $html): string
    {
        $text = preg_replace(['/<\/p>/i', '/<br\s*\/?>/i', '/<\/h[1-6]>/i'], "\n", $html);
        $text = preg_replace('/<h[1-6][^>]*>/i', '', $text ?? '');
        $text = strip_tags($text ?? '');
        $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        return trim(preg_replace("/\n{3,}/", "\n\n", $text) ?? '');
    }
}
