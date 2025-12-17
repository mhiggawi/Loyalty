<?php

namespace App\Services;

use App\Models\Tenant;
use Stripe\Stripe;
use Stripe\Customer;
use Stripe\Subscription;
use Stripe\PaymentMethod;
use Stripe\StripeClient;
use Stripe\Exception\ApiErrorException;

class StripeService
{
    protected StripeClient $stripe;

    public function __construct()
    {
        $this->stripe = new StripeClient(config('stripe.secret'));
        Stripe::setApiVersion(config('stripe.api_version'));
    }

    /**
     * Create a Stripe customer for a tenant
     */
    public function createCustomer(Tenant $tenant): ?Customer
    {
        try {
            $customer = $this->stripe->customers->create([
                'email' => $tenant->contact_email,
                'name' => $tenant->business_name,
                'phone' => $tenant->contact_phone,
                'metadata' => [
                    'tenant_id' => $tenant->id,
                    'business_slug' => $tenant->business_slug,
                ],
            ]);

            // Save Stripe customer ID to tenant
            $tenant->update(['stripe_customer_id' => $customer->id]);

            return $customer;
        } catch (ApiErrorException $e) {
            logger()->error('Stripe customer creation failed', [
                'tenant_id' => $tenant->id,
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    /**
     * Create a subscription for a tenant
     */
    public function createSubscription(Tenant $tenant, string $plan, ?string $paymentMethodId = null): ?Subscription
    {
        try {
            // Ensure tenant has a Stripe customer ID
            if (!$tenant->stripe_customer_id) {
                $this->createCustomer($tenant);
            }

            // Get plan configuration
            $planConfig = config("stripe.plans.{$plan}");
            if (!$planConfig || !$planConfig['stripe_price_id']) {
                throw new \Exception("Invalid plan or missing Stripe price ID: {$plan}");
            }

            $subscriptionData = [
                'customer' => $tenant->stripe_customer_id,
                'items' => [
                    ['price' => $planConfig['stripe_price_id']],
                ],
                'metadata' => [
                    'tenant_id' => $tenant->id,
                    'plan' => $plan,
                ],
                'expand' => ['latest_invoice.payment_intent'],
            ];

            // Attach payment method if provided
            if ($paymentMethodId) {
                $this->stripe->paymentMethods->attach($paymentMethodId, [
                    'customer' => $tenant->stripe_customer_id,
                ]);

                $this->stripe->customers->update($tenant->stripe_customer_id, [
                    'invoice_settings' => [
                        'default_payment_method' => $paymentMethodId,
                    ],
                ]);
            }

            $subscription = $this->stripe->subscriptions->create($subscriptionData);

            // Update tenant subscription info
            $tenant->update([
                'subscription_plan' => $plan,
                'subscription_status' => $subscription->status,
                'stripe_subscription_id' => $subscription->id,
                'subscription_started_at' => now(),
                'subscription_ends_at' => $subscription->current_period_end
                    ? \Carbon\Carbon::createFromTimestamp($subscription->current_period_end)
                    : null,
            ]);

            // Update plan limits
            $this->updatePlanLimits($tenant, $plan);

            return $subscription;
        } catch (ApiErrorException $e) {
            logger()->error('Stripe subscription creation failed', [
                'tenant_id' => $tenant->id,
                'plan' => $plan,
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    /**
     * Update tenant's plan limits based on subscription
     */
    public function updatePlanLimits(Tenant $tenant, string $plan): void
    {
        $planConfig = config("stripe.plans.{$plan}");
        if (!$planConfig) {
            return;
        }

        $tenant->update([
            'max_customers' => $planConfig['features']['max_customers'] ?? null,
            'max_staff' => $planConfig['features']['max_staff'] ?? null,
            'max_rewards' => $planConfig['features']['max_rewards'] ?? null,
        ]);
    }

    /**
     * Cancel a subscription
     */
    public function cancelSubscription(Tenant $tenant, bool $immediately = false): ?Subscription
    {
        try {
            if (!$tenant->stripe_subscription_id) {
                throw new \Exception('No active subscription found');
            }

            if ($immediately) {
                $subscription = $this->stripe->subscriptions->cancel($tenant->stripe_subscription_id);
            } else {
                // Cancel at period end
                $subscription = $this->stripe->subscriptions->update($tenant->stripe_subscription_id, [
                    'cancel_at_period_end' => true,
                ]);
            }

            $tenant->update([
                'subscription_status' => $subscription->status,
                'subscription_ends_at' => $subscription->cancel_at
                    ? \Carbon\Carbon::createFromTimestamp($subscription->cancel_at)
                    : null,
            ]);

            return $subscription;
        } catch (ApiErrorException $e) {
            logger()->error('Stripe subscription cancellation failed', [
                'tenant_id' => $tenant->id,
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    /**
     * Resume a canceled subscription
     */
    public function resumeSubscription(Tenant $tenant): ?Subscription
    {
        try {
            if (!$tenant->stripe_subscription_id) {
                throw new \Exception('No subscription found');
            }

            $subscription = $this->stripe->subscriptions->update($tenant->stripe_subscription_id, [
                'cancel_at_period_end' => false,
            ]);

            $tenant->update([
                'subscription_status' => $subscription->status,
                'subscription_ends_at' => null,
            ]);

            return $subscription;
        } catch (ApiErrorException $e) {
            logger()->error('Stripe subscription resume failed', [
                'tenant_id' => $tenant->id,
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    /**
     * Change subscription plan
     */
    public function changePlan(Tenant $tenant, string $newPlan): ?Subscription
    {
        try {
            if (!$tenant->stripe_subscription_id) {
                // Create new subscription if none exists
                return $this->createSubscription($tenant, $newPlan);
            }

            $planConfig = config("stripe.plans.{$newPlan}");
            if (!$planConfig || !$planConfig['stripe_price_id']) {
                throw new \Exception("Invalid plan: {$newPlan}");
            }

            // Get current subscription
            $currentSubscription = $this->stripe->subscriptions->retrieve($tenant->stripe_subscription_id);

            // Update subscription with new price
            $subscription = $this->stripe->subscriptions->update($tenant->stripe_subscription_id, [
                'items' => [
                    [
                        'id' => $currentSubscription->items->data[0]->id,
                        'price' => $planConfig['stripe_price_id'],
                    ],
                ],
                'proration_behavior' => 'always_invoice',
                'metadata' => [
                    'tenant_id' => $tenant->id,
                    'plan' => $newPlan,
                ],
            ]);

            $tenant->update([
                'subscription_plan' => $newPlan,
                'subscription_status' => $subscription->status,
            ]);

            // Update plan limits
            $this->updatePlanLimits($tenant, $newPlan);

            return $subscription;
        } catch (ApiErrorException $e) {
            logger()->error('Stripe plan change failed', [
                'tenant_id' => $tenant->id,
                'new_plan' => $newPlan,
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    /**
     * Get subscription details
     */
    public function getSubscription(Tenant $tenant): ?Subscription
    {
        try {
            if (!$tenant->stripe_subscription_id) {
                return null;
            }

            return $this->stripe->subscriptions->retrieve($tenant->stripe_subscription_id);
        } catch (ApiErrorException $e) {
            logger()->error('Stripe subscription retrieval failed', [
                'tenant_id' => $tenant->id,
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    /**
     * Get upcoming invoice
     */
    public function getUpcomingInvoice(Tenant $tenant): ?object
    {
        try {
            if (!$tenant->stripe_customer_id) {
                return null;
            }

            return $this->stripe->invoices->upcoming([
                'customer' => $tenant->stripe_customer_id,
            ]);
        } catch (ApiErrorException $e) {
            // No upcoming invoice is not an error
            return null;
        }
    }

    /**
     * Create a setup intent for payment method
     */
    public function createSetupIntent(Tenant $tenant): ?object
    {
        try {
            if (!$tenant->stripe_customer_id) {
                $this->createCustomer($tenant);
            }

            return $this->stripe->setupIntents->create([
                'customer' => $tenant->stripe_customer_id,
                'payment_method_types' => ['card'],
            ]);
        } catch (ApiErrorException $e) {
            logger()->error('Stripe setup intent creation failed', [
                'tenant_id' => $tenant->id,
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    /**
     * Get customer's payment methods
     */
    public function getPaymentMethods(Tenant $tenant): array
    {
        try {
            if (!$tenant->stripe_customer_id) {
                return [];
            }

            $paymentMethods = $this->stripe->paymentMethods->all([
                'customer' => $tenant->stripe_customer_id,
                'type' => 'card',
            ]);

            return $paymentMethods->data;
        } catch (ApiErrorException $e) {
            logger()->error('Stripe payment methods retrieval failed', [
                'tenant_id' => $tenant->id,
                'error' => $e->getMessage(),
            ]);
            return [];
        }
    }
}
