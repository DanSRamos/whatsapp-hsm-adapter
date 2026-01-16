<?php

declare(strict_types=1);

namespace WhatsApp\Adapter\Repositories;

use WhatsApp\Adapter\Models\Template;

interface TemplateRepositoryInterface
{
    public function save(Template $template): void;
    
    public function findById(string $templateId): ?Template;
    
    public function findAll(): array;
    
    public function delete(string $templateId): void;
}
