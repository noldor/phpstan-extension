# Dedicated-job model payloads

`LaravelJobPayloadRule` and `LaravelJobPayloadAssignmentRule` report `laravel.jobModelPayload` in the selected `noldors.laravel.jobs` scope. See [scope configuration](../laravel-application.md).

Known retained instance state must not be an Eloquent model, Eloquent collection, or a known array/Illuminate collection containing models. The declaration check includes promoted, inherited, private/protected and trait properties and resolved PHPDoc item types. Static and virtual computed properties are outside serialized instance payload state.

```php
// Rejected retained live model.
public function __construct(private Order $order) {}

// Accepted record identifier.
public function __construct(private readonly int $orderId) {}
```

A direct model assignment to an otherwise opaque declared job property is also rejected. Properties with setter hooks are excluded from that assignment inference because they may transform the input without retaining it. A declared retained model type is diagnosed once at its declaration rather than again for every assignment.

Scalar operation data and explicit serializable snapshot/integration-result objects remain valid. Model parameters used only during execution, ordinary locals and bare constructor inputs that are not retained are not job payload declarations. Do not apply this dedicated-job policy to events, queued listeners or notifications.

Unknown `mixed` values, arbitrary nested object graphs, dynamic storage and custom serialization prevent complete transitive proof. This check does not establish snapshot completeness or missing-record behavior. Larastan's optional model-serialization check instead permits model payloads with `SerializesModels`; that is a different contract.
