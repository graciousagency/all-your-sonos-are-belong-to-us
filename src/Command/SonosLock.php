<?php

namespace App\Command;

use duncan3dc\Sonos\Controller;
use duncan3dc\Sonos\Interfaces\SpeakerInterface;
use duncan3dc\Sonos\Network;
use duncan3dc\Sonos\Tracks\Spotify;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class SonosLock extends Command
{
    private $allowedSongs = [
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
#        '1JlDXahL6Q5InfZwqyKTDX' # What What (in the butt)
        '27AHAtAirQapVldIm4c9ZX', # Kriss Kross jump
        '4fK6E2UywZTJIa5kWnCD6x', # Friday
        '3MjUtNVVq3C8Fn0MP3zhXa', # Baby hit me one more time
        '6naxalmIoLFWR0siv8dnQQ' # Oops i did it again
    ];

    /**
     * @var Network
     */
    private $network;

    /**
     * @var int
     */
    private $queueNumber;

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
        $this->setName('sonos:lock')
            ->setDescription('Lock sonos to a specific song');

        $this->addOption('volume', null, null, 'Adjust volume (0-100)');
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return void
     * @throws \Throwable
     */
    protected function execute(InputInterface $input, OutputInterface $output): void
    {
        $loop = \React\EventLoop\Factory::create();
        $loop->addPeriodicTimer(1, function () use ($output, $input) {
            $output->writeln('Getting controllers');
            /** @var Controller $controllers */
            $controllers = $this->network->getControllers();
            foreach ($controllers as $controller) {
                /** @var Controller $controller */
                $output->writeln('Asking controller ' . $controller->getName() . ' what they are playing');
                $state = $controller->getStateDetails();

                preg_match('/%3atrack%3([\w]+)\?/', $state->getUri(), $output_array);
                $spotifySongId = $output_array[1];

                if (!in_array($spotifySongId, $this->allowedSongs)) {
                    $output->writeln("Now Playing: " . $state->getArtist() . ' - ' . $state->getTitle());
                    $output->writeln('Controller is playing an illegal song! We need to change this');
                    $this->overruleSong($input, $output);
                }

                if (in_array($spotifySongId, $this->allowedSongs)) {
                    $output->writeln('Allowed song being played :)');
                }
            }
        });
        $loop->run();
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return bool
     * @throws \duncan3dc\Sonos\Exceptions\SoapException
     */
    protected function overruleSong(InputInterface $input, OutputInterface $output): bool
    {
        /** @var Controller $controller */
        $controller->useQueue();

        if (null === $this->queueNumber) {
            $queueSize = $controller->getQueue()->count();
            $this->queueNumber = rand(1, $queueSize);
            $output->writeln('Injecting our song as ' . $this->queueNumber . ' of ' . $queueSize . ' in the queue');
            $controller->getQueue()->addTrack(new Spotify($this->songs[array_rand($this->songs)]), $this->queueNumber);
            $controller->setShuffle(true);
            usleep(10000);
        }

        $output->writeln('Skipping to song ' . $this->queueNumber . ' in the queue');
        $controller->selectTrack($this->queueNumber-1);

        usleep(10000);

        $controller->play();

        $volume = $input->getOption('volume');
        if (!empty($volume)) {
            $speakers = $controller->getSpeakers();
            /** @var SpeakerInterface $speaker */
            foreach ($speakers as $speaker) {
                if ($speaker->getVolume() < $volume) {
                    $output->writeln($speaker->getName() . ' Setting volume to ' . $volume . ', cause the volume was too low');
                    $speaker->setVolume($volume);
                }
            }
        }

        $state = $controller->getStateDetails();
        $output->writeln("Now Playing: " . $state->getArtist() . ' - ' . $state->getTitle());
    }
}
