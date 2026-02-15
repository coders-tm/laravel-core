<?php

namespace Coderstm\Http\Controllers\Admin;

use Coderstm\Http\Controllers\Controller;
use Coderstm\Http\Resources\ReportExportResource;
use Coderstm\Jobs\GenerateReport;
use Coderstm\Models\ReportExport;
use Coderstm\Services\Reports\ReportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ReportExportsController extends Controller
{
    public function index(Request $request)
    {
        $validated = $request->validate(['type' => 'nullable|string|in:'.implode(',', ReportService::all()), 'status' => 'nullable|string|in:pending,processing,completed,failed', 'category' => 'nullable|string|in:'.implode(',', array_keys(ReportService::grouped()))]);
        $query = ReportExport::forAdmin($request->user()->id)->with('admin')->latest();
        if (! empty($validated['type'])) {
            $query->ofType($validated['type']);
        }
        if (! empty($validated['status'])) {
            $query->withStatus($validated['status']);
        }
        if (! empty($validated['category'])) {
            $categoryTypes = ReportService::forCategory($validated['category']);
            $query->whereIn('type', $categoryTypes);
        }
        $exports = $query->orderBy($request->sortBy ?? 'created_at', $request->direction ?? 'desc')->paginate($request->rowsPerPage ?? 15);

        return ReportExportResource::collection($exports);
    }

    public function store(Request $request): JsonResponse
    {
        $typeValidation = $request->validate(['type' => 'required|string|in:'.implode(',', ReportService::all()), 'category' => 'nullable|string|in:'.implode(',', array_keys(ReportService::grouped()))]);
        $type = $typeValidation['type'];
        try {
            $report = ReportService::resolve($type);
            $filters = $request->input('filters', []);
            $filters = $report->validate($filters);
            $validated = $request->validate(['category' => 'nullable|string|in:'.implode(',', array_keys(ReportService::grouped())), 'fields' => 'nullable|array', 'fields.*' => 'string']);
            $category = $validated['category'] ?? ReportService::getCategory($type);
            $format = 'csv';
            $fields = $validated['fields'] ?? [];
            $extension = 'csv';
            $reportExport = ReportExport::create(['admin_id' => user('id'), 'type' => $type, 'status' => 'pending', 'filters' => array_merge($filters, ['category' => $category, 'fields' => $fields, 'format' => $format]), 'file_name' => sprintf('%s-report-%s.%s', $type, now()->format('Y-m-d-His'), $extension)]);
            GenerateReport::dispatch($reportExport);

            return response()->json(['message' => 'Report generation started. You will be able to download it shortly.', 'report_export' => $reportExport], 202);
        } catch (\Throwable $e) {
            return response()->json(['message' => 'Unable to create report export', 'error' => $e->getMessage()], 400);
        }
    }

    public function show(ReportExport $reportExport): JsonResponse
    {
        $this->authorize('view', $reportExport);

        return response()->json(new ReportExportResource($reportExport->load('admin')));
    }

    public function download(ReportExport $reportExport): JsonResponse
    {
        $this->authorize('view', $reportExport);
        if (! $reportExport->isCompleted()) {
            return response()->json(['message' => 'Report is not ready for download yet.', 'status' => $reportExport->status], 400);
        }
        if (! $reportExport->fileExists()) {
            return response()->json(['message' => 'Report file not found. It may have expired.'], 404);
        }
        $downloadUrl = Storage::temporaryUrl($reportExport->file_path, now()->addMinutes(5));

        return response()->json(['message' => 'Download link generated successfully.', 'url' => $downloadUrl, 'name' => $reportExport->file_name, 'expires_at' => now()->addMinutes(5)->toIso8601String()], 200);
    }

    public function destroy(ReportExport $reportExport): JsonResponse
    {
        $this->authorize('delete', $reportExport);
        $reportExport->delete();

        return response()->json(['message' => 'Report export deleted successfully.']);
    }

    public function destroyMultiple(Request $request): JsonResponse
    {
        $validated = $request->validate(['ids' => 'required|array', 'ids.*' => 'integer|exists:report_exports,id']);
        $count = ReportExport::forAdmin(user('id'))->whereIn('id', $validated['ids'])->get()->each(function ($export) {
            $export->delete();
        })->count();

        return response()->json(['message' => "{$count} report export(s) deleted successfully."]);
    }

    public function data(Request $request): JsonResponse
    {
        $typeValidation = $request->validate(['type' => 'required|string|in:'.implode(',', ReportService::all())]);
        $type = $typeValidation['type'];
        try {
            $report = ReportService::resolve($type);
            $filters = $request->input('filters', []);
            $filters = $report->validate($filters);
            $perPage = $request->input('rowsPerPage', 15);
            $page = $request->input('page', 1);
            $result = $report->paginate($filters, $perPage, $page);

            return response()->json($result);
        } catch (\Throwable $e) {
            report($e);

            return response()->json(['message' => 'Unable to generate report data', 'error' => $e->getMessage()], 400);
        }
    }

    public function available(): JsonResponse
    {
        $grouped = ReportService::grouped();
        $result = [];
        foreach ($grouped as $category => $types) {
            $result[$category] = array_map(function ($type) {
                return ['value' => $type, 'label' => ReportService::getLabel($type)];
            }, $types);
        }

        return response()->json(['reports' => $result, 'categories' => ReportService::getCategoryLabels()]);
    }

    public function metadata(Request $request): JsonResponse
    {
        $validated = $request->validate(['type' => 'required|string|in:'.implode(',', ReportService::all())]);
        $type = $validated['type'];
        try {
            $report = ReportService::resolve($type);
            $headers = $report->headers();
            $description = $report->getDescription();
            $fields = array_map(function ($key, $label) {
                return ['value' => $key, 'label' => $label];
            }, array_keys($headers), array_values($headers));

            return response()->json(['type' => $type, 'label' => ReportService::getLabel($type), 'description' => $description, 'fields' => array_values($fields), 'category' => ReportService::getCategory($type)]);
        } catch (\Throwable $e) {
            return response()->json(['message' => 'Unable to retrieve report metadata', 'error' => $e->getMessage()], 400);
        }
    }

    public function cleanup(): JsonResponse
    {
        $count = ReportExport::expired()->get()->each(function ($export) {
            $export->delete();
        })->count();

        return response()->json(['message' => "{$count} expired report(s) cleaned up successfully."]);
    }

    public function retry(ReportExport $reportExport): JsonResponse
    {
        $this->authorize('view', $reportExport);
        if (! $reportExport->isFailed()) {
            return response()->json(['message' => 'Only failed reports can be retried.'], 400);
        }
        $reportExport->update(['status' => 'pending', 'error_message' => null, 'started_at' => null, 'completed_at' => null]);
        GenerateReport::dispatch($reportExport);

        return response()->json(['message' => 'Report generation restarted.', 'report_export' => $reportExport->fresh()]);
    }
}
