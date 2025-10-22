# SEALMAN EMS development

## Branches

We are using following branch naming convention:

-   `x` where `x` (lowercase) corresponds to task identifier. They should contain implementation of specific tasks and should be reviewed, tested and merged to `DEV` when ready and confirmed for next release.
-   `DEV` is used to hold current development code.
-   `TEST` is used to hold code that is a release candidate and is ready for testing.
-   `PROD` is used to hold current production release code.

## Versioning lifecycle

We are using [Semantic Versioning 2.0.0](https://semver.org/). In short it is `MAJOR.MINOR.PATCH` approach (i.e. `1.0.0`, `2.5.2`).

We also have a standardized approach while developing. In our development cycle we use an abstract suffix `-rX` which stands just for release. We increment release number (`X`) while developing.

When version is tested and approved we can rename it and ship it as next production version. Depending on introduced changes next production version will have version incremented according `MAJOR.MINOR.PATCH` approach.

## Read more

You can find more information in dedicated files in `development/` directory.
