<?php

namespace App\Http\Controllers;

use App\Services\OperationService;
use Illuminate\Http\Request;

class OperationController extends Controller
{
    protected OperationService $operationService;

    public function __construct(OperationService $operationService)
    {
        $this->operationService = $operationService;
    }

    public function index(Request $request)
    {
        $perPage = $request->get('per_page', 10);
        $sort = $request->get('sort', 'desc');
        $search = $request->get('search', '');
        $page = $request->get('page', 1);

        // Гарантируем, что search всегда строка
        $search = $search ?? '';

        $operations = $this->operationService->getOperations(
            $request->user(),
            $perPage,
            $sort,
            $search,
            $page
        );

        return response()->json($operations);
    }

    public function recent(Request $request)
    {
        $operations = $this->operationService->getRecentOperations($request->user());
        return response()->json($operations);
    }

    public function statistics(Request $request)
    {
        $stats = $this->operationService->getStatistics($request->user());
        return response()->json($stats);
    }

    public function byDateRange(Request $request)
    {
        $request->validate([
            'start_date' => 'required|date',
            'end_date' => 'required|date|after:start_date',
        ]);

        $operations = $this->operationService->getOperationsByDateRange(
            $request->user(),
            $request->start_date,
            $request->end_date
        );

        return response()->json($operations);
    }

    public function monthlySummary(Request $request)
    {
        $year = $request->get('year', date('Y'));
        $summary = $this->operationService->getMonthlySummary($request->user(), $year);
        return response()->json($summary);
    }
} 