-- Seed Roles
INSERT OR IGNORE INTO roles (id, name, slug) VALUES (1, 'Administrator', 'admin');
INSERT OR IGNORE INTO roles (id, name, slug) VALUES (2, 'Manager', 'manager');
INSERT OR IGNORE INTO roles (id, name, slug) VALUES (3, 'Developer', 'developer');

-- Seed Permissions
INSERT OR IGNORE INTO permissions (id, name, slug) VALUES (1, 'manage_projects', 'manage_projects');
INSERT OR IGNORE INTO permissions (id, name, slug) VALUES (2, 'manage_tasks', 'manage_tasks');
INSERT OR IGNORE INTO permissions (id, name, slug) VALUES (3, 'manage_users', 'manage_users');
INSERT OR IGNORE INTO permissions (id, name, slug) VALUES (4, 'view_reports', 'view_reports');

-- Assign Permissions to Roles
INSERT OR IGNORE INTO role_permissions (role_id, permission_id) VALUES (1, 1);
INSERT OR IGNORE INTO role_permissions (role_id, permission_id) VALUES (1, 2);
INSERT OR IGNORE INTO role_permissions (role_id, permission_id) VALUES (1, 3);
INSERT OR IGNORE INTO role_permissions (role_id, permission_id) VALUES (1, 4);
INSERT OR IGNORE INTO role_permissions (role_id, permission_id) VALUES (2, 1);
INSERT OR IGNORE INTO role_permissions (role_id, permission_id) VALUES (2, 2);
INSERT OR IGNORE INTO role_permissions (role_id, permission_id) VALUES (2, 4);
INSERT OR IGNORE INTO role_permissions (role_id, permission_id) VALUES (3, 2);

-- Seed Users (password: 'password')
INSERT OR IGNORE INTO users (id, name, email, password) VALUES
(1, 'Alexei Volkov', 'alexei@taskforge.dev', '$2y$12$YBa9h/Kv0PETj7EsruGVx.MuP3C3a9GNLH3U0T7mZCTbRyKxC0xUO');
INSERT OR IGNORE INTO users (id, name, email, password) VALUES
(2, 'Sarah Chen', 'sarah@taskforge.dev', '$2y$12$YBa9h/Kv0PETj7EsruGVx.MuP3C3a9GNLH3U0T7mZCTbRyKxC0xUO');
INSERT OR IGNORE INTO users (id, name, email, password) VALUES
(3, 'Dev User', 'dev@taskforge.dev', '$2y$12$YBa9h/Kv0PETj7EsruGVx.MuP3C3a9GNLH3U0T7mZCTbRyKxC0xUO');

-- Assign Roles to Users
INSERT OR IGNORE INTO user_roles (user_id, role_id) VALUES (1, 1);
INSERT OR IGNORE INTO user_roles (user_id, role_id) VALUES (2, 2);
INSERT OR IGNORE INTO user_roles (user_id, role_id) VALUES (3, 3);

-- Seed Projects
INSERT OR IGNORE INTO projects (id, user_id, name, slug, description, status, priority, deadline) VALUES
(1, 1, 'Spartan Framework Core', 'spartan-core', 'Zero-dependency PHP 8.1+ MVC framework development', 'active', 'high', '2026-12-31');
INSERT OR IGNORE INTO projects (id, user_id, name, slug, description, status, priority, deadline) VALUES
(2, 2, 'Mobile App MVP', 'mobile-mvp', 'Flutter-based cross-platform mobile application', 'active', 'medium', '2026-09-15');
INSERT OR IGNORE INTO projects (id, user_id, name, slug, description, status, priority, deadline) VALUES
(3, 1, 'DevOps Pipeline', 'devops-pipeline', 'CI/CD automation with Docker and GitHub Actions', 'on_hold', 'low', '2027-03-01');

-- Seed Tasks
INSERT OR IGNORE INTO tasks (id, project_id, assigned_to, title, description, status, priority, due_date) VALUES
(1, 1, 1, 'Implement QueryBuilder Dialect System', 'Add MySQL and SQLite dialect support for identifier quoting', 'done', 'high', '2026-07-20');
INSERT OR IGNORE INTO tasks (id, project_id, assigned_to, title, description, status, priority, due_date) VALUES
(2, 1, 2, 'Add Cache Layer with File Driver', 'Implement Cache facade with put/get/remember/forget/flush', 'in_progress', 'medium', '2026-07-25');
INSERT OR IGNORE INTO tasks (id, project_id, assigned_to, title, description, status, priority, due_date) VALUES
(3, 1, 3, 'Write Comprehensive Test Suite', 'Create 30+ automated tests covering every core feature', 'todo', 'high', '2026-07-30');
INSERT OR IGNORE INTO tasks (id, project_id, assigned_to, title, description, status, priority, due_date) VALUES
(4, 2, 2, 'Design Login Screen UI', 'Create modern glassmorphism login flow', 'in_progress', 'high', '2026-08-01');
INSERT OR IGNORE INTO tasks (id, project_id, assigned_to, title, description, status, priority, due_date) VALUES
(5, 2, 3, 'Integrate REST API Client', 'Connect Flutter app to Spartan API endpoints', 'todo', 'medium', '2026-08-10');
INSERT OR IGNORE INTO tasks (id, project_id, assigned_to, title, description, status, priority, due_date) VALUES
(6, 3, 1, 'Setup Docker Compose', 'Multi-container setup with PHP, MySQL, Redis, Nginx', 'todo', 'low', '2027-01-15');

-- Seed Comments
INSERT OR IGNORE INTO comments (id, task_id, user_id, body) VALUES
(1, 1, 2, 'Great work on the dialect system! The auto-quoting is very clean.');
INSERT OR IGNORE INTO comments (id, task_id, user_id, body) VALUES
(2, 1, 1, 'Thanks! Added SQLite double-quote support as well.');
INSERT OR IGNORE INTO comments (id, task_id, user_id, body) VALUES
(3, 2, 1, 'Make sure to add LOCK_EX for atomic file writes in the cache driver.');
INSERT OR IGNORE INTO comments (id, task_id, user_id, body) VALUES
(4, 4, 3, 'The glassmorphism mockup looks amazing, Sarah!');
