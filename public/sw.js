const CACHE_NAME = 'q-track-v1';
// لا نخزن لوحة الفني حتى تتحدث فوراً بعد تسجيل الدخول/الخروج
const urlsToCache = ['/'];

self.addEventListener('install', (event) => {
  event.waitUntil(
    caches.open(CACHE_NAME).then((cache) => cache.addAll(urlsToCache))
  );
  self.skipWaiting();
});

self.addEventListener('fetch', (event) => {
  const url = new URL(event.request.url);
  // لوحة الفني وكل مساراتها: دائماً من الشبكة وليس من الكاش
  if (url.pathname === '/technician' || url.pathname.startsWith('/technician/')) {
    event.respondWith(fetch(event.request));
    return;
  }
  event.respondWith(
    caches.match(event.request).then((response) => response || fetch(event.request))
  );
});
