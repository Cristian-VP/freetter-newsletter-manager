import { cn } from "@/lib/utils";

interface BurgerMenuButtonProps {
  isOpen: boolean;
  onClick: React.MouseEventHandler<HTMLButtonElement>;
  ariaLabelOpen?: string;
  ariaLabelClose?: string;
  className?: string;
  openClassName?: string;
  closedClassName?: string;
  iconClassName?: string;
}

export function BurgerMenuButton({
  isOpen,
  onClick,
  ariaLabelOpen = "Open main menu",
  ariaLabelClose = "Close menu",
  className,
  openClassName,
  closedClassName,
  iconClassName,
}: BurgerMenuButtonProps) {
  return (
    <button
      type="button"
      aria-label={isOpen ? ariaLabelClose : ariaLabelOpen}
      onClick={onClick}
      className={cn(
        "inline-flex items-center justify-center rounded-full transition-colors",
        isOpen ? openClassName : closedClassName,
        className,
      )}
    >
      <span className="sr-only">{isOpen ? ariaLabelClose : ariaLabelOpen}</span>
      {isOpen ? (
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.5" aria-hidden="true" className={cn("h-6 w-6", iconClassName)}>
          <path d="M6 18L18 6M6 6l12 12" strokeLinecap="round" strokeLinejoin="round" />
        </svg>
      ) : (
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.6" aria-hidden="true" className={cn("h-6 w-6", iconClassName)}>
          <path d="M1 9h22" strokeLinecap="round" />
          <path d="M1 15h22" strokeLinecap="round" />
        </svg>
      )}
    </button>
  );
}
