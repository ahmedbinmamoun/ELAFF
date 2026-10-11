<?php

declare(strict_types=1);

use Elaf\Shared\Application\Html\HtmlSanitizer;
use Elaf\Shared\Application\Html\SanitizerProfile;
use Elaf\Shared\Infrastructure\Html\HtmlPurifierSanitizer;

dataset('sanitizers', [
    'htmlpurifier (container)' => fn (): HtmlSanitizer => app(HtmlSanitizer::class),
    'htmlpurifier (no cache)' => fn (): HtmlSanitizer => new HtmlPurifierSanitizer,
]);

dataset('profiles', SanitizerProfile::cases());

it('always strips scripts, frames, embeds, styles and event handlers (FR-ART-03)', function (HtmlSanitizer $sanitizer, SanitizerProfile $profile): void {
    $html = '<script>alert(1)</script><iframe src="https://x.y"></iframe><object data="x"></object><embed src="x">'
        .'<p style="color:red" onclick="x()" onmouseover="y()">نص</p><img src="x.webp" onerror="z()" alt="">'
        .'<a href="javascript:alert(1)">رابط</a><a href="data:text/html,x">بيانات</a>';

    $clean = $sanitizer->sanitize($html, $profile);

    expect($clean)->not->toContain('<script', '<iframe', '<object', '<embed', 'style=', 'onclick', 'onmouseover', 'onerror', 'javascript:', 'data:')
        ->and($clean)->toContain('<p>نص</p>');
})->with('sanitizers')->with('profiles');

it('keeps the editorial structure (FR-ART-02)', function (HtmlSanitizer $sanitizer, SanitizerProfile $profile): void {
    $html = '<h2>عنوان</h2><h3>فرعي</h3><h4>أصغر</h4><ul><li>أ</li></ul><ol><li>ب</li></ol>'
        .'<blockquote><p>اقتباس</p></blockquote><p><strong>عريض</strong> <em>مائل</em> <a href="https://example.com">رابط</a></p>'
        .'<table><caption>جدول</caption><thead><tr><th scope="col">ع</th></tr></thead><tbody><tr><td colspan="2">1</td></tr></tbody></table>'
        .'<figure><img src="/storage/a.webp" alt="صورة" width="800" height="450" /><figcaption>تعليق</figcaption></figure>';

    $clean = $sanitizer->sanitize($html, $profile);

    foreach (['<h2>', '<h3>', '<h4>', '<ul>', '<ol>', '<blockquote>', '<strong>', '<em>', 'href="https://example.com"', '<caption>', '<th scope="col">', '<td colspan="2">', '<figure>', '<figcaption>تعليق</figcaption>', 'src="/storage/a.webp"', 'width="800"'] as $kept) {
        expect($clean)->toContain($kept);
    }
})->with('sanitizers')->with('profiles');

it('drops headings outside h2-h4 but keeps their text', function (HtmlSanitizer $sanitizer): void {
    expect($sanitizer->sanitize('<h1>كبير</h1><h5>صغير</h5>', SanitizerProfile::Staff))->toBe('كبيرصغير');
})->with('sanitizers');

it('is stricter for contributors: no classes, no new windows, nofollow links (PRD §16)', function (HtmlSanitizer $sanitizer): void {
    $html = '<table class="wide"><tr><td>1</td></tr></table><a href="https://example.com" target="_blank">رابط</a>';

    expect($sanitizer->sanitize($html, SanitizerProfile::Staff))
        ->toContain('class="wide"', 'target="_blank"', 'noopener')
        ->and($sanitizer->sanitize($html, SanitizerProfile::Contributor))
        ->not->toContain('class=', 'target=')
        ->toContain('rel="nofollow"');
})->with('sanitizers');
