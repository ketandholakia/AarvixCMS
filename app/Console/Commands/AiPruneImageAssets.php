<?php

namespace App\Console\Commands;

use App\Models\AiImageAsset;
use App\Services\MediaUploadService;
use Illuminate\Console\Command;

class AiPruneImageAssets extends Command
{
    protected $signature = 'ai:prune-images {--dry-run : Preview which AI image assets would be pruned}';

    protected $description = 'Prune expired AI-generated image assets and their media files.';

    public function handle(MediaUploadService $mediaUploadService): int
    {
        $cutoff = now();
        $dryRun = $this->option('dry-run');
        $assets = AiImageAsset::query()
            ->with('media')
            ->whereNotNull('retention_expires_at')
            ->where('retention_expires_at', '<', $cutoff)
            ->orderBy('retention_expires_at')
            ->get();

        $deleted = 0;

        foreach ($assets as $asset) {
            $media = $asset->media;

            if (! $media) {
                if (! $dryRun) {
                    $asset->delete();
                }

                $deleted++;
                continue;
            }

            if ($dryRun) {
                $this->line(sprintf(
                    'Would delete media #%d (%s) and AI asset #%d.',
                    $media->id,
                    $media->path,
                    $asset->id,
                ));
                $deleted++;
                continue;
            }

            $mediaUploadService->deleteMedia($media);
            $deleted++;
        }

        $this->info(($dryRun ? 'Would prune' : 'Pruned') . " {$deleted} expired AI image assets.");

        return self::SUCCESS;
    }
}
