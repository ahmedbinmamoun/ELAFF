<?php

declare(strict_types=1);

namespace Elaf\Content\Presentation\Filament\Editor;

use DOMDocument;
use DOMElement;
use DOMNode;
use DOMXPath;
use Elaf\Shared\Presentation\PublicMedia;

/**
 * Maps between the rich editor's figure custom block and the stored HTML
 * (<figure><img><figcaption>), which is what the sanitizer and the public
 * site understand. Formatting only; no rules.
 */
final class FigureBlockConverter
{
    /**
     * Editor HTML → stored HTML.
     */
    public static function toStorage(string $html): string
    {
        return self::transform($html, '//div[@data-type="customBlock"][@data-id="'.FigureBlock::ID.'"]', static function (DOMElement $block, DOMDocument $doc): ?DOMNode {
            $config = json_decode($block->getAttribute('data-config'), true);

            return is_array($config) ? self::importHtml($doc, self::figureHtml(array_filter($config, is_string(...), ARRAY_FILTER_USE_KEY))) : null;
        });
    }

    /**
     * Stored HTML → editor HTML: each <figure> holding an image becomes a block.
     */
    public static function toEditor(string $html): string
    {
        return self::transform($html, '//figure[.//img]', static function (DOMElement $figure, DOMDocument $doc): DOMNode {
            $img = $figure->getElementsByTagName('img')->item(0);
            $caption = $figure->getElementsByTagName('figcaption')->item(0);
            $src = $img?->getAttribute('src') ?? '';
            $path = PublicMedia::pathFromUrl($src);

            $block = $doc->createElement('div');
            $block->setAttribute('data-type', 'customBlock');
            $block->setAttribute('data-id', FigureBlock::ID);
            $block->setAttribute('data-config', (string) json_encode(array_filter([
                'image' => $path,
                'src' => $path === null ? $src : null,
                'alt' => $img?->getAttribute('alt') ?? '',
                'caption' => trim($caption->textContent ?? ''),
            ], static fn (mixed $v): bool => $v !== null), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));

            return $block;
        });
    }

    /**
     * @param  array<string, mixed>  $config  image (public-disk path) or src, alt, caption
     */
    public static function figureHtml(array $config): string
    {
        $image = $config['image'] ?? null;
        $image = is_array($image) ? (reset($image) ?: null) : $image;
        $src = is_string($image) && $image !== '' ? PublicMedia::url($image) : ($config['src'] ?? '');

        if (! is_string($src) || $src === '') {
            return '';
        }

        $alt = is_string($config['alt'] ?? null) ? $config['alt'] : '';
        $caption = is_string($config['caption'] ?? null) ? trim($config['caption']) : '';
        $e = static fn (string $v): string => htmlspecialchars($v, ENT_QUOTES | ENT_HTML5, 'UTF-8');

        return '<figure><img src="'.$e($src).'" alt="'.$e($alt).'" />'
            .($caption === '' ? '' : '<figcaption>'.$e($caption).'</figcaption>')
            .'</figure>';
    }

    /**
     * @param  callable(DOMElement, DOMDocument): ?DOMNode  $replace  null removes the node
     */
    private static function transform(string $html, string $xpath, callable $replace): string
    {
        if (trim($html) === '') {
            return $html;
        }

        $doc = new DOMDocument('1.0', 'UTF-8');
        $doc->loadHTML('<!DOCTYPE html><html><head><meta charset="utf-8"></head><body>'.$html.'</body></html>', LIBXML_NOERROR | LIBXML_NOWARNING);

        $nodes = (new DOMXPath($doc))->query($xpath);
        if ($nodes === false || $nodes->length === 0) {
            return $html;
        }

        foreach (iterator_to_array($nodes) as $node) {
            if (! $node instanceof DOMElement || $node->parentNode === null) {
                continue;
            }

            $replacement = $replace($node, $doc);
            $replacement === null ? $node->parentNode->removeChild($node) : $node->parentNode->replaceChild($replacement, $node);
        }

        $body = $doc->getElementsByTagName('body')->item(0);
        $out = '';
        foreach ($body === null ? [] : $body->childNodes as $child) {
            $out .= $doc->saveHTML($child);
        }

        return $out;
    }

    private static function importHtml(DOMDocument $doc, string $html): ?DOMNode
    {
        if ($html === '') {
            return null;
        }

        $fragment = new DOMDocument('1.0', 'UTF-8');
        $fragment->loadHTML('<!DOCTYPE html><html><head><meta charset="utf-8"></head><body>'.$html.'</body></html>', LIBXML_NOERROR | LIBXML_NOWARNING);
        $node = $fragment->getElementsByTagName('body')->item(0)?->firstChild;

        return $node === null ? null : $doc->importNode($node, true);
    }
}
