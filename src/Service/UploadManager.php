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

namespace App\Service;

use App\Model\UploadInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

use function Symfony\Component\String\u;

use TusPhp\Middleware\Cors;
use TusPhp\Tus\Server as TusServer;

class UploadManager
{
    /**
     * @var FileManager
     */
    protected $fileManager;

    public function __construct(FileManager $fileManager)
    {
        $this->fileManager = $fileManager;
    }

    public function upload(UploadInterface $entity, string $field, ?string $previousValue = null)
    {
        $fieldPascalCase = u($field)->camel()->title();
        $getter = 'get'.$fieldPascalCase;
        $value = $entity->$getter();

        if (!$this->isTusUploadedFile($value)) {
            return;
        }

        $file = self::getTusFile($value);
        if (!$file) {
            // This can happen when submitting the same form with upload field twice
            // Second submit will still include previous upload token

            // Fallback to previous value
            $setter = 'set'.$fieldPascalCase;
            $entity->$setter($previousValue);

            return;
        }

        $filepath = $file['file_path'];

        try {
            $path = $this->fileManager->move($filepath, $entity->getUploadDir($field));

            $setter = 'set'.$fieldPascalCase;
            $entity->$setter($path);
        } catch (\Exception $e) {
            // Fallback to previous value in case of error
            $setter = 'set'.$fieldPascalCase;
            $entity->$setter($previousValue);
        }

        // Clear after processing upload
        $this->getTusServer($value)->getCache()->delete($value);
    }

    public static function isTusUploadedFile(?string $value): bool
    {
        if (!is_string($value) || (1 !== preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/', $value))) {
            return false;
        }

        return true;
    }

    public static function getTusFile(string $token): ?array
    {
        $tusServer = self::getTusServer($token);

        return $tusServer->getCache()->get($token);
    }

    public static function getTusServer(?string $token = null): TusServer
    {
        $server = new TusServer();
        $server->middleware()->skip(Cors::class);
        if (!$token) {
            $token = $server->getUploadKey();
        }

        if (!preg_match('/^[a-zA-Z0-9\-_]+$/', $token)) {
            throw new BadRequestHttpException('Token contains invalid characters.');
        }

        $apiPath = '/web/tus/upload';
        $dirPart = str_replace('/', '_', $apiPath);
        $dir = sys_get_temp_dir().'/web/tus/'.$dirPart.'/'.$token.'/';
        $uploadDir = $dir.'upload/';
        $cacheDir = $dir.'cache/';

        $fs = new Filesystem();
        $fs->mkdir($cacheDir);
        $fs->mkdir($uploadDir);

        $server->setApiPath($apiPath);
        $server->setUploadDir($uploadDir);

        $cacheAdapter = $server->getCache();
        $cacheAdapter->setCacheDir($cacheDir);

        return $server;
    }
}
