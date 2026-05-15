<?php

// Copyright (c) 2025 Contributors to the Eclipse Foundation.
//
// See the NOTICE file(s) distributed with this work for additional
// information regarding copyright ownership.
//
// This program and the accompanying materials are made available under the
// terms of the Apache License, Version 2.0 which is available at
// https://www.apache.org/licenses/LICENSE-2.0
//
// SPDX-License-Identifier: Apache-2.0

declare(strict_types=1);

namespace App\Validator\Constraints;

use Symfony\Component\Validator\Constraints\File as BaseFile;
use Symfony\Component\Validator\Exception\ConstraintDefinitionException;

#[\Attribute(\Attribute::TARGET_PROPERTY | \Attribute::TARGET_METHOD | \Attribute::IS_REPEATABLE)]
class TusFile extends BaseFile
{
    public const SIMPLE_MIME_TYPE_IMAGE = 'image';
    public const SIMPLE_MIME_TYPE_PDF = 'pdf';
    public const SIMPLE_MIME_TYPE_EXCEL = 'excel';

    public string $simpleMimeTypesImageMessage = 'validation.tusFileSimpleMimeTypesImage';
    public string $simpleMimeTypesPdfMessage = 'validation.tusFileSimpleMimeTypesPdf';
    public string $simpleMimeTypesExcelMessage = 'validation.tusFileSimpleMimeTypesExcel';

    public string $notFoundMessage = 'validation.tusFileNotFound';
    public string $notReadableMessage = 'validation.tusFileNotReadable';
    public string $maxSizeMessage = 'validation.tusFileMaxSize';
    public string $mimeTypesMessage = 'validation.tusFileMimeTypes';
    public string $disallowEmptyMessage = 'validation.tusFileDisallowEmpty';
    public string $filenameTooLongMessage = 'validation.tusFilenameTooLongMessage';

    public string $uploadIniSizeErrorMessage = 'validation.tusFileUploadIniSizeError';
    public string $uploadFormSizeErrorMessage = 'validation.tusFileUploadFormSizeError';
    public string $uploadPartialErrorMessage = 'validation.tusFileUploadPartialError';
    public string $uploadNoFileErrorMessage = 'validation.tusFileUploadNoFileError';
    public string $uploadNoTmpDirErrorMessage = 'validation.tusFileUploadNoTmpDirError';
    public string $uploadCantWriteErrorMessage = 'validation.tusFileUploadCantWriteError';
    public string $uploadExtensionErrorMessage = 'validation.tusFileUploadExtensionError';
    public string $uploadErrorMessage = 'validation.tusFileUploadError';

    public function __construct(
        ?string $simpleMimeTypes = null,
        null|int|string $maxSize = null,
        ?bool $binaryFormat = null,
        null|array|string $mimeTypes = null,
        ?int $filenameMaxLength = null,

        ?string $notFoundMessage = null,
        ?string $notReadableMessage = null,
        ?string $maxSizeMessage = null,
        ?string $mimeTypesMessage = null,
        ?string $disallowEmptyMessage = null,
        ?string $filenameTooLongMessage = null,

        ?string $uploadIniSizeErrorMessage = null,
        ?string $uploadFormSizeErrorMessage = null,
        ?string $uploadPartialErrorMessage = null,
        ?string $uploadNoFileErrorMessage = null,
        ?string $uploadNoTmpDirErrorMessage = null,
        ?string $uploadCantWriteErrorMessage = null,
        ?string $uploadExtensionErrorMessage = null,
        ?string $uploadErrorMessage = null,

        null|array|string $extensions = null,
        ?string $extensionsMessage = null,
        ?string $filenameCharset = null,
        ?string $filenameCountUnit = null,
        ?string $filenameCharsetMessage = null,

        ?array $groups = null,
        mixed $payload = null,
    ) {
        if ($simpleMimeTypes) {
            $simpleMimeTypesMapping = self::getSimpleMimeTypesMapping();

            if (null === $mimeTypes) {
                $mimeTypes = [];
            }

            if (!is_array($mimeTypes)) {
                $mimeTypes = [$mimeTypes];
            }

            switch ($simpleMimeTypes) {
                case self::SIMPLE_MIME_TYPE_IMAGE:
                    $mimeTypesMessage = $mimeTypesMessage ?? 'validation.tusFileSimpleMimeTypesImage';
                    break;
                case self::SIMPLE_MIME_TYPE_PDF:
                    $mimeTypesMessage = $mimeTypesMessage ?? 'validation.tusFileSimpleMimeTypesPdf';
                    break;
                case self::SIMPLE_MIME_TYPE_EXCEL:
                    $mimeTypesMessage = $mimeTypesMessage ?? 'validation.tusFileSimpleMimeTypesExcel';
                    break;
                default:
                    throw new ConstraintDefinitionException(sprintf('The "%s" constraint allows $simpleMimeTypes to be one of following values: '.implode(',', [self::SIMPLE_MIME_TYPE_IMAGE, SIMPLE_MIME_TYPE_PDF, SIMPLE_MIME_TYPE_EXCEL]), static::class));
                    break;
            }

            $mimeTypes = array_merge($mimeTypes, $simpleMimeTypesMapping[$simpleMimeTypes]);
        }

        parent::__construct(
            maxSize: $maxSize,
            binaryFormat: $binaryFormat,
            mimeTypes: $mimeTypes,
            filenameMaxLength: $filenameMaxLength,

            notFoundMessage: $notFoundMessage,
            notReadableMessage: $notReadableMessage,
            maxSizeMessage: $maxSizeMessage,
            mimeTypesMessage: $mimeTypesMessage,
            disallowEmptyMessage: $disallowEmptyMessage,
            filenameTooLongMessage: $filenameTooLongMessage,

            uploadIniSizeErrorMessage: $uploadIniSizeErrorMessage,
            uploadFormSizeErrorMessage: $uploadFormSizeErrorMessage,
            uploadPartialErrorMessage: $uploadPartialErrorMessage,
            uploadNoFileErrorMessage: $uploadNoFileErrorMessage,
            uploadNoTmpDirErrorMessage: $uploadNoTmpDirErrorMessage,
            uploadCantWriteErrorMessage: $uploadCantWriteErrorMessage,
            uploadExtensionErrorMessage: $uploadExtensionErrorMessage,
            uploadErrorMessage: $uploadErrorMessage,

            extensions: $extensions,
            extensionsMessage: $extensionsMessage,
            filenameCharset: $filenameCharset,
            filenameCountUnit: $filenameCountUnit,
            filenameCharsetMessage: $filenameCharsetMessage,

            groups: $groups,
            payload: $payload,
        );
    }

    public static function getSimpleMimeTypesMapping(): array
    {
        return [
            self::SIMPLE_MIME_TYPE_IMAGE => [
                'image/bmp',
                'image/png',
                'image/jpeg',
                'image/svg+xml',
            ],
            self::SIMPLE_MIME_TYPE_PDF => [
                'application/pdf',
            ],
            self::SIMPLE_MIME_TYPE_EXCEL => [
                'application/zip', // Google Spreadsheet is exporting XLSX with this mimeType
                'application/octet-stream',
                'application/vnd.ms-excel',
                'application/msexcel',
                'application/x-msexcel',
                'application/x-ms-excel',
                'application/x-excel',
                'application/x-dos_ms_excel',
                'application/xls',
                'application/x-xls',
                'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            ],
        ];
    }
}
