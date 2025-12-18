# Data fixtures

Fixtures are used to load a set of data into database.

All fixtures should extend `App\DataFixtures\AbstractFixtureGroupAsClass` to have a group assigned based on class fully qualified name (class name with namespace prefix). Doctrine (or Symfony) always adds group based on shortname. This is not enough in our application as we can have multiple fixtures with the same shortname in different namespaces.

Using FQN will ensure cleaner fixture loading in tests.

Example:

```php
<?php

declare(strict_types=1);

namespace App\DataFixtures;

use App\Entity\AccessTag;
use App\DataFixtures\AbstractFixtureGroupAsClass;
use Doctrine\Persistence\ObjectManager;

class AccessTagFixture extends AbstractFixtureGroupAsClass
{
    public function load(ObjectManager $manager): void
    {
        $accessTag = new AccessTag();
        $accessTag->setName('Power plant');

        $manager->persist($accessTag);
        $manager->flush();
    }
}
```

Such fixture could be loaded as follows:

```bash
php bin/console doctrine:fixtures:load --group=App\\DataFixtures\\AccessTagFixture
```

## Production fixtures

Loaded when system is initializing (starting up for the first time). Production fixtures should additionally have `prod` group (this group is used by `entrypoint.sh`). Use `App\DataFixtures\AbstractFixtureGroupProd` to achieve that.

Example:

```php
<?php

declare(strict_types=1);

namespace App\DataFixtures;

use App\Entity\AccessTag;
use App\DataFixtures\AbstractFixtureGroupProd;
use Doctrine\Persistence\ObjectManager;

class AccessTagFixture extends AbstractFixtureGroupProd
{
    public function load(ObjectManager $manager): void
    {
        $accessTag = new AccessTag();
        $accessTag->setName('Power plant');

        $manager->persist($accessTag);
        $manager->flush();
    }
}
```

## Fixtures in tests

Read `development/tests/Data fixtures.md`.
