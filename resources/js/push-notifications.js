// Web Push Notifications for GLF BiKube

class PushNotificationManager {
    constructor() {
        this.publicVapidKey = 'YOUR_VAPID_PUBLIC_KEY_HERE'; // Замінити на реальний ключ
    }

    async requestPermission() {
        if ('Notification' in window && 'serviceWorker' in navigator) {
            const permission = await Notification.requestPermission();
            
            if (permission === 'granted') {
                console.log('Push notification permission granted');
                return true;
            } else {
                console.log('Push notification permission denied');
                return false;
            }
        }
        
        console.log('Push notifications not supported');
        return false;
    }

    async registerServiceWorker() {
        if ('serviceWorker' in navigator) {
            const registration = await navigator.serviceWorker.register('/sw.js');
            console.log('Service Worker registered:', registration);
            return registration;
        }
        return null;
    }

    async subscribe(registration, token) {
        try {
            const subscription = await registration.pushManager.subscribe({
                userVisibleOnly: true,
                applicationServerKey: this.urlBase64ToUint8Array(this.publicVapidKey)
            });

            const response = await fetch('/api/v1/push/subscribe', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'Authorization': 'Bearer ' + token
                },
                body: JSON.stringify({
                    endpoint: subscription.endpoint,
                    public_key: this.arrayBufferToBase64(subscription.getKey('p256dh')),
                    auth_token: this.arrayBufferToBase64(subscription.getKey('auth')),
                    device_info: {
                        userAgent: navigator.userAgent,
                        platform: navigator.platform
                    }
                })
            });

            const data = await response.json();
            console.log('Subscribed to push:', data);
            return data;
        } catch (error) {
            console.error('Error subscribing to push:', error);
            throw error;
        }
    }

    async init(token) {
        if (!token) {
            console.log('No auth token provided');
            return false;
        }

        const permissionGranted = await this.requestPermission();
        if (!permissionGranted) {
            return false;
        }

        const registration = await this.registerServiceWorker();
        if (!registration) {
            return false;
        }

        try {
            await this.subscribe(registration, token);
            return true;
        } catch (error) {
            console.error('Failed to initialize push notifications:', error);
            return false;
        }
    }

    urlBase64ToUint8Array(base64String) {
        const padding = '='.repeat((4 - base64String.length % 4) % 4);
        const base64 = (base64String + padding)
            .replace(/\-/g, '+')
            .replace(/_/g, '/');

        const rawData = window.atob(base64);
        const outputArray = new Uint8Array(rawData.length);

        for (let i = 0; i < rawData.length; ++i) {
            outputArray[i] = rawData.charCodeAt(i);
        }
        return outputArray;
    }

    arrayBufferToBase64(buffer) {
        const bytes = new Uint8Array(buffer);
        let binary = '';
        for (let i = 0; i < bytes.byteLength; i++) {
            binary += String.fromCharCode(bytes[i]);
        }
        return window.btoa(binary);
    }
}

// Initialize push notifications if user is authenticated
if (window.PushNotificationManager) {
    const pushManager = new PushNotificationManager();
    window.pushManager = pushManager;
}

export default PushNotificationManager;

