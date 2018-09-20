<?php

namespace App\Command;

use duncan3dc\Sonos\Controller;
use duncan3dc\Sonos\Interfaces\SpeakerInterface;
use duncan3dc\Sonos\Network;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class SonosVolume extends Command
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
        $this->setName('sonos:volume')
            ->setDescription('Get the volume and/or set it to the given volume');

        $this->addArgument('volume', null, 'Volume we want (0-100)', false);
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return void
     * @throws \Throwable
     */
    protected function execute(InputInterface $input, OutputInterface $output): void
    {
        /** @var Controller $controllers */
        $controllers = $this->network->getControllers();

        /** @var Controller $controller */
        foreach ($controllers as $controller) {
            $output->writeln('Controller: ' . $controller->getName());
            $speakers = $controller->getSpeakers();

            $volume = (int) $input->getArgument('volume');
            if ($volume < 0 || $volume > 100) {
                $volume = false;
                $output->writeln('Volume is not between 0 and 100, ignoring it');
            }
            /** @var SpeakerInterface $speaker */
            foreach ($speakers as $speaker) {
                $output->writeln($speaker->getName() . ' is at volume ' . $speaker->getVolume());
                if ($volume && $volume != $speaker->getVolume()) {
                    $output->writeln("Volume doesn't match requested volume, changing it to " . $volume);
                    $speaker->setVolume($volume);
                }
            }
        }
    }
}
