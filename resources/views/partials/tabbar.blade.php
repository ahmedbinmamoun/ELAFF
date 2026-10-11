{{-- Mobile bottom tab bar; hidden from 768 px. Respects the safe-area inset. --}}
@php
    $tabs = [
        ['route' => 'home', 'url' => route('home'), 'icon' => 'home', 'label' => __('public.home')],
        ['route' => 'issues.*', 'url' => route('issues.index'), 'icon' => 'newspaper', 'label' => __('public.issues')],
        ['route' => 'favorites*', 'url' => route('favorites'), 'icon' => 'heart', 'label' => __('public.favorites')],
        ['route' => null, 'url' => url('/writer/login'), 'icon' => 'user', 'label' => __('public.account')],
    ];
@endphp
<nav class="fixed inset-x-0 bottom-0 z-40 border-t border-rule bg-paper pb-[env(safe-area-inset-bottom)] shadow-[0_-2px_8px_rgba(0,0,0,0.06)] md:hidden" aria-label="{{ __('public.tabbar') }}">
    <ul class="grid h-tabbar grid-cols-4">
        @foreach ($tabs as $tab)
            @php($active = $tab['route'] !== null && request()->routeIs($tab['route']))
            <li>
                <a href="{{ $tab['url'] }}" class="flex h-full flex-col items-center justify-center gap-0.5 text-xs {{ $active ? 'font-bold text-accent' : 'text-muted' }}" @if ($active) aria-current="page" @endif>
                    <x-svg-icon :name="$tab['icon']" class="h-6 w-6" />
                    {{ $tab['label'] }}
                </a>
            </li>
        @endforeach
    </ul>
</nav>
