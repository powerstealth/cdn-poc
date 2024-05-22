<?php

namespace Modules\Asset\Domain\Contracts;

use Modules\Asset\Domain\Models\Asset;

interface AssetRepositoryInterface
{
    public function createAssetFromUpload(
        string $status,
        string $title,
        string $description,
        string $fileName,
        ?string $key,
        ?string $uploadId,
        array $presignedUrls,
        int $fileLength,
        bool $clyUpTv,
        bool $clyUpFrontStore,
        string $owner
    ): Asset|\Exception;
    public function getAsset(string $id): Asset;
    public function updateAsset(string $id, ?array $scope, ?array $data, ?string $status, ?array $mediaInfo): Asset;
    public function listAssets(array $filters): array;
    public function deleteAsset(string $id, ?string $status): bool;
}