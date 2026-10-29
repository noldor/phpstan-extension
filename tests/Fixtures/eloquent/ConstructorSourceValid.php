<?php
namespace EloquentFixture;
use Illuminate\Database\Eloquent\Attributes\DateFormat;
use Illuminate\Database\Eloquent\Relations\Relation;
#[DateFormat('Y-m-d\\TH:i:s.uP')] class FirstConstructorSource extends BaseModel { public function __construct() { parent::__construct(); } } #[DateFormat('Y-m-d\\TH:i:s.uP')] class SecondConstructorSource extends BaseModel { public function __construct() { parent::__construct(); } }
trait SourceConstructor { public function __construct() { parent::__construct(); } }
#[DateFormat('Y-m-d\\TH:i:s.uP')]
class TraitConstructorSource extends BaseModel { use SourceConstructor; }
trait SourceAliasConstructor { public function initializeModel() { parent::__construct(); } }
#[DateFormat('Y-m-d\\TH:i:s.uP')]
class AliasedConstructorSource extends BaseModel { use SourceAliasConstructor { initializeModel as __construct; } }
function registerValidConstructorSources(): void { Relation::morphMap(['first_constructor_source'=>FirstConstructorSource::class, 'second_constructor_source'=>SecondConstructorSource::class, 'trait_constructor_source'=>TraitConstructorSource::class, 'aliased_constructor_source'=>AliasedConstructorSource::class]); }
