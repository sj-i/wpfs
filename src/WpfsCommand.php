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

use Fuse\Mounter;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

final class WpfsCommand extends Command
{
    public function __construct(private Mounter $mounter)
    {
        parent::__construct(null);
    }

    public function configure(): void
    {
        $this->setName('mount')
            ->setDescription('mount wordpress to the specified path')
            ->addArgument('path', InputArgument::REQUIRED, 'the path to mount wordpress')
        ;
    }

    public function execute(InputInterface $input, OutputInterface $output): int
    {
        /** @var string $path */
        $path = $input->getArgument('path');

        $config = require __DIR__ . '/../config/wp.php';

        \Corcel\Database::connect($config);

        $this->mounter->mount(
            $path,
            new Wpfs(
                new WordPressCorcelDriver()
            )
        );

        return 0;
    }
}
