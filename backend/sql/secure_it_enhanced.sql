-- Create sends table if it doesn't exist
CREATE TABLE IF NOT EXISTS sends (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    type ENUM('text', 'file') NOT NULL DEFAULT 'text',
    name VARCHAR(255) NOT NULL,
    access_token VARCHAR(128) UNIQUE NOT NULL,
    content TEXT,
    file_path VARCHAR(500),
    file_name VARCHAR(255),
    file_size BIGINT,
    password_hash VARCHAR(255),
    expires_at DATETIME NOT NULL,
    max_views INT DEFAULT 10,
    view_count INT DEFAULT 0,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    last_accessed DATETIME,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_access_token (access_token),
    INDEX idx_user_id (user_id),
    INDEX idx_expires_at (expires_at)
);

-- Fix sends table structure
ALTER TABLE sends ADD COLUMN IF NOT EXISTS type ENUM('text', 'file') NOT NULL DEFAULT 'text';
ALTER TABLE sends ADD COLUMN IF NOT EXISTS file_path VARCHAR(500) NULL;
ALTER TABLE sends ADD COLUMN IF NOT EXISTS file_name VARCHAR(255) NULL;
ALTER TABLE sends ADD COLUMN IF NOT EXISTS file_size BIGINT NULL;

-- Add index for better performance
CREATE INDEX IF NOT EXISTS idx_sends_access_token ON sends(access_token);
CREATE INDEX IF NOT EXISTS idx_sends_user_id ON sends(user_id);
CREATE INDEX IF NOT EXISTS idx_sends_expires_at ON sends(expires_at);