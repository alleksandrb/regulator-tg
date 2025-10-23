<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Repositories\TelegramAccountRepository;
use App\Repositories\ProxyRepository;
use App\Factories\ProxyDTOFactory;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use App\Models\AccountImport;
use Illuminate\Support\Facades\DB;

class ProcessTelegramAccountsUpload implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Base directory (relative to local storage) where batch files are stored
     */
    private string $batchDir;

    /**
     * Create a new job instance.
     */
    public function __construct(string $batchDir)
    {
        $this->batchDir = $batchDir; // e.g. telegram/uploads/{uuid}
    }

    /**
     * Execute the job.
     */
    public function handle(
        TelegramAccountRepository $accountRepository,
        ProxyRepository $proxyRepository,
        ProxyDTOFactory $proxyDTOFactory
    ): void {
        $base = rtrim($this->batchDir, '/');
        $batchId = basename($base);
        $import = AccountImport::query()->where('batch_id', $batchId)->first();
        if ($import) {
            $import->update([
                'status' => 'processing',
                'started_at' => now(),
            ]);
        }
        $accountsDir = $base . '/accounts';
        $proxyFilePath = $base . '/proxy.txt';

        $createdCount = 0;
        $failedCount = 0;
        $skippedCount = 0;
        $providedProxyIds = [];

        try {
            // 1) Create/update proxies if provided
            if (Storage::disk('local')->exists($proxyFilePath)) {
                try {
                    $proxyContent = Storage::disk('local')->get($proxyFilePath);
                    $proxies = $proxyDTOFactory->createFromFile($proxyContent);
                    foreach ($proxies as $proxyDto) {
                        $created = $proxyRepository->createProxy($proxyDto);
                        if ($created->id !== null) {
                            $providedProxyIds[] = $created->id;
                        }
                    }
                } catch (\Throwable $e) {
                    Log::error('Failed to process proxy file for batch: ' . $e->getMessage(), [
                        'batch_dir' => $this->batchDir,
                    ]);
                }
            }

            // 2) Process accounts in index order 0..N
            $index = 0;
            $proxyIndex = 0;
            while (true) {
                $sessionPath = $accountsDir . '/' . $index . '.session';
                $jsonPath = $accountsDir . '/' . $index . '.json';
                if (!Storage::disk('local')->exists($sessionPath) || !Storage::disk('local')->exists($jsonPath)) {
                    break;
                }

                try {
                    $session = Storage::disk('local')->get($sessionPath);
                    $jsonData = Storage::disk('local')->get($jsonPath);

                    $jsonDecoded = json_decode($jsonData, true);
                    $userId = isset($jsonDecoded['user_id']) ? (string) $jsonDecoded['user_id'] : null;

                    if ($userId && $accountRepository->existsByUserId($userId)) {
                        $skippedCount++;
                        $index++;
                        continue;
                    }

                    $proxyId = null;
                    if ($proxyIndex < count($providedProxyIds)) {
                        $proxyId = $providedProxyIds[$proxyIndex];
                        $proxyIndex++;
                    }

                    $accountRepository->createAccount($session, $jsonData, (string) $userId, $proxyId);
                    $createdCount++;
                } catch (\Throwable $e) {
                    $failedCount++;
                    Log::error('Failed to create account from batch: ' . $e->getMessage(), [
                        'batch_dir' => $this->batchDir,
                        'index' => $index,
                    ]);
                }

                $index++;
            }

            Log::info('Batch accounts processing completed', [
                'batch_dir' => $this->batchDir,
                'created' => $createdCount,
                'skipped' => $skippedCount,
                'failed' => $failedCount,
            ]);
            if ($import) {
                $import->update([
                    'created_count' => $createdCount,
                    'failed_count' => $failedCount,
                    'skipped_count' => $skippedCount,
                    'status' => 'completed',
                    'finished_at' => now(),
                ]);
            }
        } finally {
            // Cleanup batch directory
            try {
                Storage::disk('local')->deleteDirectory($base);
            } catch (\Throwable $e) {
                Log::warning('Failed to cleanup batch directory', [
                    'batch_dir' => $this->batchDir,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }
}


