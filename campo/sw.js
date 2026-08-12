// Bump this name whenever the deployed application shell changes. It makes
// installed PWAs discard the legacy bundle before serving the new workflow.
const CACHE = 'amazon-campo-v13'
const SHELL = ['/campo/', '/campo/manifest.webmanifest', '/campo/icon.svg', '/campo/brand-mark.svg', '/campo/brand-horizontal.svg']

async function cacheAppShell() {
  const cache = await caches.open(CACHE)
  const response = await fetch('/campo/')
  const html = await response.clone().text()
  await cache.put('/campo/', response)
  const assets = [...html.matchAll(/(?:src|href)="(\/campo\/assets\/[^"]+)"/g)].map(match => match[1])
  await cache.addAll([...SHELL.slice(1), ...assets])
}

self.addEventListener('install', event => {
  event.waitUntil(cacheAppShell())
  self.skipWaiting()
})

self.addEventListener('activate', event => {
  event.waitUntil(caches.keys().then(keys => Promise.all(keys.filter(key => key !== CACHE).map(key => caches.delete(key)))))
  self.clients.claim()
})

self.addEventListener('fetch', event => {
  const request = event.request
  if (request.method !== 'GET') return
  const url = new URL(request.url)
  if (url.pathname.startsWith('/api/')) return

  if (request.mode === 'navigate') {
    event.respondWith(fetch(request).catch(() => caches.match('/campo/')))
    return
  }

  event.respondWith(
    caches.match(request).then(cached => cached || fetch(request).then(response => {
      const copy = response.clone()
      caches.open(CACHE).then(cache => cache.put(request, copy))
      return response
    }))
  )
})
