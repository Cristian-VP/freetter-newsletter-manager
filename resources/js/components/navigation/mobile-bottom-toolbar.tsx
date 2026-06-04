import { NavItemIconButton } from "@/components/navigation/nav-item-icon-button";
import { NavigationIslandContainer } from "@/components/navigation/navigation-island-container";
import { ProfileNavAvatar } from "@/components/navigation/profile-nav-avatar";
import { type HomeNavItemKey } from "@/components/navigation/types";
import { House, LayoutGrid, PencilLine, UsersRound } from "lucide-react";

interface MobileBottomToolbarProps {
  activeItem: HomeNavItemKey | null;
  userName?: string;
  userAvatar?: string;
}

export function MobileBottomToolbar({
  activeItem,
  userName,
  userAvatar,
}: MobileBottomToolbarProps) {
  const isDashboard = activeItem === "dashboard";

  return (
    <div className="pointer-events-none fixed inset-x-0 bottom-[max(0.75rem,env(safe-area-inset-bottom))] z-95 px-3 md:hidden">
      <NavigationIslandContainer className="pointer-events-auto mx-auto w-full max-w-md px-2 py-1.5 shadow-[0_14px_24px_rgba(0,0,0,0.18)]">
        <nav aria-label="Mobile main navigation" className="flex items-center justify-between gap-1">
          {isDashboard && (
            <NavItemIconButton
              icon={LayoutGrid}
              label="Dashboard"
              isActive={activeItem === "dashboard"}
              href="/dashboard"
              className="px-3"
            />
          )}

          <NavItemIconButton
            icon={House}
            label="Home"
            isActive={activeItem === "home"}
            href="/home"
            className="px-3"
          />

          {!isDashboard && (
            <>
              <NavItemIconButton
                icon={UsersRound}
                label="Subscripciones"
                isActive={activeItem === "subscriptions"}
                href="/subscriptions"
                className="px-3"
              />

              <NavItemIconButton
                icon={PencilLine}
                label="Crear"
                isActive={activeItem === "create"}
                href="/newsletters/resume"
                className="px-3"
              />

              <ProfileNavAvatar
                name={userName}
                avatar={userAvatar}
                isActive={activeItem === "profile"}
                href="/profile"
                className="px-3"
              />
            </>
          )}
        </nav>
      </NavigationIslandContainer>
    </div>
  );
}
