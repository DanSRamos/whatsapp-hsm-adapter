<?php

declare(strict_types=1);

namespace WhatsApp\Adapter\Models\Requests;

class InteractiveListRequest
{
    private const MAX_ITEMS = 10;

    public function __construct(
        public readonly string $to,
        public readonly string $bodyText,
        public readonly string $buttonText,
        public readonly array $sections,
        public readonly ?string $headerText = null,
        public readonly ?string $footerText = null,
        public readonly ?string $notifyUrl = null
    ) {
        $this->validate();
    }

    private function validate(): void
    {
        if (empty($this->to)) {
            throw new \InvalidArgumentException('Field "to" is required');
        }

        if (empty($this->bodyText)) {
            throw new \InvalidArgumentException('Field "bodyText" is required');
        }

        if (empty($this->buttonText)) {
            throw new \InvalidArgumentException('Field "buttonText" is required');
        }

        if (empty($this->sections)) {
            throw new \InvalidArgumentException('Field "sections" is required and must not be empty');
        }

        $totalItems = 0;
        $itemIds = [];

        foreach ($this->sections as $sectionIndex => $section) {
            if (!isset($section['items']) || !is_array($section['items'])) {
                throw new \InvalidArgumentException(
                    sprintf('Section at index %d must have an "items" array', $sectionIndex)
                );
            }

            foreach ($section['items'] as $itemIndex => $item) {
                if (!isset($item['id']) || empty($item['id'])) {
                    throw new \InvalidArgumentException(
                        sprintf('Item at section %d, index %d must have an "id"', $sectionIndex, $itemIndex)
                    );
                }

                if (!isset($item['title']) || empty($item['title'])) {
                    throw new \InvalidArgumentException(
                        sprintf('Item at section %d, index %d must have a "title"', $sectionIndex, $itemIndex)
                    );
                }

                if (in_array($item['id'], $itemIds, true)) {
                    throw new \InvalidArgumentException(sprintf('Duplicate item ID "%s"', $item['id']));
                }

                $itemIds[] = $item['id'];
                $totalItems++;
            }
        }

        if ($totalItems > self::MAX_ITEMS) {
            throw new \InvalidArgumentException(
                sprintf('Maximum %d items allowed across all sections, %d provided', self::MAX_ITEMS, $totalItems)
            );
        }
    }
}
