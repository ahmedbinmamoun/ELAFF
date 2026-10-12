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

// Print issue viewer (PRD §11 step 6): full screen, swipe, keyboard arrows, zoom.
// In RTL the next page is on the left: ArrowLeft and a swipe to the right go forward.
Alpine.data('issueViewer', () => ({
    pages: [],
    index: 0,
    isOpen: false,
    zoomed: false,
    startX: null,
    init() {
        try {
            this.pages = JSON.parse(this.$el.dataset.pages ?? '[]');
        } catch {
            this.pages = [];
        }
    },
    get current() {
        return this.pages[this.index] ?? {};
    },
    open(number) {
        if (this.pages.length === 0) {
            return;
        }
        this.index = Math.min(Math.max(number - 1, 0), this.pages.length - 1);
        this.zoomed = false;
        this.isOpen = true;
        document.documentElement.classList.add('overflow-hidden');
        this.preload();
        this.$nextTick(() => this.$refs.close.focus());
    },
    close() {
        this.isOpen = false;
        document.documentElement.classList.remove('overflow-hidden');
        document.getElementById(`page-${this.index + 1}`)?.scrollIntoView({ block: 'start' });
    },
    go(index) {
        if (index >= 0 && index < this.pages.length) {
            this.index = index;
            this.zoomed = false;
            this.$refs.stage.scrollTo(0, 0);
            this.preload();
        }
    },
    next() {
        this.go(this.index + 1);
    },
    previous() {
        this.go(this.index - 1);
    },
    preload() {
        for (const page of [this.pages[this.index + 1], this.pages[this.index - 1]]) {
            if (page) {
                new Image().src = page.src;
            }
        }
    },
    toggleZoom() {
        this.zoomed = !this.zoomed;
    },
    key(event) {
        if (!this.isOpen) {
            return;
        }
        if (event.key === 'Escape') {
            this.close();
        } else if (event.key === 'ArrowLeft') {
            this.next();
        } else if (event.key === 'ArrowRight') {
            this.previous();
        }
    },
    touchStart(event) {
        this.startX = event.touches.length === 1 && !this.zoomed ? event.touches[0].clientX : null;
    },
    touchEnd(event) {
        if (this.startX === null) {
            return;
        }
        const dx = event.changedTouches[0].clientX - this.startX;
        this.startX = null;
        if (Math.abs(dx) > 50) {
            dx > 0 ? this.next() : this.previous();
        }
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
