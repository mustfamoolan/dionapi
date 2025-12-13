<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\FirebaseService;
use App\Services\NotificationService;
use Illuminate\Support\Facades\Log;

class CheckOverdueDebts extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'notifications:check-overdue-debts';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check for overdue debts and send notifications';

    protected $firebaseService;
    protected $notificationService;

    /**
     * Create a new command instance.
     */
    public function __construct(FirebaseService $firebaseService, NotificationService $notificationService)
    {
        parent::__construct();
        $this->firebaseService = $firebaseService;
        $this->notificationService = $notificationService;
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🔍 فحص الديون المتأخرة...');

        try {
            // Get overdue debts (dueDate < now and isFullyPaid = false)
            $now = new \DateTime();
            $debts = $this->firebaseService->getDebts([
                'isFullyPaid' => false,
                'dueDateBefore' => $now,
            ]);

            if (empty($debts)) {
                $this->info('✅ لا توجد ديون متأخرة');
                return 0;
            }

            // Group debts by clientUid
            $debtsByClient = [];
            foreach ($debts as $debt) {
                $clientUid = $debt['clientUid'] ?? null;
                if (!$clientUid) {
                    continue;
                }

                if (!isset($debtsByClient[$clientUid])) {
                    $debtsByClient[$clientUid] = [];
                }
                $debtsByClient[$clientUid][] = $debt;
            }

            $sentCount = 0;

            // Send notification to each client
            foreach ($debtsByClient as $clientUid => $clientDebts) {
                $totalOverdue = 0;
                foreach ($clientDebts as $debt) {
                    $totalOverdue += $debt['remainingAmount'] ?? 0;
                }

                $daysOverdue = 0;
                if (!empty($clientDebts[0]['dueDate'])) {
                    $dueDate = $clientDebts[0]['dueDate'];
                    if ($dueDate instanceof \DateTime) {
                        $daysOverdue = (int) floor((time() - $dueDate->getTimestamp()) / (60 * 60 * 24));
                    }
                }

                $result = $this->notificationService->sendToUser($clientUid, [
                    'title' => 'ديون متأخرة ⚠️',
                    'body' => "لديك " . count($clientDebts) . " دين متأخر بقيمة " . number_format($totalOverdue) . " IQD"
                ], [
                    'type' => 'overdue_debt',
                    'count' => (string) count($clientDebts),
                    'total_amount' => (string) $totalOverdue,
                    'days_overdue' => (string) $daysOverdue,
                ]);

                if ($result && $result['success']) {
                    $sentCount++;
                    $this->info("✅ تم إرسال إشعار للعميل: {$clientUid}");
                } else {
                    $this->warn("⚠️ فشل إرسال إشعار للعميل: {$clientUid}");
                }
            }

            $this->info("✅ تم إرسال {$sentCount} إشعار من أصل " . count($debtsByClient));
            return 0;

        } catch (\Exception $e) {
            Log::error('Error in CheckOverdueDebts: ' . $e->getMessage());
            $this->error('❌ حدث خطأ: ' . $e->getMessage());
            return 1;
        }
    }
}

