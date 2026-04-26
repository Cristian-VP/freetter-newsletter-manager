import { BurgerMenuButton } from "@/components/navigation/burger-menu-button";
import { AccountMenu } from "@/components/navigation/account-menu";
import { NavItemIconButton } from "@/components/navigation/nav-item-icon-button";
import { NavigationIslandContainer } from "@/components/navigation/navigation-island-container";
import { ProfileNavAvatar } from "@/components/navigation/profile-nav-avatar";
import { type HomeNavItemKey } from "@/components/navigation/types";
import { cn } from "@/lib/utils";
import { House, PencilLine, Plus, UsersRound } from "lucide-react";

interface DesktopSideNavProps {
  activeItem: HomeNavItemKey | null;
  isExpanded: boolean;
  isMenuOpen: boolean;
  onMouseEnter: React.MouseEventHandler<HTMLElement>;
  onMouseLeave: React.MouseEventHandler<HTMLElement>;
  onToggleMenu: () => void;
  onCreatePostClick: () => void;
  userName?: string;
  userAvatar?: string;
}

export function DesktopSideNav({
  activeItem,
  isExpanded,
  isMenuOpen,
  onMouseEnter,
  onMouseLeave,
  onToggleMenu,
  onCreatePostClick,
  userName,
  userAvatar,
}: DesktopSideNavProps) {
  return (
    <aside
      className="fixed bottom-4 left-4 top-4 z-30 hidden md:block"
      onMouseEnter={onMouseEnter}
      onMouseLeave={onMouseLeave}
    >
      <NavigationIslandContainer
        className={cn(
          "flex h-full flex-col justify-between p-4 transition-[width] duration-200",
          isExpanded ? "w-62.5" : "w-22",
        )}
      >
        <div className="flex h-full flex-col">
          <div className={cn("px-2 pt-1", isExpanded ? "text-left" : "text-center")}>
            <span className="satoshi-bold-italic text-3xl tracking-tight text-zinc-900">TT</span>
          </div>

          <div className="flex flex-1 items-center">
            <nav aria-label="Desktop main navigation" className="flex w-full flex-col gap-1">
              <NavItemIconButton
                icon={House}
                label="Home"
                isActive={activeItem === "home"}
                href="/home"
                showLabel={isExpanded}
              />

              <NavItemIconButton
                icon={UsersRound}
                label="Subscripciones"
                isActive={activeItem === "subscriptions"}
                href="/subscriptions"
                showLabel={isExpanded}
              />

              <NavItemIconButton
                icon={PencilLine}
                label="Crear"
                isActive={activeItem === "create"}
                href="/newsletters/resume"
                showLabel={isExpanded}
              />

              <NavItemIconButton
                icon={Plus}
                label="Nuevo post"
                isActive={false}
                onClick={onCreatePostClick}
                showLabel={isExpanded}
              />

              <ProfileNavAvatar
                name={userName}
                avatar={userAvatar}
                isActive={activeItem === "profile"}
                href="/profile"
                showLabel={isExpanded}
              />
            </nav>
          </div>
        </div>

        <div className="relative flex flex-col gap-3 pt-4">
          <BurgerMenuButton
            isOpen={isMenuOpen}
            onClick={onToggleMenu}
            className={cn("h-12 rounded-2xl text-zinc-900", isExpanded ? "w-full justify-start px-3" : "w-12 self-center")}
            openClassName="bg-zinc-100"
            closedClassName="bg-transparent hover:bg-zinc-100"
            ariaLabelOpen="Open account menu"
            ariaLabelClose="Close account menu"
          />

          {isMenuOpen ? (
            <AccountMenu
              className="absolute bottom-16 left-full ml-3 w-56"
              onNavigate={onToggleMenu}
            />
          ) : null}
        </div>

      </NavigationIslandContainer>
    </aside>
  );
}
