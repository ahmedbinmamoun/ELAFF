{{-- FR-SHR-01: WhatsApp first. $url, $title --}}
@php($text = rawurlencode($title.' '.$url))
<div class="share-bar flex flex-wrap items-center gap-2 text-sm" aria-label="{{ __('public.share') }}">
    <a class="share-btn bg-[#1f7a3d] text-white" href="https://wa.me/?text={{ $text }}" rel="noopener" target="_blank">{{ __('public.share_whatsapp') }}</a>
    <a class="share-btn bg-[#1d4e9c] text-white" href="https://www.facebook.com/sharer/sharer.php?u={{ rawurlencode($url) }}" rel="noopener" target="_blank">{{ __('public.share_facebook') }}</a>
    <a class="share-btn bg-ink text-white" href="https://x.com/intent/post?url={{ rawurlencode($url) }}&amp;text={{ rawurlencode($title) }}" rel="noopener" target="_blank">{{ __('public.share_x') }}</a>
    <button type="button" class="share-btn border border-rule" data-copy-link="{{ $url }}" data-copied="{{ __('public.copied') }}">{{ __('public.copy_link') }}</button>
    <button type="button" class="share-btn border border-rule" data-native-share data-title="{{ $title }}" data-url="{{ $url }}" hidden>{{ __('public.share_more') }}</button>
</div>
