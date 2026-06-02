<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Symfony\Component\Finder\SplFileInfo;

#[Signature('app:reset {--force : Skip confirmation prompt}')]
#[Description('Reset the application to a clean state for testing: drops and re-migrates the database, clears file sessions, and purges compiled views.')]
class ResetAppCommand extends Command
{
    public function handle(): int
    {
        if (! $this->option('force') && ! $this->confirm('This will drop all database tables and log out every user. Continue?')) {
            $this->components->warn('Aborted.');

            return self::FAILURE;
        }

        $this->components->info('Resetting application state...');

        $this->call('migrate:fresh', ['--seed' => true, '--force' => true]);

        $this->clearFileSessions();

        $this->call('view:clear');

        $this->components->success('Application reset complete.');

        return self::SUCCESS;
    }

    private function clearFileSessions(): void
    {
        $path = storage_path('framework/sessions');

        if (! File::isDirectory($path)) {
            return;
        }

        $deleted = 0;

        foreach (File::files($path) as $file) {
            /** @var SplFileInfo $file */
            if ($file->getFilename() === '.gitignore') {
                continue;
            }

            File::delete($file->getRealPath());
            $deleted++;
        }

        $this->components->task("Cleared {$deleted} session file(s)");
    }
}
