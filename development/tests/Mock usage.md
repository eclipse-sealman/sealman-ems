# Mock usage

`tests\Abstract\AbstractTestCase` provides a function `mock()` to be overwritten in specific tests to mock services.

## Example usage

Following function replaces `App\Service\VpnProviderFactory` service with our mocked service that overrides `getProvider` method with a callback that always returns `.

```php
<?php

use App\Service\VpnProviderFactory;
use tests\Abstract\AbstractTestCase;
use tests\Mock\VpnProvider\MockVpnProvider;

class Scenario3Test extends AbstractTestCase
{
    public function mock(): void
    {
        $mock = $this->createMock(VpnProviderFactory::class);
        $mock
            ->expects(self::any())
            ->method('getProvider')
            ->willReturnCallback(function () {
                return new MockVpnProvider();
            })
        ;
        static::getContainer()->set(VpnProviderFactory::class, $mock);
    }

}
```

## Disabling and enabling mock

You can enable and disable mocking by using `enableMock()` or `disableMock()`. Mocking is enabled by default. If you need more flexibility (i.e. conditional mocking) you can use your own class variables in `mock()`.

`createMock()` is method from PHPUnit. Read more about usage here:
https://docs.phpunit.de/en/10.5/test-doubles.html#mock-objects

## `mock()` reasoning

We use `KernelBrowser` by default as our `HttpClient` and in `ApiClient` (i.e. `$this->getApiClient()->request(...)` uses `KernelBrowser`). Mentioned `KernelBrowser` by default reboots kernel before making another request. This prevents us from mocking services for multiple requests in one test (they would be restarted). We could disable rebooting (`$this->getHttpClient()->disableReboot()`), but it prevents proper isolation similar to real world use case (in real world kernel is restarted on every request). In order to solve that in a developer friendly way, we mock services before each request by including it in `request()` and `jsonRequest()` methods.

We decided against overwriting `KernelBrowser` for test environment to minimize incompatibilities between production and test environment.
