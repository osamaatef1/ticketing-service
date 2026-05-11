<?php

namespace App\Support\Enums;

enum TicketPlatform: string
{
    case Manual = 'manual';
    case WhatsApp = 'whatsapp';
    case Web = 'web';
    case Api = 'api';
}
