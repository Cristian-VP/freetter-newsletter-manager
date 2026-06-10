import { AuthModal } from "@/components/auth-modal";
import { DesktopSideNav } from "@/components/navigation/desktop-side-nav";
import { AccountMenu } from "@/components/navigation/account-menu";
import { MobileBottomToolbar } from "@/components/navigation/mobile-bottom-toolbar";
import { MobileTopHeader } from "@/components/navigation/mobile-top-header";
import { type HomeNavItemKey } from "@/components/navigation/types";
import { cn } from "@/lib/utils";
import { type PageProps } from "@/types";
import { router, usePage } from "@inertiajs/react";
import { useState } from "react";

interface UserHomeLayoutProps {
  children: React.ReactNode;
  onCreateClick?: () => void;
}

export default function UserHomeLayout({ children, onCreateClick }: UserHomeLayoutProps) {
  const page = usePage<PageProps>();
  const { auth } = page.props;
  const [isMobileMenuOpen, setIsMobileMenuOpen] = useState(false);
  const [isDesktopExpanded, setIsDesktopExpanded] = useState(false);
  const [isDesktopMenuOpen, setIsDesktopMenuOpen] = useState(false);
  const [isAuthModalOpen, setIsAuthModalOpen] = useState(false);

  const isGuest = !auth.user;
  const activeItem = resolveActiveNavItem(page.url);

  const handleLoginClick = () => setIsAuthModalOpen(true);

  const handlePostCreateClick = () => {
    if (isGuest) {
      handleLoginClick();
      return;
    }

    if (onCreateClick) {
      onCreateClick();

      return;
    }

    router.visit("/home?open=composer");
  };

  return (
    <div className="min-h-screen bg-[#F7F4ED] text-zinc-900">
      <MobileTopHeader
        isMenuOpen={isMobileMenuOpen}
        onMenuToggle={() => setIsMobileMenuOpen((value) => !value)}
        onCreateClick={handlePostCreateClick}
        isGuest={isGuest}
        onLoginClick={handleLoginClick}
      />

      <DesktopSideNav
        activeItem={activeItem}
        isExpanded={isDesktopExpanded}
        isMenuOpen={isDesktopMenuOpen}
        onMouseEnter={() => setIsDesktopExpanded(true)}
        onMouseLeave={() => setIsDesktopExpanded(false)}
        onToggleMenu={() => setIsDesktopMenuOpen((value) => !value)}
        onCreatePostClick={handlePostCreateClick}
        userName={auth.user?.name}
        userAvatar={auth.user?.avatar}
        isGuest={isGuest}
        onLoginClick={handleLoginClick}
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
        userName={auth.user?.name}
        userAvatar={auth.user?.avatar}
        isGuest={isGuest}
        onLoginClick={handleLoginClick}
      />

      <AuthModal isOpen={isAuthModalOpen} onClose={() => setIsAuthModalOpen(false)} />
    </div>
  );
}

function resolveActiveNavItem(url: string): HomeNavItemKey | null {
  const pathname = normalizePathname(url);

  if (pathname === "/dashboard" || pathname.startsWith("/dashboard/")) {
    return "dashboard";
  }

  if (pathname === "/home" || pathname.startsWith("/home/")) {
    return "home";
  }

  if (pathname === "/subscriptions" || pathname.startsWith("/subscriptions/")) {
    return "subscriptions";
  }

  if (pathname === "/newsletters/create" || pathname.startsWith("/newsletters/")) {
    return "create";
  }

  if (pathname === "/profile" || pathname.startsWith("/profile/")) {
    return "profile";
  }

  return null;
}

function normalizePathname(url: string): string {
  const [path] = url.split("?");
  const normalizedPath = path.startsWith("/") ? path : `/${path}`;

  return normalizedPath.length > 1 ? normalizedPath.replace(/\/+$/, "") : normalizedPath;
}
