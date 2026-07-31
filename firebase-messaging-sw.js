importScripts('https://www.gstatic.com/firebasejs/10.12.0/firebase-app-compat.js');
importScripts('https://www.gstatic.com/firebasejs/10.12.0/firebase-messaging-compat.js');

firebase.initializeApp({
  apiKey: "AIzaSyBaeK0tPltGdm-KSBGd7mcPtYYbnh2Jik4",
  authDomain: "jtracks-c83ff.firebaseapp.com",
  projectId: "jtracks-c83ff",
  storageBucket: "jtracks-c83ff.firebasestorage.app",
  messagingSenderId: "939022827073",
  appId: "1:939022827073:web:61c4d85ffa95c28ce750c0"
});

const messaging = firebase.messaging();

messaging.onBackgroundMessage((payload) => {
  console.log('[firebase-messaging-sw.js] Received background message ', payload);
  const notificationTitle = (payload.notification && payload.notification.title)
    ? payload.notification.title
    : ((payload.data && payload.data.title) ? payload.data.title : 'Notifikasi JTracks');
    
  const notificationOptions = {
    body: (payload.notification && payload.notification.body)
      ? payload.notification.body
      : ((payload.data && payload.data.body) ? payload.data.body : ''),
    icon: '/cms/assets/media/logos/icon-192.png',
    badge: '/cms/assets/media/logos/icon-192.png',
    vibrate: [200, 100, 200],
    requireInteraction: true,
    tag: 'jtracks-task-' + (payload.data?.schedule_id || Date.now()),
    data: payload.data || {}
  };

  self.registration.showNotification(notificationTitle, notificationOptions);
});

self.addEventListener('notificationclick', (event) => {
  event.notification.close();
  const basePath = self.location.pathname.replace('/firebase-messaging-sw.js', '');
  const targetUrl = self.location.origin + basePath + '/pages/schedule/role/teknisi.php';
  event.waitUntil(
    clients.matchAll({ type: 'window', includeUncontrolled: true }).then((windowClients) => {
      for (let client of windowClients) {
        if (client.url.includes('/pages/schedule/role/teknisi.php') && 'focus' in client) {
          return client.focus();
        }
      }
      if (clients.openWindow) {
        return clients.openWindow(targetUrl);
      }
    })
  );
});

// PWA Service Worker Lifecycle
self.addEventListener('install', (event) => {
  self.skipWaiting();
});

self.addEventListener('activate', (event) => {
  event.waitUntil(self.clients.claim());
});
