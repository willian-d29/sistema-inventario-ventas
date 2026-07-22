<?php

namespace App\Http\Controllers;

use App\Http\Requests\Setting\SettingUpdateRequest;
use App\Services\BusinessSettingsService;
use App\Services\DocumentPrintLogService;
use App\Services\SettingService;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Response as HttpResponse;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Inertia\Response;

class SettingController extends Controller
{
    public function __construct(
        private readonly SettingService $service,
        private readonly BusinessSettingsService $businessSettings,
        private readonly DocumentPrintLogService $printLogService,
    )
    {
    }

    public function edit(): Response
    {
        return Inertia::render('Settings/Business', [
            'settings' => $this->businessSettings->all(),
            'documentTypes' => [
                ['value' => 'receipt', 'label' => 'Boleta'],
                ['value' => 'invoice', 'label' => 'Factura'],
            ],
            'thermalWidths' => [
                ['value' => 80, 'label' => '80 mm'],
            ],
            'dateFormats' => [
                ['value' => 'd/m/Y', 'label' => 'dd/mm/yyyy'],
                ['value' => 'Y-m-d', 'label' => 'yyyy-mm-dd'],
                ['value' => 'd-m-Y', 'label' => 'dd-mm-yyyy'],
            ],
            'timeFormats' => [
                ['value' => 'H:i', 'label' => '24 horas'],
                ['value' => 'h:i A', 'label' => '12 horas'],
            ],
        ]);
    }

    /**
     * @param SettingUpdateRequest $request
     * @return RedirectResponse
     */
    public function update(SettingUpdateRequest $request): RedirectResponse
    {
        try {
            $payload = $request->validated();
            if ($request->hasFile('logo_path')) {
                $this->businessSettings->storeLogo($request->file('logo_path'));
                unset($payload['logo_path']);
            }

            $this->service->update(payload: $payload);
            $flash = [
                "message" => 'Configuración actualizada correctamente.'
            ];
        } catch (Exception $e) {
            $flash = [
                "isSuccess" => false,
                "message"   => "No se pudo actualizar la configuración.",
            ];

            Log::error("Settings update failed!", [
                "message" => $e->getMessage(),
                "traces"  => $e->getTrace()
            ]);
        }

        return redirect()
            ->route('settings.edit')
            ->with('flash', $flash);
    }

    public function destroyLogo(): RedirectResponse
    {
        $this->businessSettings->removeLogo();

        return redirect()
            ->route('settings.edit')
            ->with('flash', ['message' => 'Logo eliminado correctamente.']);
    }

    public function printerTest(): HttpResponse
    {
        return response()->view('settings.printer-test', [
            ...$this->businessSettings->documentViewData(),
            'generatedAt' => now(),
        ]);
    }

    public function requestPrinterTestPrint(): JsonResponse
    {
        $log = $this->printLogService->record([
            'user_id' => auth()->id(),
            'document_type' => 'printer_test',
            'output_type' => 'thermal',
            'action_type' => 'print_requested',
            'metadata' => ['source' => 'settings_printer_test'],
        ]);

        return response()->json([
            'message' => 'Solicitud de impresión registrada.',
            'is_reprint' => $log->is_reprint,
            'requested_at' => app(BusinessSettingsService::class)->formatDateTime($log->requested_at),
        ]);
    }
}
