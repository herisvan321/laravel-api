<?php

namespace App\Console\Commands;

use FilesystemIterator;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use Symfony\Component\Process\Process;

class OctaneDevCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'octane:dev
                            {--host=127.0.0.1 : The IP address the server should bind to}
                            {--port=8000 : The port the server should bind to}
                            {--server= : The Octane server to use (frankenphp, swoole)}
                            {--workers=auto : The number of workers}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Start Laravel Octane with pure PHP auto-reload file watcher (zero node_modules)';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $host = $this->option('host');
        $port = $this->option('port');
        $server = $this->option('server') ?: config('octane.server', 'frankenphp');
        $workers = $this->option('workers');

        // Pastikan tidak ada server octane gantung sebelumnya
        @Artisan::call('octane:stop');

        $command = [
            PHP_BINARY,
            base_path('artisan'),
            'octane:start',
            "--server={$server}",
            "--host={$host}",
            "--port={$port}",
            "--workers={$workers}",
        ];

        $process = new Process($command, base_path(), null, null, null);
        $process->start();

        // Tangani sinyal Ctrl+C / SIGINT
        if (function_exists('pcntl_async_signals')) {
            pcntl_async_signals(true);
            pcntl_signal(SIGINT, function () use ($process) {
                $this->stopServer($process);
                exit(0);
            });
            pcntl_signal(SIGTERM, function () use ($process) {
                $this->stopServer($process);
                exit(0);
            });
        }

        $this->components->info("Laravel Octane ({$server}) running on http://{$host}:{$port}");
        $this->components->info('Auto-reload: ENABLED (Pure PHP - monitoring all project files)');
        $this->output->writeln('  <fg=yellow>Press Ctrl+C to stop the server</>'.PHP_EOL);

        // Ambil snapshot awal semua file
        $lastSnapshot = $this->getFileSnapshot();

        try {
            while ($process->isRunning()) {
                // Tampilkan log/output dari server
                $output = $process->getIncrementalOutput();
                if ($output !== '') {
                    $this->output->write($output);
                }

                $errorOutput = $process->getIncrementalErrorOutput();
                if ($errorOutput !== '') {
                    $this->output->write($errorOutput);
                }

                // Cek perubahan file setiap 500ms
                $currentSnapshot = $this->getFileSnapshot();
                $changedFile = $this->detectChange($lastSnapshot, $currentSnapshot);

                if ($changedFile !== null) {
                    $this->components->warn("File change detected: [{$changedFile}]. Reloading workers…");
                    Artisan::call('octane:reload');
                    $lastSnapshot = $currentSnapshot;
                }

                usleep(500000); // 500ms
            }

            $this->output->write($process->getIncrementalOutput());
            $this->output->write($process->getIncrementalErrorOutput());
        } finally {
            $this->stopServer($process);
        }

        return $process->getExitCode() ?? 0;
    }

    /**
     * Stop the Octane server process.
     */
    protected function stopServer(Process $process): void
    {
        if ($process->isRunning()) {
            $process->stop(2);
        }
        @Artisan::call('octane:stop');
    }

    /**
     * Ambil map semua file yang diawasi beserta mtime dan size.
     *
     * @return array<string, string>
     */
    protected function getFileSnapshot(): array
    {
        $snapshot = [];
        $watchDirs = [
            base_path('app'),
            base_path('bootstrap'),
            base_path('config'),
            base_path('database'),
            base_path('routes'),
        ];

        foreach ($watchDirs as $dir) {
            if (! is_dir($dir)) {
                continue;
            }

            $iterator = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS)
            );

            foreach ($iterator as $file) {
                if ($file->isFile()) {
                    $pathname = $file->getPathname();
                    $snapshot[$pathname] = $file->getMTime().':'.$file->getSize();
                }
            }
        }

        // File spesifik di root
        $specificFiles = [
            base_path('.env'),
            base_path('composer.json'),
            base_path('composer.lock'),
        ];

        foreach ($specificFiles as $file) {
            if (file_exists($file)) {
                $snapshot[$file] = filemtime($file).':'.filesize($file);
            }
        }

        return $snapshot;
    }

    /**
     * Deteksi apakah ada file yang ditambah, diubah, atau dihapus.
     */
    protected function detectChange(array $old, array $new): ?string
    {
        // Cek file baru atau dimodifikasi
        foreach ($new as $file => $hash) {
            if (! isset($old[$file]) || $old[$file] !== $hash) {
                return str_replace(base_path().'/', '', $file);
            }
        }

        // Cek file yang dihapus
        foreach ($old as $file => $hash) {
            if (! isset($new[$file])) {
                return str_replace(base_path().'/', '', $file);
            }
        }

        return null;
    }
}
