import AuthenticatedHomeLayout from "@/layouts/authenticated-home-layout";
import { Head, useForm, Link } from "@inertiajs/react";
import { ArrowLeft, Pencil } from "lucide-react";
import React, { useRef, useState } from "react";
import { optimizeImage } from "@/lib/image-optimizer";

export default function ProfileEdit({ profile }: { profile: any }) {
  const { data, setData, post, processing, errors } = useForm({
    name: profile.name || "",
    handle: profile.handle || "",
    bio: profile.bio || "",
    avatar: null as File | null,
    _method: "patch" // We use POST but fake a PATCH for file uploads
  });

  const [avatarPreview, setAvatarPreview] = useState<string | null>(profile.avatar_url);
  const fileInputRef = useRef<HTMLInputElement>(null);

  const handleAvatarChange = async (e: React.ChangeEvent<HTMLInputElement>) => {
    if (e.target.files && e.target.files[0]) {
      let file = e.target.files[0];

      // Usar el optimizador global (1MB, max 800px para avatar)
      const optimizedFile = await optimizeImage(file, 1, 800);

      if (optimizedFile) {
        setData("avatar", optimizedFile);
        setAvatarPreview(URL.createObjectURL(optimizedFile));
      }
    }
  };

  const submit = (e: React.FormEvent) => {
    e.preventDefault();
    // We send a POST request with _method='patch' because forms with files need POST
    post(route("profile.update"));
  };

  return (
    <AuthenticatedHomeLayout>
      <Head title="Editar perfil" />

      <section className="mx-auto flex w-full max-w-190 flex-col gap-5 md:max-w-205 pb-12">
        <header className="px-4 pt-4 md:px-0 flex items-center gap-4">
          <Link href="/profile" className="flex h-10 w-10 items-center justify-center rounded-full hover:bg-zinc-100 transition-colors">
            <ArrowLeft className="h-5 w-5 text-zinc-900" />
          </Link>
          <h1 className="text-3xl satoshi-bold tracking-tight text-zinc-900 sm:text-4xl">Editar perfil</h1>
        </header>

        <form onSubmit={submit} className="flex flex-col gap-8 px-4 md:px-0">
          {/* Avatar Section */}
          <div className="flex flex-col gap-2">
            <div className="relative h-24 w-24 sm:h-32 sm:w-32 rounded-full cursor-pointer group" onClick={() => fileInputRef.current?.click()}>
              {avatarPreview ? (
                <img src={avatarPreview} alt="Avatar" className="h-full w-full rounded-full object-cover border border-zinc-200" />
              ) : (
                <div className="flex h-full w-full items-center justify-center rounded-full bg-indigo-600 text-white text-4xl font-semibold">
                  {data.name?.charAt(0)?.toUpperCase()}
                </div>
              )}
              
              <div className="absolute inset-0 rounded-full bg-black/20 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center">
                <div className="bg-white p-2 rounded-full shadow-sm">
                  <Pencil className="h-4 w-4 text-zinc-700" />
                </div>
              </div>
              
              {/* Optional: Add a small edit badge on bottom right similar to reference if hover overlay is not preferred, but hover is nice. Or we can add both. */}
              <div className="absolute bottom-0 right-0 rounded-full bg-white p-1.5 shadow-sm border border-zinc-200">
                <Pencil className="h-4 w-4 text-zinc-700" />
              </div>
            </div>
            <input 
              type="file" 
              ref={fileInputRef} 
              className="hidden" 
              accept="image/*"
              onChange={handleAvatarChange}
            />
            {errors.avatar && <p className="text-sm text-red-500">{errors.avatar}</p>}
          </div>

          {/* Form Fields */}
          <div className="flex flex-col gap-4">
            <div className="flex flex-col gap-1">
              <label htmlFor="name" className="text-sm font-semibold text-zinc-900">Name</label>
              <input
                id="name"
                type="text"
                className="w-full rounded-md border border-zinc-300 px-3 py-2 text-zinc-900 focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500"
                value={data.name}
                onChange={(e) => setData("name", e.target.value)}
              />
              {errors.name && <p className="text-sm text-red-500">{errors.name}</p>}
            </div>

            <div className="flex flex-col gap-1">
              <label htmlFor="handle" className="text-sm font-semibold text-zinc-900">Handle</label>
              <input
                id="handle"
                type="text"
                placeholder="@username"
                className="w-full rounded-md border border-zinc-300 px-3 py-2 text-zinc-900 focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500"
                value={data.handle}
                onChange={(e) => setData("handle", e.target.value)}
              />
              {errors.handle && <p className="text-sm text-red-500">{errors.handle}</p>}
            </div>

            <div className="flex flex-col gap-1">
              <label htmlFor="bio" className="text-sm font-semibold text-zinc-900">Bio</label>
              <textarea
                id="bio"
                rows={4}
                className="w-full rounded-md border border-zinc-300 px-3 py-2 text-zinc-900 focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500 resize-y"
                value={data.bio}
                onChange={(e) => setData("bio", e.target.value)}
              />
              {errors.bio && <p className="text-sm text-red-500">{errors.bio}</p>}
            </div>
          </div>

          <div className="flex justify-end pt-4">
            <button
              type="submit"
              disabled={processing}
              className="rounded-md bg-zinc-900 px-6 py-2 text-sm font-semibold text-white shadow-sm hover:bg-zinc-800 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-zinc-900 disabled:opacity-50"
            >
              Done
            </button>
          </div>
        </form>
      </section>
    </AuthenticatedHomeLayout>
  );
}
