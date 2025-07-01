/*
Give the service worker access to Firebase Messaging.
Note that you can only use Firebase Messaging here, other Firebase libraries are not available in the service worker.
*/
importScripts("firebase-app.js");
importScripts("firebase-messaging.js");
importScripts("configs.js");

/*
Initialize the Firebase app in the service worker by passing in the messagingSenderId.
* New configuration for app@pulseservice.com
*/
firebase.initializeApp(firebaseConfig);

/*
Retrieve an instance of Firebase Messaging so that it can handle background messages.
*/
const messaging = firebase.messaging();

messaging.setBackgroundMessageHandler(function (payload) {
     const notificationTitle = payload.notification.title;
    const notificationOptions = {
        body: payload.notification.body,
        icon: payload.notification.icon,
        click_action: payload.notification.click_action,
        vibrate: [200, 100, 200]
    };


    return self.registration.showNotification(notificationTitle, notificationOptions);
});
