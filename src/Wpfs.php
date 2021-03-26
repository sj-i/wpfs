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

use Fuse\FilesystemDefaultImplementationTrait;
use Fuse\FilesystemInterface;
use Fuse\Libc\Errno\Errno;
use Fuse\Libc\Fuse\FuseFileInfo;
use Fuse\Libc\Fuse\FuseFillDir;
use Fuse\Libc\Fuse\FuseReadDirBuffer;
use Fuse\Libc\String\CBytesBuffer;
use Fuse\Libc\Sys\Stat\Stat;

final class Wpfs implements FilesystemInterface
{
    use FilesystemDefaultImplementationTrait;

    public function __construct(private WordPress $word_press)
    {
    }

    public function getattr(string $path, Stat $stat): int
    {
        $stat->st_uid = getmyuid();
        $stat->st_gid = getmygid();
        if ($this->word_press->isDirectory($path)) {
            $stat->st_mode = Stat::S_IFDIR | 0777;
            $stat->st_nlink = 2;
            return 0;
        }

        $post = $this->word_press->getPost($path);
        if (is_null($post)) {
            return -Errno::ENOENT;
        }
        $stat->st_mode = Stat::S_IFREG | 0777;
        $stat->st_nlink = 1;
        $stat->st_size = strlen($post);
        return 0;
    }

    public function readdir(
        string $path,
        FuseReadDirBuffer $buf,
        FuseFillDir $filler,
        int $offset,
        FuseFileInfo $fuse_file_info
    ): int {
        $filler($buf, '.', null, 0);
        $filler($buf, '..', null, 0);
        if (!$this->word_press->isDirectory($path)) {
            return Errno::ENOTDIR;
        }
        foreach ($this->word_press->getPostNamesInDirectory($path) as $value) {
            $filler($buf, $value, null, 0);
        }

        return 0;
    }


    public function open(string $path, FuseFileInfo $fuse_file_info): int
    {
        $post = $this->word_press->getPost($path);
        if (is_null($post)) {
            return -Errno::ENOENT;
        }
        return 0;
    }

    public function read(string $path, CBytesBuffer $buffer, int $size, int $offset, FuseFileInfo $fuse_file_info): int
    {
        $post = $this->word_press->getPost($path);
        if (is_null($post)) {
            return Errno::ENOENT;
        }

        $len = strlen($post);

        if ($offset + $size > $len) {
            $size = ($len - $offset);
        }

        $content = substr($post, $offset, $size);
        $buffer->write($content, $size);

        return $size;
    }

    public function write(string $path, string $buffer, int $size, int $offset, FuseFileInfo $fuse_file_info): int
    {
        $post = $this->word_press->getPost($path);
        if (is_null($post)) {
            return Errno::ENOENT;
        }
        $post = substr_replace($post, $buffer, $offset, $size);
        $this->word_press->updatePost($path, $post);

        return $size;
    }

    public function create(string $path, int $mode, FuseFileInfo $fuse_file_info): int
    {
        $post = $this->word_press->createPost($path);
        if (!is_null($post)) {
            return 0;
        } else {
            return Errno::ENOENT;
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
            return Errno::ENOENT;
        }

        $this->word_press->renamePost($from, $to);
        return 0;
    }
}
