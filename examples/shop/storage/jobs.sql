-- Async Job Queue Table
-- Run once to enable async listeners in MVC.Zero.
-- ─────────────────────────────────────────────────────────────────────────────
-- status:
--   pending    → waiting to be picked up by the worker
--   processing → currently being executed (prevents double-execution)
--   done       → completed successfully
--   failed     → exhausted retries or on_failure = 'stop'
--
-- on_failure:
--   retry  → re-queue with exponential backoff until max_attempts is reached
--   stop   → mark as failed immediately on first error
--
-- run_at:
--   Allows delayed execution. Worker only picks up jobs where run_at <= NOW().
--   Exponential backoff bumps this value forward on each retry.
-- ─────────────────────────────────────────────────────────────────────────────

CREATE TABLE IF NOT EXISTS jobs (
    id           INT UNSIGNED     AUTO_INCREMENT PRIMARY KEY,
    event        VARCHAR(255)     NOT NULL                        COMMENT 'Event name that triggered this job',
    listener     VARCHAR(255)     NOT NULL                        COMMENT 'Fully-qualified listener class name',
    payload      JSON             NOT NULL                        COMMENT 'Serialised event payload',
    status       ENUM('pending','processing','done','failed')
                                  NOT NULL DEFAULT 'pending',
    attempts     TINYINT UNSIGNED NOT NULL DEFAULT 0              COMMENT 'Number of execution attempts so far',
    max_attempts TINYINT UNSIGNED NOT NULL DEFAULT 3              COMMENT 'Maximum allowed attempts before marking failed',
    on_failure   ENUM('retry','stop')
                                  NOT NULL DEFAULT 'retry'        COMMENT 'Behaviour on failure: retry with backoff or stop',
    error        TEXT             NULL                            COMMENT 'Last exception message on failure',
    run_at       DATETIME         NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'Earliest time the worker may pick up this job',
    created_at   DATETIME         NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Index for the worker query: status=pending AND run_at <= NOW()
CREATE INDEX idx_jobs_worker ON jobs (status, run_at);
