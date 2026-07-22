<?php

namespace App\Helpers;

use App\Enums\Core\AmountTypeEnum;
use App\Services\BusinessSettingsService;
use Illuminate\Support\Facades\Storage;

class BaseHelper
{
    public static function perPage(?int $pageNum): int
    {
        $perPage = $pageNum ?? 25;
        if ($perPage > 500) {
            $perPage = 500;
        }

        return $perPage;
    }

    public static function convertKeyValueToLabelValueArray(array $data): array
    {
        $result = [];
        foreach ($data as $key => $value) {
            $result[] = [
                'label' => $value,
                'value' => $key,
            ];
        }

        return $result;
    }

    public static function storageLink(?string $fileName = null, string $folderPath = 'others'): string
    {
        if (! $fileName) {
            return asset('assets/img/default-image.jpg');
        }

        if (str_starts_with($fileName, 'http://') || str_starts_with($fileName, 'https://')) {
            return $fileName;
        }

        $path = "{$folderPath}/{$fileName}";

        if (! Storage::disk('public')->exists($path)) {
            return asset('assets/img/default-image.jpg');
        }

        return Storage::disk('public')->url($path);
    }

    public static function calculatePercentage(float|int $amount, float|int $percentage): float|int
    {
        return self::numberFormat($amount * ($percentage / 100));
    }

    public static function numberFormat(float|int $number): float|int
    {
        return (float) number_format(
            num: $number,
            decimals: app(BusinessSettingsService::class)->getDecimalPoint(),
            thousands_separator: ''
        );
    }

    public static function calculateDefaultDiscount(float|int $amount): array
    {
        $discount = app(BusinessSettingsService::class)->getDiscount();
        $discountType = 'percentage';
        if ($discountType == AmountTypeEnum::PERCENTAGE->value) {
            $totalDiscount = self::calculatePercentage(
                amount: $amount,
                percentage: $discount
            );
        } else {
            $totalDiscount = $discount;
        }

        return [
            'discount' => (float) $discount,
            'discountType' => $discountType,
            'totalDiscount' => (float) $totalDiscount,
        ];
    }

    public static function calculateCustomDiscount(float|int $amount, float|int $discount, string $discountType): array
    {
        if ($discountType == AmountTypeEnum::PERCENTAGE->value) {
            $totalDiscount = self::calculatePercentage(
                amount: $amount,
                percentage: $discount
            );
        } else {
            $totalDiscount = $discount;
        }

        return [
            'discount' => (float) $discount,
            'discountType' => $discountType,
            'totalDiscount' => (float) $totalDiscount,
        ];
    }

    public static function calculateTax(float|int $amount): array
    {
        $tax = app(BusinessSettingsService::class)->getTax();
        $totalTax = self::calculatePercentage(
            amount: $amount,
            percentage: $tax
        );

        return [
            'tax' => (float) $tax,
            'totalTax' => (float) $totalTax,
        ];
    }
}
