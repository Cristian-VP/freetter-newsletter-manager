<?php

namespace Domains\Delivery\Notifications;

use Domains\Publishing\Models\Post;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use TijsVerkoyen\CssToInlineStyles\CssToInlineStyles;

class NewsletterPublishedNotification extends Notification
{
    use Queueable;

    public function __construct(private readonly Post $post) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $workspaceName = $this->post->workspace?->name ?? 'Freetter';
        $authorName = $this->post->author?->name ?? $workspaceName;
        $fromAddress = (string) config('mail.from.address');
        $fromName = (string) config('mail.from.name', $workspaceName);
        $authorEmail = $this->post->author?->email;

        $mailMessage = new MailMessage;
        $mailMessage->subject($this->post->title);
        $mailMessage->from($fromAddress, $fromName);

        if (is_string($authorEmail) && $authorEmail !== '') {
            $mailMessage->replyTo($authorEmail, $authorName);
        }

        $mailMessage->view('emails.newsletter-published', [
            'post' => $this->post,
            'workspaceName' => $workspaceName,
            'authorName' => $authorName,
            'newsletterHtml' => $this->renderNewsletterHtml(),
            'excerpt' => $this->post->getExcerpt(150),
            'newsletterUrl' => $this->buildNewsletterUrl(),
        ]);

        return $mailMessage;
    }

    private function buildNewsletterUrl(): string
    {
        $handle = ltrim($this->post->author->handle ?? '', '@');

        if ($handle === '') {
            return route('home').'#post-'.$this->post->id;
        }

        return route('profile.public', ['handle' => $handle]).'?newsletter='.$this->post->id;
    }

    public function renderNewsletterHtml(): string
    {
        $content = $this->post->content ?? [];
        $nodes = $this->normalizeNodes($content);

        return trim($this->renderNodes($nodes));
    }

    public function renderEmailHtml(): string
    {
        $newsletterHtml = $this->renderNewsletterHtml();

        $excerpt = $this->post->getExcerpt(200);
        $title = $this->post->title;
        if (str_starts_with($excerpt, $title)) {
            $excerpt = trim(mb_substr($excerpt, mb_strlen($title)));
        }

        $html = view('emails.newsletter-published-html', [
            'post' => $this->post,
            'workspaceName' => $this->post->workspace?->name ?? 'Freetter',
            'authorName' => $this->post->author?->name ?? 'Freetter',
            'newsletterHtml' => $newsletterHtml,
            'excerpt' => $excerpt,
            'newsletterUrl' => $this->buildNewsletterUrl(),
        ])->render();

        $inliner = new CssToInlineStyles;
        $inlinedHtml = $inliner->convert($html);

        // Remove <style> tags that are no longer needed by email clients, but preserve those with @media queries
        $cleanedHtml = preg_replace_callback(
            '|<style[^>]*>(.*?)</style>|is',
            static fn (array $m): string => str_contains($m[1], '@media') ? $m[0] : '',
            $inlinedHtml
        );
        $html = $cleanedHtml !== null ? $cleanedHtml : $inlinedHtml;

        // Strip base64 images (Gmail blocks them)
        $html = preg_replace('/src="data:image\/[^;]+;base64,[^"]+"/', 'src=""', $html) ?? $html;

        // Convert relative storage URLs to absolute
        $appUrl = rtrim((string) config('app.url'), '/');
        $html = preg_replace('/src="(\/storage\/[^"]+)"/', 'src="'.$appUrl.'$1"', $html) ?? $html;

        return $html;
    }

    /**
     * @param  array<string, mixed>|array<int, mixed>  $content
     * @return array<int, array<string, mixed>>
     */
    private function normalizeNodes(array $content): array
    {
        $nodes = $content['content'] ?? $content['blocks'] ?? $content;

        if (! is_array($nodes)) {
            return [];
        }

        return array_values(array_filter($nodes, static fn ($node): bool => is_array($node)));
    }

    /**
     * @param  array<int, array<string, mixed>>  $nodes
     */
    private function renderNodes(array $nodes): string
    {
        $html = '';

        foreach ($nodes as $node) {
            $html .= $this->renderNode($node);
        }

        return $html;
    }

    /**
     * @param  array<string, mixed>  $node
     */
    private function renderNode(array $node): string
    {
        $type = $node['type'] ?? null;

        return match ($type) {
            'doc' => $this->renderNodes($this->normalizeNodes($node)),
            'paragraph' => $this->wrapBlock('p', $this->renderInline($node['content'] ?? [])),
            'heading' => $this->renderHeading($node),
            'blockquote' => $this->wrapBlock('blockquote', $this->renderNodes($this->normalizeNodes($node))),
            'bulletList' => $this->renderList($node, 'ul'),
            'orderedList' => $this->renderList($node, 'ol'),
            'taskList' => $this->renderList($node, 'ul', ['data-type' => 'taskList']),
            'listItem', 'taskItem' => $this->renderListItem($node),
            'image' => $this->renderImage($node),
            'horizontalRule' => '<hr />',
            'codeBlock' => $this->renderCodeBlock($node),
            default => $this->renderInline($node['content'] ?? []),
        };
    }

    /**
     * @param  array<string, mixed>  $node
     */
    private function renderHeading(array $node): string
    {
        $level = (int) ($node['attrs']['level'] ?? 2);
        $level = max(1, min(6, $level));

        return $this->wrapBlock('h'.$level, $this->renderInline($node['content'] ?? []));
    }

    /**
     * @param  array<string, mixed>  $node
     * @param  array<string, string>  $attributes
     */
    private function renderList(array $node, string $tag, array $attributes = []): string
    {
        $items = $node['content'] ?? [];
        $children = '';

        if (is_array($items)) {
            foreach ($items as $item) {
                if (is_array($item)) {
                    $children .= $this->renderListItem($item);
                }
            }
        }

        return $this->wrapBlock($tag, $children, $attributes);
    }

    /**
     * @param  array<string, mixed>  $node
     * @param  array<string, string>  $attributes
     */
    private function wrapBlock(string $tag, string $content, array $attributes = []): string
    {
        $attributeString = $this->renderAttributes($attributes);

        return sprintf('<%1$s%2$s>%3$s</%1$s>', $tag, $attributeString, $content);
    }

    /**
     * @param  array<string, mixed>  $node
     */
    private function renderListItem(array $node): string
    {
        $children = $node['content'] ?? [];
        $content = '';

        if (is_array($children)) {
            foreach ($children as $child) {
                if (is_array($child)) {
                    $content .= $this->renderNode($child);
                }
            }
        }

        if (($node['type'] ?? null) === 'taskItem') {
            $checked = (bool) ($node['attrs']['checked'] ?? false);
            $checkbox = sprintf(
                '<span style="display:inline-flex;align-items:center;gap:0.5rem;margin-right:0.5rem;vertical-align:top;"><input type="checkbox" %s disabled style="margin-top:0.2rem;" /></span>',
                $checked ? 'checked' : ''
            );

            return sprintf('<li>%s%s</li>', $checkbox, $content);
        }

        return sprintf('<li>%s</li>', $content);
    }

    /**
     * @param  array<string, mixed>  $node
     */
    private function renderImage(array $node): string
    {
        $attrs = $node['attrs'] ?? [];
        $src = e((string) ($attrs['src'] ?? ''));
        $alt = e((string) ($attrs['alt'] ?? ''));
        $title = e((string) ($attrs['title'] ?? ''));

        if ($src === '' || str_starts_with($src, 'data:')) {
            return '';
        }

        return sprintf(
            '<figure style="margin:24px 0;text-align:center;"><img src="%s" alt="%s" title="%s" style="max-width:100%%;height:auto;border-radius:12px;display:block;margin:0 auto;" /></figure>',
            $src,
            $alt,
            $title
        );
    }

    /**
     * @param  array<string, mixed>  $node
     */
    private function renderCodeBlock(array $node): string
    {
        $text = $this->renderInline($node['content'] ?? []);

        return sprintf(
            '<pre style="background:#f4f4f5;border:1px solid #e4e4e7;border-radius:12px;padding:16px;overflow:auto;"><code>%s</code></pre>',
            $text
        );
    }

    /**
     * @param  array<int, array<string, mixed>>  $nodes
     */
    private function renderInline(array $nodes): string
    {
        $html = '';

        foreach ($nodes as $node) {
            $html .= $this->renderInlineNode($node);
        }

        return $html;
    }

    /**
     * @param  array<string, mixed>  $node
     */
    private function renderInlineNode(array $node): string
    {
        $type = $node['type'] ?? null;

        if ($type === 'text') {
            $text = e((string) ($node['text'] ?? ''));

            foreach ($node['marks'] ?? [] as $mark) {
                if (! is_array($mark)) {
                    continue;
                }

                $text = $this->applyMark($text, $mark);
            }

            return $text;
        }

        return $this->renderNode($node);
    }

    /**
     * @param  array<string, mixed>  $mark
     */
    private function applyMark(string $content, array $mark): string
    {
        return match ($mark['type'] ?? null) {
            'bold' => '<strong>'.$content.'</strong>',
            'italic' => '<em>'.$content.'</em>',
            'strike' => '<s>'.$content.'</s>',
            'underline' => '<u>'.$content.'</u>',
            'code' => '<code>'.$content.'</code>',
            'subscript' => '<sub>'.$content.'</sub>',
            'superscript' => '<sup>'.$content.'</sup>',
            'highlight' => '<mark>'.$content.'</mark>',
            'link' => $this->renderLink($content, $mark),
            default => $content,
        };
    }

    /**
     * @param  array<string, mixed>  $mark
     */
    private function renderLink(string $content, array $mark): string
    {
        $attrs = $mark['attrs'] ?? [];
        $href = e((string) ($attrs['href'] ?? '#'));
        $target = e((string) ($attrs['target'] ?? '_blank'));
        $rel = e((string) ($attrs['rel'] ?? 'noopener noreferrer'));

        return sprintf('<a href="%s" target="%s" rel="%s">%s</a>', $href, $target, $rel, $content);
    }

    /**
     * @param  array<string, string>  $attributes
     */
    private function renderAttributes(array $attributes): string
    {
        if ($attributes === []) {
            return '';
        }

        $pairs = [];

        foreach ($attributes as $name => $value) {
            $pairs[] = sprintf(' %s="%s"', $name, e($value));
        }

        return implode('', $pairs);
    }
}
