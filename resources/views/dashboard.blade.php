<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SmartCache Dashboard</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: linear-gradient(135deg, #1a1a2e 0%, #16213e 100%);
            color: #e0e0e0;
            min-height: 100vh;
            padding: 2rem;
        }
        .container { max-width: 1200px; margin: 0 auto; }
        h1 {
            font-size: 2rem;
            margin-bottom: 2rem;
            background: linear-gradient(90deg, #00d4ff, #7c3aed);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        .alert {
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1rem;
        }
        .alert-success { background: rgba(34, 197, 94, 0.2); border: 1px solid #22c55e; }
        .alert-error { background: rgba(239, 68, 68, 0.2); border: 1px solid #ef4444; }
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }
        .stat-card {
            background: rgba(255, 255, 255, 0.05);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 16px;
            padding: 1.5rem;
            text-align: center;
        }
        .stat-value {
            font-size: 2.5rem;
            font-weight: bold;
            background: linear-gradient(90deg, #00d4ff, #7c3aed);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }
        .stat-label { color: #9ca3af; margin-top: 0.5rem; }
        .stat-card.hits .stat-value { color: #22c55e; -webkit-text-fill-color: #22c55e; }
        .stat-card.misses .stat-value { color: #ef4444; -webkit-text-fill-color: #ef4444; }
        .card {
            background: rgba(255, 255, 255, 0.05);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 16px;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
        }
        .card-title {
            font-size: 1.25rem;
            margin-bottom: 1rem;
            color: #fff;
        }
        .btn {
            padding: 0.5rem 1rem;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-size: 0.875rem;
            font-weight: 500;
            transition: all 0.2s;
        }
        .btn-danger {
            background: linear-gradient(135deg, #ef4444, #dc2626);
            color: white;
        }
        .btn-danger:hover { transform: translateY(-2px); box-shadow: 0 4px 12px rgba(239, 68, 68, 0.4); }
        .btn-warning {
            background: linear-gradient(135deg, #f59e0b, #d97706);
            color: white;
        }
        .btn-warning:hover { transform: translateY(-2px); box-shadow: 0 4px 12px rgba(245, 158, 11, 0.4); }
        .actions { display: flex; gap: 1rem; flex-wrap: wrap; }
        table { width: 100%; border-collapse: collapse; }
        th, td {
            padding: 0.75rem;
            text-align: left;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }
        th { color: #9ca3af; font-weight: 500; font-size: 0.75rem; text-transform: uppercase; }
        .badge {
            padding: 0.25rem 0.75rem;
            border-radius: 9999px;
            font-size: 0.75rem;
            font-weight: 500;
        }
        .badge-enabled { background: rgba(34, 197, 94, 0.2); color: #22c55e; }
        .badge-disabled { background: rgba(239, 68, 68, 0.2); color: #ef4444; }
        .config-item { display: flex; justify-content: space-between; padding: 0.5rem 0; }
        .config-label { color: #9ca3af; }
        .empty-state { text-align: center; padding: 2rem; color: #6b7280; }
        .model-row { display: flex; justify-content: space-between; align-items: center; }
        .model-info { flex: 1; }
        .model-class { font-weight: 500; color: #fff; }
        .model-table { font-size: 0.875rem; color: #9ca3af; font-family: monospace; }
    </style>
</head>
<body>
    <div class="container">
        <h1>⚡ SmartCache Dashboard</h1>

        @if(session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif

        @if(session('error'))
            <div class="alert alert-error">{{ session('error') }}</div>
        @endif

        <!-- Config -->
        <div class="card">
            <h2 class="card-title">Configuration</h2>
            <div class="config-item">
                <span class="config-label">Status</span>
                <span class="badge {{ $enabled ? 'badge-enabled' : 'badge-disabled' }}">
                    {{ $enabled ? 'Enabled' : 'Disabled' }}
                </span>
            </div>
            <div class="config-item">
                <span class="config-label">Tag Support</span>
                <span class="badge {{ $supportsTags ? 'badge-enabled' : 'badge-disabled' }}">
                    {{ $supportsTags ? 'Supported' : 'Not Supported' }}
                </span>
            </div>
            <div class="config-item">
                <span class="config-label">Prefix</span>
                <span>{{ $prefix }}</span>
            </div>
            <div class="config-item">
                <span class="config-label">Default TTL</span>
                <span>{{ $ttl }} minutes</span>
            </div>
        </div>

        <!-- Cached Models -->
        <div class="card">
            <h2 class="card-title">📦 Cached Models</h2>
            @if(count($models) > 0)
                <table>
                    <thead>
                        <tr>
                            <th>Model</th>
                            <th>Table</th>
                            <th style="text-align: right;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($models as $model)
                            <tr>
                                <td>
                                    <span class="model-class">{{ $model['short_name'] }}</span>
                                    <br>
                                    <span class="model-table">{{ $model['class'] }}</span>
                                </td>
                                <td>
                                    <span class="model-table">{{ $model['table'] }}</span>
                                </td>
                                <td style="text-align: right;">
                                    <form action="{{ route('smart-cache.clear-table', ['table' => $model['table']]) }}" method="POST" style="display: inline;">
                                        @csrf
                                        <button type="submit" class="btn btn-warning" onclick="return confirm('Invalidate cache for {{ $model['short_name'] }}?')">
                                            🗑️ Invalidate
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @else
                <div class="empty-state">
                    <p>No models using SmartCache found.</p>
                    <p style="margin-top: 0.5rem; font-size: 0.875rem;">Add the <code>HasSmartCache</code> trait to your models in <code>app/Models</code>.</p>
                </div>
            @endif
        </div>

        <!-- Clear All Cache -->
        <div class="card">
            <h2 class="card-title">⚠️ Danger Zone</h2>
            <p style="color: #9ca3af; margin-bottom: 1rem;">Clear all SmartCache entries. This action cannot be undone.</p>
            <form action="{{ route('smart-cache.clear-all') }}" method="POST">
                @csrf
                <button type="submit" class="btn btn-danger" onclick="return confirm('Clear ALL SmartCache entries?')">
                    🗑️ Clear All Cache
                </button>
            </form>
        </div>
    </div>
</body>
</html>
