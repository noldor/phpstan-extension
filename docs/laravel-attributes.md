# Laravel 13 model attribute catalog

Verified against Laravel **13.38.0**. This is the complete set of **25** attributes
under `Illuminate\Database\Eloquent\Attributes`, plus the adjacent factory
attribute `UseModel`. Verify support in the installed framework before adopting
an attribute on an older release. Source:
[Laravel Eloquent attributes](https://github.com/laravel/framework/tree/v13.38.0/src/Illuminate/Database/Eloquent/Attributes).

Use an attribute when configuring its capability. Most models do not need every
attribute. The [Eloquent checks](rules/eloquent-models.md) require `DateFormat`
explicitly and recommend native equivalents for applicable static properties;
they do not invent mass-assignment, authorization or serialization policy.

| Attribute | Target | Arguments | Recommendation |
| --- | --- | --- | --- |
| `Appends` | Class | Variadic strings/arrays of columns | Use for deliberately appended computed serialization fields. |
| `Boot` | Method | None | Use for supported model/trait boot hooks when needed. Keep business operations outside lifecycle glue. |
| `CollectedBy` | Class | Collection class | Use for a custom Eloquent collection. Preserve collection contracts/generics. |
| `Connection` | Class | String or `UnitEnum` connection name | Use for a static nondefault connection. Keep tenant/runtime resolution dynamic. |
| `DateFormat` | Class | Format string | Required on every selected concrete model: `Y-m-d\TH:i:s.uP`. It controls storage, not JSON. |
| `Fillable` | Class | Variadic strings/arrays of columns | Prefer an explicit mass-assignment allowlist when mass assignment is used. |
| `Guarded` | Class | Variadic strings/arrays of columns | Use when the model intentionally uses a denylist policy. Preserve existing protection. |
| `Hidden` | Class | Variadic strings/arrays of columns | Use for static serialization exclusions, especially sensitive values. |
| `Initialize` | Method | None | Use for supported model/trait instance initialization hooks. Preserve per-instance behavior. |
| `ObservedBy` | Class, repeatable | Observer class string or array | Prefer for static observer registration. Observer classes remain final. |
| `Refreshes` | Class | Variadic strings/arrays of columns | Use when DB-generated values must be refreshed after writes. |
| `RouteKey` | Class | Key string | Use when route binding intentionally uses a key other than the primary key. |
| `Scope` | Method | None | Use for local query scopes supported by the installed framework. |
| `ScopedBy` | Class, repeatable | Global scope class string or array | Use for static global scope registration; dynamic scope setup remains supported. |
| `Table` | Class | Named `name`, `key`, `keyType`, `incrementing`, `timestamps`, `dateFormat` | Use for static table/key settings. Prefer dedicated `DateFormat`, `WithoutIncrementing`, `WithoutTimestamps` for those specific choices. |
| `Touches` | Class | Variadic strings/arrays of relations | Use when saving this model intentionally touches related models. |
| `Unguarded` | Class | None | Use only for a deliberately unrestricted, trusted mass-assignment contract. Never add it merely to replace a protected model's settings. |
| `UseEloquentBuilder` | Class | Builder class | Use for a custom Eloquent builder; preserve its generic contract. |
| `UseFactory` | Class | Factory class | Use with `HasFactory` for an explicit static factory. Keep dynamic factory construction in code. |
| `UsePolicy` | Class | Policy class | Use for an explicit static policy association when the project's authorization contract requires it. |
| `UseResource` | Class | Resource class | Use for a chosen default JSON resource. |
| `UseResourceCollection` | Class | Resource collection class | Use for a chosen default resource collection. |
| `Visible` | Class | Variadic strings/arrays of columns | Use for a deliberate serialization allowlist. Consider interactions with `Hidden` and appended fields. |
| `WithoutIncrementing` | Class | None | Use when the model identifier is not auto-incrementing, for example externally assigned UUIDs. |
| `WithoutTimestamps` | Class | None | Use when the table/model intentionally has no Eloquent timestamps. |

`Illuminate\Database\Eloquent\Factories\Attributes\UseModel` targets a **factory
class** and accepts the model class. Use it when factory/model association cannot
be reliably inferred. It is separate from `UseFactory`, which targets the model.

## Static properties covered by the configuration check

| Property | Native equivalent |
| --- | --- |
| `$table`, `$primaryKey`, `$keyType` | `Table(name: ..., key: ..., keyType: ...)` |
| `$incrementing` | `WithoutIncrementing` for false; `Table(incrementing: true)` for an explicit true override |
| `$timestamps` | `WithoutTimestamps` for false; `Table(timestamps: true)` for an explicit true override |
| `$connection` | `Connection` |
| `$dateFormat` | `DateFormat` |
| `$fillable` | `Fillable` |
| `$guarded` | `Guarded`; intentionally empty guards may use `Unguarded` after checking the mass-assignment contract |
| `$hidden`, `$visible`, `$appends` | `Hidden`, `Visible`, `Appends` |
| `$touches`, `$refreshes` | `Touches`, `Refreshes` |
| `$builder`, `$collectionClass`, `$factory` | `UseEloquentBuilder`, `CollectedBy`, `UseFactory` |

There is no native casts attribute: use `casts()`. Properties such as `$with`,
`$withCount`, `$attributes`, `$dispatchesEvents`, `$observables`, `$perPage` and
timestamp-column constants have no equivalent in this catalog and remain valid.
The check does not blanket-replace `newFactory()`, `newCollection()`,
`newEloquentBuilder()`, boot hooks or runtime configuration with attributes.
