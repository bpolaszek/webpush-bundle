<?php

namespace BenTools\WebPushBundle\Command;

use Minishlink\WebPush\VAPID;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\HttpKernel\Kernel;

final class WebPushGenerateKeysCommand extends Command
{
    protected static $defaultName = 'webpush:generate:keys';

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('webpush:generate:keys')
            ->setDescription('Generate your VAPID keys for bentools/webpush.');
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $keys = VAPID::createVapidKeys();
        $io->success('Your VAPID keys have been generated!');
        $io->writeln(sprintf('Your public key is: <info>%s</info> ', $keys['publicKey']));
        $io->writeln(sprintf('Your private key is: <info>%s</info>', $keys['privateKey']));
        $io->newLine(2);

        if (-1 === version_compare(Kernel::VERSION, 4)) {
            $io->writeln('Update <info>app/config/config.yml</info>:');
            $io->newLine(1);
            $io->writeln('<info># app/config/config.yml</info>');
        } else {
            $io->writeln('Update <info>config/packages/bentools_webpush.yaml</info>:');
            $io->newLine(1);
            $io->writeln('<info># config/packages/bentools_webpush.yaml</info>');
        }

        $io->writeln(<<<EOF
bentools_webpush:
    settings: 
        public_key: '{$keys['publicKey']}'
        private_key: '{$keys['privateKey']}'    
EOF
        );

        return 0;
    }
}
