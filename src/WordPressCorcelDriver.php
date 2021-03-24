<?php

/**
 * This file is part of the sj-i/wpfs package.
 *
 * (c) sji <sji@sj-i.dev>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Wpfs;

use Corcel\Model\Post;

final class WordPressCorcelDriver implements WordPress
{
    /** @var array<string, string> */
    private array $path_to_slug_cache = [];
    /** @var array<string, string> */
    private array $slug_to_path_cache = [];

    public function isDirectory(string $path): bool
    {
        return $path === '/';
    }

    public function getPost(string $path): ?string
    {
        /** @var ?Post $post */
        $post = Post::find($this->getIdFromPath($path));
        if (is_null($post)) {
            return null;
        }
        $post_content = $post->post_content;
        assert(is_null($post_content) or is_string($post_content));
        return $post_content;
    }

    /** @return string[] */
    public function getPostNamesInDirectory(string $path): array
    {
        /** @var \Illuminate\Database\Eloquent\Collection $all */
        $all = Post::all();
        /** @var string[] */
        return $all->map(
            function (Post $post): string {
                assert(is_string($post->ID) and is_string($post->post_name));
                return $this->getPathFromSlug($post->ID . '_' . $post->post_name);
            }
        )->toArray();
    }

    public function updatePost(string $path, string $post_content): void
    {
        /** @var ?Post $post */
        $post = Post::find($this->getIdFromPath($path));
        if (is_null($post)) {
            return;
        }
        $post->post_content = $post_content;
        $post->update();
    }

    public function createPost(string $path): string
    {
        $post = new Post();
        $post->post_name = $this->getSlugFromPath($path);
        $post->post_title = 'new post';
        $post->post_type = 'post';
        $post->save();
        return '';
    }

    public function deletePost(string $path): void
    {
        /** @var ?Post $post */
        $post = Post::find($this->getIdFromPath($path));
        if (is_null($post)) {
            return;
        }
        $post->delete();
        // clear cache
    }

    public function renamePost(string $from, string $to): void
    {
        /** @var ?Post $post */
        $post = Post::find($this->getIdFromPath($from));
        if (is_null($post)) {
            return;
        }
        $post->post_name = $this->getSlugFromPath($to);
        $post->save();
        // clear cache
    }

    private function getIdFromPath(string $path): string
    {
        preg_match('/^\/([\d]+_)/', $path, $matches);
        return $matches[1];
    }

    private function getSlugFromPath(string $path): string
    {
        $path = substr($path, 1);
        if (isset($this->path_to_slug_cache[$path])) {
            return $this->path_to_slug_cache[$path];
        }
        return $this->tryUnsanitizePath($path);
    }

    private function getPathFromSlug(string $slug): string
    {
        if (!isset($this->slug_to_path_cache[$slug])) {
            $this->slug_to_path_cache[$slug] = $path = $this->sanitizePath($slug);
            $this->path_to_slug_cache[$path] = $slug;
        }
        return $this->slug_to_path_cache[$slug];
    }

    private function sanitizePath(string $slug): string
    {
        $slug = urldecode($slug);
        return str_replace('/', '_', $slug);
    }

    private function tryUnsanitizePath(string $path): string
    {
        $path = preg_replace('/^[\d]+_/', '', $path);
        return str_replace('_', '/', $path);
    }
}
