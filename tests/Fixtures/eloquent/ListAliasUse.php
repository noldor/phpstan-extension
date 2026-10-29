<?php
namespace EloquentFixture;
function listDerivedAliasUse(): void
{
    ListMapped::where(value: 'review_list', column: 'subject_type');
}
