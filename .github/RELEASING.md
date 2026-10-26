# Development and release workflow

1. Fetch `origin/master`, switch to `master`, fast-forward it from the remote,
   then create a new task branch before changing files. Implement a coherent task and run
   `composer check`, release-script tests and distribution verification.
2. Complete independent review, push the task branch and open a PR into `master`.
3. Use Conventional Commits for branch commits and the PR title, then use an ordinary merge commit. Required GitHub
   Actions checks are `Quality` and `PR title`; all review threads must be resolved.
   Keep the branch up to date with `master`. Merging remains a human decision.
4. After merge, Actions rechecks the resulting `master` commit. Only a successful
   quality job can start the release job, which creates an immutable tag. GitHub
   sends a signed push webhook to Packagist, and Actions waits for Packagist to
   expose the expected version at the exact checked commit.

## Versions

The first release is always `v0.0.1`. For subsequent releases, the commits
introduced since the previous tag select the SemVer increment. Ordinary merges
retain the branch commits for analysis; the highest applicable increment wins:

| Change | Example | Increment |
| --- | --- | --- |
| New rule or compatible feature | `feat(rules): add a rule` | Minor |
| Incompatible behaviour or configuration | `feat(rules)!: change allowed paths` | Major |
| Breaking-change footer | `BREAKING CHANGE: describe the incompatibility` | Major |
| Fixes and other merges | `fix(rules): handle callables` | Patch |

Versions are derived from `vX.Y.Z` tags. Keep `composer.json` without a `version`
field. Do not create or move release tags manually. The first-version override
applies only while there are no existing release tags. The release infrastructure
is excluded from distribution archives.

## Repository activation

These steps require publication authorization. Preparing local workflow files
does not authorize an initial commit, push, merge, tag or package registration.

For an empty repository, establish an explicitly approved empty initial commit
on `master`, then put the package and its automation in a task branch and PR.
This creates the base needed for branch-based development without placing the
implementation directly on `master`. Never bypass existing protection rules to
seed a repository that already has history.

The first PR requires the user's merge authorization. Packagist cannot register
the default branch until that merge puts `composer.json` on `master`. The release
preflight stops before tagging while the package is unregistered. After the
first merge, register the package, configure and verify its GitHub webhook, then
dispatch the workflow on `master` to create `v0.0.1`. Later merges use the normal
automatic release path.

Configure the merge settings before merging the initial PR, and complete
Packagist activation after its manifest reaches `master`:

- Default branch: `master`.
- Merge method: ordinary merge commits, preserving branch history.
- Active branch ruleset: import [rulesets/master.json](rulesets/master.json).
  It requires PRs, passing `Quality` and `PR title` checks from GitHub Actions,
  an up-to-date branch and resolved discussions; it blocks force
  pushes and deletion and has no bypass actors.
- Register `https://github.com/noldor/phpstan-extension` on Packagist as
  `noldors/phpstan-extension` after the repository contains its manifest.
- Configure the signed GitHub webhook described below before activating releases.
  Actions does not need a Packagist API token or username variable.
- Actions must be enabled. The release job requests `contents: write` for the
  repository's built-in `GITHUB_TOKEN`; a separate GitHub PAT is unnecessary.

Use the repository's Rulesets UI to import the JSON, or create the ruleset through
the authenticated GitHub CLI after checking existing rulesets to avoid duplicates:

```sh
gh api repos/noldor/phpstan-extension/rulesets --input .github/rulesets/master.json
```

The same workflow run creates the tag and verifies its publication. The
repository webhook notifies Packagist independently of Actions workflow
triggers; no second workflow is needed for a `GITHUB_TOKEN`-created tag.
Packagist indexes Git tags; this workflow does not upload a ZIP to Packagist.

## Packagist webhook

In the GitHub repository's **Settings > Webhooks**, configure one active webhook:

- Payload URL: `https://packagist.org/api/github?username=PACKAGIST_USERNAME`.
  Replace the placeholder with the maintaining account's Packagist login.
- Content type: `application/json`.
- Secret: that account's existing **safe API token** from its Packagist profile.
  Keep the token out of the payload URL, repository files and Actions variables.
- Events: **Just the push event**. Tag creation is a push event.
- SSL verification: enabled.

Check that the initial `ping` delivery receives a successful response from
Packagist. For an existing package, its owner package list should then stop
showing **Legacy Auto-Update**. After the first release using this flow, confirm
that the tag's `push` delivery succeeded and that the release verifier passed.

When migrating, stop the old release job before changing hooks, or let any active
release finish first. After this workflow is merged, remove the unused
`PACKAGIST_TOKEN` Actions secret and `PACKAGIST_USERNAME` Actions variable. Do not
revoke the Packagist token used by the webhook. Do not call `/api/update-package`
as a fallback: an ordinary authenticated call resets Packagist's integration
status and restores the misleading legacy warning for GitHub repositories.

See [Packagist's GitHub hook setup](https://packagist.org/about#how-to-update-packages)
and [GitHub push events](https://docs.github.com/en/webhooks/webhook-events-and-payloads#push).

## Verification and recovery

```sh
composer validate --strict
composer check
python3 -m unittest discover -s .github/tests
composer archive --format=zip --dir=build/distribution --file=composer-package
git archive --format=zip --output=build/distribution/git-package.zip HEAD
python3 .github/scripts/verify_archive.py build/distribution/composer-package.zip build/distribution/git-package.zip
```

The Git archive uses committed files. Before the initial commit, validate the
same content with a disposable Git tree outside this checkout; keep the real
repository uncommitted while publication is on hold.

Missing Packagist registration fails before a tag is created. A missing or
failing webhook causes publication verification to fail after tagging. The
verifier polls public metadata up to 120 times, five seconds apart, within the
release job's 15-minute timeout; it never requests an update itself.

For recovery, fix the webhook and use **Settings > Webhooks > Recent Deliveries >
Redeliver** on the original tag's push event, then rerun the release job. If the
delivery is no longer available, use **Update** on the Packagist package page as
a maintainer before retrying. Rerunning Actions alone does not resend the
webhook for an existing tag. A tag already pointing at the checked commit is
reused; it is never bumped again, overwritten or deleted. Retry the latest
`master` run if the branch has advanced. The workflow serializes releases, and
obsolete checked-out commits cannot tag a newer `master` commit.

Treat a Packagist version pointing at another commit as an error to investigate;
keep existing tags intact. Fix the webhook or registry problem and retry rather
than changing an already published version.

See [GitHub tag action](https://github.com/mathieudutour/github-tag-action) and
[GitHub webhook redelivery](https://docs.github.com/en/webhooks/testing-and-troubleshooting-webhooks/redelivering-webhooks).
