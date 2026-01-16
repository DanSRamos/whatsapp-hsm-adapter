<?php

declare(strict_types=1);

use WhatsApp\Adapter\Models\Requests\MediaRequest;
use WhatsApp\Adapter\Services\MediaValidator;

/**
 * Property 13: Media Validation
 * 
 * For any pedido de envio de media (imagem, documento, áudio, vídeo), 
 * o adapter deve validar formato e tamanho/duração máxima, rejeitando 
 * media inválida com erro específico
 * 
 * Validates: Requirements 7.1, 7.2, 7.3, 7.4, 7.7
 * Feature: whatsapp-hsm-adapter, Property 13: Media Validation
 */

describe('Property 13: Media Validation', function () {
    
    beforeEach(function () {
        $this->validator = new MediaValidator();
    });

    test('validates image formats (JPEG, PNG)', function () {
        $validFormats = ['jpeg', 'jpg', 'png'];
        $invalidFormats = ['gif', 'bmp'];

        // Test valid image formats
        foreach ($validFormats as $format) {
            $request = new MediaRequest(
                to: '+351912345678',
                mediaType: 'image',
                mediaUrl: "https://example.com/image.{$format}",
                filename: "image.{$format}"
            );

            expect(fn() => $this->validator->validate($request))
                ->not->toThrow(InvalidArgumentException::class);
        }

        // Test invalid image formats
        foreach ($invalidFormats as $format) {
            $request = new MediaRequest(
                to: '+351912345678',
                mediaType: 'image',
                mediaUrl: "https://example.com/image.{$format}",
                filename: "image.{$format}"
            );

            expect(fn() => $this->validator->validate($request))
                ->toThrow(InvalidArgumentException::class, 'Invalid image format');
        }
    });

    test('validates document formats (PDF, DOC, DOCX, XLS, XLSX)', function () {
        $validFormats = ['pdf', 'docx', 'xlsx'];
        $invalidFormats = ['txt', 'csv'];

        // Test valid document formats
        foreach ($validFormats as $format) {
            $request = new MediaRequest(
                to: '+351912345678',
                mediaType: 'document',
                mediaUrl: "https://example.com/document.{$format}",
                filename: "document.{$format}"
            );

            expect(fn() => $this->validator->validate($request))
                ->not->toThrow(InvalidArgumentException::class);
        }

        // Test invalid document formats
        foreach ($invalidFormats as $format) {
            $request = new MediaRequest(
                to: '+351912345678',
                mediaType: 'document',
                mediaUrl: "https://example.com/document.{$format}",
                filename: "document.{$format}"
            );

            expect(fn() => $this->validator->validate($request))
                ->toThrow(InvalidArgumentException::class, 'Invalid document format');
        }
    });

    test('validates audio formats (MP3, OGG, AMR)', function () {
        $validFormats = ['mp3', 'ogg'];
        $invalidFormats = ['wav', 'flac'];

        // Test valid audio formats
        foreach ($validFormats as $format) {
            $request = new MediaRequest(
                to: '+351912345678',
                mediaType: 'audio',
                mediaUrl: "https://example.com/audio.{$format}",
                filename: "audio.{$format}"
            );

            expect(fn() => $this->validator->validate($request))
                ->not->toThrow(InvalidArgumentException::class);
        }

        // Test invalid audio formats
        foreach ($invalidFormats as $format) {
            $request = new MediaRequest(
                to: '+351912345678',
                mediaType: 'audio',
                mediaUrl: "https://example.com/audio.{$format}",
                filename: "audio.{$format}"
            );

            expect(fn() => $this->validator->validate($request))
                ->toThrow(InvalidArgumentException::class, 'Invalid audio format');
        }
    });

    test('validates video formats (MP4, 3GP)', function () {
        $validFormats = ['mp4', '3gp'];
        $invalidFormats = ['avi', 'mov'];

        // Test valid video formats
        foreach ($validFormats as $format) {
            $request = new MediaRequest(
                to: '+351912345678',
                mediaType: 'video',
                mediaUrl: "https://example.com/video.{$format}",
                filename: "video.{$format}"
            );

            expect(fn() => $this->validator->validate($request))
                ->not->toThrow(InvalidArgumentException::class);
        }

        // Test invalid video formats
        foreach ($invalidFormats as $format) {
            $request = new MediaRequest(
                to: '+351912345678',
                mediaType: 'video',
                mediaUrl: "https://example.com/video.{$format}",
                filename: "video.{$format}"
            );

            expect(fn() => $this->validator->validate($request))
                ->toThrow(InvalidArgumentException::class, 'Invalid video format');
        }
    });

    test('extracts file extension from URL when filename not provided', function () {
        $testCases = [
            ['https://example.com/image.jpg', 'image', true],
            ['https://example.com/document.pdf', 'document', true],
            ['https://example.com/file.txt', 'image', false], // Invalid format
        ];

        foreach ($testCases as [$url, $mediaType, $shouldPass]) {
            $request = new MediaRequest(
                to: '+351912345678',
                mediaType: $mediaType,
                mediaUrl: $url
            );

            if ($shouldPass) {
                expect(fn() => $this->validator->validate($request))
                    ->not->toThrow(InvalidArgumentException::class);
            } else {
                expect(fn() => $this->validator->validate($request))
                    ->toThrow(InvalidArgumentException::class);
            }
        }
    });

    test('validates format is case-insensitive', function () {
        $testCases = [
            'image.JPG',
            'document.PDF',
            'audio.MP3',
            'video.MP4',
        ];

        foreach ($testCases as $filename) {
            $extension = pathinfo($filename, PATHINFO_EXTENSION);
            $mediaType = match(strtolower($extension)) {
                'jpg', 'jpeg', 'png' => 'image',
                'pdf', 'doc', 'docx' => 'document',
                'mp3', 'ogg', 'amr' => 'audio',
                'mp4', '3gp' => 'video',
                default => 'image'
            };

            $request = new MediaRequest(
                to: '+351912345678',
                mediaType: $mediaType,
                mediaUrl: "https://example.com/{$filename}",
                filename: $filename
            );

            expect(fn() => $this->validator->validate($request))
                ->not->toThrow(InvalidArgumentException::class);
        }
    });

    test('rejects media when extension cannot be determined', function () {
        $request = new MediaRequest(
            to: '+351912345678',
            mediaType: 'image',
            mediaUrl: 'https://example.com/file'
        );

        expect(fn() => $this->validator->validate($request))
            ->toThrow(InvalidArgumentException::class, 'Unable to determine file extension');
    });
});

/**
 * Property 14: Media Upload Method Support
 * 
 * For any media enviada, o adapter deve suportar tanto envio através 
 * de URL quanto upload direto
 * 
 * Validates: Requirements 7.5
 * Feature: whatsapp-hsm-adapter, Property 14: Media Upload Method Support
 */

describe('Property 14: Media Upload Method Support', function () {
    
    beforeEach(function () {
        $this->validator = new MediaValidator();
    });

    test('supports URL-based media upload', function () {
        $urls = [
            'https://example.com/image.jpg',
            'https://cdn.example.com/path/to/audio.mp3',
        ];

        foreach ($urls as $url) {
            $isSupported = $this->validator->validateUploadMethod($url);
            expect($isSupported)->toBeTrue();
        }
    });

    test('supports file path-based media upload', function () {
        // Create temporary test files
        $tempFiles = [];
        $formats = [
            'image' => 'jpg',
            'document' => 'pdf',
        ];

        foreach ($formats as $type => $extension) {
            $tempFile = tempnam(sys_get_temp_dir(), 'test_') . ".{$extension}";
            file_put_contents($tempFile, 'test content');
            $tempFiles[] = $tempFile;

            $isSupported = $this->validator->validateUploadMethod($tempFile);
            expect($isSupported)->toBeTrue();
        }

        // Cleanup
        foreach ($tempFiles as $file) {
            if (file_exists($file)) {
                unlink($file);
            }
        }
    });

    test('rejects invalid upload methods', function () {
        $invalidPaths = [
            '/nonexistent/path/to/file.jpg',
        ];

        foreach ($invalidPaths as $path) {
            $isSupported = $this->validator->validateUploadMethod($path);
            expect($isSupported)->toBeFalse();
        }
    });

    test('validates both URL and file path methods work with MediaRequest', function () {
        // Test URL method
        $urlRequest = new MediaRequest(
            to: '+351912345678',
            mediaType: 'image',
            mediaUrl: 'https://example.com/image.jpg'
        );

        expect($this->validator->validateUploadMethod($urlRequest->mediaUrl))->toBeTrue();

        // Test file path method
        $tempFile = tempnam(sys_get_temp_dir(), 'test_') . '.jpg';
        file_put_contents($tempFile, 'test content');

        $fileRequest = new MediaRequest(
            to: '+351912345678',
            mediaType: 'image',
            mediaUrl: $tempFile
        );

        expect($this->validator->validateUploadMethod($fileRequest->mediaUrl))->toBeTrue();

        // Cleanup
        if (file_exists($tempFile)) {
            unlink($tempFile);
        }
    });
});
