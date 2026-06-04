<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Domains\Activity\Models\ActivityLog;
use Domains\Audience\Models\Subscriber;
use Domains\Identity\Models\Membership;
use Domains\Publishing\Models\Post;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $user = $request->user();
        abort_unless($user, 401);

        $userId = (string) $user->id;

        // ── Stats ──────────────────────────────────────────────────────────
        $postCounts = Post::query()
            ->where('author_id', $userId)
            ->selectRaw('
                COUNT(*) as total,
                SUM(CASE WHEN status = ? AND published_at IS NOT NULL AND published_at <= NOW() THEN 1 ELSE 0 END) as published,
                SUM(CASE WHEN status = ? AND published_at IS NULL THEN 1 ELSE 0 END) as draft,
                SUM(CASE WHEN status = ? AND published_at > NOW() THEN 1 ELSE 0 END) as scheduled
            ', ['published', 'draft', 'scheduled'])
            ->first();

        // Get workspace IDs the user belongs to
        $workspaceIds = Membership::query()
            ->where('user_id', $userId)
            ->pluck('workspace_id')
            ->toArray();

        $subscriberCount = 0;
        if (! empty($workspaceIds)) {
            $subscriberCount = (int) Subscriber::query()
                ->whereIn('workspace_id', $workspaceIds)
                ->where('status', 'active')
                ->count();
        }

        $activitySummary = ActivityLog::activitySummary(30);

        // ─ Weekly Activity (last 12 weeks) ───────────────────────────────
        $weeklyActivity = $this->getWeeklyActivity($userId, 12);

        // ── Recent Activity Logs ───────────────────────────────────────────
        $recentLogs = ActivityLog::query()
            ->where('user_id', $userId)
            ->orWhereNull('user_id')
            ->latest('created_at')
            ->limit(8)
            ->get()
            ->map(function (ActivityLog $log): array {
                return [
                    'action_label' => $log->action_label,
                    'action' => $log->action,
                    'entity_type' => $log->entity_type,
                    'description' => $this->buildDescription($log),
                    'created_at' => $log->created_at?->toIso8601String(),
                    'created_relative' => $this->relativeTimeInSpanish($log->created_at),
                ];
            })
            ->values()
            ->toArray();

        return Inertia::render('Dashboard', [
            'stats' => [
                'total_posts' => (int) ($postCounts->total ?? 0),
                'published_posts' => (int) ($postCounts->published ?? 0),
                'draft_posts' => (int) ($postCounts->draft ?? 0),
                'scheduled_posts' => (int) ($postCounts->scheduled ?? 0),
                'total_subscribers' => $subscriberCount,
                'activity_last_30_days' => $activitySummary['total_logs'],
            ],
            'weekly_activity' => $weeklyActivity,
            'recent_logs' => $recentLogs,
        ]);
    }

    /**
     * @return array<int, array{week: string, label: string, count: int}>
     */
    private function getWeeklyActivity(string $userId, int $weeks): array
    {
        $now = Carbon::now();
        $startDate = $now->copy()->subWeeks($weeks - 1)->startOfWeek();

        // Get all activity logs for the user in the date range
        $logs = ActivityLog::query()
            ->where('created_at', '>=', $startDate)
            ->where(function ($query) use ($userId): void {
                $query->where('user_id', $userId)
                    ->orWhereNull('user_id');
            })
            ->get(['created_at']);

        // Group by week
        $weeklyCounts = [];
        foreach ($logs as $log) {
            $weekStart = Carbon::parse($log->created_at)->startOfWeek();
            $weekKey = $weekStart->format('Y-m-d');
            $weeklyCounts[$weekKey] = ($weeklyCounts[$weekKey] ?? 0) + 1;
        }

        // Build result array with all weeks (including zeros)
        $result = [];
        for ($i = 0; $i < $weeks; $i++) {
            $weekStart = $startDate->copy()->addWeeks($i);
            $weekKey = $weekStart->format('Y-m-d');
            $label = $weekStart->format('M j');

            $result[] = [
                'week' => $weekKey,
                'label' => $label,
                'count' => $weeklyCounts[$weekKey] ?? 0,
            ];
        }

        return $result;
    }

    private function buildDescription(ActivityLog $log): string
    {
        $metadata = $log->metadata ?? [];

        return match ($log->action) {
            'post.published' => sprintf('Published "%s"', $metadata['title'] ?? 'a post'),
            'post.deleted' => 'Deleted a post',
            'post.updated' => 'Updated a post',
            'workspace.created' => 'Created a workspace',
            'workspace.deleted' => 'Deleted a workspace',
            'workspace.updated' => 'Updated a workspace',
            'user.invited' => sprintf('Invited %s', $metadata['email'] ?? 'a user'),
            'user.removed' => 'Removed a user',
            'permission.changed' => 'Changed permissions',
            'subscriber.added' => 'Added a subscriber',
            'subscriber.removed' => 'Removed a subscriber',
            'subscriber.bounced' => 'Subscriber bounced',
            'subscriber.unsubscribed' => 'Subscriber unsubscribed',
            default => ucfirst(str_replace('.', ' ', $log->action)),
        };
    }

    private function relativeTimeInSpanish(?Carbon $date): string
    {
        if ($date === null) {
            return 'ahora';
        }

        $now = now();
        $diffSeconds = $date->diffInSeconds($now);

        if ($diffSeconds < 45) {
            return 'ahora';
        }

        $minutes = max(1, (int) floor($date->diffInMinutes($now)));
        if ($minutes < 60) {
            return $minutes === 1 ? '1 minuto' : $minutes.' minutos';
        }

        $hours = max(1, (int) floor($date->diffInHours($now)));
        if ($hours < 24) {
            return $hours === 1 ? '1 hora' : $hours.' horas';
        }

        $days = max(1, (int) floor($date->diffInDays($now)));
        if ($days < 7) {
            return $days === 1 ? '1 dia' : $days.' dias';
        }

        $weeks = max(1, (int) floor($days / 7));
        if ($weeks < 5) {
            return $weeks === 1 ? '1 semana' : $weeks.' semanas';
        }

        $months = max(1, (int) floor($date->diffInMonths($now)));
        if ($months < 12) {
            return $months === 1 ? '1 mes' : $months.' meses';
        }

        $years = max(1, (int) floor($date->diffInYears($now)));

        return $years === 1 ? '1 ano' : $years.' anos';
    }
}
