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

    public $simpleMimeTypesImageMessage = 'validation.tusFileSimpleMimeTypesImage';
    public $simpleMimeTypesPdfMessage = 'validation.tusFileSimpleMimeTypesPdf';
    public $simpleMimeTypesExcelMessage = 'validation.tusFileSimpleMimeTypesExcel';

    public function __construct(
        null|array $options = null,
        null|string $simpleMimeTypes = null,
        null|int|string $maxSize = null,
        null|bool $binaryFormat = null,
        null|array|string $mimeTypes = null,
        null|int $filenameMaxLength = null,
        null|string $notFoundMessage = 'validation.tusFileNotFound',
        null|string $notReadableMessage = 'validation.tusFileNotReadable',
        null|string $maxSizeMessage = 'validation.tusFileMaxSize',
        null|string $mimeTypesMessage = 'validation.tusFileMimeTypes',
        null|string $disallowEmptyMessage = 'validation.tusFileDisallowEmpty',
        null|string $filenameTooLongMessage = 'validation.tusFilenameTooLongMessage',

        null|string $uploadIniSizeErrorMessage = 'validation.tusFileUploadIniSizeError',
        null|string $uploadFormSizeErrorMessage = 'validation.tusFileUploadFormSizeError',
        null|string $uploadPartialErrorMessage = 'validation.tusFileUploadPartialError',
        null|string $uploadNoFileErrorMessage = 'validation.tusFileUploadNoFileError',
        null|string $uploadNoTmpDirErrorMessage = 'validation.tusFileUploadNoTmpDirError',
        null|string $uploadCantWriteErrorMessage = 'validation.tusFileUploadCantWriteError',
        null|string $uploadExtensionErrorMessage = 'validation.tusFileUploadExtensionError',
        null|string $uploadErrorMessage = 'validation.tusFileUploadError',
        null|array $groups = null,
        $payload = null,

        null|array|string $extensions = null,
        null|string $extensionsMessage = null, )
    {
        if (isset($options['simpleMimeTypes']) || $simpleMimeTypes) {
            $mimeTypes = isset($options['mimeTypes']) ? $options['mimeTypes'] : $mimeTypes;
            $simpleMimeTypes = isset($options['simpleMimeTypes']) ? $options['simpleMimeTypes'] : $simpleMimeTypes;
            $simpleMimeTypesMapping = self::getSimpleMimeTypesMapping();

            if (null === $mimeTypes) {
                $mimeTypes = [];
            }

            if (!is_array($mimeTypes)) {
                $mimeTypes = [$mimeTypes];
            }

            $mimeTypes = array_merge($mimeTypes, $simpleMimeTypesMapping[$simpleMimeTypes]);

            $options['mimeTypes'] = $mimeTypes;

            switch ($simpleMimeTypes) {
                case self::SIMPLE_MIME_TYPE_IMAGE:
                    $options['mimeTypesMessage'] = 'validation.tusFileSimpleMimeTypesImage';
                    break;
                case self::SIMPLE_MIME_TYPE_PDF:
                    $options['mimeTypesMessage'] = 'validation.tusFileSimpleMimeTypesPdf';
                    break;
                case self::SIMPLE_MIME_TYPE_EXCEL:
                    $options['mimeTypesMessage'] = 'validation.tusFileSimpleMimeTypesExcel';
                    break;
                default:
                    throw new ConstraintDefinitionException(sprintf('The "%s" constraint allows $simpleMimeTypes to be one of following values: '.implode(',', [self::SIMPLE_MIME_TYPE_IMAGE, SIMPLE_MIME_TYPE_PDF, SIMPLE_MIME_TYPE_EXCEL]), static::class));
                    break;
            }
        }

        parent::__construct(
            $options,
            $maxSize,
            $binaryFormat,
            $mimeTypes,
            $filenameMaxLength,
            $notFoundMessage,
            $notReadableMessage,
            $maxSizeMessage,
            $mimeTypesMessage,
            $disallowEmptyMessage,
            $filenameTooLongMessage,
            $uploadIniSizeErrorMessage,
            $uploadFormSizeErrorMessage,
            $uploadPartialErrorMessage,
            $uploadNoFileErrorMessage,
            $uploadNoTmpDirErrorMessage,
            $uploadCantWriteErrorMessage,
            $uploadExtensionErrorMessage,
            $uploadErrorMessage,
            $groups,
            $payload,
            $extensions,
            $extensionsMessage,
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
