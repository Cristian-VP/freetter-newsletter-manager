import { NavItemIconButton } from "@/components/navigation/nav-item-icon-button";
import { NavigationIslandContainer } from "@/components/navigation/navigation-island-container";
import { ProfileNavAvatar } from "@/components/navigation/profile-nav-avatar";
import { type HomeNavItemKey } from "@/components/navigation/types";
import { House, PencilLine, UsersRound } from "lucide-react";

interface MobileBottomToolbarProps {
  activeItem: HomeNavItemKey;
  onSelect: (item: HomeNavItemKey) => void;
  userName?: string;
  userAvatar?: string;
}

export function MobileBottomToolbar({
  activeItem,
  onSelect,
  userName,
  userAvatar,
}: MobileBottomToolbarProps) {
  return (
    <div className="fixed inset-x-0 bottom-3 z-40 px-3 md:hidden">
      <NavigationIslandContainer className="mx-auto w-full max-w-md px-2 py-1.5">
        <nav aria-label="Mobile main navigation" className="flex items-center justify-between gap-1">
          <NavItemIconButton
            icon={House}
            label="Home"
            isActive={activeItem === "home"}
            href="/home"
            onClick={() => onSelect("home")}
            className="px-3"
          />

          <NavItemIconButton
            icon={UsersRound}
            label="Subscripciones"
            isActive={activeItem === "subscriptions"}
            onClick={() => onSelect("subscriptions")}
            className="px-3"
          />

          <NavItemIconButton
            icon={PencilLine}
            label="Crear"
            isActive={activeItem === "create"}
            onClick={() => onSelect("create")}
            className="px-3"
          />

          <ProfileNavAvatar
            name={userName}
            avatar={userAvatar}
            isActive={activeItem === "profile"}
            onClick={() => onSelect("profile")}
            className="px-3"
          />
        </nav>
      </NavigationIslandContainer>
    </div>
  );
}
