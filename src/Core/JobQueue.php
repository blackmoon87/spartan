<?php

declare(strict_types=1);

namespace App\Core;

use PDO;
use RuntimeException;
use Throwable;

/**
 * Lightweight DB-backed Job Queue.
 *
 * Stores async listener jobs in the `jobs` table and provides
 * a run loop for the CLI worker (worker.php).
 *
 * This class is NOT used directly by application code.
 * The EventDispatcher calls push() automatically when a listener
 * is registered with async: true.
 *
 * Retry strategy — Exponential Backoff:
 *   Attempt 1 → immediate
 *   Attempt 2 → +1 minute
 *   Attempt 3 → +5 minutes
 *   Attempt N → +5 minutes (capped)
 */
class JobQueue
{
    private PDO $db;

    /** Backoff delays in seconds, indexed by attempt number (0-based after first failure) */
    private const BACKOFF = [0, 60, 300];

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Push (called by EventDispatcher on async listeners)
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Insert a new job into the queue.
     *
     * @param string $event       Event name (for traceability)
     * @param string $listener    Fully-qualified listener class name
     * @param mixed  $payload     Event payload (must be JSON-serialisable)
     * @param int    $maxAttempts Maximum retries before marking failed
     * @param string $onFailure   'retry' | 'stop'
     */
    public function push(
        string $event,
        string $listener,
        mixed  $payload,
        int    $maxAttempts = 3,
        string $onFailure   = 'retry'
    ): void {
        $onFailure = in_array($onFailure, ['retry', 'stop'], true) ? $onFailure : 'retry';

        $stmt = $this->db->prepare(
            "INSERT INTO jobs (event, listener, payload, max_attempts, on_failure)
             VALUES (?, ?, ?, ?, ?)"
        );
        $stmt->execute([
            $event,
            $listener,
            json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR),
            $maxAttempts,
            $onFailure,
        ]);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Worker Loop (called by worker.php)
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Fetch and process all pending jobs whose run_at <= NOW().
     * Returns the number of jobs processed in this pass.
     */
    public function processPending(): int
    {
        $jobs = $this->fetchPending();

        foreach ($jobs as $job) {
            $this->runJob($job);
        }

        return count($jobs);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Internal
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Fetch pending jobs ready to run, and atomically mark them as 'processing'
     * to prevent double-execution in concurrent environments.
     */
    private function fetchPending(): array
    {
        // Lock rows during selection to prevent race conditions (for MySQL/PostgreSQL)
        $this->db->beginTransaction();

        try {
            $driver = $this->db->getAttribute(PDO::ATTR_DRIVER_NAME);
            $forUpdate = $driver === 'sqlite' ? '' : ' FOR UPDATE';
            $now = date('Y-m-d H:i:s');

            $stmt = $this->db->prepare(
                "SELECT * FROM jobs
                  WHERE status = 'pending'
                    AND run_at <= ?
                  ORDER BY run_at ASC
                  LIMIT 50" . $forUpdate
            );
            $stmt->execute([$now]);
            $jobs = $stmt->fetchAll(PDO::FETCH_ASSOC);

            if (!empty($jobs)) {
                $ids          = array_column($jobs, 'id');
                $placeholders = implode(',', array_fill(0, count($ids), '?'));
                $this->db->prepare("UPDATE jobs SET status = 'processing' WHERE id IN ({$placeholders})")->execute($ids);
            }

            $this->db->commit();
        } catch (Throwable $e) {
            $this->db->rollBack();
            error_log('[JobQueue] fetchPending failed: ' . $e->getMessage());
            return [];
        }

        return $jobs;
    }

    /**
     * Execute a single job and handle success / failure.
     */
    private function runJob(array $job): void
    {
        $listenerClass = $job['listener'];
        $payload       = json_decode($job['payload'], true);
        $attempts      = (int) $job['attempts'] + 1;

        try {
            if (!class_exists($listenerClass)) {
                throw new RuntimeException("Listener class [{$listenerClass}] not found.");
            }

            $instance = new $listenerClass();

            if (!method_exists($instance, 'handle')) {
                throw new RuntimeException("Listener [{$listenerClass}] must implement handle().");
            }

            $instance->handle($payload);

            $this->markDone((int) $job['id']);

        } catch (Throwable $e) {
            $error = $e->getMessage();
            error_log("[JobQueue] Job #{$job['id']} ({$listenerClass}) failed: {$error}");

            $shouldRetry = $job['on_failure'] === 'retry'
                        && $attempts < (int) $job['max_attempts'];

            if ($shouldRetry) {
                $this->scheduleRetry((int) $job['id'], $attempts, $error);
            } else {
                $this->markFailed((int) $job['id'], $error);
            }
        }
    }

    private function markDone(int $id): void
    {
        $this->db->prepare(
            "UPDATE jobs SET status = 'done', error = NULL WHERE id = ?"
        )->execute([$id]);
    }

    private function markFailed(int $id, string $error): void
    {
        $this->db->prepare(
            "UPDATE jobs SET status = 'failed', error = ? WHERE id = ?"
        )->execute([$error, $id]);
    }

    /**
     * Schedule a retry with exponential backoff.
     * Backoff: attempt 1→0s, attempt 2→60s, attempt 3+→300s.
     */
    private function scheduleRetry(int $id, int $attempts, string $error): void
    {
        $delaySecs = self::BACKOFF[min($attempts, count(self::BACKOFF) - 1)];
        $runAt     = date('Y-m-d H:i:s', time() + $delaySecs);

        $this->db->prepare(
            "UPDATE jobs
                SET status   = 'pending',
                    attempts = ?,
                    run_at   = ?,
                    error    = ?
              WHERE id = ?"
        )->execute([$attempts, $runAt, $error, $id]);
    }
}
