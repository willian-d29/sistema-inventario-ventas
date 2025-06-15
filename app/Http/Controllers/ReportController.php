<?php

namespace App\Http\Controllers;

use App\Exports\VentasExport;
use App\Models\Order;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use Inertia\Inertia;

class ReportController extends Controller
{
    /**
     * Vista principal de reportes para el admin.
     */
    public function index()
    {
        $summary = [
            'totalVentas'    => Order::sum('total'),
            'totalGanancias' => Order::sum('profit'), // Asegúrate que existe el campo
            'totalOrdenes'   => Order::count(),
        ];

        return Inertia::render('Reports/Index', [
            'summary' => $summary,
        ]);
    }

    /**
     * Generar PDF con las ventas.
     */
    public function ventasPdf()
    {
        $ventas = Order::with('customer')->latest()->get();

        $pdf = Pdf::loadView('reports.ventas-pdf', compact('ventas'))
            ->setPaper('a4', 'portrait');

        return $pdf->download('reporte_ventas.pdf');
    }

    /**
     * Exportar ventas a Excel.
     */
    public function ventasExcel()
    {
        return Excel::download(new VentasExport, 'reporte_ventas.xlsx');
    }
}
