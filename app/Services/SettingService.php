<?php

namespace App\Services;

class SettingService
{
    public function __construct(private readonly BusinessSettingsService $businessSettings)
    {
    }

    /**
     * @param array $payload
     * @return mixed
     */
    public function update(array $payload): mixed
    {
        return $this->businessSettings->update($payload);
    }
}
