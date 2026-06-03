<x-mail::message>
# {{ $post->title }}

<p style="margin: 0 0 16px; color: #6b7280; font-size: 14px;">
    {{ $authorName }} · {{ $workspaceName }}
</p>

<div style=" width: 900px; font-size: 16px; line-height: 1.75; color: #111827;">
    {!! $newsletterHtml !!}
</div>

<x-mail::button :url="route('home').'#post-'.$post->id">
Ver newsletter
</x-mail::button>

Si prefieres leerla en la web, puedes abrirla desde el enlace anterior.
</x-mail::message>
