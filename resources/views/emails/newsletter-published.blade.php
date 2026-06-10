<x-mail::message>
<div style=" width: 900px; font-size: 16px; line-height: 1.75; color: #111827;">
    {!! $newsletterHtml !!}
</div>

<x-mail::button :url="route('profile.public', ['handle' => ltrim($post->author->handle ?? '', '@')]).'?newsletter='.$post->id">
Ver newsletter
</x-mail::button>

Si prefieres leerla en la web, puedes abrirla desde el enlace anterior.
</x-mail::message>
