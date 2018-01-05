var thisScript = document.querySelector('script[data-webpushclient]');
if (null === thisScript) {
    console.log('Do not forget to add "data-webpushclient", i.e. <script src="webpush_client.js" data-webpushclient></script>');
    throw Error("Cannot find where webpush_client.js is.");
}
var BenToolsWebPushClient = function BenToolsWebPushClient(options) {

    return {

        options: {},
        worker: null,
        registration: null,
        subscription: null,

        init: function init(options) {
            this.options = options || {};

            if (!options.url) {
                throw Error('Url has not been defined.');
            }

            this.options.url = options.url;
            this.options.swPath = this.options.swPath || thisScript.src.replace('/webpush_client.js', '/webpush_sw.js');
            this.options.promptIfNotSubscribed = 'boolean' === typeof options.promptIfNotSubscribed ? options.promptIfNotSubscribed : true;
            return this.initSW();
        },

        initSW: function initSW() {
            var that = this;

            if (!('serviceWorker' in navigator) || !('PushManager' in window)) {
                return;
            }

            that.registerServiceWorker(this.options.swPath).then(function (registration) {
                that.registration = registration;

                that.getSubscription(registration).then(function (subscription) {

                    // If a subscription was found, return it.
                    if (subscription) {
                        that.subscription = subscription;
                        return subscription;
                    } else {
                        if (true === that.options.promptIfNotSubscribed) {
                            return that.subscribe();
                        }
                    }
                });
            });
            return this;
        },

        askPermission: function askPermission() {
            return new Promise(function (resolve, reject) {
                var permissionResult = Notification.requestPermission(function (result) {
                    resolve(result);
                });

                if (permissionResult) {
                    permissionResult.then(resolve, reject);
                }
            }).then(function (permissionResult) {
                if (permissionResult !== 'granted') {
                    throw new Error('Permission was not granted.');
                }
            });
        },

        getNotificationPermissionState: function getNotificationPermissionState() {
            if (navigator.permissions) {
                return navigator.permissions.query({ name: 'notifications' }).then(function (result) {
                    return result.state;
                });
            }

            return new Promise(function (resolve) {
                resolve(Notification.permission);
            });
        },

        subscribe: function subscribe() {
            var that = this;
            return that.registration.pushManager.subscribe({
                userVisibleOnly: true,
                applicationServerKey: that.encodeServerKey(that.options.serverKey)
            }).then(function (subscription) {
                that.subscription = subscription;
                return that.registerSubscription(subscription).then(function (subscription) {
                    if ('function' === typeof that.options.onSubscribe) {
                        return that.options.onSubscribe(subscription);
                    }
                });
            });
        },

        unsubscribe: function unsubscribe() {
            var that = this;
            return this.getSubscription(this.registration).then(function (subscription) {
                that.unregisterSubscription(subscription);
                if ('function' === typeof that.options.onUnsubscribe) {
                    return that.options.onUnsubscribe(subscription);
                }
            });
        },

        revoke: function unsubscribe() {
            var that = this;
            return this.getSubscription(this.registration).then(function (subscription) {
                subscription.unsubscribe().then(function () {
                    that.unregisterSubscription(subscription);
                    if ('function' === typeof that.options.onUnsubscribe) {
                        return that.options.onUnsubscribe(subscription);
                    }
                });
            });
        },


        registerServiceWorker: function registerServiceWorker(swPath) {
            var that = this;
            return navigator.serviceWorker.register(swPath).then(function (registration) {
                that.worker = registration.active || registration.installing;
                return registration;
            });
        },

        getSubscription: function getSubscription(registration) {
            return registration.pushManager.getSubscription();
        },

        registerSubscription: function registerSubscription(subscription) {
            return fetch(this.options.url, {
                method: 'POST',
                mode: 'cors',
                credentials: 'include',
                cache: 'default',
                headers: new Headers({
                    'Accept': 'application/json',
                    'Content-Type': 'application/json'
                }),
                body: JSON.stringify(subscription)
            }).then(function () {
                return subscription;
            });
        },

        unregisterSubscription: function unregisterSubscription(subscription) {
            return fetch(this.options.url, {
                method: 'DELETE',
                mode: 'cors',
                credentials: 'include',
                cache: 'default',
                headers: new Headers({
                    'Accept': 'application/json',
                    'Content-Type': 'application/json'
                }),
                body: JSON.stringify(subscription)
            });
        },

        encodeServerKey: function encodeServerKey(serverKey) {
            var padding = '='.repeat((4 - serverKey.length % 4) % 4);
            var base64 = (serverKey + padding).replace(/\-/g, '+').replace(/_/g, '/');

            var rawData = window.atob(base64);
            var outputArray = new Uint8Array(rawData.length);

            for (var i = 0; i < rawData.length; ++i) {
                outputArray[i] = rawData.charCodeAt(i);
            }
            return outputArray;
        }

    }.init(options);
};