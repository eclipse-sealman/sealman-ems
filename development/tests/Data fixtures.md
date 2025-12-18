# Data fixtures

Read about fixtures basics in `development/Data fixtures.md`.

Put test fixtures in `tests/fixtures` folder. Divide them into folders as you see fit.

Example usage of fixtures in a test.

```php
<?php

declare(strict_types=1);

namespace Tests\Suites\WebApi\Endpoint;

use App\DataFixtures as ProdFixtures;
use Tests\DataFixtures as TestFixtures;
use Tests\Utilities\Abstract\AbstractTestCase;

class DeviceAuthenticationTest extends AbstractTestCase
{
    public static function getFixtureGroups(): array
    {
        return [
            ProdFixtures\ConfigurationFixtures::class,
            TestFixtures\Configuration\DisablePasswordRequirementsFixtures::class,
        ];
    }
}
```
