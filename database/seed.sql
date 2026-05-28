-- Default Roles
INSERT INTO `roles` (`name`, `slug`) VALUES 
('Administrator', 'admin'),
('Editor', 'editor'),
('User', 'user')
ON DUPLICATE KEY UPDATE `name` = VALUES(`name`);

-- Default Permissions
INSERT INTO `permissions` (`name`, `slug`) VALUES
('Access Dashboard', 'access_dashboard'),
('Manage Users', 'manage_users'),
('Edit Posts', 'edit_post'),
('Publish Posts', 'publish_post')
ON DUPLICATE KEY UPDATE `name` = VALUES(`name`);
