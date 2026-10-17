<?php
namespace App\Services;

use League\CommonMark\Extension\CommonMark\Node\Inline\{Link,Image};
use League\CommonMark\Parser\Inline\{InlineParserInterface,InlineParserMatch};
use League\CommonMark\Parser\InlineParserContext;

/** Private-only inline parser; fenced code and inline code stay literal. */
class WikiLinkParser implements InlineParserInterface
{
    public function __construct(private string $from, private array $paths) {}
    public function getMatchDefinition(): InlineParserMatch { return InlineParserMatch::oneOf('![[', '[['); }
    public function parse(InlineParserContext $context): bool
    {
        $cursor = $context->getCursor();
        if (!preg_match('/^(!?)\[\[([^\]\n]{1,500})\]\]/u', $cursor->getRemainder(), $match)) return false;
        $cursor->advanceBy(mb_strlen($match[0]));
        [$target, $label] = array_pad(explode('|', $match[2], 2), 2, null);
        [$name, $fragment] = array_pad(explode('#', trim($target), 2), 2, null);
        $image = $match[1] === '!' && preg_match('/\.(png|jpe?g|gif|webp|avif|img)$/i', $name);
        $resolved = $this->resolve($name, (bool) $image);
        $label ??= $name ?: $fragment ?: 'Ghi chú';
        if ($resolved !== null) {
            $url = ($image ? '/private/asset?path=' : '/private/note?path=').rawurlencode($resolved);
            if ($fragment && !$image) $url .= '#'.rawurlencode($fragment);
        } else $url = '/private/search?q='.rawurlencode($name);
        $node = $image && $resolved !== null ? new Image($url, $label) : new Link($url, $label);
        $node->data->set('attributes', ['data-private-link' => '1']);
        $context->getContainer()->appendChild($node);
        return true;
    }
    private function resolve(string $target, bool $image): ?string
    {
        if ($target === '') return $this->from;
        if (preg_match('~[\x00-\x1f\\\\:]~', $target)) return null;
        $base = dirname($this->from) === '.' ? '' : dirname($this->from).'/';
        $candidates = [];
        foreach ([$base.$target, ltrim($target, '/')] as $candidate) {
            $parts = [];
            foreach (explode('/', $candidate) as $part) {
                if ($part === '..') array_pop($parts);
                elseif ($part !== '' && $part !== '.') $parts[] = $part;
            }
            $normalized = implode('/', $parts);
            foreach ($image || preg_match('/\.mdx?$/i', $normalized) ? [$normalized] : [$normalized.'.md', $normalized.'.mdx'] as $path) {
                if (in_array($path, $this->paths, true)) return $path;
            }
        }
        foreach ($this->paths as $path) {
            if ($image ? basename($path) === basename($target) : preg_replace('/\.mdx?$/i', '', basename($path)) === preg_replace('/\.mdx?$/i', '', basename($target))) $candidates[] = $path;
        }
        return count($candidates) === 1 ? $candidates[0] : null;
    }
}
