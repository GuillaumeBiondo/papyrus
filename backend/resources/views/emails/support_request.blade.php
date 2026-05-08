<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <style>
    body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif; color: #1f2937; margin: 0; padding: 0; background: #f9fafb; }
    .wrapper { max-width: 680px; margin: 32px auto; background: #fff; border-radius: 12px; overflow: hidden; box-shadow: 0 1px 4px rgba(0,0,0,.08); }
    .header { background: #7c3aed; padding: 24px 32px; }
    .header h1 { color: #fff; margin: 0; font-size: 18px; font-weight: 600; }
    .header p { color: #ddd6fe; margin: 4px 0 0; font-size: 13px; }
    .body { padding: 28px 32px; }
    .section { margin-bottom: 24px; }
    .section-title { font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: .06em; color: #6b7280; margin-bottom: 8px; }
    .value { font-size: 14px; color: #111827; background: #f3f4f6; border-radius: 6px; padding: 10px 14px; word-break: break-word; white-space: pre-wrap; }
    .footer { padding: 16px 32px; background: #f9fafb; border-top: 1px solid #e5e7eb; font-size: 12px; color: #9ca3af; }
  </style>
</head>
<body>
<div class="wrapper">
  <div class="header">
    <h1>📬 Demande de support — Papyrus</h1>
    <p>De {{ $userName }} ({{ $userEmail }}) — {{ $sentAt }}</p>
  </div>
  <div class="body">

    <div class="section">
      <div class="section-title">Objet</div>
      <div class="value">{{ $subject }}</div>
    </div>

    <div class="section">
      <div class="section-title">Message</div>
      <div class="value">{{ $body }}</div>
    </div>

  </div>
  <div class="footer">Papyrus — demande de support utilisateur</div>
</div>
</body>
</html>
