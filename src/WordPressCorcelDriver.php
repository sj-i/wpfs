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

use Corcel\Model\Page;
use Corcel\Model\Post;

final class WordPressCorcelDriver implements WordPress
{
    public function isDirectory(string $path): bool
    {
        return $path === '/';
    }

    public function getPost(string $path): ?string
    {
        /** @var ?Page $page */
        $page = Post::slug($this->getSlugFromPath($path))->first();
        if (is_null($page)) {
            return null;
        }
        return $page->post_content;
    }

    public function getPostNamesInDirectory(string $path): array
    {
        return Post::all()->map(fn (Post $post) => $this->getPathFromSlug($post->post_name))->toArray();
    }

    public function updatePost(string $path, string $post_content): void
    {
        /** @var ?Post $page */
        $post = Post::slug($this->getSlugFromPath($path))->first();
        if (is_null($post)) {
            return;
        }
        $post->post_content = $post_content;
        $post->update();
    }

    public function createPost(string $path): ?string
    {
        $post = new Post();
        $post->post_name = $this->getSlugFromPath($path);
        $post->post_title = 'new post';
        $post->post_type = 'post';
        $post->save();
    }

    public function deletePost(string $path): void
    {
        /** @var ?Post $page */
        $post = Post::slug($this->getSlugFromPath($path))->first();
        if (is_null($post)) {
            return;
        }
        $post->delete();
        // clear cache
    }

    public function renamePost(string $from, string $to): void
    {
        /** @var ?Post $page */
        $post = Post::slug($this->getSlugFromPath($from))->first();
        if (is_null($post)) {
            return;
        }
        $post->post_name = $this->getSlugFromPath($to);
        $post->save();
        // clear cache
    }

    private function getSlugFromPath(string $path)
    {
        // get from cache
    }

    private function getPathFromSlug(string $slug)
    {
        // get from cache
    }
}