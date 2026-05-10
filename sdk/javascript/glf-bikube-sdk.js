/**
 * GLF BiKube SDK for JavaScript/Node.js
 * 
 * @version 1.0.0
 * @author GLF BiKube Team
 */

class GLFBiKubeSDK {
    constructor(config) {
        this.baseUrl = config.baseUrl || 'https://api.glfbikube.com';
        this.clientId = config.clientId;
        this.clientSecret = config.clientSecret;
        this.accessToken = config.accessToken;
        this.webhookSecret = config.webhookSecret;
        this.timeout = config.timeout || 30000;
        
        this.httpClient = this.createHttpClient();
    }

    /**
     * Create HTTP client with default configuration
     */
    createHttpClient() {
        const fetch = require('node-fetch');
        
        return {
            async request(method, endpoint, data = null, headers = {}) {
                const url = `${this.baseUrl}${endpoint}`;
                const options = {
                    method,
                    headers: {
                        'Content-Type': 'application/json',
                        'User-Agent': 'GLF-BiKube-SDK/1.0.0',
                        ...headers
                    },
                    timeout: this.timeout
                };

                if (this.accessToken) {
                    options.headers['Authorization'] = `Bearer ${this.accessToken}`;
                }

                if (data) {
                    options.body = JSON.stringify(data);
                }

                try {
                    const response = await fetch(url, options);
                    const responseData = await response.json();

                    if (!response.ok) {
                        throw new Error(`API Error: ${responseData.error_description || responseData.message || 'Unknown error'}`);
                    }

                    return responseData;
                } catch (error) {
                    throw new Error(`Network Error: ${error.message}`);
                }
            }
        };
    }

    /**
     * Authenticate using client credentials flow
     */
    async authenticate(scopes = ['read', 'write']) {
        const data = {
            grant_type: 'client_credentials',
            client_id: this.clientId,
            client_secret: this.clientSecret,
            scope: scopes.join(' ')
        };

        const response = await this.httpClient.request('POST', '/oauth/token', data);
        
        this.accessToken = response.access_token;
        
        return response;
    }

    /**
     * Create a new order
     */
    async createOrder(orderData) {
        this.validateToken();
        
        return await this.httpClient.request('POST', '/v1/orders', orderData);
    }

    /**
     * Get order details
     */
    async getOrder(orderId) {
        this.validateToken();
        
        return await this.httpClient.request('GET', `/v1/orders/${orderId}`);
    }

    /**
     * Get order status
     */
    async getOrderStatus(orderId) {
        this.validateToken();
        
        return await this.httpClient.request('GET', `/v1/orders/${orderId}/status`);
    }

    /**
     * Cancel an order
     */
    async cancelOrder(orderId, reason = null) {
        this.validateToken();
        
        const data = reason ? { reason } : {};
        return await this.httpClient.request('POST', `/v1/orders/${orderId}/cancel`, data);
    }

    /**
     * Get available services
     */
    async getServices() {
        this.validateToken();
        
        return await this.httpClient.request('GET', '/v1/services');
    }

    /**
     * Get delivery zones
     */
    async getZones() {
        this.validateToken();
        
        return await this.httpClient.request('GET', '/v1/zones');
    }

    /**
     * Get available time slots
     */
    async getAvailableSlots(date, zoneId = null) {
        this.validateToken();
        
        const params = new URLSearchParams({ date });
        if (zoneId) params.append('zone_id', zoneId);
        
        return await this.httpClient.request('GET', `/v1/slots?${params}`);
    }

    /**
     * Calculate dynamic pricing
     */
    async calculatePricing(context) {
        this.validateToken();
        
        return await this.httpClient.request('POST', '/pricing/calculate', context);
    }

    /**
     * Create webhook subscription
     */
    async createWebhookSubscription(subscriptionData) {
        this.validateToken();
        
        return await this.httpClient.request('POST', '/webhooks/subscriptions', subscriptionData);
    }

    /**
     * Get webhook subscriptions
     */
    async getWebhookSubscriptions() {
        this.validateToken();
        
        return await this.httpClient.request('GET', '/webhooks/subscriptions');
    }

    /**
     * Update webhook subscription
     */
    async updateWebhookSubscription(subscriptionId, updateData) {
        this.validateToken();
        
        return await this.httpClient.request('PUT', `/webhooks/subscriptions/${subscriptionId}`, updateData);
    }

    /**
     * Delete webhook subscription
     */
    async deleteWebhookSubscription(subscriptionId) {
        this.validateToken();
        
        return await this.httpClient.request('DELETE', `/webhooks/subscriptions/${subscriptionId}`);
    }

    /**
     * Get webhook delivery logs
     */
    async getWebhookLogs(subscriptionId) {
        this.validateToken();
        
        return await this.httpClient.request('GET', `/webhooks/subscriptions/${subscriptionId}/logs`);
    }

    /**
     * Send telemetry events
     */
    async sendTelemetryEvents(events) {
        this.validateToken();
        
        return await this.httpClient.request('POST', '/telemetry/events', { events });
    }

    /**
     * Get telemetry events
     */
    async getTelemetryEvents(resourceId, resourceType, limit = 100) {
        this.validateToken();
        
        const params = new URLSearchParams({
            resource_id: resourceId,
            resource_type: resourceType,
            limit: limit.toString()
        });
        
        return await this.httpClient.request('GET', `/telemetry/events?${params}`);
    }

    /**
     * Update ETA from telemetry
     */
    async updateEtaFromTelemetry(resourceId, resourceType) {
        this.validateToken();
        
        return await this.httpClient.request('POST', '/telemetry/eta-update', {
            resource_id: resourceId,
            resource_type: resourceType
        });
    }

    /**
     * Get telemetry anomalies
     */
    async getTelemetryAnomalies(resourceId, resourceType) {
        this.validateToken();
        
        const params = new URLSearchParams({
            resource_id: resourceId,
            resource_type: resourceType
        });
        
        return await this.httpClient.request('GET', `/telemetry/anomalies?${params}`);
    }

    /**
     * Get route optimization
     */
    async getRouteOptimization(resourceId, resourceType) {
        this.validateToken();
        
        return await this.httpClient.request('POST', '/telemetry/route-optimization', {
            resource_id: resourceId,
            resource_type: resourceType
        });
    }

    /**
     * Verify webhook signature
     */
    verifyWebhookSignature(payload, signature) {
        if (!this.webhookSecret) {
            throw new Error('Webhook secret not configured');
        }

        const crypto = require('crypto');
        const expectedSignature = 'sha256=' + crypto
            .createHmac('sha256', this.webhookSecret)
            .update(payload, 'utf8')
            .digest('hex');

        return crypto.timingSafeEqual(
            Buffer.from(signature, 'utf8'),
            Buffer.from(expectedSignature, 'utf8')
        );
    }

    /**
     * Handle webhook event
     */
    handleWebhook(payload, signature, handler) {
        if (!this.verifyWebhookSignature(payload, signature)) {
            throw new Error('Invalid webhook signature');
        }

        const event = JSON.parse(payload);
        return handler(event);
    }

    /**
     * Validate that access token is available
     */
    validateToken() {
        if (!this.accessToken) {
            throw new Error('No access token available. Please authenticate first.');
        }
    }

    /**
     * Set access token manually
     */
    setAccessToken(token) {
        this.accessToken = token;
    }

    /**
     * Get current access token
     */
    getAccessToken() {
        return this.accessToken;
    }
}

/**
 * Webhook event types
 */
const WEBHOOK_EVENTS = {
    ORDER_CREATED: 'order.created',
    ORDER_ASSIGNED: 'order.assigned',
    ORDER_ETA_CHANGED: 'order.eta_changed',
    ORDER_COMPLETED: 'order.completed',
    ORDER_CANCELLED: 'order.cancelled',
    ORDER_REFUNDED: 'order.refunded',
    TASK_STARTED: 'task.started',
    TASK_COMPLETED: 'task.completed',
    GEOFENCE_ENTERED: 'geofence.entered',
    GEOFENCE_EXITED: 'geofence.exited'
};

/**
 * Order statuses
 */
const ORDER_STATUSES = {
    PENDING: 'pending',
    CONFIRMED: 'confirmed',
    ASSIGNED: 'assigned',
    IN_PROGRESS: 'in_progress',
    COMPLETED: 'completed',
    CANCELLED: 'cancelled',
    REFUNDED: 'refunded'
};

/**
 * Service types
 */
const SERVICE_TYPES = {
    CARE: 'care',
    ECO: 'eco',
    MARKET: 'market',
    TOW: 'tow',
    RENT: 'rent',
    SHUTTLE: 'shuttle',
    MASTER: 'master',
    FOOD: 'food'
};

module.exports = {
    GLFBiKubeSDK,
    WEBHOOK_EVENTS,
    ORDER_STATUSES,
    SERVICE_TYPES
};
