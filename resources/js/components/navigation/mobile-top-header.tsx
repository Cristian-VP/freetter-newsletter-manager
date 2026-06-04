import { BurgerMenuButton } from "@/components/navigation/burger-menu-button";
import { Plus } from "lucide-react";

interface MobileTopHeaderProps {
  isMenuOpen: boolean;
  onMenuToggle: React.MouseEventHandler<HTMLButtonElement>;
  onCreateClick: () => void;
}

export function MobileTopHeader({ isMenuOpen, onMenuToggle, onCreateClick }: MobileTopHeaderProps) {
  return (
    <header className="z-40 px-3 pt-3 md:hidden">
      <div className="flex items-center justify-between">
        <button
          type="button"
          onClick={onCreateClick}
          className="inline-flex h-10 w-10 items-center justify-center rounded-full text-zinc-900 transition-colors hover:bg-zinc-100"
          aria-label="Crear"
        >
          <Plus className="h-6 w-6" />
        </button>
        {/* <span className="satoshi-bold text-lg tracking-tight text-zinc-900">Home</span> */}

        <BurgerMenuButton
          isOpen={isMenuOpen}
          onClick={onMenuToggle}
          className="h-10 w-10 text-zinc-900"
          openClassName="bg-zinc-900 text-white"
          closedClassName="bg-transparent text-zinc-900 hover:bg-zinc-100"
          ariaLabelOpen="Open navigation menu"
          ariaLabelClose="Close navigation menu"
        />
      </div>
    </header>
  );
}
