<?php

namespace App\Track;

class SmbTrack implements \duncan3dc\Sonos\Interfaces\UriInterface
{
    /**
     * @var string
     */
    private $file;

    /**
     * @var string
     */
    private $smbServerName;

    /**
     * @param string $file
     * @param        $smbServerName
     */
    public function __construct(string $file, $smbServerName)
    {
        $this->file = $file;
        $this->smbServerName = $smbServerName;
    }

    /**
     * @return string
     */
    public function getUri(): string
    {
        $parts = explode('/', $this->file);
        $parts = array_map('rawurlencode', $parts);
        return 'x-file-cifs://'.$this->smbServerName.'/' . implode('/', $parts);
    }

    /**
     * @return string
     */
    public function getMetaData(): string
    {
        return \duncan3dc\Sonos\Helper::createMetaDataXml('-1', '-1', [
            'res'               =>  $this->getUri(),
            'upnp:albumArtURI'  =>  '',
            'dc:title'          =>  'Title',
            'upnp:class'        =>  'object.item.audioItem.musicTrack',
            'dc:creator'        =>  'Artist',
            'upnp:album'        =>  'Album',
        ]);
    }


}
