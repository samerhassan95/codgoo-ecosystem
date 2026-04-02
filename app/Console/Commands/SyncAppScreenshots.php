<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\ServiceApp;
use Illuminate\Support\Facades\File;

class SyncAppScreenshots extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'apps:sync-screenshots';

    /**
     * The console command description.
     */
    protected $description = 'Sync app screenshots from filesystem to database';

    public function handle()
    {
        // Local folder path for screenshots
        $basePath = public_path('apps_screenshots');

        // URL base — NO /public, Laravel serves public as root
        $baseUrl = url('apps_screenshots');

        if (!File::isDirectory($basePath)) {
            $this->error('apps_screenshots directory not found.');
            return Command::FAILURE;
        }

        $apps = ServiceApp::all();

        foreach ($apps as $app) {

            $folder = $basePath . '/' . $app->slug;

            if (!File::isDirectory($folder)) {
                $this->warn("Skipping {$app->slug} (folder not found)");
                continue;
            }

            $screenshots = [];

            // Loop through all images in the folder
            foreach (File::files($folder) as $file) {

                if (!in_array(strtolower($file->getExtension()), ['png','jpg','jpeg','webp'])) {
                    continue;
                }

                $screenshots[] = [
                    'url' => $baseUrl . '/'
                        . rawurlencode($app->slug)
                        . '/'
                        . rawurlencode($file->getFilename()),

                    // Keep alt simple, filename without extension
                    'alt' => pathinfo($file->getFilename(), PATHINFO_FILENAME),
                ];
            }

            if (!empty($screenshots)) {
                $app->update(['screenshots' => $screenshots]);
                $this->info("✔ Synced {$app->slug}");
            } else {
                $this->warn("No screenshots for {$app->slug}");
            }
        }

        $this->info('✅ Screenshots sync completed.');
        return Command::SUCCESS;
    }
}
