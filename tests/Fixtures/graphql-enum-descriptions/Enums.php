<?php

declare(strict_types = 1);

namespace GraphQLEnumDescriptions;

use GraphQL\Type\Definition\Description;
use GraphQL\Type\Definition\Description as SchemaDescription;

#[Description('A described status.')]
enum DocumentedStatus: string
{
    #[SchemaDescription('An active status.')]
    case ACTIVE = 'active';

    #[\GraphQL\Type\Definition\Description('An inactive status.')]
    case INACTIVE = 'inactive';
}

/** This PHPDoc is not a Description attribute. */
enum UndocumentedEnum
{
    #[Description('A documented case.')]
    case ACTIVE;
}

#[SchemaDescription('A partially described status.')]
enum UndocumentedCase
{
    #[Description('A documented case.')]
    case ACTIVE;

    #[\GraphQLEnumDescriptions\Other\Description('An unrelated attribute.')]
    case INACTIVE;
}

enum InternalStatus
{
    case ACTIVE;
}

enum LazyStatus
{
    #[Description('An active status.')]
    case ACTIVE;
}

#[Description('A status registered by a closure.')]
enum LazyReplacement
{
    case ACTIVE;
}

enum DynamicLazyStatus
{
    case ACTIVE;
}

enum OtherRegistryStatus
{
    case ACTIVE;
}

namespace GraphQLEnumDescriptions\Other;

#[\Attribute]
final class Description
{
    public function __construct(string $description) {}
}
