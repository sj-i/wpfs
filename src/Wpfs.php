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

use FFI;
use FFI\CData;
use Fuse\FilesystemDefaultImplementationTrait;
use Fuse\FilesystemInterface;
use Fuse\Fuse;

final class Wpfs implements FilesystemInterface
{
    use FilesystemDefaultImplementationTrait;

    private const ENOENT = 2;
    private const ENOTDIR = 20;
    private const S_IFDIR = 0040000;
    private const S_IFREG = 0100000;

    public function __construct(private WordPress $word_press)
    {
    }

    public function getattr(string $path, CData $stat): int
    {
        $this->initializeStructStat($stat);

        $stat->st_uid = getmyuid();
        $stat->st_gid = getmygid();
        if ($this->word_press->isDirectory($path)) {
            $stat->st_mode = self::S_IFDIR | 0777;
            $stat->st_link = 2;
            return 0;
        }

        $post = $this->word_press->getPost($path);
        if (is_null($post)) {
            return -self::ENOENT;
        }
        $stat->st_mode = self::S_IFREG | 0777;
        $stat->st_nlink = 1;
        $stat->st_size = strlen((string)$post);
        return 0;
    }

    /**
     * @param string $path
     * @param CData $buf
     * @param CData|callable $filler
     * @param int $offset
     * @param CData $fuse_file_info
     * @return int
     * @psalm-param callable(CData $buf, string $name, CData $stat, int $offset):int $filler
     */
    public function readdir(string $path, CData $buf, CData $filler, int $offset, CData $fuse_file_info): int
    {
        $filler($buf, '.', null, 0);
        $filler($buf, '..', null, 0);
        if (!$this->word_press->isDirectory($path)) {
            return self::ENOTDIR;
        }
        foreach ($this->word_press->getPostNamesInDirectory($path) as $key => $value) {
            $filler($buf, (string)$key, null, 0);
        }

        return 0;
    }

    public function open(string $path, CData $fuse_file_info): int
    {
        $post = $this->word_press->getPost($path);
        if (is_null($post)) {
            return -self::ENOENT;
        }
        return 0;
    }

    public function read(string $path, CData $buffer, int $size, int $offset, CData $fuse_file_info): int
    {
        $post = $this->word_press->getPost($path);

        $len = strlen((string)$post);

        if ($offset + $size > $len) {
            $size = ($len - $offset);
        }

        $content = substr((string)$post, $offset, $size);
        FFI::memcpy($buffer, $content, $size);

        return $size;
    }

    public function write(string $path, string $buffer, int $size, int $offset, CData $fuse_file_info): int
    {
        $post = $this->word_press->getPost($path);
        $post = substr_replace($post, $buffer, $offset, $size);
        $this->word_press->updatePost($path, $post);

        return $size;
    }

    public function create(string $path, int $mode, CData $fuse_file_info): int
    {
        $post = $this->word_press->createPost($path);
        if (!is_null($post)) {
            return 0;
        } else {
            return self::ENOENT;
        }
    }

    public function unlink(string $path): int
    {
        $this->word_press->deletePost($path);
        return 0;
    }

    public function rename(string $from, string $to): int
    {
        $post = $this->word_press->getPost($from);
        if (is_null($post)) {
            return self::ENOENT;
        }

        $this->word_press->renamePost($from, $to);
    }

    private function initializeStructStat(CData $struct_stat): void
    {
        $typename = 'struct stat';
        $type = Fuse::getInstance()->ffi->type(
            $typename
        );
        $size = FFI::sizeof(
            $type
        );

        FFI::memset($struct_stat, 0, $size);
    }
}