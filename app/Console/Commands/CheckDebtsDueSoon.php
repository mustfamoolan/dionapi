<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\FirebaseService;
use App\Services\NotificationService;
use Illuminate\Support\Facades\Log;

class CheckDebtsDueSoon extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'notifications:check-debts-due-soon';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check for debts due soon and send notifications';

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
        $this->info('🔍 فحص مواعيد السداد القريبة...');

        try {
            $now = new \DateTime();
            $twoDaysLater = clone $now;
            $twoDaysLater->modify('+2 days');
            $tomorrow = clone $now;
            $tomorrow->modify('+1 day');

            // Get debts due within 2 days
            $debts = $this->firebaseService->getDebts([
                'isFullyPaid' => false,
                'dueDateAfter' => $tomorrow,
                'dueDateBefore' => $twoDaysLater,
            ]);

            if (empty($debts)) {
                $this->info('✅ لا توجد ديون قريبة الاستحقاق');
                return 0;
            }

            $sentCount = 0;

            // Send notification for each debt
            foreach ($debts as $debt) {
                $clientUid = $debt['clientUid'] ?? null;
                if (!$clientUid) {
                    continue;
                }

                $dueDate = $debt['dueDate'] ?? null;
                $daysLeft = 0;

                if ($dueDate instanceof \DateTime) {
                    $daysLeft = (int) ceil(($dueDate->getTimestamp() - time()) / (60 * 60 * 24));
                }

                $amount = $debt['remainingAmount'] ?? 0;

                $result = $this->notificationService->sendToUser($clientUid, [
                    'title' => 'موعد سداد قريب 📅',
                    'body' => "باقي {$daysLeft} يوم على موعد السداد - المبلغ: " . number_format($amount) . " IQD"
                ], [
                    'type' => 'debt_due_soon',
                    'debt_id' => $debt['id'] ?? '',
                    'days_left' => (string) $daysLeft,
                    'amount' => (string) $amount,
                ]);

                if ($result && $result['success']) {
                    $sentCount++;
                    $this->info("✅ تم إرسال إشعار للعميل: {$clientUid}");
                } else {
                    $this->warn("⚠️ فشل إرسال إشعار للعميل: {$clientUid}");
                }
            }

            $this->info("✅ تم إرسال {$sentCount} إشعار من أصل " . count($debts));
            return 0;

        } catch (\Exception $e) {
            Log::error('Error in CheckDebtsDueSoon: ' . $e->getMessage());
            $this->error('❌ حدث خطأ: ' . $e->getMessage());
            return 1;
        }
    }
}

