<?php

namespace App\Console\Commands;

use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Process;

class DeployCommand extends Command
{
     /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:deploy {--force : Force deployment without confirmation}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Deploy the application with latest updates';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('🚀 Starting deployment process...');

        try {
            // Put application in maintenance mode
            $this->info('🔧 Enabling maintenance mode...');
            $this->call('down');

            // Execute deploy.sh script
            $deployScriptPath = base_path('deploy.sh');

            if (!file_exists($deployScriptPath)) {
                throw new Exception('Deploy script not found at: ' . $deployScriptPath);
            }

            $this->info('� Executing deployment script...');

            // Make script executable (for Unix-like systems)
            if (PHP_OS_FAMILY !== 'Windows') {
                Process::run('chmod +x ' . $deployScriptPath);
            }

            // Execute the deploy script
            $result = Process::timeout(60*6)->run('sudo /bin/sh /var/www/turbo_restaurant/larament/deploy.sh');

            if ($result->failed()) {
                throw new Exception('Deployment script failed: ' . $result->errorOutput());
            }

            // Show script output
            if ($result->output()) {
                $this->line($result->output());
            }

            // Bring application back online
            $this->info('🌐 Bringing application back online...');
            $this->call('up');

            $this->newLine();
            $this->info('✅ Deployment completed successfully!');
            $this->info('🎉 Application is now live with the latest updates.');

            return Command::SUCCESS;

        } catch (Exception $e) {
            $this->error('❌ Deployment failed: ' . $e->getMessage());

            // Try to bring the application back online
            $this->warn('⚠️ Attempting to bring application back online...');
            $this->call('up');

            return Command::FAILURE;
        }

    }

}
