import { Head, usePage } from "@inertiajs/react";
import { type PageProps } from "@/types";
import AuthenticatedHomeLayout from "@/layouts/authenticated-home-layout";
import WeeklyActivityChart, { type WeeklyActivityData } from "@/components/chart/weekly-activity-chart";

interface DashboardStats {
  total_posts: number;
  published_posts: number;
  draft_posts: number;
  scheduled_posts: number;
  total_subscribers: number;
  activity_last_30_days: number;
}

interface RecentLog {
  action_label: string;
  action: string;
  entity_type: string;
  description: string;
  created_at: string | null;
  created_relative: string;
}

interface DashboardPageProps extends PageProps {
  stats: DashboardStats;
  weekly_activity: WeeklyActivityData[];
  recent_logs: RecentLog[];
}

function getDotColor(action: string): string {
  if (action.includes("post.published") || action.includes("post.created")) {
    return "bg-blue-600";
  }
  if (action.includes("subscriber")) {
    return "bg-green-600";
  }
  if (action.includes("deleted") || action.includes("removed")) {
    return "bg-zinc-400";
  }
  return "bg-zinc-400";
}

export default function Dashboard() {
  const { auth, stats, weekly_activity, recent_logs } = usePage<DashboardPageProps>().props;

  const memberSince = auth.user?.created_at
    ? new Date(auth.user.created_at).toLocaleDateString("es-ES", {
        month: "long",
        year: "numeric",
      })
    : "";

  return (
    <AuthenticatedHomeLayout>
      <Head title="Dashboard" />

      <section className="mx-auto flex w-full max-w-3xl flex-col gap-4">
        {/* Welcome */}
        <div className="rounded-4xl bg-white p-8 shadow-[0_10px_35px_rgba(15,23,42,0.08)]">
          <h1 className="text-3xl satoshi-bold tracking-tight text-zinc-900 sm:text-4xl">
            Hey, {auth.user?.name?.split(" ")[0]}
          </h1>
          {memberSince && (
            <p className="mt-2 text-zinc-500">
              Member since {memberSince}
            </p>
          )}
        </div>

        {/* Stat Cards */}
        <div className="grid grid-cols-1 gap-3 sm:grid-cols-3">
          <StatCard
            label="Posts"
            value={stats.total_posts}
            sub={`${stats.published_posts} published · ${stats.draft_posts} drafts · ${stats.scheduled_posts} scheduled`}
          />
          <StatCard
            label="Subscribers"
            value={stats.total_subscribers}
            sub="Active subscribers"
          />
          <StatCard
            label="Activity"
            value={stats.activity_last_30_days}
            sub="Actions last 30 days"
          />
        </div>

        {/* Weekly Activity Chart */}
        <div className="rounded-4xl bg-white p-8 shadow-[0_10px_35px_rgba(15,23,42,0.08)]">
          <div className="mb-4 flex items-center justify-between sm:mb-6">
            <span className="text-sm satoshi-bold text-zinc-900 sm:text-base">
              Weekly activity
            </span>
            <span className="text-xs text-zinc-400">Last 12 weeks</span>
          </div>
          <WeeklyActivityChart data={weekly_activity} />
        </div>

        {/* Recent Activity */}
        {recent_logs.length > 0 && (
          <div className="rounded-4xl bg-white p-8 shadow-[0_10px_35px_rgba(15,23,42,0.08)]">
            <span className="text-sm satoshi-bold text-zinc-900 sm:text-base">
              Recent activity
            </span>
            <div className="mt-4 flex flex-col gap-0">
              {recent_logs.map((log, i) => (
                <div
                  key={i}
                  className="flex items-start gap-3 border-b border-zinc-100 py-3 last:border-b-0 sm:gap-4 sm:py-4"
                >
                  <div
                    className={`mt-1.5 h-2 w-2 shrink-0 rounded-full ${getDotColor(log.action)}`}
                  />
                  <div className="min-w-0 flex-1">
                    <p className="text-sm leading-snug text-zinc-600 sm:text-[0.925rem]">
                      {log.description}
                    </p>
                    <p className="mt-0.5 text-xs text-zinc-400">
                      {log.created_relative}
                    </p>
                  </div>
                </div>
              ))}
            </div>
          </div>
        )}

        {/* Empty state */}
        {recent_logs.length === 0 && stats.total_posts === 0 && (
          <div className="rounded-4xl bg-white p-8 text-center shadow-[0_10px_35px_rgba(15,23,42,0.08)]">
            <p className="text-sm text-zinc-500">
              No activity yet. Start creating content to see your dashboard come alive.
            </p>
          </div>
        )}
      </section>
    </AuthenticatedHomeLayout>
  );
}

function StatCard({
  label,
  value,
  sub,
}: {
  label: string;
  value: number;
  sub: string;
}) {
  return (
    <div className="rounded-4xl bg-white p-5 shadow-[0_10px_35px_rgba(15,23,42,0.08)] transition-shadow hover:shadow-[0_10px_35px_rgba(15,23,42,0.12)] sm:p-6">
      <p className="text-xs font-medium uppercase tracking-wider text-zinc-400">
        {label}
      </p>
      <p className="mt-1 text-3xl font-extrabold tracking-tight text-zinc-900 sm:text-4xl">
        {value.toLocaleString()}
      </p>
      {sub && (
        <p className="mt-1 text-xs text-zinc-400 sm:text-sm">{sub}</p>
      )}
    </div>
  );
}
