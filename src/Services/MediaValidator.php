<?php

declare(strict_types=1);

namespace WhatsApp\Adapter\Services;

use WhatsApp\Adapter\Models\Requests\MediaRequest;

class MediaValidator
{
    // Formatos válidos por tipo de media
    private const VALID_IMAGE_FORMATS = ['jpeg', 'jpg', 'png'];
    private const VALID_DOCUMENT_FORMATS = ['pdf', 'doc', 'docx', 'xls', 'xlsx'];
    private const VALID_AUDIO_FORMATS = ['mp3', 'ogg', 'amr'];
    private const VALID_VIDEO_FORMATS = ['mp4', '3gp'];

    // Tamanhos máximos em bytes
    private const MAX_IMAGE_SIZE = 5 * 1024 * 1024; // 5MB
    private const MAX_DOCUMENT_SIZE = 100 * 1024 * 1024; // 100MB
    private const MAX_AUDIO_SIZE = 16 * 1024 * 1024; // 16MB
    private const MAX_VIDEO_SIZE = 16 * 1024 * 1024; // 16MB

    // Durações máximas em segundos
    private const MAX_AUDIO_DURATION = 900; // 15 minutos
    private const MAX_VIDEO_DURATION = 900; // 15 minutos

    /**
     * Valida um pedido de media
     *
     * @throws \InvalidArgumentException se a validação falhar
     */
    public function validate(MediaRequest $request): void
    {
        $this->validateFormat($request->mediaType, $request->mediaUrl, $request->filename);
        $this->validateSize($request->mediaType, $request->mediaUrl);
        
        if (in_array($request->mediaType, ['audio', 'video'], true)) {
            $this->validateDuration($request->mediaType, $request->mediaUrl);
        }
    }

    /**
     * Valida o formato do ficheiro de media
     */
    private function validateFormat(string $mediaType, string $mediaUrl, ?string $filename): void
    {
        $extension = $this->extractExtension($mediaUrl, $filename);
        
        if ($extension === null) {
            throw new \InvalidArgumentException(
                "Unable to determine file extension for media type '{$mediaType}'"
            );
        }

        $validFormats = $this->getValidFormats($mediaType);
        
        if (!in_array(strtolower($extension), $validFormats, true)) {
            throw new \InvalidArgumentException(
                sprintf(
                    'Invalid %s format "%s". Valid formats: %s',
                    $mediaType,
                    $extension,
                    implode(', ', $validFormats)
                )
            );
        }
    }

    /**
     * Valida o tamanho do ficheiro de media
     */
    private function validateSize(string $mediaType, string $mediaUrl): void
    {
        // Se for uma URL, tentamos obter o tamanho via HEAD request
        if ($this->isUrl($mediaUrl)) {
            $size = $this->getRemoteFileSize($mediaUrl);
            
            if ($size === null) {
                // Não conseguimos determinar o tamanho, mas não bloqueamos
                // O provedor fará a validação final
                return;
            }
        } else {
            // Se for um caminho local, obtemos o tamanho diretamente
            if (!file_exists($mediaUrl)) {
                throw new \InvalidArgumentException("File not found: {$mediaUrl}");
            }
            
            $size = filesize($mediaUrl);
            
            if ($size === false) {
                throw new \InvalidArgumentException("Unable to determine file size: {$mediaUrl}");
            }
        }

        $maxSize = $this->getMaxSize($mediaType);
        
        if ($size > $maxSize) {
            throw new \InvalidArgumentException(
                sprintf(
                    '%s size %s exceeds maximum allowed size of %s',
                    ucfirst($mediaType),
                    $this->formatBytes($size),
                    $this->formatBytes($maxSize)
                )
            );
        }
    }

    /**
     * Valida a duração de áudio ou vídeo
     */
    private function validateDuration(string $mediaType, string $mediaUrl): void
    {
        // Para validação de duração, precisaríamos de uma biblioteca como FFmpeg
        // Por enquanto, implementamos uma validação básica que pode ser estendida
        
        // Se for uma URL, não podemos validar duração sem fazer download
        if ($this->isUrl($mediaUrl)) {
            return;
        }

        // Se for um ficheiro local, poderíamos usar FFmpeg para obter a duração
        // Por enquanto, apenas verificamos se o ficheiro existe
        if (!file_exists($mediaUrl)) {
            throw new \InvalidArgumentException("File not found: {$mediaUrl}");
        }

        // Nota: Para validação completa de duração, seria necessário:
        // 1. Instalar FFmpeg
        // 2. Usar uma biblioteca PHP como PHP-FFMpeg
        // 3. Extrair metadados do ficheiro
        // Exemplo:
        // $ffprobe = FFMpeg\FFProbe::create();
        // $duration = $ffprobe->format($mediaUrl)->get('duration');
        // if ($duration > $this->getMaxDuration($mediaType)) { throw ... }
    }

    /**
     * Extrai a extensão do ficheiro da URL ou filename
     */
    private function extractExtension(string $mediaUrl, ?string $filename): ?string
    {
        // Primeiro tenta obter do filename se fornecido
        if ($filename !== null) {
            $extension = pathinfo($filename, PATHINFO_EXTENSION);
            if (!empty($extension)) {
                return $extension;
            }
        }

        // Depois tenta obter da URL
        $path = parse_url($mediaUrl, PHP_URL_PATH);
        if ($path !== null) {
            $extension = pathinfo($path, PATHINFO_EXTENSION);
            if (!empty($extension)) {
                return $extension;
            }
        }

        return null;
    }

    /**
     * Verifica se uma string é uma URL
     */
    private function isUrl(string $path): bool
    {
        return filter_var($path, FILTER_VALIDATE_URL) !== false;
    }

    /**
     * Obtém o tamanho de um ficheiro remoto via HEAD request
     */
    private function getRemoteFileSize(string $url): ?int
    {
        $headers = @get_headers($url, true);
        
        if ($headers === false) {
            return null;
        }

        // Procura pelo header Content-Length
        if (isset($headers['Content-Length'])) {
            $contentLength = $headers['Content-Length'];
            
            // Pode ser um array se houver redirects
            if (is_array($contentLength)) {
                $contentLength = end($contentLength);
            }
            
            return (int) $contentLength;
        }

        return null;
    }

    /**
     * Retorna os formatos válidos para um tipo de media
     */
    private function getValidFormats(string $mediaType): array
    {
        return match ($mediaType) {
            'image' => self::VALID_IMAGE_FORMATS,
            'document' => self::VALID_DOCUMENT_FORMATS,
            'audio' => self::VALID_AUDIO_FORMATS,
            'video' => self::VALID_VIDEO_FORMATS,
            default => []
        };
    }

    /**
     * Retorna o tamanho máximo para um tipo de media
     */
    private function getMaxSize(string $mediaType): int
    {
        return match ($mediaType) {
            'image' => self::MAX_IMAGE_SIZE,
            'document' => self::MAX_DOCUMENT_SIZE,
            'audio' => self::MAX_AUDIO_SIZE,
            'video' => self::MAX_VIDEO_SIZE,
            default => 0
        };
    }

    /**
     * Retorna a duração máxima para um tipo de media
     */
    private function getMaxDuration(string $mediaType): int
    {
        return match ($mediaType) {
            'audio' => self::MAX_AUDIO_DURATION,
            'video' => self::MAX_VIDEO_DURATION,
            default => 0
        };
    }

    /**
     * Formata bytes para formato legível
     */
    private function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $i = 0;
        
        while ($bytes >= 1024 && $i < count($units) - 1) {
            $bytes /= 1024;
            $i++;
        }

        return round($bytes, 2) . ' ' . $units[$i];
    }

    /**
     * Valida se o método de upload é suportado (URL ou upload direto)
     */
    public function validateUploadMethod(string $mediaUrl): bool
    {
        // Suporta URLs (http/https)
        if ($this->isUrl($mediaUrl)) {
            return true;
        }

        // Suporta caminhos de ficheiros locais
        if (file_exists($mediaUrl)) {
            return true;
        }

        return false;
    }
}
