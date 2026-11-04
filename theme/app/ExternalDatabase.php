<?php

declare(strict_types=1);

namespace TaborovaJizda\Theme;

final class ExternalDatabase
{
    private \mysqli $connection;

    public function __construct()
    {
        foreach (['TJ_DB_HOST', 'TJ_DB_USER', 'TJ_DB_PASSWORD', 'TJ_DB_NAME'] as $constant) {
            if (! defined($constant)) {
                throw new \RuntimeException(sprintf('Missing external database constant %s.', $constant));
            }
        }

        $this->connection = new \mysqli(TJ_DB_HOST, TJ_DB_USER, TJ_DB_PASSWORD, TJ_DB_NAME);
        if ($this->connection->connect_errno) {
            throw new \RuntimeException('Unable to connect to the external database.');
        }
        $this->connection->set_charset('utf8mb4');
    }

    public function connection(): \mysqli
    {
        return $this->connection;
    }

    public function __destruct()
    {
        if (isset($this->connection)) {
            $this->connection->close();
        }
    }
}
