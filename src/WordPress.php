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

interface WordPress
{
    public function isDirectory(string $path): bool;
    public function getPost(string $path): ?string;
    /** @return string[] */
    public function getPostNamesInDirectory(string $path): array;
    public function updatePost(string $path, string $post_content): void;
    public function createPost(string $path): ?string;
    public function deletePost(string $path): void;
    public function renamePost(string $from, string $to): void;
}
