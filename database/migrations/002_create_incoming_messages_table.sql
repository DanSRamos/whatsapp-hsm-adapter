-- Tabela de mensagens recebidas
CREATE TABLE incoming_messages (
    id VARCHAR(255) PRIMARY KEY,
    from_number VARCHAR(20) NOT NULL,
    to_number VARCHAR(20) NOT NULL,
    type VARCHAR(50) NOT NULL,
    content JSON NOT NULL,
    context_message_id VARCHAR(255) NULL,
    received_at TIMESTAMP NOT NULL,
    processed BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_from_number (from_number),
    INDEX idx_received_at (received_at),
    INDEX idx_processed (processed),
    FOREIGN KEY (context_message_id) REFERENCES messages(id) ON DELETE SET NULL
);
