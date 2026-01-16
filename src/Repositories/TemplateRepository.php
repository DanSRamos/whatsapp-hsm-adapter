<?php

declare(strict_types=1);

namespace WhatsApp\Adapter\Repositories;

use WhatsApp\Adapter\Models\Template;
use PDO;

class TemplateRepository implements TemplateRepositoryInterface
{
    public function __construct(
        private PDO $pdo
    ) {}

    public function save(Template $template): void
    {
        $driver = $this->pdo->getAttribute(PDO::ATTR_DRIVER_NAME);
        
        if ($driver === 'sqlite') {
            $sql = <<<SQL
                INSERT OR REPLACE INTO templates (
                    id, name, language, status, category, components,
                    rejection_reason, last_synced_at,
                    created_at, updated_at
                ) VALUES (
                    :id, :name, :language, :status, :category, :components,
                    :rejection_reason, :last_synced_at,
                    COALESCE((SELECT created_at FROM templates WHERE id = :id), CURRENT_TIMESTAMP),
                    CURRENT_TIMESTAMP
                )
            SQL;
        } else {
            // MySQL/PostgreSQL
            $sql = <<<SQL
                INSERT INTO templates (
                    id, name, language, status, category, components,
                    rejection_reason, last_synced_at
                ) VALUES (
                    :id, :name, :language, :status, :category, :components,
                    :rejection_reason, :last_synced_at
                )
                ON DUPLICATE KEY UPDATE
                    name = VALUES(name),
                    language = VALUES(language),
                    status = VALUES(status),
                    category = VALUES(category),
                    components = VALUES(components),
                    rejection_reason = VALUES(rejection_reason),
                    last_synced_at = VALUES(last_synced_at),
                    updated_at = CURRENT_TIMESTAMP
            SQL;
        }

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            'id' => $template->id,
            'name' => $template->name,
            'language' => $template->language,
            'status' => $template->status,
            'category' => $template->category,
            'components' => json_encode($template->components),
            'rejection_reason' => $template->rejectionReason,
            'last_synced_at' => (new \DateTimeImmutable())->format('Y-m-d H:i:s'),
        ]);
    }

    public function findById(string $templateId): ?Template
    {
        $sql = <<<SQL
            SELECT id, name, language, status, category, components, rejection_reason
            FROM templates
            WHERE id = :id
        SQL;

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['id' => $templateId]);
        
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$row) {
            return null;
        }

        return new Template(
            id: $row['id'],
            name: $row['name'],
            language: $row['language'],
            status: $row['status'],
            category: $row['category'],
            components: json_decode($row['components'], true),
            rejectionReason: $row['rejection_reason']
        );
    }

    public function findAll(): array
    {
        $sql = <<<SQL
            SELECT id, name, language, status, category, components, rejection_reason
            FROM templates
            ORDER BY name, language
        SQL;

        $stmt = $this->pdo->query($sql);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        return array_map(function ($row) {
            return new Template(
                id: $row['id'],
                name: $row['name'],
                language: $row['language'],
                status: $row['status'],
                category: $row['category'],
                components: json_decode($row['components'], true),
                rejectionReason: $row['rejection_reason']
            );
        }, $rows);
    }

    public function delete(string $templateId): void
    {
        $sql = 'DELETE FROM templates WHERE id = :id';
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['id' => $templateId]);
    }
}
