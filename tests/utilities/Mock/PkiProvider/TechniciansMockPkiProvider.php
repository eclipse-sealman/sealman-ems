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
 * Class uses fake self-generated CA with subject "Fake Technicians OpenVPN CA".
 */
class TechniciansMockPkiProvider extends MockPkiProvider
{
    public function __construct()
    {
        parent::__construct(
            <<<CERTIFICATE
-----BEGIN CERTIFICATE-----
MIIDsTCCApmgAwIBAgIUdGBa3rDdf0koy6g9ESDZSwXetHgwDQYJKoZIhvcNAQEL
BQAwZzELMAkGA1UEBhMCUEwxEjAQBgNVBAgMCUx1YmVsc2tpZTEPMA0GA1UEBwwG
THVibGluMQ0wCwYDVQQKDARUZXN0MSQwIgYDVQQDDBtGYWtlIFRlY2huaWNpYW5z
IE9wZW5WUE4gQ0EwIBcNMjUxMjE3MTUwNTA5WhgPMzAyNTA0MTkxNTA1MDlaMGcx
CzAJBgNVBAYTAlBMMRIwEAYDVQQIDAlMdWJlbHNraWUxDzANBgNVBAcMBkx1Ymxp
bjENMAsGA1UECgwEVGVzdDEkMCIGA1UEAwwbRmFrZSBUZWNobmljaWFucyBPcGVu
VlBOIENBMIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8AMIIBCgKCAQEAl4VWpyo8Icb7
29QNyxpoa4wUGpSOzKPqXGfysyw3M6V4zeNU6ViXoJPDjucGDuv3ZfvvW1guL77W
FugP0RSWb9tFLoLQah8J3DqlrRoGYfFmyjv6OL4hpQQ0AFsbjG73XYlKv2ZmAHoZ
OJqZ+OTNYugX+enOWMUs/A4RUF5fLM+WACepGIhDMIM7nG9vHLy4U3c4HkiH3qsS
Z5z1erX42FnmFdJWnsZPNiOdfk60XSNfUb0K3KsD/V7NjkDHzikUyUYoEksMfLEB
99lsj2gT7erpRfWv+8OXdvllh8NX4MdX1JbNkVY1gjeMjoV60Cn5uFYnj5m4D796
pdHVZSDzAwIDAQABo1MwUTAdBgNVHQ4EFgQUL3KmJVU8vQmwEH/Xd540/yy3SPcw
HwYDVR0jBBgwFoAUL3KmJVU8vQmwEH/Xd540/yy3SPcwDwYDVR0TAQH/BAUwAwEB
/zANBgkqhkiG9w0BAQsFAAOCAQEAloNdsuktbs1XwKE2Ug3/XPr1Vjh2NlIEnWTy
MpSUq4MnVVePRLNlq1G+qltpuba1/z2NbunWhuyUxr/BsyukgLzlBpwUfwoh+cAM
Z+NJPQ/lPfbauLZLlBg2/ErXF/mEjueaZXmP3LgfWI0frc6tRbvpLn+xSuIPG3/+
PPMKbkFR3mOaOLnAX2kjdO2ehNORF6DwaD4eB2FxnwHm8S1gMzH76EX3RP6uJ6ia
akyR/ZITrommRxNwm+TLI0cRYZ81/FwEMF4OMHoHTN4aDYM+G4Q1ZZrp4fSS2NdW
l3xuJhbWjslPIbCH6zCUltSlbqfkWT1VmbbcbwrqaN4ooahqeg==
-----END CERTIFICATE-----
CERTIFICATE,
            <<<PRIVATEKEY
-----BEGIN PRIVATE KEY-----
MIIEvQIBADANBgkqhkiG9w0BAQEFAASCBKcwggSjAgEAAoIBAQCXhVanKjwhxvvb
1A3LGmhrjBQalI7Mo+pcZ/KzLDczpXjN41TpWJegk8OO5wYO6/dl++9bWC4vvtYW
6A/RFJZv20UugtBqHwncOqWtGgZh8WbKO/o4viGlBDQAWxuMbvddiUq/ZmYAehk4
mpn45M1i6Bf56c5YxSz8DhFQXl8sz5YAJ6kYiEMwgzucb28cvLhTdzgeSIfeqxJn
nPV6tfjYWeYV0laexk82I51+TrRdI19RvQrcqwP9Xs2OQMfOKRTJRigSSwx8sQH3
2WyPaBPt6ulF9a/7w5d2+WWHw1fgx1fUls2RVjWCN4yOhXrQKfm4ViePmbgPv3ql
0dVlIPMDAgMBAAECggEAAUpLw4syBjhGC8EacoU+GgzJB7SwsQsSRt5humcvQrSZ
869PCxFoHDzEGJZ5IgLW7b44ZUbkq5ETt0jGCF8+HkBGIEH8WVaKbx7PnSACVDzY
DNbpkn3JwTh3G8gcmhuyO2zyIrgZHF4KPX7DE6kZVC3jptIw6Mq2XaClQsOL2LQ2
c/Sh4IEZ7n+OPJTUO4QT38JDPYfMa4V7x/Ctl6RLWa1Rr8eDiXB2dwcQS+yusz1R
We9FPSEQnM/yJmjmm0niSh8W9anhDbbO7ZJkMfkAFTKLfyHpLSoRILidieN9wIZS
aVW/aHVnFU3BeZp8HbnttmE1vT64nqDq5Q3Wuh7n0QKBgQDRjzNbXr6Q4YbJkpcB
IWyGWRfPAWszQ0nLJLZZY4YLIHLmkYwDxsMy98ouk1ZgPmqzdXGNEZml7kPCru+J
yyVA8J+V7vsUJ6CDobEAFo4s9u893ZkC63OqdeZXvfPIjwt7xor1q45Q0EfJEEaY
c+VKBtQSRSLkr2jlEY98WfgWEQKBgQC5GXmeHsVKnTTWsY9FLKNT7EsF0J7C41gE
EOrZ0nrVZJ11hP+PCT/82Z5F9+aVpy/gHfc1VWPnyXp6DfUoAexlantpmh9YrMwa
KGSF5G/bnAs6gLereYGDfYJ1W9p6vs1ywQh4QizsZh173ha+/e05LDRf9UJp3A1n
vnuwMkuT0wKBgDhbc6tZ8pQSIuao5rVmIMKMyUthjUvvB7R7PhMSIeVyJ5R8hhQp
6ysU2qnl8+/UOWvj5NLUbebjChQcac079dveGnz/FUUZVyCvZmOorTnIexS/OLxB
SA0KwhTMv/grCCKUhaCGL7LqILQhDWtIl9xts7DqKPUpe2NHcrg1lfORAoGAAVVF
ovXlxdvL/Z/ZwE3J/1i8UZZnDlBE2gKlLlxttgu7dpU2ofkXFOMcWLSoXHuPxLVy
ilfLGGhDEY34LgmVgkV6DhCmGSVlcurHjo9Onu5IxmsW541AeYn13pKCyE+He0QY
o4UWfb1eiP3YfBeoFblTxz0k9U44an7ctTAnizECgYEAnUXEYycqTP3g2QcUv9cw
a364XRaosFvQsFrikzgTcSHSH/a2Mv+8u+RymA8n3Mbztnq/XxgbPC8kITTeXFoa
0Sd0auvznkz2g8qeeavCcUtzIOaVrA1tSBAXSNaI0DHfW4OSzARbqxIMRXl9RGKt
Z+inoj5hsDkLCMh1ah7DwmQ=
-----END PRIVATE KEY-----
PRIVATEKEY
        );
    }
}
