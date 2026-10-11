/**
 * Design tokens (PRD §10). Re-brand by editing this file only.
 * Loaded by Tailwind v4 through `@config` in resources/css/app.css.
 */
export default {
    theme: {
        extend: {
            colors: {
                brand: { DEFAULT: '#0e2a47', dark: '#0a1f35', light: '#1c3d63' },
                accent: { DEFAULT: '#c8102e', dark: '#a00d25' },
                ink: '#16202b',
                muted: '#5b6672',
                paper: '#ffffff',
                canvas: '#f3f5f8',
                rule: '#e2e6eb',
            },
            fontFamily: {
                // Self-hosted subsets arrive in Phase 6; system fallbacks until then.
                sans: ['Cairo', 'Segoe UI', 'Tahoma', 'system-ui', 'sans-serif'],
                serif: ['Noto Naskh Arabic', 'Cairo', 'Times New Roman', 'serif'],
            },
            borderRadius: {
                card: '0.5rem',
                badge: '0.25rem',
            },
            spacing: {
                tap: '2.75rem',
                gutter: '1rem',
                tabbar: '4rem',
            },
            maxWidth: {
                site: '80rem',
            },
        },
    },
};
