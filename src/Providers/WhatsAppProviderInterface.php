<?php

declare(strict_types=1);

namespace WhatsApp\Adapter\Providers;

use Psr\Http\Message\ServerRequestInterface;
use WhatsApp\Adapter\Models\Requests\HSMRequest;
use WhatsApp\Adapter\Models\Requests\TextRequest;
use WhatsApp\Adapter\Models\Requests\MediaRequest;
use WhatsApp\Adapter\Models\Requests\InteractiveButtonsRequest;
use WhatsApp\Adapter\Models\Requests\InteractiveListRequest;
use WhatsApp\Adapter\Providers\Models\ProviderSendResult;
use WhatsApp\Adapter\Providers\Models\ProviderMessageStatus;
use WhatsApp\Adapter\Providers\Models\ProviderTemplate;
use WhatsApp\Adapter\Providers\Models\DeliveryReport;
use WhatsApp\Adapter\Models\IncomingMessage;
use WhatsApp\Adapter\Providers\Models\TemplateUpdate;

/**
 * Interface for WhatsApp provider implementations (Infobip, Twilio, etc.)
 * 
 * This interface defines the contract that all WhatsApp providers must implement
 * to support sending messages, querying status, managing templates, and processing webhooks.
 */
interface WhatsAppProviderInterface
{
    /**
     * Send a template/HSM message
     *
     * @param HSMRequest $request The HSM message request
     * @return ProviderSendResult The result of the send operation
     */
    public function sendTemplate(HSMRequest $request): ProviderSendResult;

    /**
     * Send a free-text message
     *
     * @param TextRequest $request The text message request
     * @return ProviderSendResult The result of the send operation
     */
    public function sendText(TextRequest $request): ProviderSendResult;

    /**
     * Send media (image, document, audio, video)
     *
     * @param MediaRequest $request The media message request
     * @return ProviderSendResult The result of the send operation
     */
    public function sendMedia(MediaRequest $request): ProviderSendResult;

    /**
     * Send interactive message with buttons
     *
     * @param InteractiveButtonsRequest $request The interactive buttons request
     * @return ProviderSendResult The result of the send operation
     */
    public function sendInteractiveButtons(InteractiveButtonsRequest $request): ProviderSendResult;

    /**
     * Send interactive message with list
     *
     * @param InteractiveListRequest $request The interactive list request
     * @return ProviderSendResult The result of the send operation
     */
    public function sendInteractiveList(InteractiveListRequest $request): ProviderSendResult;

    /**
     * Query the status of a message
     *
     * @param string $messageId The message ID to query
     * @return ProviderMessageStatus The message status
     */
    public function getMessageStatus(string $messageId): ProviderMessageStatus;

    /**
     * Retrieve all available templates
     *
     * @return array<ProviderTemplate> Array of templates
     */
    public function getTemplates(): array;

    /**
     * Retrieve a specific template by ID
     *
     * @param string $templateId The template ID
     * @return ProviderTemplate|null The template or null if not found
     */
    public function getTemplate(string $templateId): ?ProviderTemplate;

    /**
     * Validate webhook received from the provider
     *
     * @param ServerRequestInterface $request The HTTP request
     * @return bool True if webhook is valid, false otherwise
     */
    public function validateWebhook(ServerRequestInterface $request): bool;

    /**
     * Process delivery report webhook
     *
     * @param array $payload The webhook payload
     * @return DeliveryReport The parsed delivery report
     */
    public function processDeliveryReport(array $payload): DeliveryReport;

    /**
     * Process incoming message webhook
     *
     * @param array $payload The webhook payload
     * @return IncomingMessage The parsed incoming message
     */
    public function processIncomingMessage(array $payload): IncomingMessage;

    /**
     * Process template update webhook
     *
     * @param array $payload The webhook payload
     * @return TemplateUpdate The parsed template update
     */
    public function processTemplateUpdate(array $payload): TemplateUpdate;

    /**
     * Get the provider name
     *
     * @return string The provider name (e.g., 'infobip', 'twilio')
     */
    public function getName(): string;
}
