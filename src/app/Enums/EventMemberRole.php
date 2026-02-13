<?php

namespace App\Enums;

enum EventMemberRole: string
{
    case Owner = 'owner';
    case Member = 'member';
}
