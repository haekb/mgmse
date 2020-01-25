<?php

namespace Tests\Unit\Socket\Stubs;

use Evenement\EventEmitter;
use React\Datagram\SocketInterface;
use React\Stream\WritableStreamInterface;
use React\Stream\Util;

/**
 * UDP Connection Stub
 * Class ConnectionStub
 * @package Tests\Unit\Socket\Stubs
 */
class UDPSocketStub extends EventEmitter implements SocketInterface
{
    private $data = '';

    public function send($data, $remoteAddress = null)
    {
        $this->data .= $data;

        return true;
    }

    public function isReadable()
    {
        return true;
    }

    public function isWritable()
    {
        return true;
    }

    public function pause()
    {
    }

    public function resume()
    {
    }

    public function pipe(WritableStreamInterface $dest, array $options = array())
    {
        Util::pipe($this, $dest, $options);

        return $dest;
    }

    public function end($data = null)
    {
    }

    public function close()
    {
    }

    public function getData()
    {
        return $this->data;
    }

    public function getRemoteAddress()
    {
        return '127.0.0.1';
    }

    public function getLocalAddress()
    {
        return '127.0.0.1';
    }
}
