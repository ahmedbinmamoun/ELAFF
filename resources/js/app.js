// Public site behaviour: share buttons (FR-SHR-01). No framework, a few hundred bytes.

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
