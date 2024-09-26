<?php

namespace Modules\Asset\Domain\Contracts;

use Modules\Asset\Domain\Enums\AssetTrashedStatusEnum;
use Modules\Asset\Domain\Models\Asset;

interface AssetRepositoryInterface
{
    public function createAssetFromUpload(
        string $status,
        string $title,
        string $description,
        array $tags,
        string $fileName,
        ?string $key,
        ?string $uploadId,
        array $presignedUrls,
        int $fileLength,
        string $owner,
        string $verification,
        bool $published
    ): Asset|\Exception;
    public function getAsset(string $id): Asset|\Exception;
    public function isAssetPublished(string $id): bool|\Exception;
    public function updateAsset(string $id, ?array $data, ?string $status, ?bool $published, ?array $mediaInfo, ?string $verification): Asset|\Exception;
    public function listAssets(int $page, int $limit, string $sortField, string $sortOrder, array $filters, ?string $search, AssetTrashedStatusEnum $trashedItems, bool $setPagination): array|\Exception;
    public function deleteAsset(string $id, ?string $status, bool $hard): bool|\Exception;
}