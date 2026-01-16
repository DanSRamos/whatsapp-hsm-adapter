<?php

declare(strict_types=1);

namespace WhatsApp\Adapter\Services;

use WhatsApp\Adapter\Models\Requests\InteractiveButtonsRequest;
use WhatsApp\Adapter\Models\Requests\InteractiveListRequest;

class InteractiveValidator
{
    private const MAX_BUTTONS = 3;
    private const MAX_LIST_ITEMS = 10;

    /**
     * Valida uma mensagem com botões interativos
     *
     * @throws \InvalidArgumentException se a validação falhar
     */
    public function validateButtons(InteractiveButtonsRequest $request): void
    {
        $this->validateButtonCount($request->buttons);
        $this->validateButtonUniqueness($request->buttons);
        $this->validateButtonContent($request->buttons);
    }

    /**
     * Valida uma mensagem com lista interativa
     *
     * @throws \InvalidArgumentException se a validação falhar
     */
    public function validateList(InteractiveListRequest $request): void
    {
        $this->validateListItemCount($request->sections);
        $this->validateListItemUniqueness($request->sections);
        $this->validateListItemContent($request->sections);
    }

    /**
     * Valida que o número de botões não excede o máximo
     */
    private function validateButtonCount(array $buttons): void
    {
        $count = count($buttons);
        
        if ($count === 0) {
            throw new \InvalidArgumentException('At least one button is required');
        }

        if ($count > self::MAX_BUTTONS) {
            throw new \InvalidArgumentException(
                sprintf(
                    'Maximum of %d buttons allowed, %d provided',
                    self::MAX_BUTTONS,
                    $count
                )
            );
        }
    }

    /**
     * Valida que o número total de itens na lista não excede o máximo
     */
    private function validateListItemCount(array $sections): void
    {
        $totalItems = 0;
        
        foreach ($sections as $section) {
            if (!isset($section['rows']) || !is_array($section['rows'])) {
                throw new \InvalidArgumentException('Each section must have a "rows" array');
            }
            
            $totalItems += count($section['rows']);
        }

        if ($totalItems === 0) {
            throw new \InvalidArgumentException('At least one list item is required');
        }

        if ($totalItems > self::MAX_LIST_ITEMS) {
            throw new \InvalidArgumentException(
                sprintf(
                    'Maximum of %d list items allowed, %d provided',
                    self::MAX_LIST_ITEMS,
                    $totalItems
                )
            );
        }
    }

    /**
     * Valida que todos os botões têm IDs únicos
     */
    private function validateButtonUniqueness(array $buttons): void
    {
        $ids = [];
        
        foreach ($buttons as $index => $button) {
            if (!isset($button['id'])) {
                throw new \InvalidArgumentException(
                    sprintf('Button at index %d is missing required field "id"', $index)
                );
            }

            $id = $button['id'];
            
            if (in_array($id, $ids, true)) {
                throw new \InvalidArgumentException(
                    sprintf('Duplicate button ID found: "%s"', $id)
                );
            }

            $ids[] = $id;
        }
    }

    /**
     * Valida que todos os itens da lista têm IDs únicos
     */
    private function validateListItemUniqueness(array $sections): void
    {
        $ids = [];
        
        foreach ($sections as $sectionIndex => $section) {
            if (!isset($section['rows']) || !is_array($section['rows'])) {
                continue;
            }

            foreach ($section['rows'] as $rowIndex => $row) {
                if (!isset($row['id'])) {
                    throw new \InvalidArgumentException(
                        sprintf(
                            'List item at section %d, row %d is missing required field "id"',
                            $sectionIndex,
                            $rowIndex
                        )
                    );
                }

                $id = $row['id'];
                
                if (in_array($id, $ids, true)) {
                    throw new \InvalidArgumentException(
                        sprintf('Duplicate list item ID found: "%s"', $id)
                    );
                }

                $ids[] = $id;
            }
        }
    }

    /**
     * Valida que todos os botões têm texto descritivo
     */
    private function validateButtonContent(array $buttons): void
    {
        foreach ($buttons as $index => $button) {
            if (!isset($button['text']) || empty(trim($button['text']))) {
                throw new \InvalidArgumentException(
                    sprintf('Button at index %d is missing required field "text"', $index)
                );
            }

            // Valida tipo de botão se especificado
            if (isset($button['type'])) {
                $this->validateButtonType($button['type'], $index);
            }
        }
    }

    /**
     * Valida que todos os itens da lista têm texto descritivo
     */
    private function validateListItemContent(array $sections): void
    {
        foreach ($sections as $sectionIndex => $section) {
            // Valida título da seção se presente
            if (isset($section['title']) && empty(trim($section['title']))) {
                throw new \InvalidArgumentException(
                    sprintf('Section at index %d has empty title', $sectionIndex)
                );
            }

            if (!isset($section['rows']) || !is_array($section['rows'])) {
                continue;
            }

            foreach ($section['rows'] as $rowIndex => $row) {
                if (!isset($row['title']) || empty(trim($row['title']))) {
                    throw new \InvalidArgumentException(
                        sprintf(
                            'List item at section %d, row %d is missing required field "title"',
                            $sectionIndex,
                            $rowIndex
                        )
                    );
                }

                // Descrição é opcional, mas se presente não pode ser vazia
                if (isset($row['description']) && empty(trim($row['description']))) {
                    throw new \InvalidArgumentException(
                        sprintf(
                            'List item at section %d, row %d has empty description',
                            $sectionIndex,
                            $rowIndex
                        )
                    );
                }
            }
        }
    }

    /**
     * Valida o tipo de botão
     */
    private function validateButtonType(string $type, int $index): void
    {
        $validTypes = ['reply', 'url', 'call'];
        
        if (!in_array($type, $validTypes, true)) {
            throw new \InvalidArgumentException(
                sprintf(
                    'Button at index %d has invalid type "%s". Valid types: %s',
                    $index,
                    $type,
                    implode(', ', $validTypes)
                )
            );
        }

        // Nota: Validação adicional específica por tipo poderia ser adicionada aqui
        // Por exemplo, botões de tipo 'url' devem ter um campo 'url' válido
        // botões de tipo 'call' devem ter um campo 'phone_number' válido
    }
}
