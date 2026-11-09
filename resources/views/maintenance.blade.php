<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex">
    <title>Kısa bir ara veriyoruz – Lusso Home Concept</title>
    <link rel="stylesheet" href="https://fonts.bunny.net/css?family=lato:400,700&display=swap">
    <style>
        body { margin: 0; min-height: 100vh; display: flex; align-items: center; justify-content: center; background: #37383c; color: rgba(255,255,255,.8); font-family: Lato, Arial, sans-serif; text-align: center; padding: 20px; }
        img { width: 240px; margin-bottom: 30px; }
        h1 { color: #fff; font-weight: 400; font-size: 28px; margin: 0 0 12px; }
        a { color: #dca344; }
    </style>
</head>
<body>
<div>
    <img src="{{ asset('img/logo.png') }}" alt="Lusso Home Concept">
    <h1>{{ setting('maintenance_title', 'Kısa bir ara veriyoruz') }}</h1>
    <p>{{ setting('maintenance_text', 'Sitemizi sizin için yeniliyoruz. Çok yakında tekrar buradayız.') }}</p>
    <p>{{ setting('support_phone', '+90 539 109 99 00') }} · <a href="https://www.instagram.com/lussohomeconcept">Instagram</a></p>
</div>
</body>
</html>
