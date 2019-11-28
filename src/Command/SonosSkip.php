<?php

namespace App\Command;

use duncan3dc\Sonos\Controller;
use duncan3dc\Sonos\Network;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class SonosSkip extends Command
{
    /**
     * @var Network
     */
    private $network;

    /**
     * @param null|string $name
     * @param Network $network
     */
    public function __construct(?string $name = null, Network $network)
    {
        parent::__construct($name);
        $this->network = $network;
    }

    protected function configure()
    {
        $this->setName('sonos:skip')
            ->setDescription('Skip the current song');
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return void
     * @throws \Throwable
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        /** @var Controller $controllers */
        $controllers = $this->network->getControllers();

        /** @var Controller $controller */
        foreach ($controllers as $controller) {
            $output->writeln('Controller: ' . $controller->getName());
            $state = $controller->getStateDetails();

            if (!$state->isStreaming()) {
                $output->writeln('We are playing "' . $state->getArtist() . ' - ' . $state->getTitle() . '" at the moment... lets skip it');
                $controller->setCrossfade(true);
                $controller->next();
                usleep(10000);
                $state = $controller->getStateDetails();
                $output->writeln('Skipped! Now playing "' . $state->getArtist() . ' - ' . $state->getTitle());
            } else {
                $output->writeln('Currently playing a stream, we cant skip songs on a stream :(');
            }
        }
        return 0;
    }
}
