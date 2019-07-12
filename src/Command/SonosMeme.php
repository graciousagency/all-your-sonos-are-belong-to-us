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
        '1JlDXahL6Q5InfZwqyKTDX', # What What (in the butt)
        '27AHAtAirQapVldIm4c9ZX', # Kriss Kross jump
        '4fK6E2UywZTJIa5kWnCD6x', # Friday
        '3MjUtNVVq3C8Fn0MP3zhXa', # Baby hit me one more time
        '6naxalmIoLFWR0siv8dnQQ', # Oops i did it again
        '0p2dFdbKM7QV8r8tdySuoE', # Jacking it in san diego
        '35hWFT2iRk3hUYUnYRY9YL', # Kanye's Birthday
        '7MKNP9GEcCj4Vcfw3IerQ6', # Old town road,
        '2KH16WveTQWT6KOG9Rg6e2', # Eye of the tiger
        '3SFXsFpeGmBTtQvKiwYMDA', # Pretty fly for a white guy
        '5PONCrsJnRyMsHBsnUS6I9',
        '1oTHteQbmJw15rPxPVXUTv', # Insane in the brain,
        '1F2WlnUZgreayYJE0cbPk7', # Mah na mah na
        '5awDvzxWfd53SSrsRZ8pXO', # Du Hast
        '0GxIAMtKFYTQZpR1avO6HB', # Mann Gegen Mann
        '5ygDXis42ncn6kYG14lEVG', # Baby Shark
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

    protected function configure(): void
    {
        $this->setName('sonos:meme')
            ->setDescription('Put on a meme song');

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
        $output->writeln('Getting controllers');
        $controllers = $this->network->getControllers();
        $randomSongKey = array_rand($this->songs);
        /** @var Controller $controllers */
        foreach ($controllers as $controller) {
            /** @var Controller $controller */
            $output->writeln('Telling controller ' . $controller->getName() . ' to play our meme song');
            $controller->useQueue();

            $queueSize = $controller->getQueue()->count();
            if (!$queueSize) {
                $queueNumber = 1;
            }

            if ($queueSize) {
                $queueNumber = random_int(1, $queueSize);
            }

            $output->writeln('Injecting our song as ' . $queueNumber . ' of ' . $queueSize . ' in the queue');
            $controller->getQueue()->addTrack(
                new Spotify(
                    $this->songs[$randomSongKey]
                ),
                $queueNumber
            );

            $controller->setShuffle(true);
            $output->writeln('Skipping to song ' . $queueNumber . ' in the queue');
            $controller->selectTrack($queueNumber-1);

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
            $output->writeln('Now Playing: ' . $state->getArtist() . ' - ' . $state->getTitle());
        }

        $output->writeln('Done! our meme song should be playing now!');
    }
}
