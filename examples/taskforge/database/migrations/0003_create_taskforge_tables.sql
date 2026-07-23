CREATE TABLE IF NOT EXISTS "projects" (
    "id" INTEGER PRIMARY KEY AUTOINCREMENT,
    "user_id" INTEGER NOT NULL,
    "name" VARCHAR(200) NOT NULL,
    "slug" VARCHAR(200) NOT NULL UNIQUE,
    "description" TEXT,
    "status" VARCHAR(20) DEFAULT 'active',
    "priority" VARCHAR(10) DEFAULT 'medium',
    "deadline" DATE,
    "created_at" DATETIME DEFAULT CURRENT_TIMESTAMP,
    "updated_at" DATETIME DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS "tasks" (
    "id" INTEGER PRIMARY KEY AUTOINCREMENT,
    "project_id" INTEGER NOT NULL,
    "assigned_to" INTEGER,
    "title" VARCHAR(255) NOT NULL,
    "description" TEXT,
    "status" VARCHAR(20) DEFAULT 'todo',
    "priority" VARCHAR(10) DEFAULT 'medium',
    "due_date" DATE,
    "completed_at" DATETIME,
    "created_at" DATETIME DEFAULT CURRENT_TIMESTAMP,
    "updated_at" DATETIME DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS "comments" (
    "id" INTEGER PRIMARY KEY AUTOINCREMENT,
    "task_id" INTEGER NOT NULL,
    "user_id" INTEGER NOT NULL,
    "body" TEXT NOT NULL,
    "created_at" DATETIME DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS "activity_logs" (
    "id" INTEGER PRIMARY KEY AUTOINCREMENT,
    "user_id" INTEGER,
    "action" VARCHAR(100) NOT NULL,
    "entity_type" VARCHAR(50),
    "entity_id" INTEGER,
    "details" TEXT,
    "created_at" DATETIME DEFAULT CURRENT_TIMESTAMP
);
