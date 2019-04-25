<?php

namespace App\Command;

use duncan3dc\Sonos\Controller;
use duncan3dc\Sonos\Network;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * @TODO Move blacklist to a yaml config file, might be better and easier to configure
 */
class SonosBlacklist extends Command
{
    /**
     * @var array
     */
    private $blacklist = [
        'George Ezra' => [
            'Shotgun'
        ]
    ];

    /**
     * @var Network
     */
    private $network;

    /**
     * @param null|string $name
     * @param Network     $network
     */
    public function __construct(?string $name = null, Network $network)
    {
        parent::__construct($name);
        $this->network = $network;
    }

    protected function configure()
    {
        $this->setName('sonos:blacklist')->setDescription('Lock sonos to a specific song');
    }

    /**
     * @param InputInterface  $input
     * @param OutputInterface $output
     * @return void
     * @throws \Throwable
     */
    protected function execute(InputInterface $input, OutputInterface $output): void
    {
        $loop = \React\EventLoop\Factory::create();
        $output->writeln('Starting our watch :)');

        /** @var Controller $controllers */
        $controllers = $this->network->getControllers();
        $loop->addPeriodicTimer(1, function () use ($output, $controllers) {

            /** @var Controller $controller */
            foreach ($controllers as $controller) {
                $state = $controller->getStateDetails();
                if (!$state->isStreaming() &&
                    isset($this->blacklist[$state->getArtist()]) &&
                    in_array($state->getTitle(), $this->blacklist[$state->getArtist()], true))
                {
                    $output->writeln('We are playing "' . $state->getArtist() . ' - ' . $state->getTitle() . '" at the moment... lets skip it');
                    $controller->setCrossfade(true);
                    $controller->next();

                    sleep(1);

                    $state = $controller->getStateDetails();
                    $output->writeln('Skipped! Now playing "' . $state->getArtist() . ' - ' . $state->getTitle());
                }
            }
        });
        $loop->run();
    }
}
