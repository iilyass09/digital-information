<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use PDO;
use Throwable;

class DatabaseRestoreCommand extends Command
{
    protected $signature = 'db:restore
                            {--file= : Path to a dump file (defaults to database/dumps/database.sql)}
                            {--force : Wipe existing rows before restoring}';

    protected $description = 'Import database/dumps/database.sql into the connected database';

    public function handle(): int
    {
        $filePath = $this->resolveDumpPath();

        if (! File::exists($filePath)) {
            $this->error('Dump file not found: '.$filePath);
            $this->line('Copy database.sql from the previous machine into '.database_path('dumps').'.');

            return self::FAILURE;
        }

        $connection = DB::connection();

        try {
            /** @var PDO $pdo */
            $pdo = $connection->getPdo();
        } catch (Throwable $e) {
            $this->error('Could not connect to the database: '.$e->getMessage());

            return self::FAILURE;
        }

        if (! $this->option('force') && $this->hasExistingContent($pdo, $connection->getDatabaseName())) {
            $this->error('The database already contains data. Re-run with --force to wipe and restore it.');

            return self::FAILURE;
        }

        $statements = $this->parseStatements(File::get($filePath));

        if ($statements === []) {
            $this->error('No executable statements found in '.$filePath);

            return self::FAILURE;
        }

        $pdo->exec('SET FOREIGN_KEY_CHECKS=0');

        try {
            foreach ($statements as $statement) {
                $pdo->exec($statement);
            }
        } catch (Throwable $e) {
            $this->error('Restore failed: '.$e->getMessage());
            $this->line('The database may be partially restored. Check it before serving the board.');

            return self::FAILURE;
        } finally {
            $pdo->exec('SET FOREIGN_KEY_CHECKS=1');
        }

        $this->info('Database restored from '.$filePath);
        $this->line('Statements executed: '.count($statements));

        if ($this->shouldSeedDemoData()) {
            $this->components->warn('Seeders add demo records on top of the restored data.');
        }

        return self::SUCCESS;
    }

    protected function resolveDumpPath(): string
    {
        $option = $this->option('file');

        return is_string($option) && $option !== ''
            ? $option
            : database_path('dumps'.DIRECTORY_SEPARATOR.'database.sql');
    }

    /**
     * True when at least one migrated table holds rows.
     */
    protected function hasExistingContent(PDO $pdo, ?string $database): bool
    {
        if ($database === null || $database === '') {
            return false;
        }

        try {
            $statement = $pdo->prepare('SELECT table_name FROM information_schema.tables WHERE table_schema = ?');
            $statement->execute([$database]);
            $tables = $statement->fetchAll(PDO::FETCH_COLUMN, 0);
        } catch (Throwable) {
            return false;
        }

        foreach ($tables as $table) {
            $quoted = '`'.str_replace('`', '``', (string) $table).'`';

            try {
                if ((int) $pdo->query('SELECT COUNT(*) FROM '.$quoted)->fetchColumn() > 0) {
                    return true;
                }
            } catch (Throwable) {
                continue;
            }
        }

        return false;
    }

    /**
     * Split a dump into statements, dropping comments and honouring quoted
     * strings so semicolons inside values do not split a statement.
     *
     * @return string[]
     */
    protected function parseStatements(string $sql): array
    {
        $statements = [];
        $current = '';
        $length = strlen($sql);
        $inSingle = false;
        $inDouble = false;
        $inBacktick = false;
        $inLineComment = false;
        $inBlockComment = false;

        for ($i = 0; $i < $length; $i++) {
            $char = $sql[$i];
            $next = $sql[$i + 1] ?? '';

            if ($inLineComment) {
                if ($char === "\n") {
                    $inLineComment = false;
                    $current .= $char;
                }

                continue;
            }

            if ($inBlockComment) {
                if ($char === '*' && $next === '/') {
                    $inBlockComment = false;
                    $i++;
                }

                continue;
            }

            if (! $inSingle && ! $inDouble && ! $inBacktick) {
                if ($char === '-' && $next === '-') {
                    $inLineComment = true;
                    $i++;

                    continue;
                }

                if ($char === '#') {
                    $inLineComment = true;

                    continue;
                }

                if ($char === '/' && $next === '*') {
                    $inBlockComment = true;
                    $i++;

                    continue;
                }
            }

            if ($char === '\\' && ! $inBacktick) {
                $current .= $char.$next;
                $i++;

                continue;
            }

            if ($char === "'" && ! $inDouble && ! $inBacktick) {
                $inSingle = ! $inSingle;
            } elseif ($char === '"' && ! $inSingle && ! $inBacktick) {
                $inDouble = ! $inDouble;
            } elseif ($char === '`' && ! $inSingle && ! $inDouble) {
                $inBacktick = ! $inBacktick;
            }

            if ($char === ';' && ! $inSingle && ! $inDouble && ! $inBacktick) {
                $trimmed = trim($current);

                if ($trimmed !== '') {
                    $statements[] = $trimmed;
                }

                $current = '';

                continue;
            }

            $current .= $char;
        }

        $trimmed = trim($current);

        if ($trimmed !== '') {
            $statements[] = $trimmed;
        }

        return $statements;
    }

    protected function shouldSeedDemoData(): bool
    {
        return app()->environment('local', 'testing');
    }
}
