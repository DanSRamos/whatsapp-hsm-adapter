<?php

namespace App\Module\Health;

use PhpAmqpLib\Connection\AbstractConnection;
use PhpAmqpLib\Exception\AMQPConnectionClosedException;
use PhpAmqpLib\Exception\AMQPIOException;

class RabbitMqHealthCheck
{
    public function __construct(private readonly AbstractConnection $connection)
    {
    }

    public function healthy(): bool
    {
        try {
            if ($this->connection->isConnected()) {
                return true;
            }

            $this->connection->reconnect();

            return $this->connection->isConnected();
        } catch (AMQPConnectionClosedException|AMQPIOException) {
            return false;
        }
    }
}
