// Public site behaviour, kept small (PRD §4: ≤ 40 KB gzipped JS).
import Alpine from 'alpinejs';

const FAVORITES_KEY = 'elaf.favorites';

function readFavorites() {
    try {
        const ids = JSON.parse(localStorage.getItem(FAVORITES_KEY) ?? '[]');
        return Array.isArray(ids) ? ids.filter(Number.isInteger) : [];
    } catch {
        return [];
    }
}

// Reader favourites: article ids in this browser only (no account, no server state).
Alpine.store('favorites', {
    ids: readFavorites(),
    has(id) {
        return this.ids.includes(id);
    },
    toggle(id) {
        this.ids = this.has(id) ? this.ids.filter((x) => x !== id) : [id, ...this.ids].slice(0, 50);
        try {
            localStorage.setItem(FAVORITES_KEY, JSON.stringify(this.ids));
        } catch {
            // Private mode or storage full: favourites last for this page only.
        }
    },
});

// Breaking news ticker: prev/next, advancing on its own unless the reader prefers less motion.
Alpine.data('ticker', (count) => ({
    index: 0,
    init() {
        if (count > 1 && !window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
            this.timer = setInterval(() => this.next(), 6000);
        }
    },
    destroy() {
        clearInterval(this.timer);
    },
    next() {
        this.index = (this.index + 1) % count;
    },
    previous() {
        this.index = (this.index - 1 + count) % count;
    },
}));

// /favorites: renders the saved ids through a small HTML endpoint.
Alpine.data('favoritesPage', (endpoint) => ({
    state: 'loading',
    html: '',
    async load() {
        const ids = Alpine.store('favorites').ids;
        if (ids.length === 0) {
            this.state = 'empty';
            return;
        }
        const response = await fetch(`${endpoint}?ids=${ids.join(',')}`, { headers: { Accept: 'text/html' } });
        this.html = (await response.text()).trim();
        this.state = this.html === '' ? 'empty' : 'ready';
    },
}));

window.Alpine = Alpine;
Alpine.start();

// Share bar (FR-SHR-01): copy link and the native share sheet.
for (const button of document.querySelectorAll('[data-copy-link]')) {
    button.addEventListener('click', async () => {
        try {
            await navigator.clipboard.writeText(button.dataset.copyLink);
            const label = button.textContent;
            button.textContent = button.dataset.copied;
            setTimeout(() => (button.textContent = label), 2000);
        } catch {
            window.prompt('', button.dataset.copyLink);
        }
    });
}

if (navigator.share) {
    for (const button of document.querySelectorAll('[data-native-share]')) {
        button.hidden = false;
        button.addEventListener('click', () => {
            navigator.share({ title: button.dataset.title, url: button.dataset.url }).catch(() => {});
        });
    }
}
