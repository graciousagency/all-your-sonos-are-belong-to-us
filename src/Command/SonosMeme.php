<?php

namespace App\Command;

use duncan3dc\Sonos\Controller;
use duncan3dc\Sonos\Interfaces\SpeakerInterface;
use duncan3dc\Sonos\Network;
use duncan3dc\Sonos\Tracks\Spotify;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class SonosMeme extends Command
{
    private $songs = [
        '4uLU6hMCjMI75M1A2tKUQC', # Never gonna give you up
        '34x6hEJgGAOQvmlMql5Ige', # Dangerzone
        '5YbgcwHjQhdT1BYQ4rxWlD', # Don't worry, be happy
        '61KSXW8r9oI1hPHv9Jx1tL', # ????
        '0ikz6tENMONtK6qGkOrU3c', # Rock me amadeus
        '3MrRksHupTVEQ7YbA0FsZK', # Final Count Down
        '7aHRctaQ7vjxVTVmY8OhAA', # We Built This City
        '2IHaGyfxNoFPLJnaEg4GTs', # What is love
        '5VOoT3AIIStTSN8cSMrSD4', # Nyan cat theme
        '2yAVzRiEQooPEJ9SYx11L3', # Im Blue
        '0UREO3QWbXJW3gOUXpK1am', # Fresh prince of bel-air
        '2nUJvBO87SkxCViQsLc9Zr', # Mans not hot
        '2b80TuUiQmpXRq9zpRGNdu', # Running in the 90s
        '1R2SZUOGJqqBiLuvwKOT2Y', # Gangnam style
        '3UL6Lzsocv9Ucizgzid2B0', # We like to party
        '756juKwBfFSyXV3x00xdDX', # Trololol song
        '1B75hgRqe7A4fwee3g3Wmu', # Can't touch this
    ];

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
        $this->setName('sonos:meme')
            ->setDescription('Put on a meme song');
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return void
     * @throws \Throwable
     */
    protected function execute(InputInterface $input, OutputInterface $output): void
    {
        $output->writeln('Getting controllers');
        /** @var Controller $controllers */
        $controllers = $this->network->getControllers();
        foreach ($controllers as $controller) {
            /** @var Controller $controller */
            $output->writeln('Telling controller ' . $controller->getName() . ' to play our meme song');
            $controller->useQueue();
            $controller->getQueue()->addTrack(new Spotify($this->songs[array_rand($this->songs)]), 1);
            $controller->setShuffle(true);
            $controller->selectTrack(0);
            $controller->play();
            $speakers = $controller->getSpeakers();

            /** @var SpeakerInterface $speaker */
            foreach ($speakers as $speaker) {
                if ($speaker->getVolume() < 25) {
                    $output->writeln($speaker->getName() . ' Setting volume to 30, cause the volume was too low');
                    $speaker->setVolume(25);
                }
            }

            $state = $controller->getStateDetails();
            $output->writeln("Now Playing: " . $state->getArtist() . ' - ' . $state->getTitle());
        }

        $output->writeln('Done! our meme song should be playing now!');
    }
}
