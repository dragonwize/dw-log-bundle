<?php

declare(strict_types=1);

namespace Dragonwize\DwLogBundle\Command;

use Doctrine\DBAL\Connection;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'dw:log:drop-table',
    description: 'Drop the dw_log table if it exists'
)]
class DropTableCommand extends Command
{
    private const string TABLE_NAME = 'dw_log';

    public function __construct(
        private readonly bool $isEnabled,
        private readonly Connection $conn
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addOption(
            'force',
            'f',
            InputOption::VALUE_NONE,
            'Force the operation without confirmation'
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        if (!$this->isEnabled) {
            // Not enabled in this environment.
            return Command::SUCCESS;
        }

        $io = new SymfonyStyle($input, $output);

        // Check if table exists using plain SQL
        if (!$this->tableExists()) {
            $io->info(\sprintf('Table "%s" does not exist.', self::TABLE_NAME));

            return Command::SUCCESS;
        }

        // Get force option
        $force = $input->getOption('force');

        // Confirm deletion unless --force is used
        if (!$force) {
            $io->warning('This will permanently delete the dw_log table and all log data!');

            if (!$io->confirm('Are you sure you want to drop the table?', false)) {
                $io->info('Operation cancelled.');

                return Command::SUCCESS;
            }
        }

        $io->info(\sprintf('Dropping table "%s"...', self::TABLE_NAME));

        try {
            $this->conn->executeStatement('DROP TABLE ' . self::TABLE_NAME);

            $io->success(\sprintf('Table "%s" dropped successfully!', self::TABLE_NAME));

            return Command::SUCCESS;
        } catch (\Exception $e) {
            $io->error(\sprintf('Failed to drop table: %s', $e->getMessage()));

            return Command::FAILURE;
        }
    }

    private function tableExists(): bool
    {
        $platform     = $this->conn->getDatabasePlatform();
        $platformName = $platform::class;

        try {
            // Use platform-specific SQL to check table existence
            $sql = match (true) {
                str_contains($platformName, 'PostgreSQL')                                      => "SELECT EXISTS (
                    SELECT FROM information_schema.tables 
                    WHERE table_schema = 'public' 
                    AND table_name = :table_name
                )",
                str_contains($platformName, 'MySQL') || str_contains($platformName, 'MariaDB') => 'SELECT COUNT(*) 
                    FROM information_schema.tables 
                    WHERE table_schema = DATABASE() 
                    AND table_name = :table_name',
                str_contains($platformName, 'SQLite')                                          => "SELECT COUNT(*) 
                    FROM sqlite_master 
                    WHERE type = 'table' 
                    AND name = :table_name",
                str_contains($platformName, 'SQLServer')                                       => 'SELECT COUNT(*) 
                    FROM INFORMATION_SCHEMA.TABLES 
                    WHERE TABLE_NAME = :table_name',
                default                                                                        => 'SELECT COUNT(*) 
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
