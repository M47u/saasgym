// Service Worker básico
self.addEventListener('install', (event) => {
  console.log('Service Worker instalado');
  self.skipWaiting();
});

self.addEventListener('activate', (event) => {
  console.log('Service Worker activado');
  event.waitUntil(clients.claim());
});

self.addEventListener('fetch', (event) => {
  // Aquí puedes agregar lógica de caché si lo deseas
});
