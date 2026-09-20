CREATE TABLE IF NOT EXISTS users (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    telegram_id BIGINT UNSIGNED NOT NULL UNIQUE,
    telegram_username VARCHAR(255) NULL,
    name VARCHAR(255) NOT NULL,
    avatar VARCHAR(1024) NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    last_login TIMESTAMP NULL,
    last_ip VARCHAR(45) NULL,
    INDEX idx_users_last_login (last_login)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS admins (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(100) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    rank TINYINT UNSIGNED NOT NULL DEFAULT 1,
    activate TINYINT(1) NOT NULL DEFAULT 0,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    last_login TIMESTAMP NULL,
    last_ip VARCHAR(45) NULL,
    INDEX idx_admins_rank_active (rank, activate),
    CONSTRAINT chk_admin_rank CHECK (rank IN (1,2,3))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS cryptovouchers (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL,
    telegram_id BIGINT UNSIGNED NOT NULL,
    telegram_username VARCHAR(255) NULL,
    name VARCHAR(255) NOT NULL,
    amount INT UNSIGNED NOT NULL,
    code_hash CHAR(64) NOT NULL,
    code_encrypted TEXT NOT NULL,
    status ENUM('pending','proofed','invalid') NOT NULL DEFAULT 'pending',
    submitted_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    viewed_at TIMESTAMP NULL,
    viewed_by BIGINT UNSIGNED NULL,
    processed_at TIMESTAMP NULL,
    processed_by BIGINT UNSIGNED NULL,
    INDEX idx_vouchers_status (status),
    INDEX idx_vouchers_submitted (submitted_at),
    INDEX idx_vouchers_telegram (telegram_id),
    UNIQUE KEY uq_voucher_code_hash (code_hash),
    CONSTRAINT fk_voucher_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_voucher_viewed_by FOREIGN KEY (viewed_by) REFERENCES admins(id) ON DELETE SET NULL,
    CONSTRAINT fk_voucher_processed_by FOREIGN KEY (processed_by) REFERENCES admins(id) ON DELETE SET NULL,
    CONSTRAINT chk_voucher_amount CHECK (amount IN (50,75,150))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS admin_logs (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    admin_id BIGINT UNSIGNED NULL,
    action VARCHAR(64) NOT NULL,
    target_type VARCHAR(64) NULL,
    target_id BIGINT UNSIGNED NULL,
    ip_address VARCHAR(45) NULL,
    user_agent VARCHAR(1024) NULL,
    metadata JSON NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_admin_logs_action_time (action, created_at),
    INDEX idx_admin_logs_target (target_type, target_id),
    CONSTRAINT fk_admin_logs_admin FOREIGN KEY (admin_id) REFERENCES admins(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS admin_login_logs (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    admin_id BIGINT UNSIGNED NULL,
    username VARCHAR(100) NOT NULL,
    ip_address VARCHAR(45) NULL,
    user_agent VARCHAR(1024) NULL,
    result ENUM('success','failed') NOT NULL,
    reason VARCHAR(255) NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_admin_login_ip_time (ip_address, created_at),
    INDEX idx_admin_login_username_time (username, created_at),
    CONSTRAINT fk_admin_login_logs_admin FOREIGN KEY (admin_id) REFERENCES admins(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
