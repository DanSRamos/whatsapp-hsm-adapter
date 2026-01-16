-- Tabela de mensagens enviadas
CREATE TABLE messages (
    id VARCHAR(255) PRIMARY KEY,
    type VARCHAR(50) NOT NULL,
    to_number VARCHAR(20) NOT NULL,
    from_number VARCHAR(20) NOT NULL,
    status VARCHAR(50) NOT NULL,
    content JSON NOT NULL,
    sent_at TIMESTAMP NOT NULL,
    delivered_at TIMESTAMP NULL,
    read_at TIMESTAMP NULL,
    error_message TEXT NULL,
    metadata JSON NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_to_number (to_number),
    INDEX idx_status (status),
    INDEX idx_sent_at (sent_at)
);
