# Development

Currently we are working on version `0.x.y` which is the initial version. As soon as code is stable we will move to `1.0.0`.

We plan to support every major version i.e. `1.x.y` and `2.x.y`. We will stop support of `0.x.y` when `1.x.y` is released.

-   Each major release will have its' own branch i.e. `release/1.x`, `release/2.x` and `release/3.x` (called release branches).
-   Issues describe features, improvements and bugs. They can also include discussion, reasoning and decisions. This is our source of truth for what we changed in the application (a goal, human-readable). Title of the issue should be concise, labels should be correctly assigned. Both title and labels will be used to generate changelog on release.
-   Pull requests are be created based on those issues. Recommended branch naming is `iX/human-friendly-name`.
-   Focus discussion in pull request about how it is implemented, not what is implemented. Keep issues as source of truth for what has been implemented. When discussion in PR impacts what has been implemented, move this information to an issue.
-   Pull requests for chores (i.e. dependabot updates, package updates) can be created without an issue. Recommended branch naming is `chore/human-friendly-name`.
-   When reasonable try to rebase a pull request before merging. You can decide whether do merge or squash merge based on pull request commit history.
-   Changes to release branches can be pushed directly to prepare release candidate or production release.

## Commit messages

Commit messages should be structured as follows:

-   Working with an issue `#ISSUE_NUMBER: Message` (i.e. `#123: Remove deprecated methods`). Capitalized subject is preferred. This way issues will be neatly linked to commits.
-   Doing a chore `chore: Message` (i.e. `chore: Update PHP to 8.4.12`). Capitalized subject is preferred.
-   Commit messages for updating metadata versions or releases are hard-coded in workflows or scripts.

## Versioning lifecycle

We are using [Semantic Versioning 2.0.0](https://semver.org/). In short it is `MAJOR.MINOR.PATCH` approach (i.e. `1.0.0`, `2.5.2`).

We use release candidates (`-rcX` suffix) to build software version for testing.

When RC version is tested and approved we rename it and ship it as next production version. Depending on introduced changes next production version will have version incremented according `MAJOR.MINOR.PATCH` approach.

## Read more

You can find more information in dedicated files in `development/` directory.
