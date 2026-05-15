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

use App\Service\Helper\SymfonyDirTrait;
use App\Tool\Urlizer;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;
use Symfony\Component\HttpFoundation\File\File;

class FileManager
{
    use SymfonyDirTrait;

    public function getSysTempDir(): string
    {
        return sys_get_temp_dir();
    }

    public function getTmpFile(string $prefix = 'tmp_file_'): string
    {
        $fs = new Filesystem();

        return $fs->tempnam($this->getSysTempDir(), $prefix);
    }

    public function dumpTmpFile(string $content, string $prefix = 'tmp_file_'): string
    {
        $fs = new Filesystem();

        $tmpFile = $this->getTmpFile($prefix);
        $fs->dumpFile($tmpFile, $content);

        return $tmpFile;
    }

    public function createTmpDir(?string $prefix = null): string
    {
        $fs = new Filesystem();
        $tmpDir = $fs->tempnam($this->getSysTempDir(), $prefix ?? 'app_');

        // tempnam creates a file, we need a directory
        $fs->remove($tmpDir);
        $fs->mkdir($tmpDir, 0700);

        return $tmpDir;
    }

    public function removeTmpDir(string $tmpDir): void
    {
        $fs = new Filesystem();
        if ($fs->exists($tmpDir)) {
            $fs->remove($tmpDir);
        }
    }

    public function move($sourceFile, $desiredFilepath, $copy = false): ?string
    {
        $fs = new Filesystem();

        try {
            $file = new File($sourceFile);

            $destinationFilename = $this->slugify($desiredFilepath, $file->getBasename('.'.$file->getExtension()), $file->getExtension());
            if (!$fs->exists($desiredFilepath)) {
                $fs->mkdir($desiredFilepath);
            }

            if ($copy) {
                $fs->copy($sourceFile, $desiredFilepath.$destinationFilename);
            } else {
                $fs->rename($sourceFile, $desiredFilepath.$destinationFilename);
            }

            return $desiredFilepath.$destinationFilename;
        } catch (\Exception $ex) {
            throw $ex;
        }
    }

    public function mkdir($dir, int $mode = 0777): void
    {
        $fs = new Filesystem();
        try {
            $fs->mkdir($dir, $mode);
        } catch (\Exception $ex) {
        }
    }

    public function getFilesize($sourceFile)
    {
        if (!file_exists($sourceFile)) {
            return false;
        }
        $content = file_get_contents($sourceFile);

        return strlen($content);
    }

    public function remove($filepath): void
    {
        $fs = new Filesystem();

        try {
            $file = new File($filepath);
            $dir = $file->getPath();
            $fs->remove($file);
            $this->safeRemoveDir($dir);
        } catch (\Exception $ex) {
        }
    }

    public function removeFile(string $path): void
    {
        $fs = new Filesystem();

        try {
            $fs->remove($path);
        } catch (\Exception $ex) {
        }
    }

    public function safeRemoveDir($dir): void
    {
        $fs = new Filesystem();

        if ($fs->exists($dir)) {
            $finder = new Finder();
            $finder->files()->in($dir);

            if (0 === count($finder)) {
                $parentDir = dirname($dir);
                $fs = new Filesystem();
                $fs->remove($dir);

                $this->safeRemoveDir($parentDir);
            }
        }
    }

    protected function slugify($desiredFilepath, $fileName, $fileExtension, $iteration = 0): ?string
    {
        $fs = new Filesystem();

        $newBasename = Urlizer::urlize($fileName.($iteration > 0 ? '-'.$iteration : ''));
        $destinationFilename = $newBasename.'.'.$fileExtension;
        if ($fs->exists($desiredFilepath.$destinationFilename)) {
            ++$iteration;
            $destinationFilename = $this->slugify($desiredFilepath, $fileName, $fileExtension, $iteration);
        }

        return $destinationFilename;
    }
}
