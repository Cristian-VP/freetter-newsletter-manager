<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title>{{ $post->title }}</title>
    <style>
        .newsletter-body {
            margin: 0;
            padding: 0;
            background-color: #f9fafb;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
        }
        .newsletter-container {
            max-width: 680px;
            margin: 0 auto;
            background-color: #ffffff;
            padding: 40px 48px;
            border-radius: 12px;
        }
        .newsletter-title {
            font-size: 28px;
            font-weight: 700;
            color: #111827;
            margin: 0 0 8px 0;
            line-height: 1.2;
        }
        .newsletter-meta {
            font-size: 15px;
            color: #6b7280;
            margin: 0 0 24px 0;
        }
        .newsletter-content {
            font-size: 17px;
            line-height: 1.75;
            color: #111827;
        }
        .newsletter-content p {
            margin: 0 0 16px 0;
        }
        .newsletter-content h1,
        .newsletter-content h2,
        .newsletter-content h3 {
            color: #111827;
            margin: 24px 0 12px 0;
            line-height: 1.3;
        }
        .newsletter-content h1 {
            font-size: 24px;
        }
        .newsletter-content h2 {
            font-size: 20px;
        }
        .newsletter-content h3 {
            font-size: 18px;
        }
        .newsletter-content blockquote {
            border-left: 4px solid #e5e7eb;
            padding-left: 16px;
            margin: 16px 0;
            color: #4b5563;
        }
        .newsletter-content ul,
        .newsletter-content ol {
            margin: 0 0 16px 0;
            padding-left: 24px;
        }
        .newsletter-content li {
            margin-bottom: 4px;
        }
        .newsletter-content pre {
            background: #f4f4f5;
            border: 1px solid #e4e4e7;
            border-radius: 12px;
            padding: 16px;
            overflow: auto;
            margin: 16px 0;
        }
        .newsletter-content code {
            font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
            font-size: 14px;
        }
        .newsletter-content img {
            max-width: 100%;
            height: auto;
            border-radius: 12px;
            display: block;
            margin: 24px auto;
        }
        .newsletter-content figure {
            margin: 24px 0;
            text-align: center;
        }
        .newsletter-content hr {
            border: 0;
            border-top: 1px solid #e5e7eb;
            margin: 24px 0;
        }
        .newsletter-cta {
            text-align: center;
            margin: 32px 0 16px 0;
        }
        .newsletter-cta a {
            display: inline-block;
            background-color: #111827;
            color: #ffffff;
            text-decoration: none;
            padding: 12px 24px;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 500;
        }
        .newsletter-footer {
            text-align: center;
            font-size: 15px;
            color: #6b7280;
            margin-top: 24px;
        }
        @media only screen and (max-width: 600px) {
            .newsletter-container {
                max-width: 100% !important;
                width: 100% !important;
                padding: 24px 12px !important;
                border-radius: 0 !important;
            }
            .newsletter-title {
                font-size: 24px !important;
            }
            .newsletter-content {
                font-size: 17px !important;
            }
            .newsletter-content h1 {
                font-size: 22px !important;
            }
            .newsletter-content h2 {
                font-size: 19px !important;
            }
            .newsletter-content h3 {
                font-size: 17px !important;
            }
            .newsletter-cta a {
                display: block !important;
                width: 100% !important;
                box-sizing: border-box !important;
                text-align: center !important;
            }
        }
    </style>
</head>
<body class="newsletter-body">
    <!-- Preview text for email clients -->
    <div style="display: none; max-height: 0px; overflow: hidden;">
        {{ $excerpt ?? '' }}
    </div>
    <!-- Push down preview text to hide other content -->
    <div style="display: none; max-height: 0px; overflow: hidden;">
        &nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;
    </div>
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0">
        <tr>
            <td align="center" style="padding: 40px 16px;">
                <table role="presentation" class="newsletter-container" width="680" cellpadding="0" cellspacing="0" border="0">
                    <tr>
                        <td>
                            <div class="newsletter-content">
                                {!! $newsletterHtml !!}
                            </div>
                            <div class="newsletter-cta">
                                <a href="{{ route('home').'#post-'.$post->id }}">Ver newsletter</a>
                            </div>
                            <p class="newsletter-footer">
                                Si prefieres leerla en la web, puedes abrirla desde el enlace anterior.
                            </p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
