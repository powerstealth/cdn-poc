<?php

namespace Modules\Playlist\Domain\Contracts;

interface PlaylistRepositoryInterface
{
    public function getPlaylist(string $section, ?string $user): array|\Exception;
    public function setPlaylist(array $items, string $section, string $user): bool|\Exception;
}