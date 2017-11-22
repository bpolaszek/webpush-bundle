<?php

namespace BenTools\WebPushBundle\Command;

use Minishlink\WebPush\VAPID;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

final class WebPushGenerateKeysCommand extends ContainerAwareCommand
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('webpush:generate:keys')
            ->setDescription('Generate your VAPID keys for BenTools/WebPush.');
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new SymfonyStyle($input, $output);
        $keys = VAPID::createVapidKeys();
        $io->success('Your VAPID keys have been generated!');
        $io->writeln(sprintf('Your public key is: <info>%s</info> ', $keys['publicKey']));
        $io->writeln(sprintf('Your private key is: <info>%s</info>', $keys['privateKey']));
        $io->newLine(2);
        $io->writeln('Store them in your <info>app/config/parameters.yml</info>:');
        $io->newLine(1);
        $io->writeln(<<<EOF
<info># app/config/parameters.yml.dist</info>
parameters:    
    bentools_webpush.public_key: ~
    bentools_webpush.private_key: ~        
EOF
        );

        $io->newLine(1);
        $io->writeln(<<<EOF
<info># app/config/parameters.yml</info>
parameters:    
    bentools_webpush.public_key: '{$keys['publicKey']}'
    bentools_webpush.private_key: '{$keys['privateKey']}'        
EOF
        );

        $io->newLine(2);
        $io->writeln('Then update your <info>app/config/config.yml</info>:');
        $io->newLine(1);
        $io->writeln(<<<EOF
<info># app/config/config.yml</info>        
bentools_webpush:
    settings:
        public_key: '%bentools_webpush.public_key%'
        private_key: '%bentools_webpush.private_key%'
EOF
        );
    }
}
