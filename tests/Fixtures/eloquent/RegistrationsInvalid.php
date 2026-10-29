<?php

namespace EloquentFixture;

use Illuminate\Database\Eloquent\Attributes\{DateFormat, ObservedBy};
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Foundation\Support\Providers\EventServiceProvider;

#[DateFormat('Y-m-d\\TH:i:s.uP')]
#[ObservedBy(AttributeObserver::class)]
class RegisteredModel extends BaseModel {}

#[DateFormat('Y-m-d\\TH:i:s.uP')]
class MissingAlias extends BaseModel {}

class AttributeObserver {}
class StaticObserver {}
class ProviderObserver {}

class ObserverProvider extends EventServiceProvider
{
    protected $observers = [RegisteredModel::class => [ProviderObserver::class]];
}

function registrations(array $dynamic): void
{
    Relation::enforceMorphMap(['registered' => RegisteredModel::class]);
    RegisteredModel::observe(StaticObserver::class);
    Relation::morphMap($dynamic);
    RegisteredModel::where('subject_type', 'registered');
    $attributes = ['subject_type' => 'registered'];
    $model = new RegisteredModel;
    $model->subject_type = 'registered';
}
