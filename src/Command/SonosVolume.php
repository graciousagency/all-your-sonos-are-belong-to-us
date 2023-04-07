<?php

namespace App\Command;

use duncan3dc\Sonos\Controller;
use duncan3dc\Sonos\Interfaces\SpeakerInterface;
use duncan3dc\Sonos\Network;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class SonosVolume extends Command
{
    private Network $network;

    public function __construct(Network $network)
    {
        parent::__construct();
        $this->network = $network;
    }

    protected function configure()
    {
        $this->setName('sonos:volume')
            ->setDescription('Get the volume and/or set it to the given volume');

        $this->addArgument('volume', null, 'Volume we want (0-100)', false);
        $this->addArgument('speaker', InputArgument::OPTIONAL, 'Room of the speaker', false);
        $this->addArgument('bass', InputArgument::OPTIONAL, 'Bass (-5 - 5)', 0);
        $this->addArgument('treble', InputArgument::OPTIONAL, 'Treble (-5 - 5)', 0);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $controllers = $this->network->getControllers();

        foreach ($controllers as $controller) {
            $output->writeln('Controller: ' . $controller->getIp() .' :: '. $controller->getRoom());
            $speakers = $controller->getSpeakers();

            $volume = (int) $input->getArgument('volume');
            $treble = (int) $input->getArgument('treble');
            $bass = (int) $input->getArgument('bass');
            $targetSpeakerRoom = (string) $input->getArgument('speaker');
            if ($volume < 0 || $volume > 100) {
                $volume = false;
                $output->writeln('Volume is not between 0 and 100, ignoring it');
            }

            if ($bass < -5 || $bass > 5) {
                $bass = 0;
            }

            if ($treble < -5 || $treble > 5) {
                $treble = 0;
            }

            foreach ($speakers as $speaker) {
                if ($targetSpeakerRoom && $speaker->getRoom() !== $targetSpeakerRoom) {
                    continue;
                }

                $output->writeln($speaker->getRoom() . ' is at volume ' . $speaker->getVolume());
                if ($volume && $volume != $speaker->getVolume()) {
                    $output->writeln("Volume doesn't match requested volume, changing it to " . $volume);
                    $speaker->setVolume($volume);
                }

                if ($treble != $speaker->getTreble()) {
                    $speaker->setTreble($treble);
                    $output->writeln("Setting treble to " . $treble);
                }

                if ($bass != $speaker->getBass()) {
                    $speaker->setBass($bass);
                    $output->writeln("Setting bass to " . $bass);
                }


            }
        }
        return 0;
    }
}
