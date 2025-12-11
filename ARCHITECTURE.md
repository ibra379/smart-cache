# 📖 SmartCache - Guida Tecnica Completa

Questo documento spiega l'architettura completa e l'implementazione del package SmartCache.

---

## 📁 Struttura del Progetto

```
smart-cache/
├── config/
│   └── smart-cache.php              # Configurazione del package
├── src/
│   ├── HasSmartCache.php            # Trait principale per i model
│   ├── SmartCacheManager.php        # Gestione centrale della cache
│   ├── SmartCacheServiceProvider.php # Service provider Laravel
│   ├── Observers/
│   │   └── SmartCacheObserver.php   # Invalidazione automatica della cache
│   └── Support/
│       └── CacheKeyGenerator.php    # Generazione chiavi cache uniche
├── workbench/
│   ├── app/Models/                  # Model di test (Post, User)
│   └── database/
│       ├── factories/               # Factory per i model
│       └── migrations/              # Migrazioni di test
└── tests/
    ├── Feature/                     # Test di integrazione
    └── Unit/                        # Test unitari
```

---

## 🔧 Componenti Principali

### 1. SmartCacheServiceProvider

**File:** `src/SmartCacheServiceProvider.php`

**Scopo:** Registra il package con Laravel e crea il singleton SmartCacheManager.

```php
class SmartCacheServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        $package
            ->name('smart-cache')
            ->hasConfigFile();  // Pubblica config/smart-cache.php
    }

    public function packageRegistered(): void
    {
        // Crea UNA SINGOLA istanza di SmartCacheManager per tutta l'app
        $this->app->singleton(SmartCacheManager::class, function ($app) {
            return new SmartCacheManager(
                config('smart-cache.driver', 'auto'),
                config('smart-cache.prefix', 'smart_cache'),
                config('smart-cache.ttl', 60),
                config('smart-cache.enabled', true),
                config('smart-cache.logging', false)
            );
        });
    }
}
```

**Concetto chiave:** `singleton()` assicura che esista UNA SOLA istanza di SmartCacheManager, così tutti i model condividono le stesse impostazioni cache.

---

### 2. SmartCacheManager

**File:** `src/SmartCacheManager.php`

**Scopo:** Hub centrale per tutte le operazioni di cache. Gestisce il caching effettivo, il rilevamento del supporto tags e l'invalidazione.

#### Costruttore

```php
public function __construct(
    protected string $driver,   // 'auto', 'redis', 'file', ecc.
    protected string $prefix,   // 'smart_cache' - prefisso per tutte le chiavi
    protected int $ttl,         // TTL predefinito in minuti
    protected bool $enabled,    // Switch globale on/off
    protected bool $logging     // Logga cache hits/misses
) {
    $this->cache = $this->resolveCache();  // Ottiene lo store cache
}
```

#### Rilevamento Supporto Tags

```php
public function supportsTags(): bool
{
    try {
        $this->cache->tags(['test']);  // Prova a usare i tags
        return true;                    // Funziona! Il driver supporta i tags
    } catch (\BadMethodCallException) {
        return false;                   // Ha lanciato eccezione = nessun supporto tags
    }
}
```

**Perché è importante:**
- Redis, Memcached, Array, Database → Supportano i tags ✅
- File driver → NON supporta i tags ❌

#### Il Metodo Remember

```php
public function remember(string $key, array $tags, int $ttl, callable $callback): mixed
{
    if (!$this->enabled) {
        return $callback();  // Cache disabilitata, esegui solo la query
    }

    $fullKey = $this->prefix . '.' . $key;  // es. "smart_cache.abc123"

    if ($this->supportsTags() && !empty($tags)) {
        // Usa i tags per invalidazione raggruppata
        return $this->cache->tags($tags)->remember($fullKey, $ttl * 60, $callback);
    } else {
        // Fallback senza tags
        return $this->cache->remember($fullKey, $ttl * 60, $callback);
    }
}
```

**Flusso:**
1. Controlla se il caching è abilitato
2. Costruisce la chiave cache completa con prefisso
3. Se i tags sono supportati → usa `Cache::tags(['smart_cache.posts'])->remember(...)`
4. Altrimenti → usa il normale `Cache::remember(...)`

#### Invalidazione Cache

```php
public function invalidateTags(array $tags): void
{
    if (!$this->supportsTags()) {
        $this->logWarning('Il driver cache non supporta i tags.');
        return;  // Non può invalidare senza tags!
    }

    foreach ($tags as $tag) {
        $this->cache->tags([$tag])->flush();  // Elimina TUTTE le entry con questo tag
    }
}
```

**Questo viene chiamato dall'Observer quando un model cambia.**

---

### 3. Trait HasSmartCache

**File:** `src/HasSmartCache.php`

**Scopo:** Il trait che aggiungi ai tuoi model Eloquent. Fornisce lo scope `smartCache()` e i metodi per le query.

#### Metodo Boot (Registra automaticamente l'Observer)

```php
public static function bootHasSmartCache(): void
{
    static::observe(SmartCacheObserver::class);
}
```

**Laravel chiama automaticamente `bootHasSmartCache()` quando il model viene usato.** Questo registra l'observer per l'invalidazione automatica della cache.

#### Lo Scope smartCache

```php
public function scopeSmartCache(Builder $query, ?int $ttl = null): Builder
{
    if (!static::$smartCacheEnabled || !config('smart-cache.enabled', true)) {
        return $query;  // Caching disabilitato, ritorna la query invariata
    }

    // Memorizza il TTL sul model per uso successivo
    $query->getModel()->smartCacheTtl = $ttl;

    return $query;
}
```

**Utilizzo:** `User::smartCache(30)->where(...)`

Il TTL viene memorizzato sull'istanza del model così `smartGet()` può leggerlo dopo.

#### Il Metodo smartGet

```php
public function scopeSmartGet(Builder $query, array $columns = ['*']): Collection
{
    return $this->executeWithSmartCache($query, function () use ($query, $columns) {
        return $query->get($columns);
    });
}
```

#### Il Metodo di Esecuzione Principale

```php
protected function executeWithSmartCache(
    Builder $query,
    callable $callback,
    string $type = 'get',
    ?string $column = null
): mixed {
    $cacheManager = app(SmartCacheManager::class);

    // Controlla se il caching è abilitato
    if (!$cacheManager->isEnabled() || !static::$smartCacheEnabled) {
        return $callback();  // Niente cache, esegui la query direttamente
    }

    // Ottieni TTL (da smartCache(30) o dal config predefinito)
    $ttl = $query->getModel()->smartCacheTtl ?? $cacheManager->getTtl();

    // Genera chiave cache unica basata sulla query
    $cacheKey = match ($type) {
        'count' => CacheKeyGenerator::generateForCount($query),
        'first' => CacheKeyGenerator::generateForFirst($query),
        'sum', 'avg', 'max', 'min' => CacheKeyGenerator::generateForAggregate($query, $type, $column),
        default => CacheKeyGenerator::generate($query),
    };

    // Costruisce il tag per la tabella di questo model
    $table = $query->getModel()->getTable();  // es. 'posts'
    $prefix = $cacheManager->getPrefix();      // es. 'smart_cache'
    $tags = [$prefix . '.' . $table];          // ['smart_cache.posts']

    // Usa SmartCacheManager per cachare il risultato
    return $cacheManager->remember($cacheKey, $tags, $ttl, $callback);
}
```

**Flusso completo:**
1. Ottieni l'istanza SmartCacheManager
2. Controlla se il caching è abilitato
3. Determina il TTL
4. Genera chiave cache unica da SQL + bindings
5. Costruisce il tag basato sul nome tabella del model
6. Chiama `SmartCacheManager::remember()` che gestisce il caching

---

### 4. CacheKeyGenerator

**File:** `src/Support/CacheKeyGenerator.php`

**Scopo:** Crea chiavi cache uniche e deterministiche basate sulla query.

```php
public static function generate(Builder $builder): string
{
    $query = $builder->getQuery();

    // Ottieni SQL e parametri
    $sql = $query->toSql();           // "SELECT * FROM posts WHERE published = ?"
    $bindings = $query->getBindings(); // [true]

    // Includi gli eager loads (relazioni)
    $eagerLoads = array_keys($builder->getEagerLoads());  // ['author', 'comments']

    // Combina tutto
    $components = [
        'sql' => $sql,
        'bindings' => $bindings,
        'eager_loads' => $eagerLoads,
    ];

    // Crea hash MD5 per chiave compatta e unica
    return md5(serialize($components));  // "a1b2c3d4e5f6..."
}
```

**Perché MD5?**
- Deterministico: Stessa query = stesso hash (sempre)
- Compatto: Stringa fissa di 32 caratteri
- Veloce: MD5 è molto veloce da calcolare

**Tipi di query diversi ottengono suffissi:**
- `get` → `abc123`
- `count` → `abc123:count`
- `first` → `abc123:first`
- `sum` → `abc123:sum:amount`

---

### 5. SmartCacheObserver

**File:** `src/Observers/SmartCacheObserver.php`

**Scopo:** Osserva gli eventi del model e invalida la cache automaticamente.

```php
class SmartCacheObserver
{
    public function __construct(
        protected SmartCacheManager $cacheManager
    ) {}

    public function created(Model $model): void
    {
        $this->invalidateCache($model);
    }

    public function updated(Model $model): void
    {
        $this->invalidateCache($model);
    }

    public function deleted(Model $model): void
    {
        $this->invalidateCache($model);
    }

    protected function invalidateCache(Model $model): void
    {
        $table = $model->getTable();      // 'posts'
        $prefix = $this->cacheManager->getPrefix();  // 'smart_cache'

        // Elimina tutte le entry cache taggate con 'smart_cache.posts'
        $this->cacheManager->invalidateTags([$prefix . '.' . $table]);
    }
}
```

**Come funziona:**
1. Quando chiami `Post::create([...])`, Laravel lancia l'evento `created`
2. L'observer lo intercetta e chiama `invalidateCache()`
3. Tutte le entry cache con tag `smart_cache.posts` vengono eliminate
4. La prossima query ottiene dati freschi dal database

---

## 🔄 Diagramma di Flusso Completo

```
1. CODICE UTENTE
   Post::smartCache(30)->where('published', true)->smartGet()
   
2. SCOPE: smartCache(30)
   └─ Memorizza TTL (30) sull'istanza del model
   
3. SCOPE: smartGet()
   └─ Chiama executeWithSmartCache()
   
4. executeWithSmartCache()
   ├─ Ottiene SmartCacheManager
   ├─ Controlla se abilitato
   ├─ Ottiene TTL (30 minuti)
   ├─ Genera chiave cache: "a1b2c3..."
   ├─ Costruisce tags: ['smart_cache.posts']
   └─ Chiama cacheManager->remember()
   
5. SmartCacheManager::remember()
   ├─ Controlla supporto tags
   ├─ Se tags supportati:
   │   └─ Cache::tags(['smart_cache.posts'])
   │         ->remember('smart_cache.a1b2c3', 1800, callback)
   └─ Ritorna dati dalla cache o freschi

6. DOPO: Post::create(['title' => 'Nuovo'])
   └─ SmartCacheObserver::created()
       └─ invalidateTags(['smart_cache.posts'])
           └─ Cache::tags(['smart_cache.posts'])->flush()
               └─ TUTTE le cache dei Post eliminate!
```

---

## 🧪 Architettura dei Test

### TestCase.php

```php
abstract class TestCase extends Orchestra
{
    use RefreshDatabase;  // Resetta il database tra i test

    protected function defineDatabaseMigrations(): void
    {
        // Carica le migrazioni dal workbench
        $this->loadMigrationsFrom(__DIR__.'/../workbench/database/migrations');
    }

    protected function getEnvironmentSetUp($app): void
    {
        // Usa SQLite in-memory per velocità
        config()->set('database.default', 'testing');
        config()->set('database.connections.testing', [
            'driver' => 'sqlite',
            'database' => ':memory:',
        ]);

        // Usa array cache (supporta tags, veloce)
        config()->set('cache.default', 'array');
    }
}
```

### Model del Workbench

I model in `workbench/app/Models/` sono usati SOLO per i test:

```php
class Post extends Model
{
    use HasFactory;
    use HasSmartCache;  // Il trait che stiamo testando!
}
```

### Factory

```php
class PostFactory extends Factory
{
    // Stato predefinito: valori casuali
    public function definition(): array
    {
        return [
            'title' => fake()->sentence(),
            'published' => fake()->boolean(),
        ];
    }

    // Stato con nome: sempre pubblicato
    public function published(): static
    {
        return $this->state(['published' => true]);
    }
}
```

**Utilizzo nei test:**
```php
Post::factory()->published()->count(5)->create();
```

---

## ⚙️ Configurazione Spiegata

```php
// config/smart-cache.php

return [
    // Quale driver cache usare
    // 'auto' = predefinito Laravel, oppure specifica: 'redis', 'memcached', ecc.
    'driver' => env('SMART_CACHE_DRIVER', 'auto'),

    // Durata cache predefinita in MINUTI
    // Può essere sovrascritta per query: User::smartCache(30)->...
    'ttl' => env('SMART_CACHE_TTL', 60),

    // Prefisso per tutte le chiavi cache per evitare collisioni
    // Le chiavi appaiono come: smart_cache.a1b2c3d4
    'prefix' => 'smart_cache',

    // Switch globale on/off
    // Imposta a false nel .env per i test
    'enabled' => env('SMART_CACHE_ENABLED', true),

    // Logga operazioni cache per debug
    'logging' => env('SMART_CACHE_LOGGING', false),
];
```

---

## 🎯 Decisioni di Design Chiave

### 1. Caching Opt-in
```php
// Le query normali funzionano invariate
User::where('active', true)->get();  // Niente cache

// Solo smartCache() abilita il caching
User::smartCache()->where('active', true)->smartGet();  // Cachato
```

**Perché:** Sicuro per default, nessun breaking change al codice esistente.

### 2. Invalidazione a Livello Tabella
Quando QUALSIASI Post cambia, TUTTE le cache dei Post vengono eliminate.

**Pro:** Semplice, sempre corretto
**Contro:** Potrebbe eliminare più del necessario

La futura v2.0 potrebbe aggiungere invalidazione a livello record.

### 3. MD5 per le Chiavi Cache
Deterministico + compatto. Stessa query genera sempre la stessa chiave.

### 4. Metodi Separati (smartGet, smartFirst, smartCount)
Invece di sovrascrivere `get()`, usiamo metodi espliciti.

**Perché:** Intento chiaro, niente magia, facile da capire.

---

## 📚 Riepilogo

| Componente | Responsabilità |
|-----------|---------------|
| **ServiceProvider** | Registra il package, crea singleton |
| **SmartCacheManager** | Gestisce tutte le operazioni cache |
| **HasSmartCache** | Trait con scope per i model |
| **CacheKeyGenerator** | Crea chiavi uniche dalle query |
| **SmartCacheObserver** | Invalidazione automatica sugli eventi model |

**La magia sta nella combinazione:** Cache tags di Laravel + Observer pattern + Generazione chiavi MD5 = caching automatico e intelligente con zero configurazione.
