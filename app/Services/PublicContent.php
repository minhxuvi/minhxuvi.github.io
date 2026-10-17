<?php

namespace App\Services;

use Illuminate\Support\Facades\File;

class PublicContent
{
    public function pages(): array
    {
        $pages = [];
        foreach (File::allFiles(base_path('content')) as $file) {
            if (!in_array($file->getExtension(), ['md', 'mdx'])) continue;
            $path = str_replace('\\', '/', $file->getRelativePathname());
            $slug = preg_replace('~(?:^|/)index\.mdx?$~', '', $path);
            $slug = preg_replace('~\.mdx?$~', '', $slug);
            $source = $file->getContents();
            $rendered = app(Markdown::class)->render($source);
            $pages['/'.$slug] = [...$rendered, 'url' => '/'.$slug, 'source' => $source, 'title' => $rendered['title'] ?: basename($slug)];
        }
        return $pages;
    }

    public function menu(): array
    {
        return json_decode(file_get_contents(config_path('navigation.json')), true, flags: JSON_THROW_ON_ERROR);
    }

    public function search(string $query): array
    {
        $query = mb_strtolower(trim($query));
        if ($query === '') return [];
        return array_values(array_map(function ($page) {
            return ['title' => $page['title'], 'url' => $page['url'], 'excerpt' => mb_substr(preg_replace('/\s+/u', ' ', strip_tags($page['html'])), 0, 200)];
        }, array_filter($this->pages(), fn ($page) => str_contains(mb_strtolower($page['title'].' '.$page['source']), $query))));
    }
}
