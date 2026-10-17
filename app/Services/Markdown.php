<?php

namespace App\Services;

use League\CommonMark\Environment\Environment;
use League\CommonMark\Extension\CommonMark\CommonMarkCoreExtension;
use League\CommonMark\Extension\GithubFlavoredMarkdownExtension;
use League\CommonMark\Extension\FrontMatter\FrontMatterExtension;
use League\CommonMark\Extension\HeadingPermalink\HeadingPermalinkExtension;
use League\CommonMark\MarkdownConverter;
use DOMDocument;

class Markdown
{
    public function render(string $source, ?string $privatePath = null, array $privatePaths = []): array
    {
        $environment = new Environment([
            'html_input' => 'strip', 'allow_unsafe_links' => false,
            'max_nesting_level' => 40,
            'heading_permalink' => ['symbol' => '#', 'insert' => 'after', 'apply_id_to_heading' => true, 'id_prefix' => '', 'fragment_prefix' => ''],
        ]);
        foreach ([new CommonMarkCoreExtension, new GithubFlavoredMarkdownExtension, new FrontMatterExtension, new HeadingPermalinkExtension] as $extension) {
            $environment->addExtension($extension);
        }
        if ($privatePath !== null) $environment->addInlineParser(new WikiLinkParser($privatePath, $privatePaths), 200);
        $result = (new MarkdownConverter($environment))->convert($source);
        $meta = method_exists($result, 'getFrontMatter') ? $result->getFrontMatter() : [];
        $dom = new DOMDocument('1.0', 'UTF-8');
        $old = libxml_use_internal_errors(true);
        $dom->loadHTML('<?xml encoding="UTF-8"><div id="markdown-root">'.$result.'</div>', LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
        libxml_clear_errors(); libxml_use_internal_errors($old);
        $xpath = new \DOMXPath($dom);
        // Preserve Nextra's explicit heading anchors ([#chang-1]) without
        // enabling arbitrary HTML or a general-purpose attribute extension.
        foreach ($xpath->query('//h1|//h2|//h3|//h4|//h5|//h6') as $heading) {
            if (!preg_match('/\s+\[#([a-zA-Z0-9_-]{1,80})\]\s*#?$/u', $heading->textContent, $match)) continue;
            $id = $match[1];
            foreach ($xpath->query('.//text()', $heading) as $text) {
                $text->nodeValue = str_replace(' [#'.$id.']', '', $text->nodeValue);
            }
            $heading->setAttribute('id', $id);
            foreach ($heading->getElementsByTagName('a') as $link) {
                if ($link->getAttribute('class') === 'heading-permalink') $link->setAttribute('href', '#'.$id);
            }
        }
        // DOM order, rather than heading level, determines the table of contents.
        $toc = [];
        foreach ($xpath->query('//h1|//h2|//h3') as $heading) {
            $toc[] = ['id' => $heading->getAttribute('id'), 'title' => trim(rtrim($heading->textContent, '#')), 'level' => (int) substr($heading->tagName, 1)];
        }
        foreach ($dom->getElementsByTagName('a') as $node) {
            $href = $node->getAttribute('href');
            if ($privatePath !== null) {
                if (!$node->hasAttribute('data-private-link')) $node->setAttribute('href', $this->privateUrl($href, $privatePath, false));
            } elseif (!preg_match('~^(?:[a-z][a-z0-9+.-]*:|//|#)~i', $href)) {
                $parts = explode('#', $href, 2);
                $parts[0] = preg_replace('~(?:/index)?\.mdx?$~', '', $parts[0]);
                $node->setAttribute('href', implode('#', $parts));
            }
            if (preg_match('~^https?://~', $node->getAttribute('href'))) {
                $node->setAttribute('rel', 'noopener noreferrer');
            }
        }
        foreach ($dom->getElementsByTagName('img') as $node) {
            $src = $node->getAttribute('src');
            // Private notes cannot make the browser fetch remote tracking images.
            $node->setAttribute('src', $privatePath !== null && !$node->hasAttribute('data-private-link') ? $this->privateUrl($src, $privatePath, true) : $src);
            $node->setAttribute('loading', 'lazy');
            $node->setAttribute('referrerpolicy', 'no-referrer');
        }
        $html = '';
        foreach ($dom->getElementById('markdown-root')->childNodes as $child) $html .= $dom->saveHTML($child);
        return ['html' => $html, 'toc' => $toc, 'title' => is_array($meta) && is_scalar($meta['title'] ?? null) ? (string) $meta['title'] : null, 'description' => is_array($meta) && is_scalar($meta['description'] ?? null) ? (string) $meta['description'] : ''];
    }

    private function privateUrl(string $url, string $notePath, bool $image): string
    {
        if (str_starts_with($url, '#') && !$image) return $url;
        if (preg_match('~^(?:[a-z][a-z0-9+.-]*:|//)~i', $url)) return $image ? '' : $url;
        $parts = explode('#', $url, 2);
        $path = rawurldecode(explode('?', $parts[0], 2)[0]);
        $segments = [];
        $combined = str_starts_with($path, '/') ? ltrim($path, '/') : (dirname($notePath) === '.' ? '' : dirname($notePath).'/').$path;
        foreach (explode('/', $combined) as $segment) {
            if ($segment === '..') array_pop($segments);
            elseif ($segment !== '' && $segment !== '.') $segments[] = $segment;
        }
        $path = implode('/', $segments);
        return ($image ? '/private/asset?path=' : '/private/note?path=').rawurlencode($path).(isset($parts[1]) && !$image ? '#'.rawurlencode($parts[1]) : '');
    }
}
