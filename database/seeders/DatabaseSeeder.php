<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Carbon\Carbon;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // 1. Create Sample Tenants (Merchants)
        $this->createTenants();

        // 2. Create Global Customers
        $this->createGlobalCustomers();

        // 3. Create Customer Memberships
        $this->createCustomerMemberships();

        // 4. Create Points Settings
        $this->createPointsSettings();

        // 5. Create Tiers
        $this->createTiers();

        // 6. Create Staff
        $this->createStaff();

        // 7. Create Rewards
        $this->createRewards();

        // 8. Create Sample Transactions
        $this->createTransactions();

        // 9. Create Sample Redemptions
        $this->createRedemptions();

        // 10. Create Sample Notifications
        $this->createNotifications();

        $this->command->info('✅ Database seeded successfully!');
    }

    private function createTenants(): void
    {
        $tenants = [
            [
                'business_name' => 'Café Aroma',
                'business_slug' => 'cafe-aroma',
                'business_type' => 'cafe',
                'email' => 'owner@cafearoma.com',
                'phone' => '+962791234567',
                'logo_url' => null,
                'primary_color' => '#8B4513',
                'subscription_plan' => 'professional',
                'subscription_status' => 'active',
                'subscription_expires_at' => Carbon::now()->addMonths(3),
                'max_customers' => 2000,
                'max_staff' => 10,
                'trial_ends_at' => Carbon::now()->subMonths(1),
                'api_key' => Str::random(40),
            ],
            [
                'business_name' => 'Fresh Fitness Gym',
                'business_slug' => 'fresh-fitness',
                'business_type' => 'gym',
                'email' => 'admin@freshfitness.com',
                'phone' => '+962792345678',
                'logo_url' => null,
                'primary_color' => '#FF6B35',
                'subscription_plan' => 'starter',
                'subscription_status' => 'active',
                'subscription_expires_at' => Carbon::now()->addMonth(),
                'max_customers' => 500,
                'max_staff' => 3,
                'trial_ends_at' => Carbon::now()->subWeeks(2),
                'api_key' => Str::random(40),
            ],
            [
                'business_name' => 'Bella Salon & Spa',
                'business_slug' => 'bella-salon',
                'business_type' => 'salon',
                'email' => 'info@bellasalon.com',
                'phone' => '+962793456789',
                'logo_url' => null,
                'primary_color' => '#E91E63',
                'subscription_plan' => 'free_trial',
                'subscription_status' => 'trial',
                'subscription_expires_at' => null,
                'max_customers' => 50,
                'max_staff' => 2,
                'trial_ends_at' => Carbon::now()->addWeek(),
                'api_key' => Str::random(40),
            ],
        ];

        foreach ($tenants as $tenant) {
            \DB::table('tenants')->insert(array_merge($tenant, [
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ]));
        }

        $this->command->info('✓ Created 3 sample tenants');
    }

    private function createGlobalCustomers(): void
    {
        $customers = [
            [
                'phone_number' => '+962790000001',
                'email' => 'ahmad.khalil@example.com',
                'full_name' => 'Ahmad Khalil',
                'date_of_birth' => '1990-05-15',
                'password_hash' => Hash::make('password123'),
                'device_token' => 'fcm_token_ahmad_' . Str::random(20),
                'language' => 'ar',
                'phone_verified_at' => Carbon::now(),
                'email_verified_at' => Carbon::now(),
            ],
            [
                'phone_number' => '+962790000002',
                'email' => 'sara.hassan@example.com',
                'full_name' => 'Sara Hassan',
                'date_of_birth' => '1995-08-22',
                'password_hash' => Hash::make('password123'),
                'device_token' => 'fcm_token_sara_' . Str::random(20),
                'language' => 'ar',
                'phone_verified_at' => Carbon::now(),
                'email_verified_at' => Carbon::now(),
            ],
            [
                'phone_number' => '+962790000003',
                'email' => 'omar.abdullah@example.com',
                'full_name' => 'Omar Abdullah',
                'date_of_birth' => '1988-12-10',
                'password_hash' => Hash::make('password123'),
                'device_token' => 'fcm_token_omar_' . Str::random(20),
                'language' => 'en',
                'phone_verified_at' => Carbon::now(),
                'email_verified_at' => Carbon::now(),
            ],
            [
                'phone_number' => '+962790000004',
                'email' => 'layla.mansour@example.com',
                'full_name' => 'Layla Mansour',
                'date_of_birth' => '1992-03-18',
                'password_hash' => Hash::make('password123'),
                'device_token' => 'fcm_token_layla_' . Str::random(20),
                'language' => 'ar',
                'phone_verified_at' => Carbon::now(),
                'email_verified_at' => null,
            ],
            [
                'phone_number' => '+962790000005',
                'email' => 'kareem.yousef@example.com',
                'full_name' => 'Kareem Yousef',
                'date_of_birth' => '1985-11-25',
                'password_hash' => Hash::make('password123'),
                'device_token' => 'fcm_token_kareem_' . Str::random(20),
                'language' => 'ar',
                'phone_verified_at' => Carbon::now(),
                'email_verified_at' => Carbon::now(),
            ],
        ];

        foreach ($customers as $customer) {
            \DB::table('global_customers')->insert(array_merge($customer, [
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ]));
        }

        $this->command->info('✓ Created 5 global customers');
    }

    private function createCustomerMemberships(): void
    {
        $memberships = [
            // Café Aroma memberships
            ['global_customer_id' => 1, 'tenant_id' => 1, 'current_points' => 250, 'total_points_earned' => 450, 'total_visits' => 8, 'tier_level' => 'bronze', 'total_spent' => 120.50],
            ['global_customer_id' => 2, 'tenant_id' => 1, 'current_points' => 750, 'total_points_earned' => 1200, 'total_visits' => 15, 'tier_level' => 'silver', 'total_spent' => 350.00],
            ['global_customer_id' => 3, 'tenant_id' => 1, 'current_points' => 2100, 'total_points_earned' => 2800, 'total_visits' => 32, 'tier_level' => 'gold', 'total_spent' => 890.00],

            // Fresh Fitness memberships
            ['global_customer_id' => 1, 'tenant_id' => 2, 'current_points' => 3500, 'total_points_earned' => 4200, 'total_visits' => 45, 'tier_level' => 'platinum', 'total_spent' => 1200.00],
            ['global_customer_id' => 4, 'tenant_id' => 2, 'current_points' => 150, 'total_points_earned' => 150, 'total_visits' => 3, 'tier_level' => 'bronze', 'total_spent' => 90.00],

            // Bella Salon memberships
            ['global_customer_id' => 2, 'tenant_id' => 3, 'current_points' => 420, 'total_points_earned' => 650, 'total_visits' => 5, 'tier_level' => 'bronze', 'total_spent' => 180.00],
            ['global_customer_id' => 5, 'tenant_id' => 3, 'current_points' => 1800, 'total_points_earned' => 2500, 'total_visits' => 12, 'tier_level' => 'gold', 'total_spent' => 550.00],
        ];

        foreach ($memberships as $membership) {
            \DB::table('customer_memberships')->insert(array_merge($membership, [
                'qr_code_hash' => 'QR-' . Str::random(20),
                'membership_status' => 'active',
                'joined_at' => Carbon::now()->subMonths(rand(1, 6)),
                'last_visit_at' => Carbon::now()->subDays(rand(1, 30)),
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ]));
        }

        $this->command->info('✓ Created 7 customer memberships');
    }

    private function createPointsSettings(): void
    {
        $settings = [
            // Café Aroma: 1 JOD = 10 points
            [
                'tenant_id' => 1,
                'currency_to_points_ratio' => 10.00,
                'points_expiry_months' => 12,
                'allow_partial_redemption' => true,
                'min_points_for_redemption' => 50,
                'welcome_bonus_points' => 50,
                'birthday_bonus_points' => 100,
                'referrer_bonus_points' => 200,
                'referee_bonus_points' => 100,
            ],

            // Fresh Fitness: 1 JOD = 5 points
            [
                'tenant_id' => 2,
                'currency_to_points_ratio' => 5.00,
                'points_expiry_months' => 6,
                'allow_partial_redemption' => false,
                'min_points_for_redemption' => 100,
                'welcome_bonus_points' => 100,
                'birthday_bonus_points' => 200,
                'referrer_bonus_points' => 300,
                'referee_bonus_points' => 150,
            ],

            // Bella Salon: 1 JOD = 20 points
            [
                'tenant_id' => 3,
                'currency_to_points_ratio' => 20.00,
                'points_expiry_months' => null, // Never expire
                'allow_partial_redemption' => true,
                'min_points_for_redemption' => 100,
                'welcome_bonus_points' => 100,
                'birthday_bonus_points' => 250,
                'referrer_bonus_points' => 500,
                'referee_bonus_points' => 250,
            ],
        ];

        foreach ($settings as $setting) {
            \DB::table('points_settings')->insert(array_merge($setting, [
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ]));
        }

        $this->command->info('✓ Created points settings for 3 tenants');
    }

    private function createTiers(): void
    {
        $tiersPerTenant = [
            // Café Aroma tiers
            ['tenant_id' => 1, 'name' => 'Bronze', 'level' => 'bronze', 'min_points' => 0, 'points_multiplier' => 1.00, 'icon' => '🥉', 'color' => '#CD7F32', 'benefits' => 'Standard rewards access'],
            ['tenant_id' => 1, 'name' => 'Silver', 'level' => 'silver', 'min_points' => 500, 'points_multiplier' => 1.50, 'icon' => '🥈', 'color' => '#C0C0C0', 'benefits' => '1.5x points multiplier, Priority service'],
            ['tenant_id' => 1, 'name' => 'Gold', 'level' => 'gold', 'min_points' => 1500, 'points_multiplier' => 2.00, 'icon' => '🥇', 'color' => '#FFD700', 'benefits' => '2x points multiplier, Free delivery, Exclusive events'],
            ['tenant_id' => 1, 'name' => 'Platinum', 'level' => 'platinum', 'min_points' => 3000, 'points_multiplier' => 3.00, 'icon' => '💎', 'color' => '#E5E4E2', 'benefits' => '3x points multiplier, VIP lounge access, Birthday surprise'],

            // Fresh Fitness tiers
            ['tenant_id' => 2, 'name' => 'Bronze', 'level' => 'bronze', 'min_points' => 0, 'points_multiplier' => 1.00, 'icon' => '🥉', 'color' => '#CD7F32', 'benefits' => 'Basic member benefits'],
            ['tenant_id' => 2, 'name' => 'Silver', 'level' => 'silver', 'min_points' => 600, 'points_multiplier' => 1.25, 'icon' => '🥈', 'color' => '#C0C0C0', 'benefits' => '1.25x points, Free fitness assessment'],
            ['tenant_id' => 2, 'name' => 'Gold', 'level' => 'gold', 'min_points' => 1800, 'points_multiplier' => 1.75, 'icon' => '🥇', 'color' => '#FFD700', 'benefits' => '1.75x points, Personal trainer session'],
            ['tenant_id' => 2, 'name' => 'Platinum', 'level' => 'platinum', 'min_points' => 3500, 'points_multiplier' => 2.50, 'icon' => '💎', 'color' => '#E5E4E2', 'benefits' => '2.5x points, Unlimited group classes, Nutrition consultation'],

            // Bella Salon tiers
            ['tenant_id' => 3, 'name' => 'Bronze', 'level' => 'bronze', 'min_points' => 0, 'points_multiplier' => 1.00, 'icon' => '🥉', 'color' => '#CD7F32', 'benefits' => 'Standard rewards'],
            ['tenant_id' => 3, 'name' => 'Silver', 'level' => 'silver', 'min_points' => 500, 'points_multiplier' => 1.40, 'icon' => '🥈', 'color' => '#C0C0C0', 'benefits' => '1.4x points, 10% discount on products'],
            ['tenant_id' => 3, 'name' => 'Gold', 'level' => 'gold', 'min_points' => 1500, 'points_multiplier' => 2.00, 'icon' => '🥇', 'color' => '#FFD700', 'benefits' => '2x points, 15% discount, Free hair mask'],
            ['tenant_id' => 3, 'name' => 'Platinum', 'level' => 'platinum', 'min_points' => 3000, 'points_multiplier' => 3.00, 'icon' => '💎', 'color' => '#E5E4E2', 'benefits' => '3x points, 20% discount, VIP treatment'],
        ];

        foreach ($tiersPerTenant as $index => $tier) {
            \DB::table('tiers')->insert(array_merge($tier, [
                'display_order' => $index + 1,
                'is_active' => true,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ]));
        }

        $this->command->info('✓ Created 12 tier definitions (4 per tenant)');
    }

    private function createStaff(): void
    {
        $staff = [
            // Café Aroma staff
            [
                'tenant_id' => 1,
                'full_name' => 'Noor Al-Ahmad',
                'email' => 'noor@cafearoma.com',
                'phone' => '+962797777001',
                'password_hash' => Hash::make('staff123'),
                'role' => 'admin',
                'permissions' => json_encode(['can_scan_qr' => true, 'can_add_points' => true, 'can_redeem' => true, 'can_view_reports' => true, 'can_manage_staff' => true]),
                'is_active' => true,
            ],
            [
                'tenant_id' => 1,
                'full_name' => 'Zaid Ibrahim',
                'email' => 'zaid@cafearoma.com',
                'phone' => '+962797777002',
                'password_hash' => Hash::make('staff123'),
                'role' => 'staff',
                'permissions' => json_encode(['can_scan_qr' => true, 'can_add_points' => true, 'can_redeem' => true, 'can_view_reports' => false, 'can_manage_staff' => false]),
                'is_active' => true,
            ],

            // Fresh Fitness staff
            [
                'tenant_id' => 2,
                'full_name' => 'Hala Mustafa',
                'email' => 'hala@freshfitness.com',
                'phone' => '+962797777003',
                'password_hash' => Hash::make('staff123'),
                'role' => 'admin',
                'permissions' => json_encode(['can_scan_qr' => true, 'can_add_points' => true, 'can_redeem' => true, 'can_view_reports' => true, 'can_manage_staff' => true]),
                'is_active' => true,
            ],

            // Bella Salon staff
            [
                'tenant_id' => 3,
                'full_name' => 'Rania Saleh',
                'email' => 'rania@bellasalon.com',
                'phone' => '+962797777004',
                'password_hash' => Hash::make('staff123'),
                'role' => 'manager',
                'permissions' => json_encode(['can_scan_qr' => true, 'can_add_points' => true, 'can_redeem' => true, 'can_view_reports' => true, 'can_manage_staff' => false]),
                'is_active' => true,
            ],
        ];

        foreach ($staff as $member) {
            \DB::table('staff')->insert(array_merge($member, [
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ]));
        }

        $this->command->info('✓ Created 4 staff members');
    }

    private function createRewards(): void
    {
        $rewards = [
            // Café Aroma rewards
            [
                'tenant_id' => 1,
                'title_ar' => 'قهوة مجانية',
                'title_en' => 'Free Coffee',
                'description_ar' => 'قهوة ساخنة مجانية (حجم وسط)',
                'description_en' => 'Free hot coffee (medium size)',
                'category' => 'drink',
                'reward_type' => 'free_product',
                'points_required' => 100,
                'stock' => null,
                'is_active' => true,
            ],
            [
                'tenant_id' => 1,
                'title_ar' => 'خصم 20%',
                'title_en' => '20% Discount',
                'description_ar' => 'خصم 20% على أي مشروب',
                'description_en' => '20% off any beverage',
                'category' => 'discount',
                'reward_type' => 'percentage_discount',
                'discount_value' => 20.00,
                'points_required' => 150,
                'stock' => null,
                'is_active' => true,
            ],
            [
                'tenant_id' => 1,
                'title_ar' => 'كيك مجاني',
                'title_en' => 'Free Cake Slice',
                'description_ar' => 'قطعة كيك مجانية من اختيارك',
                'description_en' => 'Free cake slice of your choice',
                'category' => 'food',
                'reward_type' => 'free_product',
                'points_required' => 200,
                'stock' => 50,
                'is_active' => true,
                'min_tier_required' => 'silver',
            ],

            // Fresh Fitness rewards
            [
                'tenant_id' => 2,
                'title_ar' => 'جلسة مجانية مع مدرب',
                'title_en' => 'Free Personal Training Session',
                'description_ar' => 'جلسة تدريب مجانية لمدة 60 دقيقة',
                'description_en' => 'Free 60-minute personal training session',
                'category' => 'experience',
                'reward_type' => 'experience',
                'points_required' => 500,
                'stock' => 20,
                'is_active' => true,
            ],
            [
                'tenant_id' => 2,
                'title_ar' => 'خصم 15% على الاشتراك الشهري',
                'title_en' => '15% Off Monthly Membership',
                'description_ar' => 'خصم 15% على تجديد الاشتراك الشهري',
                'description_en' => '15% off monthly membership renewal',
                'category' => 'discount',
                'reward_type' => 'percentage_discount',
                'discount_value' => 15.00,
                'points_required' => 300,
                'stock' => null,
                'is_active' => true,
                'min_tier_required' => 'silver',
            ],

            // Bella Salon rewards
            [
                'tenant_id' => 3,
                'title_ar' => 'قص شعر مجاني',
                'title_en' => 'Free Haircut',
                'description_ar' => 'قص شعر مجاني',
                'description_en' => 'Complimentary haircut',
                'category' => 'service',
                'reward_type' => 'free_product',
                'points_required' => 400,
                'stock' => 30,
                'is_active' => true,
            ],
            [
                'tenant_id' => 3,
                'title_ar' => 'خصم 10 دنانير',
                'title_en' => '10 JOD Off',
                'description_ar' => 'خصم 10 دنانير على أي خدمة',
                'description_en' => '10 JOD off any service',
                'category' => 'discount',
                'reward_type' => 'fixed_discount',
                'discount_value' => 10.00,
                'points_required' => 300,
                'stock' => null,
                'is_active' => true,
            ],
        ];

        foreach ($rewards as $reward) {
            \DB::table('rewards')->insert(array_merge($reward, [
                'display_order' => 0,
                'total_redemptions' => 0,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ]));
        }

        $this->command->info('✓ Created 7 rewards');
    }

    private function createTransactions(): void
    {
        // Sample transactions for customer_membership_id = 1 (Ahmad at Café Aroma)
        $transactions = [
            [
                'tenant_id' => 1,
                'customer_membership_id' => 1,
                'type' => 'earn',
                'points' => 50,
                'amount' => null,
                'description' => 'Welcome bonus',
                'balance_after' => 50,
                'created_at' => Carbon::now()->subMonths(2),
            ],
            [
                'tenant_id' => 1,
                'customer_membership_id' => 1,
                'type' => 'earn',
                'points' => 150,
                'amount' => 15.00,
                'description' => 'Purchase: 15 JOD',
                'balance_after' => 200,
                'created_at' => Carbon::now()->subMonths(2)->addDays(5),
            ],
            [
                'tenant_id' => 1,
                'customer_membership_id' => 1,
                'type' => 'redeem',
                'points' => -100,
                'description' => 'Redeemed: Free Coffee',
                'reference_id' => 1,
                'reference_type' => 'reward',
                'balance_after' => 100,
                'created_at' => Carbon::now()->subMonth(),
            ],
            [
                'tenant_id' => 1,
                'customer_membership_id' => 1,
                'type' => 'earn',
                'points' => 200,
                'amount' => 20.00,
                'description' => 'Purchase: 20 JOD',
                'balance_after' => 300,
                'created_at' => Carbon::now()->subWeeks(2),
            ],
            [
                'tenant_id' => 1,
                'customer_membership_id' => 1,
                'type' => 'bonus',
                'points' => 50,
                'description' => 'Daily check-in bonus',
                'balance_after' => 350,
                'created_at' => Carbon::now()->subWeek(),
            ],
        ];

        foreach ($transactions as $transaction) {
            \DB::table('transactions')->insert(array_merge($transaction, [
                'updated_at' => Carbon::now(),
            ]));
        }

        $this->command->info('✓ Created 5 sample transactions');
    }

    private function createRedemptions(): void
    {
        $redemptions = [
            [
                'tenant_id' => 1,
                'customer_membership_id' => 1,
                'reward_id' => 1,
                'points_used' => 100,
                'status' => 'used',
                'redemption_code' => 'RDM-' . Str::upper(Str::random(6)),
                'qr_code_hash' => 'QR-RED-' . Str::random(20),
                'redeemed_at' => Carbon::now()->subMonth(),
                'approved_at' => Carbon::now()->subMonth()->addMinutes(5),
                'used_at' => Carbon::now()->subMonth()->addHours(2),
                'approved_by' => 1,
                'used_by' => 1,
            ],
            [
                'tenant_id' => 1,
                'customer_membership_id' => 3,
                'reward_id' => 3,
                'points_used' => 200,
                'status' => 'approved',
                'redemption_code' => 'RDM-' . Str::upper(Str::random(6)),
                'qr_code_hash' => 'QR-RED-' . Str::random(20),
                'redeemed_at' => Carbon::now()->subDays(3),
                'approved_at' => Carbon::now()->subDays(3)->addMinutes(10),
                'approved_by' => 1,
            ],
        ];

        foreach ($redemptions as $redemption) {
            \DB::table('redemptions')->insert(array_merge($redemption, [
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ]));
        }

        $this->command->info('✓ Created 2 sample redemptions');
    }

    private function createNotifications(): void
    {
        $notifications = [
            [
                'tenant_id' => 1,
                'customer_membership_id' => 1,
                'type' => 'points_earned',
                'title_ar' => 'تم إضافة نقاط!',
                'title_en' => 'Points Earned!',
                'message_ar' => 'لقد حصلت على 150 نقطة من عملية الشراء الأخيرة',
                'message_en' => 'You earned 150 points from your recent purchase',
                'is_read' => true,
                'sent_push' => true,
                'sent_at' => Carbon::now()->subWeeks(2),
            ],
            [
                'tenant_id' => 1,
                'customer_membership_id' => 3,
                'type' => 'tier_upgrade',
                'title_ar' => 'ترقية المستوى!',
                'title_en' => 'Tier Upgrade!',
                'message_ar' => 'مبروك! أصبحت الآن من فئة الذهب',
                'message_en' => 'Congratulations! You are now Gold tier',
                'is_read' => false,
                'sent_push' => true,
                'sent_email' => true,
                'sent_at' => Carbon::now()->subDays(5),
            ],
            [
                'tenant_id' => 2,
                'customer_membership_id' => 4,
                'type' => 'reward_available',
                'title_ar' => 'مكافأة جديدة!',
                'title_en' => 'New Reward Available!',
                'message_ar' => 'مكافأة جديدة متاحة: جلسة تدريب مجانية',
                'message_en' => 'New reward available: Free Training Session',
                'is_read' => false,
                'sent_push' => true,
                'sent_at' => Carbon::now()->subDay(),
            ],
        ];

        foreach ($notifications as $notification) {
            \DB::table('notifications')->insert(array_merge($notification, [
                'priority' => 'normal',
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ]));
        }

        $this->command->info('✓ Created 3 sample notifications');
    }
}
