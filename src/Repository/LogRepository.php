<?php

declare(strict_types=1);

namespace Dragonwize\DwLogBundle\Repository;

use Carbon\CarbonImmutable;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\ParameterType;

class LogRepository
{
    private const string TABLE_NAME = 'dw_log';

    public function __construct(
        private Connection $conn
    ) {}

    /**
     * Find logs with pagination and optional filters.
     *
     * @return array{logs: array<array<string, mixed>>, total: int}
     */
    public function findWithPagination(
        int $page = 1,
        int $limit = 50,
        ?string $search = null,
        ?string $level = null,
        ?string $channel = null
    ): array {
        $offset = ($page - 1) * $limit;
        $params = [];
        $types  = [];

        // Build WHERE clause
        $whereClauses = [];

        if ($search !== null && $search !== '') {
            $whereClauses[]   = 'message ILIKE :search';
            $params['search'] = '%' . $search . '%';
            $types['search']  = ParameterType::STRING;
        }

        if ($level !== null && $level !== '') {
            $whereClauses[]  = 'level_name = :level';
            $params['level'] = $level;
            $types['level']  = ParameterType::STRING;
        }

        if ($channel !== null && $channel !== '') {
            $whereClauses[]    = 'channel = :channel';
            $params['channel'] = $channel;
            $types['channel']  = ParameterType::STRING;
        }

        $whereSQL = $whereClauses !== [] ? 'WHERE ' . implode(' AND ', $whereClauses) : '';

        // Get total count.
        $countSQL = 'SELECT COUNT(*) as total FROM ' . self::TABLE_NAME . ' ' . $whereSQL;
        $total    = (int) $this->conn->executeQuery($countSQL, $params, $types)->fetchOne();

        // Get logs.
        $sql = 'SELECT id, channel, level, level_name, message, context, extra, created_at 
                FROM ' . self::TABLE_NAME . ' 
                ' . $whereSQL . '
                ORDER BY created_at DESC
                LIMIT :limit OFFSET :offset';

        $params['limit']  = $limit;
        $params['offset'] = $offset;
        $types['limit']   = ParameterType::INTEGER;
        $types['offset']  = ParameterType::INTEGER;

        $logs = $this->conn->executeQuery($sql, $params, $types)->fetchAllAssociative();

        foreach ($logs as &$log) {
            // Convert date to object for easy formating.
            $log['created_at'] = new CarbonImmutable($log['created_at'], 'UTC');
            // $log['context'] = json_decode($log['context'], true) ?? [];
            // $log['extra']   = json_decode($log['extra'], true) ?? [];
        }

        return [
            'logs'  => $logs,
            'total' => $total,
        ];
    }

    /**
     * Get distinct log levels.
     *
     * @return array<string>
     */
    public function getDistinctLevels(): array
    {
        $sql = 'SELECT DISTINCT level_name, level 
                FROM ' . self::TABLE_NAME . ' 
                ORDER BY level, level_name DESC';

        $result = $this->conn->executeQuery($sql)->fetchFirstColumn();

        return $result;
    }

    /**
     * Get distinct channels.
     *
     * @return array<string>
     */
    public function getDistinctChannels(): array
    {
        $sql = 'SELECT DISTINCT channel 
                FROM ' . self::TABLE_NAME . ' 
                ORDER BY channel ASC';

        $result = $this->conn->executeQuery($sql)->fetchFirstColumn();

        return $result;
    }
}
