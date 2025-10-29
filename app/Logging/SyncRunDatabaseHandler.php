<?php

namespace App\Logging;

use Monolog\Handler\AbstractProcessingHandler;
use Monolog\LogRecord;
use App\Models\SyncRunLog;

class SyncRunDatabaseHandler extends AbstractProcessingHandler
{
    /** @var int|null */
    protected static $currentSyncRunId = null;

    /**
     * Sets the ID of the current SyncRun to associate logs with.
     * @param int|null $syncRunId
     * @return void
     */
    public static function setCurrentSyncRunId(?int $syncRunId): void
    {
        self::$currentSyncRunId = $syncRunId;
    }

    /**
     * Writes the record down to the database only if an active SyncRun is set.
     * @param LogRecord $record
     * @return void
     */
    protected function write(LogRecord $record): void
    {
        if (self::$currentSyncRunId === null) {
            // If no SyncRun ID is set, we don't log to the database.
            return;
        }

        // Save to the database
        SyncRunLog::create([
            'sync_run_id' => self::$currentSyncRunId,
            'level' => strtolower($record->level->getName()),
            'message' => $record->message,
            'context' => $record->context,
            'created_at' => $record->datetime,
        ]);
    }
}