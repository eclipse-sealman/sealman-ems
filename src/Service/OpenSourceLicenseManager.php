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

use App\Entity\OpenSourceLicense;
use App\Service\Helper\ConfigurationManagerTrait;
use App\Service\Helper\EntityManagerTrait;
use App\Service\Helper\SymfonyDirTrait;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Process\Process;

class OpenSourceLicenseManager
{
    use ConfigurationManagerTrait;
    use EntityManagerTrait;
    use SymfonyDirTrait;

    public const LICENSES_FILE = 'licenses/licenses.csv';
    public const COMPOSER_LICENSES_FILE = 'licenses/composer-licenses.csv';
    public const LICENSES_TXT_FILE = 'licenses/licenses.txt';
    public const LICENSE_MISSING_CONTENT = '* NO LICENSE INFORMATION FOUND **';

    /**
     * @var ?Filesystem
     */
    protected $filesystem = null;

    /**
     * @var ?array
     */
    protected $composerLockPackages = null;

    public function isLoadRequired(): bool
    {
        $md5Hash = $this->getConfiguration()->getOpenSourceLicenseMd5Hash();
        if (!$md5Hash) {
            return true;
        }

        return $this->getLicensesFileMd5Hash() !== $md5Hash;
    }

    public function load(): void
    {
        $this->clearOpenSourceLicenses();

        $fp = fopen($this->getLicensesFile(), 'r');

        while (($row = fgetcsv($fp)) !== false) {
            if (!isset($row[1])) {
                continue;
            }

            if (!isset($row[2])) {
                continue;
            }

            $license = new OpenSourceLicense();
            $license->setName($row[1]);
            $license->setVersion($row[2]);
            $license->setLicenseType($row[3] ?? null);
            $license->setDescription($row[4] ?? null);
            $license->setLicenseContent($row[5] ?? null);

            $this->entityManager->persist($license);
        }

        fclose($fp);

        $this->entityManager->flush();

        $configuration = $this->getConfiguration();
        $configuration->setOpenSourceLicenseMd5Hash($this->getLicensesFileMd5Hash());

        $this->entityManager->persist($configuration);
        $this->entityManager->flush();
    }

    public function dumpTxt(): void
    {
        $licensesTxtParts = [];

        $fp = fopen($this->getLicensesFile(), 'r');

        while (($row = fgetcsv($fp)) !== false) {
            if (!isset($row[1])) {
                continue;
            }

            if (!isset($row[2])) {
                continue;
            }

            $licensesTxtParts[] = $row[1]."\n".$row[2]."\n".($row[3] ?? null)."\n".($row[4] ?? null)."\n".($row[5] ?? null)."\n";
        }

        fclose($fp);

        file_put_contents($this->getLicensesTxtFile(), implode("\n\n", $licensesTxtParts));
    }

    protected function clearOpenSourceLicenses(): void
    {
        $queryBuilder = $this->getRepository(OpenSourceLicense::class)->createQueryBuilder('b');
        $queryBuilder->delete();

        $queryBuilder->getQuery()->execute();
    }

    public function composerDump(): void
    {
        $process = new Process(['composer', 'licenses', '--no-dev', '--format=json']);
        $process->setTimeout(3 * 60);
        $process->run();

        $licensesOutput = json_decode($process->getOutput(), true);
        if (null === $licensesOutput || !isset($licensesOutput['dependencies'])) {
            throw new \Exception('Could not list licenses using composer');
        }

        $fp = fopen($this->getComposerLicensesFile(), 'w');

        foreach ($licensesOutput['dependencies'] as $packageName => $packageData) {
            $version = $packageData['version'] ?? null;

            $csvData = [
                $packageName.'@'.$version,
                $packageName,
                $version,
                implode(' ', $packageData['license'] ?? []),
                $this->getDescription($packageName),
                $this->getLicenseContent($packageName),
            ];

            fputcsv($fp, $csvData, escape: '\\');
        }

        fclose($fp);
    }

    protected function getLicenseContentFileNames(): array
    {
        $fileNames = [];

        $names = ['license', 'License', 'LICENSE'];
        $extensions = ['', '.md', '.txt'];

        foreach ($names as $name) {
            foreach ($extensions as $extension) {
                $fileNames[] = $name.$extension;
            }
        }

        return $fileNames;
    }

    protected function getFilesystem(): Filesystem
    {
        if (null === $this->filesystem) {
            $this->filesystem = new Filesystem();
        }

        return $this->filesystem;
    }

    protected function getComposerLockPackages(): ?array
    {
        if (null === $this->composerLockPackages) {
            $composerLockJson = json_decode(file_get_contents($this->projectDir.'/composer.lock'), true);

            $this->composerLockPackages = $composerLockJson['packages'] ?? [];
        }

        return $this->composerLockPackages;
    }

    protected function getDescription(string $packageName): ?string
    {
        $packages = $this->getComposerLockPackages();

        foreach ($packages as $package) {
            if ($package['name'] === $packageName) {
                return $package['description'] ?? null;
            }
        }

        return null;
    }

    protected function getLicenseContent(string $packageName): ?string
    {
        $fileNames = $this->getLicenseContentFileNames();
        $packagePath = $this->projectDir.'/vendor/'.$packageName;
        $filesystem = $this->getFilesystem();

        foreach ($fileNames as $fileName) {
            $licenseFile = $packagePath.'/'.$fileName;
            if ($filesystem->exists($licenseFile)) {
                return file_get_contents($licenseFile);
            }
        }

        return self::LICENSE_MISSING_CONTENT;
    }

    protected function getLicensesFileMd5Hash(): string
    {
        return md5_file($this->getLicensesFile());
    }

    protected function getLicensesFile(): string
    {
        return $this->projectDir.'/'.self::LICENSES_FILE;
    }

    protected function getComposerLicensesFile(): string
    {
        return $this->projectDir.'/'.self::COMPOSER_LICENSES_FILE;
    }

    public function getLicensesTxtFile(): string
    {
        return $this->projectDir.'/'.self::LICENSES_TXT_FILE;
    }
}
