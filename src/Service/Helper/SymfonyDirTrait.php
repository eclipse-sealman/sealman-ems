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

namespace App\Service\Helper;

use Symfony\Contracts\Service\Attribute\Required;

trait SymfonyDirTrait
{
    /**
     * @var string
     */
    protected $projectDir;

    /**
     * @var string
     */
    protected $publicDir;

    /**
     * @var string
     */
    protected $filestorageDir;

    /**
     * @var string
     */
    protected $logsDir;

    #[Required]
    public function setProjectDir(string $projectDir)
    {
        $this->projectDir = $projectDir;
    }

    #[Required]
    public function setPublicDir(string $publicDir)
    {
        $this->publicDir = $publicDir;
    }

    #[Required]
    public function setFilestorageDir(string $filestorageDir)
    {
        $this->filestorageDir = $filestorageDir;
    }

    #[Required]
    public function setLogsDir(string $logsDir)
    {
        $this->logsDir = $logsDir;
    }
}
