<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\FirebaseService;
use App\Services\NotificationService;
use Illuminate\Support\Facades\Log;

class CheckExpiredSubscriptions extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'notifications:check-expired-subscriptions';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check for expired and expiring soon subscriptions and send notifications';

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
        $this->info('🔍 فحص الاشتراكات المنتهية...');

        try {
            $now = new \DateTime();
            $fiveDaysLater = clone $now;
            $fiveDaysLater->modify('+5 days');

            // Get all active clients
            $clients = $this->firebaseService->getClientsByFilter(['status' => 'active', 'is_active' => true]);

            $expiredCount = 0;
            $expiringSoonCount = 0;

            foreach ($clients as $client) {
                $expiresAt = $client['activation_expires_at'] ?? null;
                if (!$expiresAt) {
                    continue;
                }

                // Convert to DateTime if needed
                if (is_int($expiresAt)) {
                    $expiresAt = new \DateTime('@' . $expiresAt);
                } elseif (!($expiresAt instanceof \DateTime)) {
                    continue;
                }

                $firebaseUid = $client['firebase_uid'];

                // Check if expired today
                $expiresDate = clone $expiresAt;
                $expiresDate->setTime(0, 0, 0);
                $today = clone $now;
                $today->setTime(0, 0, 0);

                if ($expiresDate <= $today) {
                    // Update status to expired
                    $this->firebaseService->updateClientStatus($firebaseUid, 'expired');

                    // Send expired notification
                    $result = $this->notificationService->sendToUser($firebaseUid, [
                        'title' => 'انتهى اشتراكك ❌',
                        'body' => 'اشتراكك انتهى، يرجى التجديد'
                    ], [
                        'type' => 'subscription_expired',
                        'expired_at' => $expiresAt->format('Y-m-d'),
                    ]);

                    if ($result && $result['success']) {
                        $expiredCount++;
                        $this->info("✅ تم إرسال إشعار انتهاء الاشتراك للعميل: {$firebaseUid}");
                    }
                }
                // Check if expiring within 5 days
                elseif ($expiresAt <= $fiveDaysLater && $expiresAt > $now) {
                    $daysLeft = (int) ceil(($expiresAt->getTimestamp() - time()) / (60 * 60 * 24));

                    $result = $this->notificationService->sendToUser($firebaseUid, [
                        'title' => 'اشتراكك ينتهي قريباً ⏰',
                        'body' => "باقي {$daysLeft} أيام على انتهاء اشتراكك"
                    ], [
                        'type' => 'subscription_expiring_soon',
                        'days_left' => (string) $daysLeft,
                        'expires_at' => $expiresAt->format('Y-m-d'),
                    ]);

                    if ($result && $result['success']) {
                        $expiringSoonCount++;
                        $this->info("✅ تم إرسال إشعار قرب انتهاء الاشتراك للعميل: {$firebaseUid}");
                    }
                }
            }

            $this->info("✅ تم إرسال {$expiredCount} إشعار انتهاء و {$expiringSoonCount} إشعار قرب الانتهاء");
            return 0;

        } catch (\Exception $e) {
            Log::error('Error in CheckExpiredSubscriptions: ' . $e->getMessage());
            $this->error('❌ حدث خطأ: ' . $e->getMessage());
            return 1;
        }
    }
}

