<?php

namespace Modules\Playlist\Domain\Contracts;

interface PlaylistRepositoryInterface
{
    public function getPlaylist(string $section): array|\Exception;
    public function setPlaylist(array $items, string $section): bool|\Exception;
}