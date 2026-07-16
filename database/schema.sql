CREATE DATABASE IF NOT EXISTS survey_db;

USE survey_db;

CREATE TABLE IF NOT EXISTS roles (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL UNIQUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

INSERT IGNORE INTO roles (id, name) VALUES
(1, 'user'),
(2, 'admin_opd'),
(3, 'superadmin');

CREATE TABLE IF NOT EXISTS users (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nik CHAR(16) NOT NULL UNIQUE,
    full_name VARCHAR(255) NOT NULL,
    username VARCHAR(25) NOT NULL UNIQUE,
    email VARCHAR(255) NULL UNIQUE,
    phone VARCHAR(20) NULL UNIQUE,
    email_verified_at DATETIME NULL,
    phone_verified_at DATETIME NULL,
    profile_photo_path VARCHAR(255) NULL,
    password VARCHAR(255) NOT NULL,
    role_id INT UNSIGNED NOT NULL DEFAULT 1,
    position ENUM(
        'asn',
        'non_asn',
        'public'
    ) NOT NULL DEFAULT 'public',
    opd_pengampu VARCHAR(255) NULL,
    is_active BOOLEAN DEFAULT TRUE, -- Untuk jejak audit, sehingga sistem active/deactive user lebih baik daripada delete user
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (role_id) REFERENCES roles (id)
);

SET @users_opd_column_exists = (
    SELECT COUNT(*)
    FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
        AND TABLE_NAME = 'users'
        AND COLUMN_NAME = 'opd_pengampu'
);
SET @users_opd_statement = IF(
    @users_opd_column_exists = 0,
    'ALTER TABLE users ADD COLUMN opd_pengampu VARCHAR(255) NULL AFTER position',
    'DO 0'
);
PREPARE users_opd_stmt FROM @users_opd_statement;
EXECUTE users_opd_stmt;
DEALLOCATE PREPARE users_opd_stmt;

CREATE TABLE IF NOT EXISTS revoked_tokens (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    token_hash CHAR(64) NOT NULL UNIQUE,
    expires_at DATETIME NOT NULL,
    revoked_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS contact_verifications (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    channel ENUM('email', 'phone') NOT NULL,
    target VARCHAR(255) NOT NULL,
    code VARCHAR(255) NOT NULL,
    attempts TINYINT UNSIGNED NOT NULL DEFAULT 0,
    expires_at DATETIME NOT NULL,
    verified_at DATETIME NULL,
    sent_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS password_resets (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    email VARCHAR(255) NOT NULL,
    selector CHAR(32) NOT NULL UNIQUE,
    token_hash VARCHAR(255) NOT NULL,
    expires_at DATETIME NOT NULL,
    used_at DATETIME NULL,
    sent_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS privileges (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL UNIQUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS role_privileges (
    role_id INT UNSIGNED NOT NULL,
    privilege_id INT UNSIGNED NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (role_id, privilege_id),
    FOREIGN KEY (role_id) REFERENCES roles (id) ON DELETE CASCADE,
    FOREIGN KEY (privilege_id) REFERENCES privileges (id) ON DELETE CASCADE
);

INSERT IGNORE INTO privileges (name) VALUES
('survey:read'),
('survey:create'),
('survey:update'),
('survey:delete'),
('analytics:read'),
('user:read'),
('user:update'),
('role:read'),
('role:create'),
('role:update'),
('role:delete');

INSERT IGNORE INTO role_privileges (role_id, privilege_id)
SELECT r.id, p.id
FROM roles r
JOIN privileges p ON p.name IN ('survey:read', 'survey:create', 'survey:update', 'survey:delete', 'analytics:read')
WHERE r.name IN ('admin_opd', 'superadmin');

INSERT IGNORE INTO role_privileges (role_id, privilege_id)
SELECT r.id, p.id
FROM roles r
JOIN privileges p ON p.name IN ('user:read', 'user:update', 'role:read', 'role:create', 'role:update', 'role:delete')
WHERE r.name = 'superadmin';

CREATE TABLE IF NOT EXISTS surveys (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    description TEXT,
    instructions TEXT,
    opd_pengampu VARCHAR(255) NULL,
    estimated_time INT UNSIGNED,
    thumbnail_path VARCHAR(255) NULL,
    status ENUM('draft', 'upcoming', 'open', 'closed') NOT NULL DEFAULT 'draft',
    created_by INT UNSIGNED NOT NULL,
    opens_at DATETIME,
    closes_at DATETIME,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (created_by) REFERENCES users (id) ON DELETE RESTRICT
);

SET @surveys_opd_column_exists = (
    SELECT COUNT(*)
    FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
        AND TABLE_NAME = 'surveys'
        AND COLUMN_NAME = 'opd_pengampu'
);
SET @surveys_opd_statement = IF(
    @surveys_opd_column_exists = 0,
    'ALTER TABLE surveys ADD COLUMN opd_pengampu VARCHAR(255) NULL AFTER instructions',
    'DO 0'
);
PREPARE surveys_opd_stmt FROM @surveys_opd_statement;
EXECUTE surveys_opd_stmt;
DEALLOCATE PREPARE surveys_opd_stmt;

CREATE TABLE IF NOT EXISTS survey_restrictions (
    survey_id INT UNSIGNED NOT NULL,
    position ENUM('asn', 'non_asn', 'public') NOT NULL,
    PRIMARY KEY (survey_id, position),
    FOREIGN KEY (survey_id) REFERENCES surveys(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS survey_pages (
    survey_id INT UNSIGNED NOT NULL,
    page SMALLINT UNSIGNED NOT NULL,
    section VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (survey_id, page),
    FOREIGN KEY (survey_id) REFERENCES surveys(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS questions (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    survey_id INT UNSIGNED NOT NULL,
    image_path VARCHAR(255),
    question_text TEXT NOT NULL,
    question_type ENUM(
        'free_text',
        'radio_button',
        'checkbox',
        'dropdown',
        'rating_scale',
        'file_upload'
    ) NOT NULL,
    is_required BOOLEAN DEFAULT FALSE,
    question_order SMALLINT UNSIGNED NOT NULL DEFAULT 1,
    page SMALLINT UNSIGNED NOT NULL DEFAULT 1,
    parent_option_id INT UNSIGNED NULL, -- NULL artinya question tersebut selalu muncul, namun jika ada value maka question tersebut hanya muncul apabila opsi dengan id pada tabel options yang sama dengan parent_option_id dipilih sebagai jawaban
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (survey_id) REFERENCES surveys (id) ON DELETE CASCADE
    -- FK parent_option_id akan ditambahkan setelah membuat tabel options dibawah ini, untuk mencegah circular reference antara tabel questions dan options
);

CREATE TABLE IF NOT EXISTS options ( -- Hanya untuk pertanyaan bertipe radio_button, checkbox, dropdown, dan rating_scale
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    question_id INT UNSIGNED NOT NULL,
    option_text VARCHAR(255) NOT NULL,
    option_order SMALLINT UNSIGNED NOT NULL DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (question_id) REFERENCES questions (id) ON DELETE CASCADE
);

SET @parent_option_fk_exists = (
    SELECT COUNT(*)
    FROM INFORMATION_SCHEMA.TABLE_CONSTRAINTS
    WHERE CONSTRAINT_SCHEMA = DATABASE()
        AND TABLE_NAME = 'questions'
        AND CONSTRAINT_NAME = 'fk_parent_option_id'
);
SET @parent_option_fk_statement = IF(
    @parent_option_fk_exists = 0,
    'ALTER TABLE questions ADD CONSTRAINT fk_parent_option_id FOREIGN KEY (parent_option_id) REFERENCES options (id) ON DELETE CASCADE',
    'DO 0'
);
PREPARE parent_option_fk_stmt FROM @parent_option_fk_statement;
EXECUTE parent_option_fk_stmt;
DEALLOCATE PREPARE parent_option_fk_stmt;

CREATE TABLE IF NOT EXISTS responses (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    survey_id INT UNSIGNED NOT NULL,
    user_id INT UNSIGNED NOT NULL,
    status ENUM('draft', 'submitted') NOT NULL DEFAULT 'submitted',
    current_page SMALLINT UNSIGNED DEFAULT 0,
    submitted_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE (survey_id, user_id),
    FOREIGN KEY (survey_id) REFERENCES surveys (id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE RESTRICT
);

CREATE TABLE IF NOT EXISTS answers (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    response_id INT UNSIGNED NOT NULL,
    question_id INT UNSIGNED NOT NULL,
    file_path VARCHAR(255), -- Untuk file_upload
    answer_text TEXT, -- Untuk free_text
    option_id INT UNSIGNED, -- Untuk radio_button, checkbox, dropdown, dan rating_scale
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (response_id) REFERENCES responses (id) ON DELETE CASCADE,
    FOREIGN KEY (question_id) REFERENCES questions (id) ON DELETE CASCADE,
    FOREIGN KEY (option_id) REFERENCES options (id) ON DELETE SET NULL
);
