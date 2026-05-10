<?php

namespace App\Support;

final readonly class ActingAdmin
{
    public function __construct(
        public int $id,
        public ?string $name = null,
        public ?string $email = null,
    ) {}
}
