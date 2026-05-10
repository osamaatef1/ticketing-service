<?php

namespace App\Services;

final readonly class AdminDTO
{
    public function __construct(
        public int $id,
        public ?string $name,
        public ?string $email,
        public ?string $avatar_url = null,
    ) {}

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'avatar_url' => $this->avatar_url,
        ];
    }
}
