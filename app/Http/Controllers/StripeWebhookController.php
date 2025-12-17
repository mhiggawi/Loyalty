<?php

namespace App\Http\Controllers;

use App\Models\Tenant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Stripe\Webhook;
use Stripe\Exception\SignatureVerificationException;

class StripeWebhookController extends Controller
{
    /**
     * Handle Stripe webhook events
     */
    public function handleWebhook(Request $request)
    {
        $payload = $request->getContent();
        $sigHeader = $request->header('Stripe-Signature');
        $webhookSecret = config('stripe.webhook_secret');

        try {
            $event = Webhook::constructEvent($payload, $sigHeader, $webhookSecret);
        } catch (\UnexpectedValueException $e) {
            // Invalid payload
            return response()->json(['error' => 'Invalid payload'], 400);
        } catch (SignatureVerificationException $e) {
            // Invalid signature
            return response()->json(['error' => 'Invalid signature'], 400);
        }

        // Handle the event
        switch ($event->type) {
            case 'customer.subscription.created':
                $this->handleSubscriptionCreated($event->data->object);
                break;

            case 'customer.subscription.updated':
                $this->handleSubscriptionUpdated($event->data->object);
                break;

            case 'customer.subscription.deleted':
                $this->handleSubscriptionDeleted($event->data->object);
                break;

            case 'invoice.payment_succeeded':
                $this->handleInvoicePaymentSucceeded($event->data->object);
                break;

            case 'invoice.payment_failed':
                $this->handleInvoicePaymentFailed($event->data->object);
                break;

            case 'customer.subscription.trial_will_end':
                $this->handleTrialWillEnd($event->data->object);
                break;

            default:
                Log::info('Unhandled Stripe webhook event', ['type' => $event->type]);
        }

        return response()->json(['status' => 'success'], 200);
    }

    /**
     * Handle subscription created event
     */
    protected function handleSubscriptionCreated($subscription)
    {
        $tenant = Tenant::where('stripe_subscription_id', $subscription->id)->first();

        if ($tenant) {
            $tenant->update([
                'subscription_status' => $subscription->status,
                'subscription_started_at' => now(),
                'subscription_ends_at' => $subscription->current_period_end
                    ? \Carbon\Carbon::createFromTimestamp($subscription->current_period_end)
                    : null,
            ]);

            Log::info('Subscription created', ['tenant_id' => $tenant->id]);
        }
    }

    /**
     * Handle subscription updated event
     */
    protected function handleSubscriptionUpdated($subscription)
    {
        $tenant = Tenant::where('stripe_subscription_id', $subscription->id)->first();

        if ($tenant) {
            $tenant->update([
                'subscription_status' => $subscription->status,
                'subscription_ends_at' => $subscription->current_period_end
                    ? \Carbon\Carbon::createFromTimestamp($subscription->current_period_end)
                    : null,
            ]);

            // If subscription was canceled
            if ($subscription->cancel_at_period_end) {
                $tenant->update([
                    'subscription_ends_at' => $subscription->cancel_at
                        ? \Carbon\Carbon::createFromTimestamp($subscription->cancel_at)
                        : null,
                ]);
            }

            Log::info('Subscription updated', [
                'tenant_id' => $tenant->id,
                'status' => $subscription->status,
            ]);
        }
    }

    /**
     * Handle subscription deleted event
     */
    protected function handleSubscriptionDeleted($subscription)
    {
        $tenant = Tenant::where('stripe_subscription_id', $subscription->id)->first();

        if ($tenant) {
            $tenant->update([
                'subscription_status' => 'canceled',
                'subscription_ends_at' => now(),
            ]);

            Log::warning('Subscription deleted', ['tenant_id' => $tenant->id]);

            // Optionally notify the tenant
            // You can create a notification here
        }
    }

    /**
     * Handle successful payment
     */
    protected function handleInvoicePaymentSucceeded($invoice)
    {
        $tenant = Tenant::where('stripe_customer_id', $invoice->customer)->first();

        if ($tenant && $invoice->subscription) {
            // Payment succeeded, subscription is active
            $tenant->update(['subscription_status' => 'active']);

            Log::info('Invoice payment succeeded', [
                'tenant_id' => $tenant->id,
                'amount' => $invoice->amount_paid / 100,
            ]);
        }
    }

    /**
     * Handle failed payment
     */
    protected function handleInvoicePaymentFailed($invoice)
    {
        $tenant = Tenant::where('stripe_customer_id', $invoice->customer)->first();

        if ($tenant) {
            $tenant->update(['subscription_status' => 'past_due']);

            Log::warning('Invoice payment failed', [
                'tenant_id' => $tenant->id,
                'amount' => $invoice->amount_due / 100,
            ]);

            // Optionally send notification to tenant about payment failure
        }
    }

    /**
     * Handle trial ending soon
     */
    protected function handleTrialWillEnd($subscription)
    {
        $tenant = Tenant::where('stripe_subscription_id', $subscription->id)->first();

        if ($tenant) {
            Log::info('Trial will end soon', [
                'tenant_id' => $tenant->id,
                'trial_end' => $subscription->trial_end,
            ]);

            // Optionally send notification to tenant about trial ending
        }
    }
}
