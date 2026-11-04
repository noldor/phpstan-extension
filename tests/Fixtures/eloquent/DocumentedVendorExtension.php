<?php
namespace PropertyDocsFixture;
use Illuminate\Database\Eloquent\Attributes\{DateFormat, Table};

/** @property array{enabled: bool} $payload */
#[DateFormat('Y-m-d\\TH:i:s.uP')]
#[Table('extended_documented_models')]
class DocumentedVendorExtension extends \PropertyDocsVendor\DocumentedParent
{
    protected function casts(): array { return ['payload' => 'array']; }
}
