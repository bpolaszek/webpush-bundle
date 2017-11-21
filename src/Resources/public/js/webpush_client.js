/* https://github.com/madmurphy/cookies.js (GPL3) */
if (typeof docCookies === 'undefined') {
    var docCookies = {
        getItem: function (e) {
            return e ? decodeURIComponent(document.cookie.replace(new RegExp("(?:(?:^|.*;)\\s*" + encodeURIComponent(e).replace(/[\-\.\+\*]/g, "\\$&") + "\\s*\\=\\s*([^;]*).*$)|^.*$"), "$1")) || null : null
        }, setItem: function (e, o, n, t, r, c) {
            if (!e || /^(?:expires|max\-age|path|domain|secure)$/i.test(e)) {
                return !1;
            }
            var s = "";
            if (n) {
                switch (n.constructor) {
                    case Number:
                        s = n === 1 / 0 ? "; expires=Fri, 31 Dec 9999 23:59:59 GMT" : "; max-age=" + n;
                                    break;
                    case String:
                        s = "; expires=" + n;
                                    break;
                    case Date:
                        s = "; expires=" + n.toUTCString()
                }
            }
            return document.cookie = encodeURIComponent(e) + "=" + encodeURIComponent(o) + s + (r ? "; domain=" + r : "") + (t ? "; path=" + t : "") + (c ? "; secure" : ""), !0
        }, removeItem: function (e, o, n) {
            return this.hasItem(e) ? (document.cookie = encodeURIComponent(e) + "=; expires=Thu, 01 Jan 1970 00:00:00 GMT" + (n ? "; domain=" + n : "") + (o ? "; path=" + o : ""), !0) : !1
        }, hasItem: function (e) {
            return !e || /^(?:expires|max\-age|path|domain|secure)$/i.test(e) ? !1 : new RegExp("(?:^|;\\s*)" + encodeURIComponent(e).replace(/[\-\.\+\*]/g, "\\$&") + "\\s*\\=").test(document.cookie)
        }, keys: function () {
            for (var e = document.cookie.replace(/((?:^|\s*;)[^\=]+)(?=;|$)|^\s*|\s*(?:\=[^;]*)?(?:\1|$)/g, "").split(/\s*(?:\=[^;]*)?;\s*/), o = e.length, n = 0; o > n; n++) {
                e[n] = decodeURIComponent(e[n]);
            }
            return e
        }
    };
    "undefined" != typeof module && "undefined" != typeof module.exports && (module.exports = docCookies);
}

const thisScript = document.getElementsByTagName('script')[document.getElementsByTagName('script').length - 1];
const BenToolsWebPushClient = function (options) {

    return ({

        options: {},
        worker: null,
        registration: null,
        subscription: null,

        init: function (options) {
            this.options = options || {};

            if (!options.url) {
                throw Error('Url has not been defined.');
            }

            this.options.url = options.url;
            this.options.swPath = this.options.swPath || thisScript.src.replace('/webpush_client.js', '/webpush_sw.js');
            this.options.promptIfNotSubscribed = ('boolean' === typeof options.promptIfNotSubscribed) ? options.promptIfNotSubscribed : true;
            return this.initSW();
        },

        getDeviceHash: function () {
            if (docCookies.hasItem('push_dh')) {
                return docCookies.getItem('push_dh');
            } else {
                const value = Math.random().toString(36).replace(/[^a-z0-9]+/g, '').substr(0, 16);
                docCookies.setItem('push_dh', value);
                return value;
            }
        },

        deleteDeviceHash() {
            docCookies.removeItem(this.getDeviceHash());
        },

        initSW: function () {
            const that = this;

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

                })
            });
            return this;
        },

        askPermission: function () {
            return new Promise(function (resolve, reject) {
                const permissionResult = Notification.requestPermission(function (result) {
                    resolve(result);
                });

                if (permissionResult) {
                    permissionResult.then(resolve, reject);
                }
            })
                .then(function (permissionResult) {
                    if (permissionResult !== 'granted') {
                        throw new Error('Permission was not granted.');
                    }
                });
        },

        getNotificationPermissionState: function () {
            if (navigator.permissions) {
                return navigator.permissions.query({name: 'notifications'})
                    .then((result) => {
                        return result.state;
                    });
            }

            return new Promise((resolve) => {
                resolve(Notification.permission);
            });
        },

        subscribe: function () {
            const that = this;
            return that.registration.pushManager.subscribe({
                userVisibleOnly: true,
                applicationServerKey: that.encodeServerKey(that.options.serverKey)
            }).then(function (subscription) {
                that.subscription = subscription;
                return that.registerSubscription(subscription).then((subscription) => {
                    if ('function' === typeof that.options.onSubscribe) {
                        return that.options.onSubscribe(subscription);
                    }
                });
            });
        },

        unsubscribe: function () {
            const that = this;
            const pushManager = this.registration.pushManager;
            return this.getSubscription(this.registration).then((subscription) => {
                subscription.unsubscribe().then(() => {
                    that.unregisterSubscription();
                    if ('function' === typeof that.options.onUnsubscribe) {
                        return that.options.onUnsubscribe(subscription);
                    }
                });
            });
        },

        registerServiceWorker: function (swPath) {
            const that = this;
            return navigator.serviceWorker.register(swPath)
                .then(function (registration) {
                    that.worker = registration.active || registration.installing;
                    return registration;
                });
        },

        getSubscription: function (registration) {
            return registration.pushManager.getSubscription();
        },

        registerSubscription: function (subscription) {
            const that = this;
            return fetch(this.options.url, {
                method: 'POST',
                mode: 'cors',
                credentials: 'include',
                cache: 'default',
                headers: new Headers({
                    'Accept': 'application/json',
                    'Content-Type': 'application/json'
                }),
            body: JSON.stringify({
                deviceHash: that.getDeviceHash(),
                subscription: subscription
                })
            }).then(function () {
                return subscription;
            });
        },

        unregisterSubscription: function () {
            const that = this;
            return fetch(this.options.url, {
                method: 'DELETE',
                mode: 'cors',
                credentials: 'include',
                cache: 'default',
                headers: new Headers({
                    'Accept': 'application/json',
                    'Content-Type': 'application/json'
                }),
            body: JSON.stringify({
                deviceHash: that.getDeviceHash()
                })
            });
        },
        encodeServerKey: function (serverKey) {
            const padding = '='.repeat((4 - serverKey.length % 4) % 4);
            const base64 = (serverKey + padding)
                .replace(/\-/g, '+')
                .replace(/_/g, '/');

            const rawData = window.atob(base64);
            const outputArray = new Uint8Array(rawData.length);

            for (let i = 0; i < rawData.length; ++i) {
                outputArray[i] = rawData.charCodeAt(i);
            }
            return outputArray;
        }

    }).init(options);
};

