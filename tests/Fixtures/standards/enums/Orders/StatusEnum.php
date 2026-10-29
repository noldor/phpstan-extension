<?php

namespace StandardsFixture\Enums;

enum StatusEnum
{
    case PENDING_APPROVAL;
}

enum Status // @expect laravel.enumName
{
    case PendingApproval; // @expect laravel.enumCase
}
