import http from 'k6/http';
import { check, sleep } from 'k6';
import { Rate } from 'k6/metrics';

// Custom metrics
const errorRate = new Rate('errors');

// Test configuration
export const options = {
    stages: [
        { duration: '2m', target: 100 }, // Ramp up to 100 RPS
        { duration: '5m', target: 100 }, // Stay at 100 RPS
        { duration: '2m', target: 0 },   // Ramp down
    ],
    thresholds: {
        http_req_duration: ['p(95)<500'], // 95% of requests should be below 500ms
        http_req_failed: ['rate<0.01'],    // Error rate should be less than 1%
        errors: ['rate<0.01'],
    },
};

const BASE_URL = __ENV.BASE_URL || 'http://localhost:2244';

export default function () {
    // Health check (read operation)
    let healthRes = http.get(`${BASE_URL}/api/v1/health`);
    check(healthRes, {
        'health check status is 200': (r) => r.status === 200,
        'health check has status ok': (r) => JSON.parse(r.body).status === 'ok',
    }) || errorRate.add(1);
    
    sleep(0.5);

    // Get catalog (read operation)
    let catalogRes = http.get(`${BASE_URL}/catalog`);
    check(catalogRes, {
        'catalog status is 200': (r) => r.status === 200,
    }) || errorRate.add(1);
    
    sleep(0.3);

    // Get service types (read operation)
    let servicesRes = http.get(`${BASE_URL}/api/v1/service-types`);
    check(servicesRes, {
        'services status is 200': (r) => r.status === 200,
    }) || errorRate.add(1);
    
    sleep(0.2);

    // Simulate order creation (write operation) - only 10% of requests
    if (Math.random() < 0.1) {
        const payload = JSON.stringify({
            user_id: 1,
            items: [
                {
                    service_type_id: 1,
                    quantity: 1,
                },
            ],
            location: {
                lat: 68.4384,
                lng: 17.4278,
                address: 'Test Address',
            },
        });

        const params = {
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
            },
        };

        let orderRes = http.post(`${BASE_URL}/api/v1/orders`, payload, params);
        check(orderRes, {
            'order creation status is 200 or 201': (r) => [200, 201].includes(r.status),
        }) || errorRate.add(1);
        
        sleep(1);
    }
}

export function handleSummary(data) {
    return {
        'stdout': JSON.stringify(data, null, 2),
        'summary.json': JSON.stringify(data, null, 2),
    };
}


