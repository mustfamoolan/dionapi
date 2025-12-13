<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\FirebaseService;
use App\Services\NotificationService;
use Illuminate\Support\Facades\Log;

class CheckLowStock extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'notifications:check-low-stock';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check for low stock products and send notifications';

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
        $this->info('🔍 فحص المخزون المنخفض...');

        try {
            // Get products with low stock
            $products = $this->firebaseService->getProducts([
                'lowStockOnly' => true,
            ]);

            if (empty($products)) {
                $this->info('✅ لا توجد منتجات بمخزون منخفض');
                return 0;
            }

            // Group products by clientUid
            $productsByClient = [];
            foreach ($products as $product) {
                $clientUid = $product['clientUid'] ?? null;
                if (!$clientUid) {
                    continue;
                }

                if (!isset($productsByClient[$clientUid])) {
                    $productsByClient[$clientUid] = [];
                }
                $productsByClient[$clientUid][] = $product;
            }

            $sentCount = 0;

            // Send notification to each client
            foreach ($productsByClient as $clientUid => $clientProducts) {
                $productNames = array_map(function ($p) {
                    return $p['name'] ?? 'غير معروف';
                }, $clientProducts);

                $result = $this->notificationService->sendToUser($clientUid, [
                    'title' => 'مخزون منخفض 📦',
                    'body' => count($clientProducts) . " منتج تحتاج إلى تعبئة"
                ], [
                    'type' => 'low_stock',
                    'count' => (string) count($clientProducts),
                    'products' => json_encode($productNames),
                ]);

                if ($result && $result['success']) {
                    $sentCount++;
                    $this->info("✅ تم إرسال إشعار للعميل: {$clientUid}");
                } else {
                    $this->warn("⚠️ فشل إرسال إشعار للعميل: {$clientUid}");
                }
            }

            $this->info("✅ تم إرسال {$sentCount} إشعار من أصل " . count($productsByClient));
            return 0;

        } catch (\Exception $e) {
            Log::error('Error in CheckLowStock: ' . $e->getMessage());
            $this->error('❌ حدث خطأ: ' . $e->getMessage());
            return 1;
        }
    }
}

