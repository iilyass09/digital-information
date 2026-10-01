<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use PDO;
use Throwable;

class DatabaseDumpCommand extends Command
{
    protected $signature = 'db:dump
                            {--tables=* : Limit the dump to the given tables}
                            {--output= : Write to this path instead of database/dumps/database.sql}
                            {--force : Overwrite an existing dump}';

    protected $description = 'Export the connected database to database/dumps/database.sql';

    /**
     * Number of rows written per INSERT statement.
     */
    private const INSERT_BATCH_SIZE = 200;

    /**
     * Ephemeral runtime tables. Restoring stale sessions or cache entries on a
     * different machine is never desirable, and session payloads can carry
     * tokens that have no business travelling with the project.
     *
     * @var string[]
     */
    private const SKIPPED_TABLES = ['sessions', 'cache', 'cache_locks', 'jobs', 'job_batches', 'failed_jobs'];

    public function handle(): int
    {
        $connection = DB::connection();

        try {
            /** @var PDO $pdo */
            $pdo = $connection->getPdo();
        } catch (Throwable $e) {
            $this->error('Could not connect to the database: '.$e->getMessage());

            return self::FAILURE;
        }

        $filePath = $this->resolveDumpPath();
        if (file_exists($filePath) && ! $this->option('force')) {
            $this->error('A dump already exists at '.$filePath.'. Use --force to overwrite it.');

            return self::FAILURE;
        }

        $tables = $this->resolveTables($pdo, $connection->getDatabaseName());
        if ($tables === []) {
            $this->error('No tables found to dump.');

            return self::FAILURE;
        }

        if (! is_dir($directory = dirname($filePath)) && ! @mkdir($directory, 0775, true) && ! is_dir($directory)) {
            $this->error('Could not create directory: '.$directory);

            return self::FAILURE;
        }

        $handle = @fopen($filePath, 'wb');
        if ($handle === false) {
            $this->error('Could not open dump file for writing: '.$filePath);

            return self::FAILURE;
        }

        $written = 0;
        $dumped = [];

        try {
            fwrite($handle, $this->header($connection->getName()));

            foreach ($tables as $table) {
                if (in_array($table, self::SKIPPED_TABLES, true)) {
                    continue;
                }

                $createStatement = $this->createTableStatement($pdo, $table);

                if ($createStatement === null) {
                    $this->warn('Skipping unknown table: '.$table);

                    continue;
                }

                $rows = $this->fetchRows($pdo, $table);
                $rowCount = count($rows);

                fwrite($handle, "-- Table: `{$table}`\n");
                fwrite($handle, "DROP TABLE IF EXISTS `{$table}`;\n");
                fwrite($handle, $createStatement.";\n");
                fwrite($handle, "-- Rows: {$rowCount}\n\n");

                if ($rowCount > 0) {
                    $binaryColumns = $this->binaryColumns($pdo, $table);
                    $this->writeInserts($handle, $pdo, $table, $rows, $binaryColumns);
                }

                $written += $rowCount;
                $dumped[] = $table;
            }

            fwrite($handle, "SET FOREIGN_KEY_CHECKS=1;\n");
        } catch (Throwable $e) {
            fclose($handle);
            @unlink($filePath);

            throw $e;
        }

        fclose($handle);

        $this->info('Database dumped to '.$filePath);
        $this->line('Tables: '.count($dumped).', rows: '.$written);
        $this->line('Copy this single file to the new machine, then run: php artisan db:restore');

        return self::SUCCESS;
    }

    public function resolveDumpPath(): string
    {
        $option = $this->option('output');

        if (is_string($option) && $option !== '') {
            return $option;
        }

        $configured = config('admin.dump_path');

        return is_string($configured) && $configured !== ''
            ? $configured
            : database_path('dumps'.DIRECTORY_SEPARATOR.'database.sql');
    }

    protected function header(string $connectionName): string
    {
        return implode("\n", [
            '-- Digital Information Board database dump',
            '-- Generated: '.date('Y-m-d H:i:s T'),
            '-- Connection: '.$connectionName,
            '-- Restore with: php artisan db:restore --force',
            '',
            'SET NAMES utf8mb4;',
            'SET FOREIGN_KEY_CHECKS=0;',
            '',
        ])."\n";
    }

    /**
     * @return string[]
     */
    protected function resolveTables(PDO $pdo, ?string $database): array
    {
        $requested = array_filter(array_map('trim', (array) $this->option('tables')));

        if ($requested !== []) {
            return array_values($requested);
        }

        $statement = $database
            ? $pdo->prepare('SELECT table_name FROM information_schema.tables WHERE table_schema = ? ORDER BY table_name')
            : $pdo->query('SHOW TABLES');

        if ($database) {
            $statement->execute([$database]);
        }

        $tables = $statement->fetchAll(PDO::FETCH_COLUMN, 0);

        // "migrations" is bookkeeping, not board content, but keeping it makes a
        // restored database report the same migration state as the source.
        return array_values(array_map('strval', $tables));
    }

    protected function createTableStatement(PDO $pdo, string $table): ?string
    {
        try {
            $quoted = $this->quoteIdentifier($table);
            $row = $pdo->query('SHOW CREATE TABLE '.$quoted)->fetch(PDO::FETCH_NUM);

            return $row === false ? null : (string) $row[1];
        } catch (Throwable) {
            return null;
        }
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    protected function fetchRows(PDO $pdo, string $table): array
    {
        return $pdo->query('SELECT * FROM '.$this->quoteIdentifier($table))->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Columns holding raw bytes are written as hex literals so binary content
     * survives the round trip regardless of connection charset.
     *
     * @return string[]
     */
    protected function binaryColumns(PDO $pdo, string $table): array
    {
        try {
            $rows = $pdo->query('SHOW FULL COLUMNS FROM '.$this->quoteIdentifier($table))->fetchAll(PDO::FETCH_ASSOC);
        } catch (Throwable) {
            return [];
        }

        $binary = [];

        foreach ($rows as $column) {
            if (str_contains(strtolower((string) $column['Type']), 'blob')
                || str_contains(strtolower((string) $column['Type']), 'binary')) {
                $binary[] = (string) $column['Field'];
            }
        }

        return $binary;
    }

    /**
     * @param  array<int, array<string, mixed>>  $rows
     * @param  string[]  $binaryColumns
     */
    protected function writeInserts($handle, PDO $pdo, string $table, array $rows, array $binaryColumns): void
    {
        $columns = array_keys($rows[0]);
        $prefix = 'INSERT INTO `'.$table.'` (`'.implode('`, `', $columns).'`) VALUES ';

        $buffer = [];
        $rowCount = count($rows);

        foreach ($rows as $index => $row) {
            $values = [];

            foreach ($columns as $column) {
                $values[] = in_array($column, $binaryColumns, true)
                    ? $this->binaryLiteral($row[$column])
                    : $this->literal($pdo, $row[$column]);
            }

            $buffer[] = '('.implode(', ', $values).')';

            if (count($buffer) >= self::INSERT_BATCH_SIZE || $index === $rowCount - 1) {
                fwrite($handle, $prefix.implode(', ', $buffer).";\n");
                $buffer = [];
            }
        }

        fwrite($handle, "\n");
    }

    protected function literal(PDO $pdo, mixed $value): string
    {
        if ($value === null) {
            return 'NULL';
        }

        if (is_bool($value)) {
            return $value ? '1' : '0';
        }

        if (is_int($value) || is_float($value)) {
            return (string) $value;
        }

        return $pdo->quote((string) $value);
    }

    protected function binaryLiteral(mixed $value): string
    {
        if ($value === null) {
            return 'NULL';
        }

        return "X'".bin2hex((string) $value)."'";
    }

    protected function quoteIdentifier(string $identifier): string
    {
        return '`'.str_replace('`', '``', $identifier).'`';
    }
}
