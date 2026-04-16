import { cn } from "@/lib/utils";

interface NavigationIslandContainerProps {
  children: React.ReactNode;
  className?: string;
}

export function NavigationIslandContainer({ children, className }: NavigationIslandContainerProps) {
  return (
    <div
      className={cn(
        "rounded-[1.75rem] bg-white shadow-[0_10px_35px_rgba(15,23,42,0.08)]",
        className,
      )}
    >
      {children}
    </div>
  );
}
