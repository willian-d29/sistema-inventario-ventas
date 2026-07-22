<?php

namespace App\Services;

use App\Enums\Setting\SettingFieldsEnum;
use Carbon\CarbonInterface;
use DateTimeZone;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class BusinessSettingsService
{
    public const LOGO_PATH = 'business';

    public function all(): array
    {
        $settings = [];
        foreach (SettingFieldsEnum::defaults() as $key => $default) {
            $settings[$key] = $this->typed($key, settings()->get($key, $default));
        }

        $settings['logo_url'] = $this->getLogoUrl();
        $settings['date_time_format'] = trim($this->getDateFormat().' '.$this->getTimeFormat());

        return $settings;
    }

    public function public(): array
    {
        $settings = collect($this->all())
            ->only([...SettingFieldsEnum::publicKeys(), 'logo_url', 'date_time_format'])
            ->all();

        unset($settings[SettingFieldsEnum::LOGO_PATH->value]);

        return $settings;
    }

    public function documentViewData(): array
    {
        return [
            'business' => $this->public(),
            'money' => fn (float|int|string|null $amount): string => $this->money($amount),
            'formatDate' => fn (CarbonInterface|string|null $value): string => $this->formatDate($value),
            'formatDateTime' => fn (CarbonInterface|string|null $value): string => $this->formatDateTime($value),
        ];
    }

    public function seedDefaults(): void
    {
        foreach (SettingFieldsEnum::defaults() as $key => $default) {
            if (! settings()->has($key)) {
                settings()->set($key, $this->storeValue($key, $default));
            }
        }
    }

    public function update(array $payload): array
    {
        $processPayload = [];
        foreach (SettingFieldsEnum::defaults() as $key => $default) {
            if (array_key_exists($key, $payload)) {
                $processPayload[$key] = $this->storeValue($key, $payload[$key]);
            }
        }

        if ($processPayload) {
            settings()->set($processPayload);
        }

        return $this->all();
    }

    public function storeLogo(object $file): string
    {
        $oldLogo = $this->getLogoPath();
        $safeName = Str::slug(pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME));
        $fileName = ($safeName ?: 'logo').'-'.now()->format('YmdHis').'-'.Str::random(8).'.'.$file->getClientOriginalExtension();
        $storedPath = $file->storeAs(self::LOGO_PATH, $fileName, 'public');

        settings()->set(SettingFieldsEnum::LOGO_PATH->value, $fileName);

        if ($oldLogo && $oldLogo !== $fileName) {
            Storage::disk('public')->delete(self::LOGO_PATH.'/'.$oldLogo);
        }

        return $storedPath;
    }

    public function removeLogo(): void
    {
        $logo = $this->getLogoPath();

        settings()->set(SettingFieldsEnum::LOGO_PATH->value, null);

        if ($logo) {
            Storage::disk('public')->delete(self::LOGO_PATH.'/'.$logo);
        }
    }

    public function getBusinessName(): string
    {
        return (string) $this->typed(SettingFieldsEnum::BUSINESS_NAME->value, settings()->get(
            SettingFieldsEnum::BUSINESS_NAME->value,
            SettingFieldsEnum::defaults()[SettingFieldsEnum::BUSINESS_NAME->value]
        ));
    }

    public function getLogoPath(): ?string
    {
        $logo = settings()->get(SettingFieldsEnum::LOGO_PATH->value);

        return filled($logo) ? (string) $logo : null;
    }

    public function getLogoUrl(): ?string
    {
        $logo = $this->getLogoPath();
        if (! $logo || ! Storage::disk('public')->exists(self::LOGO_PATH.'/'.$logo)) {
            return null;
        }

        return Storage::disk('public')->url(self::LOGO_PATH.'/'.$logo);
    }

    public function getTimezone(): string
    {
        $timezone = (string) $this->typed(SettingFieldsEnum::TIMEZONE->value, settings()->get(
            SettingFieldsEnum::TIMEZONE->value,
            SettingFieldsEnum::defaults()[SettingFieldsEnum::TIMEZONE->value]
        ));

        return in_array($timezone, DateTimeZone::listIdentifiers(), true)
            ? $timezone
            : SettingFieldsEnum::defaults()[SettingFieldsEnum::TIMEZONE->value];
    }

    public function getDateFormat(): string
    {
        return (string) $this->typed(SettingFieldsEnum::DATE_FORMAT->value, settings()->get(
            SettingFieldsEnum::DATE_FORMAT->value,
            SettingFieldsEnum::defaults()[SettingFieldsEnum::DATE_FORMAT->value]
        ));
    }

    public function getTimeFormat(): string
    {
        return (string) $this->typed(SettingFieldsEnum::TIME_FORMAT->value, settings()->get(
            SettingFieldsEnum::TIME_FORMAT->value,
            SettingFieldsEnum::defaults()[SettingFieldsEnum::TIME_FORMAT->value]
        ));
    }

    public function getCurrencySymbol(): string
    {
        return (string) $this->typed(SettingFieldsEnum::CURRENCY_SYMBOL->value, settings()->get(
            SettingFieldsEnum::CURRENCY_SYMBOL->value,
            SettingFieldsEnum::defaults()[SettingFieldsEnum::CURRENCY_SYMBOL->value]
        ));
    }

    public function getDecimalPoint(): int
    {
        return (int) $this->typed(SettingFieldsEnum::DECIMAL_POINT->value, settings()->get(
            SettingFieldsEnum::DECIMAL_POINT->value,
            SettingFieldsEnum::defaults()[SettingFieldsEnum::DECIMAL_POINT->value]
        ));
    }

    public function getTax(): float
    {
        return (float) $this->typed(SettingFieldsEnum::TAX->value, settings()->get(
            SettingFieldsEnum::TAX->value,
            SettingFieldsEnum::defaults()[SettingFieldsEnum::TAX->value]
        ));
    }

    public function getDiscount(): float
    {
        return (float) $this->typed(SettingFieldsEnum::DISCOUNT->value, settings()->get(
            SettingFieldsEnum::DISCOUNT->value,
            SettingFieldsEnum::defaults()[SettingFieldsEnum::DISCOUNT->value]
        ));
    }

    public function getThermalWidth(): int
    {
        return (int) $this->typed(SettingFieldsEnum::THERMAL_PAPER_WIDTH->value, settings()->get(
            SettingFieldsEnum::THERMAL_PAPER_WIDTH->value,
            SettingFieldsEnum::defaults()[SettingFieldsEnum::THERMAL_PAPER_WIDTH->value]
        ));
    }

    public function shouldOpenPrintDialog(): bool
    {
        return (bool) $this->typed(SettingFieldsEnum::AUTO_OPEN_PRINT_DIALOG->value, settings()->get(
            SettingFieldsEnum::AUTO_OPEN_PRINT_DIALOG->value,
            SettingFieldsEnum::defaults()[SettingFieldsEnum::AUTO_OPEN_PRINT_DIALOG->value]
        ));
    }

    public function formatDate(CarbonInterface|string|null $value): string
    {
        return $this->asBusinessCarbon($value)?->format($this->getDateFormat()) ?? '-';
    }

    public function formatDateTime(CarbonInterface|string|null $value): string
    {
        $format = trim($this->getDateFormat().' '.$this->getTimeFormat());

        return $this->asBusinessCarbon($value)?->format($format) ?? '-';
    }

    public function money(float|int|string|null $amount): string
    {
        return trim($this->getCurrencySymbol().' '.number_format((float) $amount, $this->getDecimalPoint(), '.', ''));
    }

    private function asBusinessCarbon(CarbonInterface|string|null $value): ?CarbonInterface
    {
        if (! $value) {
            return null;
        }

        $date = $value instanceof CarbonInterface ? $value : Carbon::parse($value);

        return $date->copy()->timezone($this->getTimezone());
    }

    private function typed(string $key, mixed $value): mixed
    {
        if ($value === null) {
            return null;
        }

        return match ($key) {
            SettingFieldsEnum::THERMAL_SHOW_LOGO->value,
            SettingFieldsEnum::THERMAL_SHOW_CUSTOMER->value,
            SettingFieldsEnum::THERMAL_SHOW_PAYMENT_REFS->value,
            SettingFieldsEnum::AUTO_OPEN_PRINT_DIALOG->value,
            SettingFieldsEnum::RETURN_TO_POS_AFTER_PRINT->value,
            SettingFieldsEnum::KEEP_SALE_CONFIRMATION->value => filter_var($value, FILTER_VALIDATE_BOOLEAN),
            SettingFieldsEnum::THERMAL_PAPER_WIDTH->value,
            SettingFieldsEnum::PRINT_COPIES->value,
            SettingFieldsEnum::DECIMAL_POINT->value => (int) $value,
            SettingFieldsEnum::DISCOUNT->value,
            SettingFieldsEnum::TAX->value => (float) $value,
            default => filled($value) ? (string) $value : null,
        };
    }

    private function storeValue(string $key, mixed $value): mixed
    {
        $typed = $this->typed($key, $value);

        return is_bool($typed) ? ($typed ? '1' : '0') : $typed;
    }
}
