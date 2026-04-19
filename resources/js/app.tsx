/// <reference types="vite/client" />
import "../css/app.css";

import { createInertiaApp, type ResolvedComponent } from "@inertiajs/react";
import { resolvePageComponent } from "laravel-vite-plugin/inertia-helpers";
import { createRoot } from "react-dom/client";
import { route as routeFn } from "ziggy-js";

declare global {
  const route: typeof routeFn;
}

const appName = import.meta.env.VITE_APP_NAME || "Freeter";
type InertiaPageModule = { default: ResolvedComponent };

const rootPages = import.meta.glob<InertiaPageModule>("./pages/**/*.tsx");
const modulePages = import.meta.glob<InertiaPageModule>("../../app-modules/*/resources/js/pages/**/*.tsx");

createInertiaApp({
  title: (title) => `${title} - ${appName}`,
  resolve: (name) => {
    if (!name.includes("::")) {
      return resolvePageComponent(`./pages/${name}.tsx`, rootPages).then((module) => module.default);
    }

    const [module, page] = name.split("::");
    const modulePagePath = `../../app-modules/${module}/resources/js/pages/${page}.tsx`;

    if (modulePages[modulePagePath]) {
      return resolvePageComponent(modulePagePath, modulePages).then((module) => module.default);
    }

    console.warn(`[Inertia] Page "${name}" no encontrada. Fallback a "${page}" en root.`);

    return resolvePageComponent(`./pages/${page}.tsx`, rootPages).then((module) => module.default);
  },
  setup({ el, App, props }) {
    const root = createRoot(el);

    root.render(<App {...props} />);
  },
  progress: {
    color: "#4B5563",
  },
});
