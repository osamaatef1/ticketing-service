<?php

namespace App\Support\Enums;

enum ReplyAuthor: string
{
    case Admin = 'admin';
    case Customer = 'customer';
}
