<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\BugSeverity;
use App\Enums\BugStatus;
use App\Models\BugReport;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

final class BugReportService
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function report(array $data, ?User $user, string $userAgent): BugReport
    {
        $severity = BugSeverity::from($data['severity']);

        $report = DB::transaction(function () use ($data, $user, $userAgent, $severity): BugReport {
            return BugReport::create([
                'user_id' => $user?->id,
                'reference' => $this->nextReference(),
                'title' => $data['title'],
                'severity' => $severity,
                'scope' => $data['scope'],
                'description' => $data['description'],
                'page' => $data['page'] ?? null,
                'context' => [
                    'user_agent' => $userAgent,
                    'reported_by' => $user?->username,
                    'app_version' => config('app.version', 'dev'),
                ],
                'status' => BugStatus::Open,
            ]);
        });

        Log::channel('anomalies')->log(
            $severity === BugSeverity::Blocking ? 'error' : 'warning',
            'Anomalie signalée : '.$report->title,
            [
                'reference' => $report->reference,
                'severity' => $severity->value,
                'scope' => $report->scope,
                'page' => $report->page,
                'user_id' => $user?->id,
            ],
        );

        return $report;
    }

    private function nextReference(): string
    {
        $year = now()->format('Y');
        $count = BugReport::query()->whereYear('created_at', $year)->count() + 1;

        return sprintf('BUG-%s-%04d', $year, $count);
    }
}
