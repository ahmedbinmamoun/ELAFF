<?php

declare(strict_types=1);

namespace Elaf\Shared\Infrastructure\Html;

use Elaf\Shared\Application\Html\HtmlSanitizer;
use Elaf\Shared\Application\Html\SanitizerProfile;
use HTMLPurifier;
use HTMLPurifier_Config;
use HTMLPurifier_HTMLDefinition;

/**
 * Allow-list sanitizer (FR-ART-03, ADR-0004). Headings h2-h4, lists, quotes,
 * links, tables and captioned images; never scripts, iframes, embeds,
 * inline styles or event handlers.
 */
final class HtmlPurifierSanitizer implements HtmlSanitizer
{
    private const STRUCTURE = 'p,br,strong,b,em,i,u,s,sub,sup,h2,h3,h4,ul,ol,li,blockquote,hr,'
        .'thead,tbody,tfoot,tr,caption,figcaption,'
        .'th[colspan|rowspan|scope],td[colspan|rowspan],img[src|alt|width|height]';

    private const STAFF = self::STRUCTURE.',a[href|title|target|rel],table[class],figure[class],span[class]';

    private const CONTRIBUTOR = self::STRUCTURE.',a[href|title],table,figure,span';

    /** @var array<string, HTMLPurifier> */
    private array $purifiers = [];

    /**
     * @param  string|null  $cacheDirectory  writable directory for HTMLPurifier's definition cache; null disables it
     */
    public function __construct(private readonly ?string $cacheDirectory = null) {}

    public function sanitize(string $html, SanitizerProfile $profile): string
    {
        return trim($this->purifier($profile)->purify($html));
    }

    private function purifier(SanitizerProfile $profile): HTMLPurifier
    {
        return $this->purifiers[$profile->value] ??= new HTMLPurifier($this->config($profile));
    }

    private function config(SanitizerProfile $profile): HTMLPurifier_Config
    {
        $config = HTMLPurifier_Config::createDefault();
        $config->set('Core.Encoding', 'UTF-8');
        $config->set('HTML.Doctype', 'XHTML 1.0 Transitional');
        $config->set('HTML.Allowed', $profile === SanitizerProfile::Staff ? self::STAFF : self::CONTRIBUTOR);
        $config->set('URI.AllowedSchemes', ['http' => true, 'https' => true, 'mailto' => true]);
        $config->set('HTML.SafeIframe', false);
        $config->set('HTML.SafeEmbed', false);
        $config->set('HTML.SafeObject', false);
        $config->set('CSS.AllowedProperties', []);
        $config->set('HTML.DefinitionID', 'elaf-'.$profile->value);
        $config->set('HTML.DefinitionRev', 1);

        if ($profile === SanitizerProfile::Staff) {
            $config->set('Attr.AllowedFrameTargets', ['_blank']);
            $config->set('Attr.AllowedRel', ['noopener', 'noreferrer', 'nofollow']);
        } else {
            $config->set('HTML.Nofollow', true);
        }

        if ($this->cacheDirectory === null) {
            $config->set('Cache.DefinitionImpl', null);
        } else {
            $config->set('Cache.SerializerPath', $this->cacheDirectory);
        }

        $definition = $config->maybeGetRawHTMLDefinition();

        if ($definition instanceof HTMLPurifier_HTMLDefinition) {
            // HTML5 elements HTMLPurifier does not know: captioned images.
            $definition->addElement('figure', 'Block', 'Flow', 'Common');
            $definition->addElement('figcaption', 'Block', 'Flow', 'Common');
        }

        return $config;
    }
}
