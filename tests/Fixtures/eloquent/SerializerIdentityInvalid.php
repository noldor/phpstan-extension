<?php
namespace EloquentFixture;
use DateTimeInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Attributes\DateFormat;
use Illuminate\Database\Eloquent\Relations\Relation;
#[DateFormat('Y-m-d\\TH:i:s.uP')]
class SameLineSerializer extends Model { protected function serializeDate(DateTimeInterface $date): string { return $date->format('Y-m-d'); } protected function casts(): array { return []; } }
function registerSameLineSerializer(): void { Relation::morphMap(['same_line_serializer'=>SameLineSerializer::class]); }
