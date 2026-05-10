<?php

namespace GLFBiKube\SDK;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;

/**
 * GLF BiKube SDK for PHP
 *
 * @version 1.0.0
 *
 * @author GLF BiKube Team
 */
class GLFBiKubeSDK
{
    private string $baseUrl;

    private string $clientId;

    private string $clientSecret;

    private ?string $accessToken = null;

    private ?string $webhookSecret = null;

    private int $timeout;

    private Client $httpClient;

    public function __construct(array $config)
    {
        $this->baseUrl = $config['base_url'] ?? 'https://api.glfbikube.com';
        $this->clientId = $config['client_id'];
        $this->clientSecret = $config['client_secret'];
        $this->accessToken = $config['access_token'] ?? null;
        $this->webhookSecret = $config['webhook_secret'] ?? null;
        $this->timeout = $config['timeout'] ?? 30;

        $this->httpClient = new Client([
            'base_uri' => $this->baseUrl,
            'timeout' => $this->timeout,
            'headers' => [
                'User-Agent' => 'GLF-BiKube-SDK-PHP/1.0.0',
                'Content-Type' => 'application/json',
            ],
        ]);
    }

    /**
     * Authenticate using client credentials flow
     */
    public function authenticate(array $scopes = ['read', 'write']): array
    {
        $response = $this->httpClient->post('/oauth/token', [
            'form_params' => [
                'grant_type' => 'client_credentials',
                'client_id' => $this->clientId,
                'client_secret' => $this->clientSecret,
                'scope' => implode(' ', $scopes),
            ],
        ]);

        $data = json_decode($response->getBody()->getContents(), true);
        $this->accessToken = $data['access_token'];

        return $data;
    }

    /**
     * Create a new order
     */
    public function createOrder(array $orderData): array
    {
        $this->validateToken();

        return $this->makeRequest('POST', '/v1/orders', $orderData);
    }

    /**
     * Get order details
     */
    public function getOrder(string $orderId): array
    {
        $this->validateToken();

        return $this->makeRequest('GET', "/v1/orders/{$orderId}");
    }

    /**
     * Get order status
     */
    public function getOrderStatus(string $orderId): array
    {
        $this->validateToken();

        return $this->makeRequest('GET', "/v1/orders/{$orderId}/status");
    }

    /**
     * Cancel an order
     */
    public function cancelOrder(string $orderId, ?string $reason = null): array
    {
        $this->validateToken();

        $data = $reason ? ['reason' => $reason] : [];

        return $this->makeRequest('POST', "/v1/orders/{$orderId}/cancel", $data);
    }

    /**
     * Get available services
     */
    public function getServices(): array
    {
        $this->validateToken();

        return $this->makeRequest('GET', '/v1/services');
    }

    /**
     * Get delivery zones
     */
    public function getZones(): array
    {
        $this->validateToken();

        return $this->makeRequest('GET', '/v1/zones');
    }

    /**
     * Get available time slots
     */
    public function getAvailableSlots(string $date, ?string $zoneId = null): array
    {
        $this->validateToken();

        $query = ['date' => $date];
        if ($zoneId) {
            $query['zone_id'] = $zoneId;
        }

        return $this->makeRequest('GET', '/v1/slots', null, $query);
    }

    /**
     * Calculate dynamic pricing
     */
    public function calculatePricing(array $context): array
    {
        $this->validateToken();

        return $this->makeRequest('POST', '/pricing/calculate', $context);
    }

    /**
     * Create webhook subscription
     */
    public function createWebhookSubscription(array $subscriptionData): array
    {
        $this->validateToken();

        return $this->makeRequest('POST', '/webhooks/subscriptions', $subscriptionData);
    }

    /**
     * Get webhook subscriptions
     */
    public function getWebhookSubscriptions(): array
    {
        $this->validateToken();

        return $this->makeRequest('GET', '/webhooks/subscriptions');
    }

    /**
     * Update webhook subscription
     */
    public function updateWebhookSubscription(string $subscriptionId, array $updateData): array
    {
        $this->validateToken();

        return $this->makeRequest('PUT', "/webhooks/subscriptions/{$subscriptionId}", $updateData);
    }

    /**
     * Delete webhook subscription
     */
    public function deleteWebhookSubscription(string $subscriptionId): array
    {
        $this->validateToken();

        return $this->makeRequest('DELETE', "/webhooks/subscriptions/{$subscriptionId}");
    }

    /**
     * Get webhook delivery logs
     */
    public function getWebhookLogs(string $subscriptionId): array
    {
        $this->validateToken();

        return $this->makeRequest('GET', "/webhooks/subscriptions/{$subscriptionId}/logs");
    }

    /**
     * Send telemetry events
     */
    public function sendTelemetryEvents(array $events): array
    {
        $this->validateToken();

        return $this->makeRequest('POST', '/telemetry/events', ['events' => $events]);
    }

    /**
     * Get telemetry events
     */
    public function getTelemetryEvents(string $resourceId, string $resourceType, int $limit = 100): array
    {
        $this->validateToken();

        $query = [
            'resource_id' => $resourceId,
            'resource_type' => $resourceType,
            'limit' => $limit,
        ];

        return $this->makeRequest('GET', '/telemetry/events', null, $query);
    }

    /**
     * Update ETA from telemetry
     */
    public function updateEtaFromTelemetry(string $resourceId, string $resourceType): array
    {
        $this->validateToken();

        return $this->makeRequest('POST', '/telemetry/eta-update', [
            'resource_id' => $resourceId,
            'resource_type' => $resourceType,
        ]);
    }

    /**
     * Get telemetry anomalies
     */
    public function getTelemetryAnomalies(string $resourceId, string $resourceType): array
    {
        $this->validateToken();

        $query = [
            'resource_id' => $resourceId,
            'resource_type' => $resourceType,
        ];

        return $this->makeRequest('GET', '/telemetry/anomalies', null, $query);
    }

    /**
     * Get route optimization
     */
    public function getRouteOptimization(string $resourceId, string $resourceType): array
    {
        $this->validateToken();

        return $this->makeRequest('POST', '/telemetry/route-optimization', [
            'resource_id' => $resourceId,
            'resource_type' => $resourceType,
        ]);
    }

    /**
     * Verify webhook signature
     */
    public function verifyWebhookSignature(string $payload, string $signature): bool
    {
        if (! $this->webhookSecret) {
            throw new \InvalidArgumentException('Webhook secret not configured');
        }

        $expectedSignature = 'sha256='.hash_hmac('sha256', $payload, $this->webhookSecret);

        return hash_equals($expectedSignature, $signature);
    }

    /**
     * Handle webhook event
     */
    public function handleWebhook(string $payload, string $signature, callable $handler): mixed
    {
        if (! $this->verifyWebhookSignature($payload, $signature)) {
            throw new \InvalidArgumentException('Invalid webhook signature');
        }

        $event = json_decode($payload, true);

        return $handler($event);
    }

    /**
     * Make HTTP request
     */
    private function makeRequest(string $method, string $endpoint, ?array $data = null, ?array $query = null): array
    {
        $options = [];

        if ($this->accessToken) {
            $options['headers']['Authorization'] = 'Bearer '.$this->accessToken;
        }

        if ($data !== null) {
            $options['json'] = $data;
        }

        if ($query !== null) {
            $options['query'] = $query;
        }

        try {
            $response = $this->httpClient->request($method, $endpoint, $options);

            return json_decode($response->getBody()->getContents(), true);
        } catch (RequestException $e) {
            $response = $e->getResponse();
            $errorData = json_decode($response->getBody()->getContents(), true);

            throw new GLFBiKubeException(
                $errorData['error_description'] ?? $errorData['message'] ?? 'Unknown error',
                $response->getStatusCode(),
                $errorData
            );
        }
    }

    /**
     * Validate that access token is available
     */
    private function validateToken(): void
    {
        if (! $this->accessToken) {
            throw new \RuntimeException('No access token available. Please authenticate first.');
        }
    }

    /**
     * Set access token manually
     */
    public function setAccessToken(string $token): void
    {
        $this->accessToken = $token;
    }

    /**
     * Get current access token
     */
    public function getAccessToken(): ?string
    {
        return $this->accessToken;
    }
}

/**
 * Custom exception class
 */
class GLFBiKubeException extends \Exception
{
    private array $errorData;

    public function __construct(string $message, int $code = 0, array $errorData = [])
    {
        parent::__construct($message, $code);
        $this->errorData = $errorData;
    }

    public function getErrorData(): array
    {
        return $this->errorData;
    }
}

/**
 * Webhook event types
 */
class WebhookEvents
{
    public const ORDER_CREATED = 'order.created';

    public const ORDER_ASSIGNED = 'order.assigned';

    public const ORDER_ETA_CHANGED = 'order.eta_changed';

    public const ORDER_COMPLETED = 'order.completed';

    public const ORDER_CANCELLED = 'order.cancelled';

    public const ORDER_REFUNDED = 'order.refunded';

    public const TASK_STARTED = 'task.started';

    public const TASK_COMPLETED = 'task.completed';

    public const GEOFENCE_ENTERED = 'geofence.entered';

    public const GEOFENCE_EXITED = 'geofence.exited';
}

/**
 * Order statuses
 */
class OrderStatuses
{
    public const PENDING = 'pending';

    public const CONFIRMED = 'confirmed';

    public const ASSIGNED = 'assigned';

    public const IN_PROGRESS = 'in_progress';

    public const COMPLETED = 'completed';

    public const CANCELLED = 'cancelled';

    public const REFUNDED = 'refunded';
}

/**
 * Service types
 */
class ServiceTypes
{
    public const CARE = 'care';

    public const ECO = 'eco';

    public const MARKET = 'market';

    public const TOW = 'tow';

    public const RENT = 'rent';

    public const SHUTTLE = 'shuttle';

    public const MASTER = 'master';

    public const FOOD = 'food';
}
