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
        bool $clyUpFrontStore
    ): Asset|\Exception;

    public function getAsset(string $id): Asset;
}