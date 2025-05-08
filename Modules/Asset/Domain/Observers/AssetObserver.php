<?php
namespace Modules\Asset\Domain\Observers;

use Illuminate\Support\Facades\Mail;
use Modules\Asset\Domain\Enums\AssetVerificationEnum;
use Modules\Asset\Domain\Mail\UploadMail;
use Modules\Asset\Domain\Models\Asset;

class AssetObserver
{
    public function updated(Asset $asset): void
    {
        try {
            if ($asset->isDirty('verification')) {
                $newStatus = AssetVerificationEnum::getAllItemsAsArray()[$asset->verification];
                $subject = __('messages.video_verification');
                $body = __('messages.video_status', [
                    'name' => $asset->file_name,
                    'status' => $newStatus,
                ]);
                Mail::to($asset->owner->email)->send(new UploadMail($subject, $body));
            }
        }catch (\Exception $e){
            // Continue
        }

    }
}