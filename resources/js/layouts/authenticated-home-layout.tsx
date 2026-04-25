import { DesktopSideNav } from "@/components/navigation/desktop-side-nav";
import { AccountMenu } from "@/components/navigation/account-menu";
import { MobileBottomToolbar } from "@/components/navigation/mobile-bottom-toolbar";
import { MobileTopHeader } from "@/components/navigation/mobile-top-header";
import { type HomeNavItemKey } from "@/components/navigation/types";
import { cn } from "@/lib/utils";
import { type PageProps } from "@/types";
import { usePage } from "@inertiajs/react";
import { useState } from "react";

interface AuthenticatedHomeLayoutProps {
  children: React.ReactNode;
  onCreateClick?: () => void;
}

export default function AuthenticatedHomeLayout({ children, onCreateClick }: AuthenticatedHomeLayoutProps) {
  const { auth } = usePage<PageProps>().props;
  const [activeItem, setActiveItem] = useState<HomeNavItemKey>("home");
  const [isMobileMenuOpen, setIsMobileMenuOpen] = useState(false);
  const [isDesktopExpanded, setIsDesktopExpanded] = useState(false);
  const [isDesktopMenuOpen, setIsDesktopMenuOpen] = useState(false);

  const handlePostCreateClick = () => {
    onCreateClick?.();
  };

  const handleSelect = (item: HomeNavItemKey) => {
    setActiveItem(item);
  };

  return (
    <div className="min-h-screen bg-[#F7F4ED] text-zinc-900">
      <MobileTopHeader
        isMenuOpen={isMobileMenuOpen}
        onMenuToggle={() => setIsMobileMenuOpen((value) => !value)}
        onCreateClick={handlePostCreateClick}
      />

      <DesktopSideNav
        activeItem={activeItem}
        isExpanded={isDesktopExpanded}
        isMenuOpen={isDesktopMenuOpen}
        onMouseEnter={() => setIsDesktopExpanded(true)}
        onMouseLeave={() => setIsDesktopExpanded(false)}
        onToggleMenu={() => setIsDesktopMenuOpen((value) => !value)}
        onCreatePostClick={handlePostCreateClick}
        onSelect={handleSelect}
        userName={auth.user?.name}
        userAvatar={auth.user?.avatar}
      />

      {isMobileMenuOpen ? (
        <div className="px-3 pt-2 md:hidden">
          <AccountMenu className="w-full" onNavigate={() => setIsMobileMenuOpen(false)} />
        </div>
      ) : null}

      <main className={cn("mx-auto min-h-screen w-full px-3 pb-28 pt-4 md:pl-60 md:pr-6 md:pb-8 md:pt-8")}>
        {children}
      </main>

      <MobileBottomToolbar
        activeItem={activeItem}
        onSelect={handleSelect}
        userName={auth.user?.name}
        userAvatar={auth.user?.avatar}
      />
    </div>
  );
}
