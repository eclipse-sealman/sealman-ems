# ApiClient

`tests\Abstract\ApiClient\ApiClient` class is designed to provide a default and standardized way of interacting with REST API while writing tests. Using `ApiClient` should provide better readability of tests and quicker implementation. Additionally it provides reasonable parameter and asserts defaults.

It essentially uses `jsonRequest` method from `tests\AbstractAbstractTestCase` with predefined parameters and asserts.

## Basic usage

All usage examples are assuming your test class is extending `tests\AbstractAbstractTestCase`.

In most cases you would like to login to execute requests.

```php
$this->loginApi('admin', 'admin');
```

Usage of `ApiClient` looks as follows:

```php
// Recommended to keep this formatting for consistency (uri parameter in a new line)
$this->getApiClient()->request(
    uri: '/web/api/accesstag/create',
    method: 'POST', // method could be omitted as `/web/api/accesstag/create` is unique
);
```

`POST` request will be executed using `/web/api/accesstag/create` endpoint. Lets go through definition of this request to understand what values are used.

## Understanding request and definition

This is a definition of `/web/api/accesstag/create` endpoint.

```php
new ApiClientRequest(
    uri: '/web/api/accesstag/create',
    method: 'POST',
    entityClass: AccessTag::class,
    provide: true,
    parameters: [
        'name' => 'Access tag {counter}',
    ],
    asserts: [
        Assert::RESPONSE_200,
        Assert::RESPONSE_JSON,
        Assert::ENTITY_EXISTS,
        'id' => Assert::RESPONSE_VALUE_IS_ID,
        'name' => [
            Assert::RESPONSE_VALUE_EQUALS_PARAMETER_VALUE,
            Assert::RESPONSE_VALUE_EQUALS_ENTITY_VALUE,
        ],
    ],
);
```

-   `uri` and `method` are self-explanatory and they need to be unique.
-   `uriParameters` defines parameters that should be replaced in `uri`
-   `entityClass` is used to provide data and it also used by `asserts` (i.e. `Assert::ENTITY_EXISTS`)
-   `provide` defines whether data returned by the response should be provided
-   `parameters` defines parameters that should be send as payload
-   `asserts` is a list of asserts that should be validated

More on providing data, using parameters and asserts in next chapters.

In our case request would look as follows:

-   `POST` to `/web/api/accesstag/create`
-   Payload `{"name": "Access tag 1"}`
-   Following asserts:
-   -   Response status code = `200`
-   -   Response is a JSON
-   -   `id` in response a valid ID (integer and >= 0)
-   -   `name` in the response the same as in parameter (payload) and the same as in entity of `entityClass`

## Parameters

Every request that has `provide: true` will automatically provide response data using `entityClass` camel cased short name (i.e. `accessTag`). In short you have one set of data for each `entityClass`.

Let's assume following provided data:

```php
$this->getApiClient()->request(
    uri: '/web/api/accesstag/create',
);
// This is exactly what happens when we set `provide: true` in a request
$this->provide(AccessTag::class, $this->getResponseContentAsArray());
$deviceType = $this->getRepository(DeviceType::class)->findOneBy(['name' => 'TK800']);
$this->getApiClient()->provide(DeviceType::class, [
    'id' => $deviceType->getId(),
    'name' => $deviceType->getName(),
]);
```

This means we should have following structure of `$provides` in `ApiClient`.

```json
{
    "accessTag": {
        "id": 1,
        "name": "Access tag 1",
        "representation": "Access tag 1"
    },
    "deviceType": {
        "id": 1,
        "name": "TK800"
    }
}
```

You can then use those values as parameters using dot path "syntax".

```php
$this->getApiClient()->request(
    uri: '/web/api/accesstag/{id}',
    uriParameter: [
        'id' => '{accessTag.id}'
    ],
    method: 'POST',
    parameters: [
        'name' => 'Access tag for {deviceType.name}',
    ],
);
```

There is a shorthand for using parameters for a request that includes `entityClass` definition. It auto-prefixes paths without does with camel cased short name of `entityClass`. Example:

```php
$this->getApiClient()->request(
    uri: '/web/api/accesstag/{id}',
    uriParameter: [
        'id' => '{id}' // <-- This will be auto-prefixed with `accessTag.` as for this request `entityClass` is set to AccessTag::class
    ],
    method: 'POST',
    parameters: [
        'name' => 'Access tag for {deviceType.name}',
    ],
);
```

This approach with a well-defined requests allows you to write a simple scenario of testing a CRUD with couple of lines:

```php
$this->loginApi('admin', 'admin');

$deviceType = $this->getRepository(DeviceType::class)->findOneBy(['name' => 'TK800']);
$this->getApiClient()->provide(DeviceType::class, ['id' => $deviceType->getId()]);

$this->getApiClient()->request(
    uri: '/web/api/template/create',
);

$this->getApiClient()->request(
    uri: '/web/api/templateversion/create/staging/{template}',
);
$this->getApiClient()->request(
    uri: '/web/api/templateversion/{id}',
    method: 'GET',
);
$this->getApiClient()->request(
    uri: '/web/api/templateversion/list',
);
$this->getApiClient()->request(
    uri: '/web/api/templateversion/{id}',
    method: 'POST',
    parameters: [
        'name' => 'Template version {uuid}',
    ],
);
$this->getApiClient()->request(
    uri: '/web/api/templateversion/{id}',
    method: 'DELETE',
);
```

Take a look at parameter defaults in `tests\Abstract\ApiClient\Requests\TemplateVersionRequests` to get more understanding.

### Fixed parameters

There are also parameters that are always available:

-   `{counter}` - Starts from 1 and increments after each request. Counter is separate for each `entityClass`.
-   `{uuid}` - Generated Uuid v4 (`Uuid::v4()->toRfc4122()`)

## Asserts

Requests can additionally run validation. Asserts are run again (or on) response content. `asserts` definition has three approaches.

```php
new ApiClientRequest(
    asserts: [
        // Asserts not connected to any property
        Assert::RESPONSE_200,
        Assert::RESPONSE_JSON,
        // Asserts connected to property (name in this case)
        'name' => [
            // List of asserts to apply with that property
            Assert::RESPONSE_VALUE_EQUALS_PARAMETER_VALUE,
            Assert::RESPONSE_VALUE_EQUALS_ENTITY_VALUE,
        ],
        // Asserts which simply compare the value using assertSame()
        'type' => TemplateVersionType::STAGING->value, // <-- We expect response content to have a property 'type' with value TemplateVersionType::STAGING->value
        'myProperty' => 'myValue' // <-- We expect response content to have a property 'myProperty' with value 'myValue'
    ],
);
```

You can find list of asserts in enum `tests\Abstract\ApiClient\ApiClientAssert`.

When value is not an `ApiClientAssert` enum values are compared using `assertSame()`.
