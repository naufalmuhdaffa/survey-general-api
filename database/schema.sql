-- Active: 1768106846815@@127.0.0.1@3306@survey_db
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
    password VARCHAR(255) NOT NULL,
    role_id INT UNSIGNED NOT NULL DEFAULT 1,
    position ENUM(
        'asn',
        'non_asn',
        'public'
    ) NOT NULL DEFAULT 'public',
    is_active BOOLEAN DEFAULT TRUE, -- Untuk jejak audit, sehingga sistem active/deactive user lebih baik daripada delete user
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (role_id) REFERENCES roles (id)
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
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
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
('user:read'),
('user:update'),
('role:read'),
('role:create'),
('role:update'),
('role:delete');

INSERT IGNORE INTO role_privileges (role_id, privilege_id)
SELECT r.id, p.id
FROM roles r
JOIN privileges p ON p.name IN ('survey:read', 'survey:create', 'survey:update', 'survey:delete')
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

ALTER TABLE questions
ADD CONSTRAINT fk_parent_option_id FOREIGN KEY (parent_option_id) REFERENCES options (id) ON DELETE CASCADE;

CREATE TABLE IF NOT EXISTS responses (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    survey_id INT UNSIGNED NOT NULL,
    user_id INT UNSIGNED NOT NULL,
    submitted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
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
