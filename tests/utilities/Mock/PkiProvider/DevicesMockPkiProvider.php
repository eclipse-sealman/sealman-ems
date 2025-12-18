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

namespace Tests\Utilities\Mock\PkiProvider;

/**
 * Class uses fake self-generated CA with subject "Fake Devices OpenVPN CA".
 */
class DevicesMockPkiProvider extends MockPkiProvider
{
    public function __construct()
    {
        parent::__construct(
            <<<CERTIFICATE
-----BEGIN CERTIFICATE-----
MIIDqTCCApGgAwIBAgIUFmV0F/hH+tMwLLesNAW5Sw/raCwwDQYJKoZIhvcNAQEL
BQAwYzELMAkGA1UEBhMCUEwxEjAQBgNVBAgMCUx1YmVsc2tpZTEPMA0GA1UEBwwG
THVibGluMQ0wCwYDVQQKDARUZXN0MSAwHgYDVQQDDBdGYWtlIERldmljZXMgT3Bl
blZQTiBDQTAgFw0yNTEyMTcxNTA0MzZaGA8zMDI1MDQxOTE1MDQzNlowYzELMAkG
A1UEBhMCUEwxEjAQBgNVBAgMCUx1YmVsc2tpZTEPMA0GA1UEBwwGTHVibGluMQ0w
CwYDVQQKDARUZXN0MSAwHgYDVQQDDBdGYWtlIERldmljZXMgT3BlblZQTiBDQTCC
ASIwDQYJKoZIhvcNAQEBBQADggEPADCCAQoCggEBAJLOrE2VnCxxqHI+jyji9Lld
baGa/B9CsGECPlGMYaPTI+VfffyXp0CJQ5juAvrQ8/RoHg0308nI2AG9AZRUWbK6
ZEvKSjyRhIA0Lo3T/UK7BtZeSlpJKIsMrOGFqPQalY3zdFOC//vE6qhkaPOFNK3M
R+t0QAXg40aHOrSgIyna1zV6ggGpEXWEkYBB1YngYRoBordN5flxrBlJ2ZKne6lP
LPy+9jRpWNu08CRlnkP/DbF/0u17pPo4DQFe1MwMXH83bzIY0hFEv8+Nc/IECj5H
I0ribsRY02SliURxJsWZDe2aq8twTt9gs5lcXSmoegHkLWs5DgPm/Odd/UK2908C
AwEAAaNTMFEwHQYDVR0OBBYEFLlldUJRj+oWhN0R+tnD8vfDfQBDMB8GA1UdIwQY
MBaAFLlldUJRj+oWhN0R+tnD8vfDfQBDMA8GA1UdEwEB/wQFMAMBAf8wDQYJKoZI
hvcNAQELBQADggEBAFh2Hhbd2vUfbt0b4S/GNjb8ZXmaLSb/Bzk+15cN7WZPk4ie
K+vcP/srz22pxWfQtvYIxohvPRpWE5b0YuopRS1RRU+pD3nV2uvmi+CCZBctmt+W
HPj6dXq5kgEfmTjfGBpKzDtGOq4MTus6So8xWy7yABf9BLGHsS/lRWesnL0M2AGb
i1UmDX/opFfoipUHQBVT6Qx5NvsV5bJW8EXv8i4b8FGEOyKzRKbiq+F2v9lYMvfF
b7/lRJmR1tZ7B3dXqtsqcwgGBuRKfWk2Fd91n2xRJNcdJp8pG6jMmHDbBz1q7iTr
iCIFdua9s+eG6Sl4qR+IOaTkv7xYcgLDQxjCMf4=
-----END CERTIFICATE-----
CERTIFICATE,
            <<<PRIVATEKEY
-----BEGIN PRIVATE KEY-----
MIIEvAIBADANBgkqhkiG9w0BAQEFAASCBKYwggSiAgEAAoIBAQCSzqxNlZwscahy
Po8o4vS5XW2hmvwfQrBhAj5RjGGj0yPlX338l6dAiUOY7gL60PP0aB4NN9PJyNgB
vQGUVFmyumRLyko8kYSANC6N0/1CuwbWXkpaSSiLDKzhhaj0GpWN83RTgv/7xOqo
ZGjzhTStzEfrdEAF4ONGhzq0oCMp2tc1eoIBqRF1hJGAQdWJ4GEaAaK3TeX5cawZ
SdmSp3upTyz8vvY0aVjbtPAkZZ5D/w2xf9Lte6T6OA0BXtTMDFx/N28yGNIRRL/P
jXPyBAo+RyNK4m7EWNNkpYlEcSbFmQ3tmqvLcE7fYLOZXF0pqHoB5C1rOQ4D5vzn
Xf1CtvdPAgMBAAECggEAAo0Sg8NKnvUXUxY7i5+CAX9E9UQfByC0Cu4sjLiOm80C
oyzpILdhQdGreeACROnWS2l3GDi09p0wWmYL6HrHbOp97Vk7DVlj/uGpQMVYcc0+
iFbEHlSGHqseN/RdeWca291z/d2EED5y63EvPQgUCxnGfX9xe14Aj7cFh0z/FMOW
MIPH1rBYoPJPSPyvdWOLVU9b/jM6Vpr2YHTOwZUYJROECL3FLtV3K1bfphsoPtiw
0VgkojMIyhFhm4uhzjF805N4RPnpVSPwQjDA6vdgh1VVF5GWaC3cTosLBZRWjT+p
PsR8kc1XD5W6UVBjUqCrW1qFaaZh2x7eynnzT1y5aQKBgQC8HHaDnKSe7T2unpEY
KOSR7g3yG79RuiTgiNmRzkdX5k2G0ROj76ZXAh9zf8+pYovymRpHDpyEQDTvoKbe
YGJm8FjdUvn/nPRDWfeXFLh8Wvg6ji7fMOvy/1Y2VioiiltUu7cwEktSG2I/t0Jy
qd3M9glW2LCqF7ikOiy6zKT7NwKBgQDHyihxYd2/1Q6ZTt7ovzVEu6ATA/XctH+j
uC9q7ygwwuU5w2jzMiVHISuc3hneGdli8h2xaKGJmzcAi8mHozvhwxqy4/zYvKLl
G7QcPmBPpfiuUEYq9Y/ChSbPNoEiElssJ38ALEINwRTqkE5MlGY8MiJf2BOvTVCl
oIzComvgqQKBgBlwg/qSkZTIosHdweORjC/MEOjAJeSIlvoip8HXMsDJbYyg89YE
z+sOZ3B4RX4zzJdXaz1W1YXxJPePM2H5iPVA5dOwqAyQjlwZa6lr7PMsXkuU9PPM
Kuym6WLZzkLzkRxpcoG2x6bn+yaAwyS8ojlYwLSVA3dNU/QpxejRSjKLAoGAJPjk
Qxc5Uia7bOOLnMbFtNKD7QDunslIVaPgIonfhiaLBQWEhnzhKSiaSY1QfCmMcSMd
G5ehTTXMF+3GfbNXgY/5gOFwCSvfeUaHLjLc3+B3BMsWMR0AXZ8Gb5JGk2eSN7mX
ZxDJqIHyvPW3h7RutvUQJ2x1OBu2sO/lHJ+yFwECgYBqLnVrC9MpQpFKjYKXo2JX
bn5hgjza4YpxNF7c0c4pL+JsQhQM7zKr9WcEXsz/nALx+I7wJ1MbdsxhI3iAnfuq
09SbkUoLFceOMEzFVdMM9qjhdtwpp1JAqyHPMCfYzieod4He3o6Jje4T1JICVnK6
6I+zy0paK11PO1GKGlaS6g==
-----END PRIVATE KEY-----
PRIVATEKEY
        );
    }
}
