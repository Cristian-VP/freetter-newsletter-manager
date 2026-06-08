import { cn } from "@/lib/utils";
import { Link } from "@inertiajs/react";
import { type LucideIcon } from "lucide-react";

interface NavItemIconButtonProps {
  icon: LucideIcon;
  label: string;
  isActive?: boolean;
  href?: string;
  onClick?: () => void;
  showLabel?: boolean;
  className?: string;
}

export function NavItemIconButton({
  icon: Icon,
  label,
  isActive = false,
  href,
  onClick,
  showLabel = false,
  className,
}: NavItemIconButtonProps) {
  const content = (
    <>
      <Icon
        className={cn(
          "h-6 w-6 shrink-0 transition-colors",
          isActive ? "fill-zinc-900 stroke-zinc-900" : "fill-transparent stroke-zinc-700",
        )}
      />
      {showLabel ? <span className="text-xl satoshi-medium text-zinc-900">{label}</span> : null}
    </>
  );

  if (href) {
    return (
      <Link
        href={href}
        onClick={onClick}
        className={cn(
          "inline-flex items-center gap-4 rounded-2xl px-3 py-2.5 transition-colors hover:bg-zinc-100",
          isActive ? "bg-zinc-100" : "bg-transparent",
          className,
        )}
      >
        {content}
      </Link>
    );
  }

  return (
    <button
      type="button"
      onClick={onClick}
      className={cn(
        "inline-flex items-center gap-4 rounded-2xl px-3 py-2.5 transition-colors hover:bg-zinc-100",
        isActive ? "bg-zinc-100" : "bg-transparent",
        className,
      )}
      aria-label={label}
    >
      {content}
    </button>
  );
}
