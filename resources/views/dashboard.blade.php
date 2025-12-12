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
            padding: 0.75rem 1.5rem;
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
        .btn-primary {
            background: linear-gradient(135deg, #7c3aed, #5b21b6);
            color: white;
        }
        .btn-primary:hover { transform: translateY(-2px); box-shadow: 0 4px 12px rgba(124, 58, 237, 0.4); }
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
        .badge-hit { background: rgba(34, 197, 94, 0.2); color: #22c55e; }
        .badge-miss { background: rgba(239, 68, 68, 0.2); color: #ef4444; }
        .badge-enabled { background: rgba(34, 197, 94, 0.2); color: #22c55e; }
        .badge-disabled { background: rgba(239, 68, 68, 0.2); color: #ef4444; }
        .config-item { display: flex; justify-content: space-between; padding: 0.5rem 0; }
        .config-label { color: #9ca3af; }
        .input-group { display: flex; gap: 0.5rem; margin-top: 1rem; }
        input[type="text"] {
            flex: 1;
            padding: 0.75rem;
            border: 1px solid rgba(255, 255, 255, 0.2);
            border-radius: 8px;
            background: rgba(255, 255, 255, 0.05);
            color: #fff;
        }
        input[type="text"]::placeholder { color: #6b7280; }
        .empty-state { text-align: center; padding: 2rem; color: #6b7280; }
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

        <!-- Stats Cards -->
        <div class="stats-grid">
            <div class="stat-card hits">
                <div class="stat-value">{{ $stats['hits'] }}</div>
                <div class="stat-label">Cache Hits</div>
            </div>
            <div class="stat-card misses">
                <div class="stat-value">{{ $stats['misses'] }}</div>
                <div class="stat-label">Cache Misses</div>
            </div>
            <div class="stat-card">
                <div class="stat-value">{{ $stats['total'] }}</div>
                <div class="stat-label">Total Requests</div>
            </div>
            <div class="stat-card">
                <div class="stat-value">{{ $stats['ratio'] }}%</div>
                <div class="stat-label">Hit Ratio</div>
            </div>
        </div>

        <!-- Config & Actions -->
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

        <!-- Clear Cache -->
        <div class="card">
            <h2 class="card-title">Clear Cache</h2>
            <div class="actions">
                <form action="{{ route('smart-cache.clear-all') }}" method="POST">
                    @csrf
                    <button type="submit" class="btn btn-danger" onclick="return confirm('Clear all cache?')">
                        🗑️ Clear All Cache
                    </button>
                </form>
            </div>

            <form action="{{ route('smart-cache.clear-model') }}" method="POST">
                @csrf
                <div class="input-group">
                    <input type="text" name="model" placeholder="App\Models\User" required>
                    <button type="submit" class="btn btn-primary">Clear Model Cache</button>
                </div>
            </form>
        </div>

        <!-- Recent Queries -->
        <div class="card">
            <h2 class="card-title">Recent Queries</h2>
            @if(count($stats['queries']) > 0)
                <table>
                    <thead>
                        <tr>
                            <th>Time</th>
                            <th>Table</th>
                            <th>Type</th>
                            <th>Status</th>
                            <th>Cache Key</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($stats['queries'] as $query)
                            <tr>
                                <td>{{ $query['time'] }}</td>
                                <td>{{ $query['table'] }}</td>
                                <td>{{ $query['type'] }}</td>
                                <td>
                                    <span class="badge badge-{{ $query['status'] }}">
                                        {{ strtoupper($query['status']) }}
                                    </span>
                                </td>
                                <td style="font-family: monospace; font-size: 0.75rem;">{{ Str::limit($query['key'], 32) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @else
                <div class="empty-state">No queries recorded yet.</div>
            @endif
        </div>
    </div>
</body>
</html>
