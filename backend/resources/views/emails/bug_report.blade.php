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
    .value a { color: #7c3aed; text-decoration: none; }
    .call { margin-bottom: 10px; border-left: 3px solid #e5e7eb; padding-left: 12px; }
    .call-head { font-size: 13px; font-weight: 600; color: #374151; margin-bottom: 4px; }
    .call-head .status-ok  { color: #059669; }
    .call-head .status-err { color: #dc2626; }
    .call-detail { font-size: 12px; color: #6b7280; background: #f9fafb; border-radius: 4px; padding: 6px 10px; white-space: pre-wrap; word-break: break-all; }
    .error-line { font-size: 12px; color: #dc2626; background: #fef2f2; border-radius: 4px; padding: 5px 10px; margin-bottom: 4px; white-space: pre-wrap; word-break: break-all; }
    .empty { font-size: 13px; color: #9ca3af; font-style: italic; }
    .footer { padding: 16px 32px; background: #f9fafb; border-top: 1px solid #e5e7eb; font-size: 12px; color: #9ca3af; }
  </style>
</head>
<body>
<div class="wrapper">
  <div class="header">
    <h1>🐛 Bug sur Papyrus</h1>
    <p>Signalé par {{ $userName }} ({{ $userEmail }}) — {{ $reportedAt }}</p>
  </div>
  <div class="body">

    <div class="section">
      <div class="section-title">Message</div>
      <div class="value">{{ $message }}</div>
    </div>

    <div class="section">
      <div class="section-title">URL</div>
      <div class="value"><a href="{{ $url }}">{{ $url }}</a></div>
    </div>

    <div class="section">
      <div class="section-title">Derniers appels API</div>
      @forelse ($apiCalls as $call)
        <div class="call">
          <div class="call-head">
            <span>{{ $call['method'] }} {{ $call['url'] }}</span>
            &nbsp;—&nbsp;
            @if ($call['status'])
              <span class="{{ $call['status'] < 400 ? 'status-ok' : 'status-err' }}">{{ $call['status'] }}</span>
            @else
              <span class="status-err">Erreur réseau</span>
            @endif
            &nbsp;<small style="color:#9ca3af">{{ $call['at'] }}</small>
          </div>
          @if ($call['request'] && $call['request'] !== 'null')
            <div class="call-detail">→ {{ $call['request'] }}</div>
          @endif
          @if ($call['response'] && $call['response'] !== 'null')
            <div class="call-detail" style="margin-top:4px">← {{ $call['response'] }}</div>
          @endif
        </div>
      @empty
        <p class="empty">Aucun appel API enregistré.</p>
      @endforelse
    </div>

    <div class="section">
      <div class="section-title">Erreurs console</div>
      @forelse ($consoleErrors as $err)
        <div class="error-line">{{ $err }}</div>
      @empty
        <p class="empty">Aucune erreur console.</p>
      @endforelse
    </div>

  </div>
  <div class="footer">Papyrus — rapport automatique</div>
</div>
</body>
</html>
