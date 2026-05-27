# Heimdall

A Composer plugin that guards the bridge between Packagist and your `vendor/` directory against supply chain attacks.

Named for the Norse watchman of the Bifrost, Heimdall inspects every package the Composer resolver wants to install and enforces security policies — starting with a
minimum package age, so that freshly-published (and potentially compromised) releases cannot enter your project until they have had time to be vetted by the wider
community.

## Why

Supply chain attacks on PHP packages typically follow the same pattern: an attacker publishes a malicious version of a popular package (or a typosquat of one), and any
project running `composer update` within minutes or hours pulls it in before anyone notices. By the time the package is yanked, the damage is done.

A minimum age policy is the simplest, most effective mitigation: refuse to install any version that is younger than _N_ days. Most malicious releases are caught and
removed within a day or two.

## Requirements

- PHP `^8.4`
- Composer `^2.0`

## Installation

```bash
composer require encoredigitalgroup/heimdall
```

On the very first install Heimdall cannot guard itself — Composer activates plugins only after they have been installed. Every subsequent `composer update` /
`composer require` runs through the policy.

## Configuration

All configuration lives under the `extra.heimdall` key in your project's `composer.json`.

```json
{
    "extra": {
        "heimdall": {
            "minimum_age": 7,
            "trusted": {
                "vendors": [
                    "encoredigitalgroup"
                ],
                "packages": [
                    "acme/widgets"
                ]
            }
        }
    }
}
```

### `minimum_age`

|             |                                                      |
|-------------|------------------------------------------------------|
| **Type**    | `int` (days)                                         |
| **Default** | _unset_ — policy is inactive                         |
| **Example** | `7` requires every package to be at least 7 days old |

When the resolver discovers a candidate version whose release date is more recent than the threshold, Heimdall removes it from the dependency pool and emits a warning:

```
heimdall: rejecting acme/widgets 2.4.1 — released 2026-05-25, below minimum age 7 days
```

The resolver then falls back to the next-best version that satisfies your constraints. If no version is old enough, Composer fails with its normal "no matching package"
error.

#### Notes

- The root package is always exempt — Heimdall will never block your own project from resolving.
- Packages without a release date (e.g. path or VCS repositories whose metadata does not include one) are passed through unfiltered. Heimdall only acts on data it can
  trust.
- Values must be positive integers. Strings that look like integers (`"7"`) are accepted; anything else is rejected with a warning and the policy stays inactive.
- The `encoredigitalgroup/heimdall` package is always exempt from its own policy, so the plugin can always update itself.

### `trusted.vendors`

|             |                                                                |
|-------------|----------------------------------------------------------------|
| **Type**    | `string[]` (vendor names — the part before `/`)                |
| **Default** | `[]`                                                           |
| **Example** | `["encoredigitalgroup"]` trusts every package from that vendor |

Any package whose vendor segment matches an entry in this list bypasses the minimum age check entirely. Useful for first-party packages you publish yourself, where the
release pipeline already enforces equivalent guarantees.

### `trusted.packages`

|             |                                               |
|-------------|-----------------------------------------------|
| **Type**    | `string[]` (fully-qualified `vendor/name`)    |
| **Default** | `[]`                                          |
| **Example** | `["acme/widgets"]` trusts that single package |

Trusts a specific package regardless of vendor. If the package's vendor is already in `trusted.vendors`, the per-package entry is redundant — vendor trust takes
precedence.

Both lists are matched case-insensitively against the canonical Composer package name.

## How it works

Heimdall subscribes to Composer's `PluginEvents::PRE_POOL_CREATE` event, which fires once at the start of dependency resolution with the full set of candidate packages.
The plugin filters that set in place — packages younger than `minimum_age` are removed before the SAT solver ever sees them — so resolution remains deterministic and
there are no second-order effects on Composer's solver.

## Escape hatches

- `composer install --no-plugins` bypasses Heimdall entirely. Use deliberately, e.g. in an emergency rollback.
- Removing or zeroing the `extra.heimdall.minimum_age` key disables the policy.
- Adding a vendor to `extra.heimdall.trusted.vendors` or a package to `extra.heimdall.trusted.packages` exempts it from the age check without disabling the policy
  globally.

## License

See [License](https://docs.encoredigitalgroup.com/license)
