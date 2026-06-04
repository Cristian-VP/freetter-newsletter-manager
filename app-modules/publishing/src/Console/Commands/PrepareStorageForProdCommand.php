<?php

namespace Domains\Publishing\Console\Commands;

use Domains\Identity\Models\Workspace;
use Domains\Publishing\Models\Post;
use Domains\Publishing\Models\PostVersion;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class PrepareStorageForProdCommand extends Command
{
    protected $signature = 'app:prepare-storage-for-prod';
    protected $description = 'Cleans up localhost URLs from the database (Posts and Workspaces) replacing them with relative paths before production deployment.';

    public function handle(): int
    {
        $this->info('Starting database cleanup for production storage...');

        $this->cleanupPosts();
        $this->cleanupPostVersions();
        $this->cleanupWorkspaces();

        $this->info('Cleanup completed successfully.');
        return self::SUCCESS;
    }

    private function cleanupPosts(): void
    {
        $count = 0;
        Post::query()->chunkById(100, function ($posts) use (&$count) {
            foreach ($posts as $post) {
                if (!is_array($post->content)) continue;
                
                $originalContent = json_encode($post->content);
                $newContent = $this->normalizeImageUrls($post->content);
                
                if ($originalContent !== json_encode($newContent)) {
                    $post->content = $newContent;
                    $post->saveQuietly();
                    $count++;
                }
            }
        });
        
        $this->info("Cleaned up {$count} posts.");
    }

    private function cleanupPostVersions(): void
    {
        $count = 0;
        PostVersion::query()->chunkById(100, function ($versions) use (&$count) {
            foreach ($versions as $version) {
                if (!is_array($version->content)) continue;
                
                $originalContent = json_encode($version->content);
                $newContent = $this->normalizeImageUrls($version->content);
                
                if ($originalContent !== json_encode($newContent)) {
                    $version->content = $newContent;
                    $version->saveQuietly();
                    $count++;
                }
            }
        });
        
        $this->info("Cleaned up {$count} post versions.");
    }

    private function cleanupWorkspaces(): void
    {
        $count = 0;
        Workspace::query()->chunkById(100, function ($workspaces) use (&$count) {
            foreach ($workspaces as $workspace) {
                $dirty = false;
                $branding = $workspace->branding_config ?? [];
                
                if (isset($branding['logo_url'])) {
                    $src = $branding['logo_url'];
                    if (str_starts_with($src, 'http://localhost') || str_starts_with($src, 'http://127.0.0.1')) {
                        $parsed = parse_url($src);
                        if (isset($parsed['path'])) {
                            $branding['logo_url'] = $parsed['path'];
                            $dirty = true;
                        }
                    }
                }
                
                if ($dirty) {
                    $workspace->branding_config = $branding;
                    $workspace->saveQuietly();
                    $count++;
                }
            }
        });
        
        $this->info("Cleaned up {$count} workspaces.");
    }

    private function normalizeImageUrls(array $content): array
    {
        $walk = function (&$node) use (&$walk) {
            if (is_array($node)) {
                if (isset($node['type']) && $node['type'] === 'image' && isset($node['attrs']['src'])) {
                    $src = $node['attrs']['src'];
                    if (str_starts_with($src, 'http://localhost') || str_starts_with($src, 'http://127.0.0.1')) {
                        $parsed = parse_url($src);
                        if (isset($parsed['path'])) {
                            $node['attrs']['src'] = $parsed['path'];
                        }
                    }
                }
                foreach ($node as &$child) {
                    $walk($child);
                }
            }
        };

        $walk($content);
        return $content;
    }
}
