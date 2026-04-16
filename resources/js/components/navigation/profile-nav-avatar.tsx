import { cn } from "@/lib/utils";
import { Avatar, AvatarFallback, AvatarImage } from "@/components/ui/avatar";
import { useInitials } from "@/hooks/use-initials";

interface ProfileNavAvatarProps {
  isActive?: boolean;
  name?: string;
  avatar?: string;
  onClick?: () => void;
  showLabel?: boolean;
  className?: string;
}

export function ProfileNavAvatar({
  isActive = false,
  name,
  avatar,
  onClick,
  showLabel = false,
  className,
}: ProfileNavAvatarProps) {
  const getInitials = useInitials();

  return (
    <button
      type="button"
      onClick={onClick}
      className={cn(
        "inline-flex items-center gap-4 rounded-2xl px-3 py-2.5 transition-colors hover:bg-zinc-100",
        isActive ? "bg-zinc-100" : "bg-transparent",
        className,
      )}
      aria-label="Profile"
    >
      <Avatar className={cn("h-7 w-7 border border-zinc-300", isActive ? "ring-2 ring-zinc-900 ring-offset-1" : "ring-0")}>
        <AvatarImage src={avatar} alt={name} />
        <AvatarFallback className="bg-zinc-200 text-[11px] text-zinc-700">{name ? getInitials(name) : ""}</AvatarFallback>
      </Avatar>
      {showLabel ? <span className="text-xl satoshi-medium text-zinc-900">Perfil</span> : null}
    </button>
  );
}
