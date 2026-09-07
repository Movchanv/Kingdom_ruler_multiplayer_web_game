<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Support\StoreBugReportRequest;
use App\Models\BugReport;
use App\Models\User;
use App\Services\BugReportService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class SupportController extends Controller
{
    use ApiResponse;

    public function __construct(private readonly BugReportService $reports) {}

    public function store(StoreBugReportRequest $request): JsonResponse
    {
        /** @var User|null $user */
        $user = $request->user();

        $report = $this->reports->report(
            $request->validated(),
            $user,
            (string) $request->userAgent(),
        );

        return $this->success(
            ['reference' => $report->reference],
            __('Merci ! Votre signalement a été enregistré sous la référence :reference.', [
                'reference' => $report->reference,
            ]),
            201,
        );
    }

    public function index(Request $request): JsonResponse
    {
        $reports = BugReport::query()
            ->with('user:id,username')
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->string('status')))
            ->latest()
            ->paginate(20)
            ->through(fn (BugReport $report): array => [
                'id' => $report->id,
                'reference' => $report->reference,
                'title' => $report->title,
                'severity' => $report->severity->value,
                'severity_label' => $report->severity->label(),
                'scope' => $report->scope,
                'status' => $report->status->value,
                'status_label' => $report->status->label(),
                'page' => $report->page,
                'description' => $report->description,
                'reported_by' => $report->user?->username,
                'created_at' => $report->created_at?->toIso8601String(),
                'resolution' => $report->resolution,
            ]);

        return $this->success($reports);
    }
}
