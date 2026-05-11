<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AdminClient
{
    public function __construct(
        private readonly string $baseUrl,
        private readonly ?string $token,
        private readonly int $cacheTtl,
    ) {}

    public function find(int $id): ?AdminDTO
    {
        if ($id <= 0) {
            return null;
        }

        $cached = Cache::get($this->cacheKey($id));
        if ($cached !== null) {
            return $cached === false ? null : $this->fromArray($cached);
        }

        try {
            $response = $this->client()->get("/api/admins/{$id}");
        } catch (\Throwable $e) {
            Log::warning('AdminClient.find failed', ['id' => $id, 'error' => $e->getMessage()]);
            return null;
        }

        if ($response->status() === 404) {
            Cache::put($this->cacheKey($id), false, $this->cacheTtl);
            return null;
        }

        if (!$response->successful()) {
            Log::warning('AdminClient.find non-2xx', ['id' => $id, 'status' => $response->status()]);
            return null;
        }

        $dto = $this->fromArray($response->json('data') ?? $response->json());
        Cache::put($this->cacheKey($id), $dto->toArray(), $this->cacheTtl);
        return $dto;
    }

    /**
     * @param  int[]  $ids
     * @return array<int, AdminDTO>
     */
    public function findMany(array $ids): array
    {
        $ids = array_values(array_unique(array_filter(array_map('intval', $ids), fn ($i) => $i > 0)));
        if ($ids === []) {
            return [];
        }

        $result = [];
        $missing = [];
        foreach ($ids as $id) {
            $cached = Cache::get($this->cacheKey($id));
            if ($cached === false) {
                continue;
            }
            if ($cached !== null) {
                $result[$id] = $this->fromArray($cached);
            } else {
                $missing[] = $id;
            }
        }

        if ($missing === []) {
            return $result;
        }

        try {
            $response = $this->client()->get('/api/admins', ['ids' => implode(',', $missing)]);
        } catch (\Throwable $e) {
            Log::warning('AdminClient.findMany failed', ['ids' => $missing, 'error' => $e->getMessage()]);
            return $result;
        }

        if (!$response->successful()) {
            Log::warning('AdminClient.findMany non-2xx', ['ids' => $missing, 'status' => $response->status()]);
            return $result;
        }

        $rows = $response->json('data') ?? $response->json() ?? [];
        $foundIds = [];
        foreach ($rows as $row) {
            $dto = $this->fromArray($row);
            $result[$dto->id] = $dto;
            $foundIds[] = $dto->id;
            Cache::put($this->cacheKey($dto->id), $dto->toArray(), $this->cacheTtl);
        }

        foreach (array_diff($missing, $foundIds) as $id) {
            Cache::put($this->cacheKey($id), false, $this->cacheTtl);
        }

        return $result;
    }

    private function client()
    {
        $http = Http::baseUrl($this->baseUrl)
            ->timeout(5)
            ->acceptJson();

        if ($this->token) {
            $http = $http->withHeaders(['X-Internal-Token' => $this->token]);
        }

        return $http;
    }

    private function cacheKey(int $id): string
    {
        return "admin:{$id}";
    }

    private function fromArray(array $row): AdminDTO
    {
        return new AdminDTO(
            id: (int) ($row['id'] ?? 0),
            name: $row['name'] ?? null,
            email: $row['email'] ?? null,
            avatar_url: $row['avatar_url'] ?? null,
        );
    }
}
