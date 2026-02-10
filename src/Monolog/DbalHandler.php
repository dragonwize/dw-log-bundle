<?php

declare(strict_types=1);

namespace Dragonwize\DwLogBundle\Monolog;

use Carbon\CarbonImmutable;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\ParameterType;
use Monolog\Handler\AbstractProcessingHandler;
use Monolog\Level;
use Monolog\LogRecord;

class DbalHandler extends AbstractProcessingHandler
{
    private const TABLE_NAME = 'dw_log';

    public function __construct(
        private Connection $conn,
        int|string|Level $level = Level::Debug,
        bool $bubble = true
    ) {
        parent::__construct($level, $bubble);
    }

    protected function write(LogRecord $record): void
    {
        try {
            $this->conn->insert(
                self::TABLE_NAME,
                [
                    'channel'    => $record->channel,
                    'level'      => $record->level->value,
                    'level_name' => $record->level->getName(),
                    'message'    => $record->message,
                    'context'    => json_encode($record->context),
                    'extra'      => json_encode($record->extra),
                    'created_at' => CarbonImmutable::now('UTC')->toIso8601ZuluString('millisecond'),
                ],
                [
                    'channel'    => ParameterType::STRING,
                    'level'      => ParameterType::INTEGER,
                    'level_name' => ParameterType::STRING,
                    'message'    => ParameterType::STRING,
                    'context'    => ParameterType::STRING,
                    'extra'      => ParameterType::STRING,
                    'created_at' => ParameterType::STRING,
                ]
            );
        } catch (\Throwable $e) {
            // If fail writing to DB based log, silently continue to prevent
            // circular dependency issues. DB base logging should not be the
            // only log access used.
            // This could be prevented by creating the table and schema outside
            // of Symfony but that would hurt/complicate the DX.
        }
    }
}
