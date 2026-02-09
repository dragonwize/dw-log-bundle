<?php

declare(strict_types=1);

namespace Dragonwize\DwLogBundle\Command;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Types\Types;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'dw:log:create-table',
    description: 'Create the dw_log table if it does not exist'
)]
class CreateTableCommand extends Command
{
    private const string TABLE_NAME = 'dw_log';

    public function __construct(
        private readonly bool $isEnabled,
        private readonly Connection $conn
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        if (!$this->isEnabled) {
            // Not enabled in this environment.
            return Command::SUCCESS;
        }

        $io = new SymfonyStyle($input, $output);

        // Check if table already exists using plain SQL
        if ($this->tableExists()) {
            $io->info(\sprintf('Table "%s" already exists.', self::TABLE_NAME));

            return Command::SUCCESS;
        }

        $io->info(\sprintf('Creating table "%s"...', self::TABLE_NAME));

        // Create schema
        $schema = new Schema();
        $table  = $schema->createTable(self::TABLE_NAME);

        // Add columns
        $table->addColumn('id', Types::BIGINT)
            ->setAutoincrement(true)
            ->setNotnull(true)
            ->setComment('Auto-incrementing big integer ID');

        $table->addColumn('channel', Types::STRING)
            ->setLength(50)
            ->setNotnull(true)
            ->setComment('Monolog channel name');

        $table->addColumn('level', Types::SMALLINT)
            ->setNotnull(true)
            ->setComment('Numeric log level (PSR-3)');

        $table->addColumn('level_name', Types::STRING)
            ->setLength(50)
            ->setNotnull(true)
            ->setComment('Human-readable log level');

        $table->addColumn('message', Types::TEXT)
            ->setNotnull(true)
            ->setComment('Log message');

        $table->addColumn('context', Types::JSONB)
            ->setNotnull(true)
            ->setDefault('{}')
            ->setComment('Additional context data as JSON');

        $table->addColumn('extra', Types::JSONB)
            ->setNotnull(true)
            ->setDefault('{}')
            ->setComment('Extra data added by processors as JSON');

        $table->addColumn('created_at', 'carbon_immutable')
            ->setNotnull(true)
            ->setComment('When the log entry was created');

        // Set primary key
        $table->setPrimaryKey(['id']);

        // Add indexes for optimal query performance
        $table->addIndex(['level'], 'idx_log_level');
        $table->addIndex(['level_name'], 'idx_log_level_name');
        $table->addIndex(['channel'], 'idx_log_channel');
        $table->addIndex(['created_at'], 'idx_log_created_at');

        // Set table comment
        $table->addOption('comment', 'Application logs stored via DwLog bundle');

        // Generate and execute SQL
        $sqlStatements = $schema->toSql($this->conn->getDatabasePlatform());

        foreach ($sqlStatements as $sql) {
            $this->conn->executeStatement($sql);
        }

        $io->success(\sprintf('Table "%s" created successfully!', self::TABLE_NAME));

        return Command::SUCCESS;
    }

    private function tableExists(): bool
    {
        $platform     = $this->conn->getDatabasePlatform();
        $platformName = $platform::class;

        try {
            // Use platform-specific SQL to check table existence
            $sql = match (true) {
                str_contains($platformName, 'PostgreSQL') => "
                    SELECT EXISTS (
                        SELECT FROM information_schema.tables 
                        WHERE table_schema = 'public' 
                        AND table_name = :table_name
                    )",
                str_contains($platformName, 'MySQL')
                || str_contains($platformName, 'MariaDB') => '
                    SELECT COUNT(*) 
                    FROM information_schema.tables 
                    WHERE table_schema = DATABASE() 
                    AND table_name = :table_name',
                str_contains($platformName, 'SQLite')     => "
                    SELECT COUNT(*) 
                    FROM sqlite_master 
                    WHERE type = 'table' 
                    AND name = :table_name",
                default                                   => '
                    SELECT COUNT(*) 
                    FROM information_schema.tables 
                    WHERE table_name = :table_name',
            };

            $result = $this->conn->executeQuery($sql, [
                'table_name' => self::TABLE_NAME,
            ])->fetchOne();

            return (bool) $result;
        } catch (\Exception) {
            // If query fails, assume table doesn't exist
            return false;
        }
    }
}
