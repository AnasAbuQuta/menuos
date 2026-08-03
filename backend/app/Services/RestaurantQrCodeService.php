<?php

namespace App\Services;

use App\Models\Restaurant;
use Endroid\QrCode\Encoding\Encoding;
use Endroid\QrCode\ErrorCorrectionLevel;
use Endroid\QrCode\QrCode;
use Endroid\QrCode\Writer\PngWriter;

class RestaurantQrCodeService
{
    /** @return array{public_menu_url: string, qr_code: string} */
    public function generate(Restaurant $restaurant): array
    {
        $publicMenuUrl = $this->publicMenuUrl($restaurant);
        $qrCode = new QrCode(
            data: $publicMenuUrl,
            encoding: new Encoding('UTF-8'),
            errorCorrectionLevel: ErrorCorrectionLevel::Medium,
            size: 360,
            margin: 16,
        );

        return [
            'public_menu_url' => $publicMenuUrl,
            'qr_code' => (new PngWriter)->write($qrCode)->getDataUri(),
        ];
    }

    public function publicMenuUrl(Restaurant $restaurant): string
    {
        return rtrim(config('app.public_frontend_url'), '/').'/menu/'.$restaurant->slug;
    }
}
