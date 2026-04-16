import { NavigationIslandContainer } from "@/components/navigation/navigation-island-container";
import { Link } from "@inertiajs/react";
import { LayoutGrid, LogOut, Settings } from "lucide-react";

interface AccountMenuProps {
  className?: string;
  onNavigate?: () => void;
}

export function AccountMenu({ className, onNavigate }: AccountMenuProps) {
  return (
    <NavigationIslandContainer className={className}>
      <div className="flex min-h-72 flex-col justify-between gap-5 p-3">
        <div className="flex flex-col gap-2">
          <Link
            href="/dashboard"
            prefetch
            onClick={onNavigate}
            className="inline-flex items-center gap-3 rounded-2xl px-3 py-2.5 text-base satoshi-medium text-zinc-900 transition-colors hover:bg-zinc-100 md:text-sm"
          >
            <LayoutGrid className="h-5 w-5 shrink-0" />
            <span>Dashboard</span>
          </Link>
        </div>

        <div className="flex flex-col gap-2">
          <Link
            href="/settings"
            prefetch
            onClick={onNavigate}
            className="inline-flex items-center gap-3 rounded-2xl px-3 py-2.5 text-base satoshi-medium text-zinc-900 transition-colors hover:bg-zinc-100 md:text-sm"
          >
            <Settings className="h-5 w-5 shrink-0" />
            <span>Configuracion</span>
          </Link>

          <Link
            method="post"
            href={route("logout")}
            as="button"
            onClick={onNavigate}
            className="inline-flex w-full items-center gap-3 rounded-2xl px-3 py-2.5 text-base satoshi-medium text-zinc-900 transition-colors hover:bg-zinc-100 md:text-sm"
          >
            <LogOut className="h-5 w-5 shrink-0" />
            <span>Sign out</span>
          </Link>
        </div>
      </div>
    </NavigationIslandContainer>
  );
}
