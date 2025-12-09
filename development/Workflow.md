# Workflow

Notes:

-   SAST (Static code analysis) is not yet implemented
-   Tests are not yet implemented (just HttpClient tests exists)
-   Run dependabot to keep packages up to date is not yet implemented
-   SBOM is not yet implemented

## Release

Release CI/CD is triggered on release branches when changes are detected to `.github/metadata/PREVIOUS_VERSION` or `.github/metadata/VERSION`.

### Release candidate

Checkout to a release branch. Run:

```bash
./.github/scripts/update_metadata_version.sh 1.0.2-rc1
```

This script automatically updates `.github/metadata/PREVIOUS_VERSION` and `.github/metadata/VERSION`. It also add those file to git and prepares a commit. You need to push it by yourself.

### Production release

Checkout to a release branch. Run:

```bash
./.github/scripts/update_metadata_version.sh 1.0.2
```

This script automatically updates `.github/metadata/PREVIOUS_VERSION` and `.github/metadata/VERSION`. It also add those file to git and prepares a commit. You need to push it by yourself.

## CI/CD

### Pull requests

Defined in `.github/workflows/pr.yaml`.

Following jobs are run on pull requests:

-   SAST (Static code analysis)
-   Build changed images (they are not pushed to docker repository)
-   Run smoke tests

### Release branches

Defined in `.github/workflows/release.yaml`.

Changes to release branches can trigger 4 types of release: `none`, `rc`, `production` or `initial`. Read about release types in `.github/scripts/detect_release_metadata.sh`.

On release = `none`:

-   SAST (Static code analysis)
-   Build changed images (they are not pushed to docker repository)
-   Run smoke tests

On release = `rc`:

-   SAST (Static code analysis)
-   For each changed image: update version, build and push to docker repository
-   Update deployment versions
-   Push changes to git
-   Run full tests

On release = `production`:

-   SAST (Static code analysis)
-   For each changed image: update version, build and push to docker repository
-   For each unchanged image: add tag to existing image on docker repository and update version
-   Update deployment versions
-   Push changes to git
-   Run full tests
-   Generate SBOM

On release = `initial`:

-   SAST (Static code analysis)
-   All images: Update version, build and push to docker repository
-   Update deployment versions
-   Push changes to git
-   Run full tests
-   Generate SBOM

Periodically (i.e. weekly):

-   Run dependabot to keep packages up to date

### Detecting changed images

Changed to images are detected using `dorny/paths-filter@v3`. It compares changed files based on image docker ignore file (i.e. `frontend.Dockerfile.dockerignore`). Filters are defined in `.github/path-filters.yaml` and include image dependency (i.e. when `frontend` image changes the filters also marks `app` as changed).

### Example image version build flow

For readability only relevant images are listed.

Initial release `1.0.0`:

-   `core`: 1.0.0
-   `frontend`: 1.0.0
-   `backend`: 1.0.0
-   `app`: 1.0.0

Release candidate `1.0.1-rc1` (only frontend image changed):

-   `core`: 1.0.0
-   `frontend`: 1.0.1-rc1
-   `app`: 1.0.1-rc1

Release candidate `1.0.1-rc2` (only app image changed):

-   `core`: 1.0.0
-   `frontend`: 1.0.1-rc1
-   `app`: 1.0.1-rc2

Production release `1.0.1`:

-   `core`: 1.0.1 (tagged from 1.0.0)
-   `frontend`: 1.0.1 (tagged from 1.0.1-rc1)
-   `app`: 1.0.1

Release candidate `1.0.2-rc1` (only backend image changed):

-   `core`: 1.0.1
-   `backend`: 1.0.2-rc1
-   `frontend`: 1.0.1
-   `app`: 1.0.2-rc1

Production release `1.0.2`:

-   `core`: 1.0.2 (tagged from 1.0.1)
-   `backend`: 1.0.2 (tagged from 1.0.2-rc1)
-   `frontend`: 1.0.2 (tagged from 1.0.1)
-   `app`: 1.0.2

### SAST (Static code analysis)

Tools to consider for SAST:

-   Psalm (Symfony uses it)
-   PHPStan (Sylius uses it)
-   PHPSpec (Sylius uses it)
-   PHPArkitect (Sylius uses it)
-   Run symfony security:check
-   PHP lint (`php -l`) in case none of above tools covers it
