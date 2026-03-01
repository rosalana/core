# Rosalana Core -- Technical Context for AI Agents

## What This Is

Rosalana Core is a Laravel Composer package (`rosalana/core`) that serves as the foundation of a multi-application ecosystem. It provides service-to-service communication, authentication, shared state management, and package management. It is one of ~9 interconnected packages that form the Rosalana ecosystem -- all built by a single developer, concurrently, because the components are interdependent.

This is an internal (not open-source) package under active development. The author works on it in spare time. Tests are intentionally deferred -- the author is still shaping the architecture and considers tests premature at this stage.

**Namespace:** `Rosalana\Core\`
**Laravel version:** 12.x
**PHP version:** ^8.2
**Key external dependency:** `rosalana/configure` (config file management), `ext-redis` (phpredis)

---

## Architecture Overview

The package has 6 major subsystems. Understanding their relationships is critical before making any changes.

```
                    ┌─────────────┐
                    │  Basecamp   │  (Central server / authority)
                    └──────┬──────┘
                           │
              ┌────────────┼────────────┐
              │            │            │
         ┌────┴────┐ ┌────┴────┐ ┌────┴────┐
         │  App A  │ │  App B  │ │  App C  │  (Each runs rosalana/core)
         └─────────┘ └─────────┘ └─────────┘
              │                        │
              └────── Outpost ─────────┘  (Redis Streams async messaging)
```

### 1. Basecamp (Synchronous HTTP Communication)

**Files:** `src/Services/Basecamp/`
**Facade:** `Rosalana\Core\Facades\Basecamp`

HTTP client for communicating with the central Basecamp server and other applications in the ecosystem.

**Key design decisions:**
- `Manager` uses a fluent builder pattern: `Basecamp::to('app-b')->withAuth()->timeout(10)->get('/endpoint')`
- `Manager` is bound as **non-singleton** (`$this->app->bind(...)`) -- each resolve gets a fresh instance. This is intentional because the builder accumulates state (targets, pipeline, mock mode, etc.) and must be clean per-use.
- Uses **Strategy pattern** for request preparation. Two strategies exist:
  - `BasecampStrategy`: sets base URL from config, signs request with HMAC headers (X-App-Id, X-Timestamp, X-Signature)
  - `AppStrategy`: discovers target app's URL via Basecamp API, authenticates with a signed Revizor ticket as Bearer token
- The `Request` class is a value-object-like builder that accumulates URL, headers, method, body, then executes via Laravel's HTTP client.
- Error handling is delegated to the strategy (`strategy->throw()`), which maps error type strings to specific exception classes via enums (`BasecampErrorType`, `HttpAppErrorType`).
- `Serviceable` trait enables sub-service registration: `Basecamp::apps()`, `Basecamp::tickets()` resolve registered service instances dynamically via `__call`.
- Pipeline integration: responses can be automatically piped through a named Pipeline for post-processing.
- `ghost()` mode skips pipeline execution. `mock()` mode returns fake responses without HTTP calls.

### 2. Outpost (Asynchronous Redis Streams Messaging)

**Files:** `src/Services/Outpost/`
**Facade:** `Rosalana\Core\Facades\Outpost`

Message broker for app-to-app async communication using Redis Streams with Consumer Groups.

**Message lifecycle:**
```
App A: Outpost::to('app-b')->request('order.create', $payload)
  → sends via Basecamp POST /outpost/send
  → Basecamp dispatches to App B's Redis stream

App B: Worker reads stream → resolves handler (Listener class or Registry callback)
  → App B responds: $message->confirm($payload) or $message->fail($payload)

App A: Worker picks up response → resolves Promise callbacks (onConfirm/onFail/onUnreachable)
```

**Key design decisions:**
- Message namespace format is strict: `{group}.{name}:{status}` where status is one of `request`, `confirmed`, `failed`, `unreachable`. Validated by regex in `Message::ensureValid()`.
- Three handler resolution strategies (tried in order by `MessageReceived` action):
  1. **Promise**: if a correlation ID exists and a stored callback matches, resolve it. Callbacks are serialized via `SerializableClosure` and stored in Redis Context.
  2. **Listener class**: convention-based class resolution from namespace (e.g., `order.create` → `App\Outpost\Order\Create`). Must extend `Rosalana\Core\Services\Outpost\Listener`.
  3. **Registry**: closure-based handlers registered via `Outpost::receive('namespace', fn)`. Supports wildcard matching.
- `Worker` runs an infinite loop using `XREADGROUP` with blocking timeout of 5000ms. Messages are ACK'd after processing. Consumer name is `hostname-pid`.
- `broadcast()` sets target to `['*']`, `except()` adds exclusions prefixed with `!`.
- `Promise` stores callbacks keyed by correlation ID in the app's Redis Context store. This means promises survive process restarts -- they're not in-memory.

**Important:** The Worker catches all `\Throwable` silently (no logging) to prevent crashes. This is a known gap.

### 3. Revizor (Authentication Protocol)

**Files:** `src/Services/Revizor/`, `src/Support/Signer.php`, `src/Support/Cipher.php`
**Facade:** `Rosalana\Core\Facades\Revizor`

Custom service-to-service authentication protocol. Two distinct mechanisms:

#### 3a. Request Signing (App ↔ Basecamp)

Used for all direct Basecamp API calls. Similar to AWS Signature V4.

```
Signed data: "{METHOD}\n{URL}\n{timestamp_ms}\n{normalized_body}\n{app_id}"
Algorithm: HMAC-SHA256 with shared secret (from config rosalana.basecamp.secret)
Transport: X-App-Id, X-Timestamp, X-Signature headers
```

- `RequestSigner` extends abstract `Signer` which provides `sign()`, `compare()`, timestamp management.
- Body normalization handles edge cases: empty arrays/strings/null → empty string, JSON re-encoding for consistency.
- Timestamp is millisecond-precision (`microtime(true) * 1000`).

#### 3b. Ticket System (App ↔ App)

Used for inter-app communication. Conceptually similar to Kerberos.

**Ticket states (state machine):**
```
LOCKED (from Basecamp)  →  UNLOCKED (key decrypted)  →  SIGNED (key removed, signature added)
     has: key(encrypted), locked=true    has: key(plain), locked=false     has: signature, timestamp (no key)
```

**Flow:**
1. App A calls `Revizor::ticketFor('app-b')` which checks wallet (Redis Context) first, then buys from Basecamp if missing.
2. Ticket arrives LOCKED: key is AES-256-CBC encrypted with the shared secret.
3. `ticket->sign()` unlocks (decrypts key) → computes HMAC-SHA256 of `"{ticket_id}\n{timestamp}"` using the key → removes key from payload → adds signature + timestamp.
4. `ticket->seal()` base64-encodes the payload for transport as Bearer token.
5. Receiver calls `Ticket::fromRequest()` → unwraps → `TicketValidator::verify()`:
   - Checks format (must be signed state)
   - Checks ticket expiration
   - Checks timestamp freshness (within `signature_ttl` seconds, default 60)
   - Checks replay (signature not in cache)
   - Looks up ticket on Basecamp (gets original with key)
   - Recomputes signature with original key + received timestamp
   - Compares with `hash_equals()` (timing-safe)

**Security properties:**
- Per-ticket keys (compromise of one ticket doesn't affect others)
- Keys never travel in plaintext (locked state for storage, removed for transport)
- Replay protection via signature caching
- Timestamp freshness limits signature reuse window
- Timing-safe comparison prevents timing attacks
- Standard crypto primitives (AES-256-CBC, HMAC-SHA256), no custom cryptography

**Known issue in Cipher.php:** The method names `encrypt` and `decrypt` are SWAPPED relative to their actual behavior. `encrypt()` performs decryption (base64_decode → openssl_decrypt) and `decrypt()` performs encryption (openssl_encrypt → base64_encode). This works because callers (Ticket lock/unlock) are consistently inverted too, but it is extremely confusing. The calling convention is: `Cipher::encrypt($value)` = "decrypt/reveal this value", `Cipher::decrypt($value)` = "encrypt/hide this value".

**Why not OAuth:** The author correctly identified that OAuth solves user-centric authorization ("App wants User's data") while Rosalana needs service-to-service authorization ("App A authenticates as itself to App B"). OAuth 2.0 Client Credentials flow could technically work but doesn't provide per-request signing or per-target ticket isolation. The ticket-based approach is more appropriate for this use case.

### 4. Pipeline (Extensible Middleware System)

**Files:** `src/Services/Pipeline/`
**Facade:** `Rosalana\Core\Facades\Pipeline`

Named pipeline registry. Pipelines are chains of callables that process data sequentially (built on Laravel's Pipeline).

```php
Pipeline::resolve('user.sync')->extend(fn($response, $next) => $next($response));
Pipeline::resolve('user.sync')->run($basecampResponse);
```

- `Registry` uses **static state** (`static $pipelines`) -- pipelines persist for the process lifetime.
- Pipelines are auto-created on first `resolve()`.
- Used internally by Basecamp Manager (auto-runs pipeline after response) and Hooks system.
- `$scope` property exists on Pipeline but is marked "not implemented yet".

### 5. Context Store (Redis-backed Hierarchical State)

**Files:** `src/Services/App/Context.php`, `src/Services/App/ContextStore.php`
**Facade:** `Rosalana\Core\Facades\Context` (also accessible via `App::context()`)

Hierarchical document store built on flat Redis keys. This is the most complex subsystem.

**Key concepts:**
- **Scoping:** Every operation happens within a scope. Default scope is `__app`. Scopes can represent entities (e.g., `user.42`). Scope extraction logic: if input is a class name, uses basename; if object with `getKey()`, uses `classname.id`.
- **Storage model:** Dot-paths map to colon-separated Redis keys: `rosalana:{app_id}:ctx:{scope}:{path:segments}`
- **Array handling:** Arrays can't be stored as single Redis values (TTL per-field needed). Instead, each array element is a separate key, with an `__array` marker key storing `list` or `assoc` to enable reconstruction.
- **Indexing:** Each scope maintains a Redis SET of all its keys (`__index` suffix). This enables subtree operations without SCAN/KEYS.
- **Global scope registry:** A Redis SET tracks all active scopes for an app ID. Auto-registered on first write, auto-unregistered when scope becomes empty.
- **Two modes:** `ContextStore::scoped($scope)` for scope-bound operations, `ContextStore::global()` for cross-scope operations (all, raw, flush, find).

**Important implementation details:**
- `put()` recursively flattens arrays into individual keys with markers. Overwrites delete the existing subtree first.
- `get()` checks for a leaf value first, then attempts subtree reconstruction via `dumpSubtree()`.
- `dumpSubtree()` iterates the scope index, reconstructs nested arrays using `Arr::set()`, then applies array metadata (markers) to convert to proper lists/assocs.
- `find()` supports wildcard patterns across scopes (global mode) or within a scope (scoped mode). Uses `Str::is()` for matching.
- Hooks fire on `context:update`, `context:forget`, `context:clear`, `context:flush`.
- Requires phpredis extension (`\Redis` instance), NOT Predis.

**Known issues:**
- `decrement()` calls `registerScope()` instead of `requireScoped()` (inconsistency with `increment()`)
- `shift()` does `array_unshift` (prepend) but the name suggests "remove from front"
- `pop()` doesn't return the popped value

### 6. Trace (Runtime Observability)

**Files:** `src/Services/Trace/`
**Facade:** `Rosalana\Core\Facades\Trace`

Runtime tracing system integrated into all major subsystems.

- `Trace::capture(fn() => ..., 'Scope:name')` wraps operations in trace spans.
- Records three types of data: `record` (general data), `decision` (what was decided/dispatched), `exception`.
- `Trace::start()` / `Trace::finish()->log('console')` for manual trace lifecycle.
- Rendering is pluggable via `Registry` -- maps scope patterns (with wildcards like `Outpost:handler:{listener|registry|promise}`) to renderer classes.
- Can be disabled via config `rosalana.tracer.runtime.enabled`.
- Integrated into: Basecamp requests, Outpost send/receive/handlers, Listener execution.

---

## Other Components

### App Manager (`src/Services/App/`)
- Facade: `Rosalana\Core\Facades\App`
- Central access point for app identity: `App::id()`, `App::slug()`, `App::name()`, `App::secret()`, `App::config($key)`, `App::context()`, `App::hooks()`
- `Meta` resolves values from `config('rosalana.*')`
- `Hooks` is an event system built on Pipeline: `App::hooks()->on('context:update', fn($data) => ...)`, `App::hooks()->run('context:update', $payload)`
- Hook alias format must be `group:name` (validated)
- Magic method support: `App::hooks()->onContextUpdate(fn)` → resolves to `context:update`

### Actions (`src/Services/Actions/`, `src/Contracts/Action.php`)
- Command pattern: `Action` interface with `handle()`, `isQueueable()`, `isBroadcastable()`
- `Runner::run($action)`: if queueable → dispatch to queue; else handle synchronously, then broadcast if broadcastable.
- Traits control behavior: `QueuedOnly`, `BroadcastOnly`, `QueuedAndBroadcast`, `SynchronousExecution`
- `Inline` action wraps a closure into an Action.
- Global helper: `run($action)`, `action(fn() => ...)`

### Exception Hierarchy (`src/Exceptions/`)
- `RosalanaHttpException` -- base for HTTP errors, carries response array with type/message/errors
- `BasecampErrorType` and `HttpAppErrorType` enums map error type strings to specific exception classes and throw them
- `Handler::convertExceptionToApiResponse()` converts any Throwable to a standardized JSON error response for internal API routes
- All error responses return **HTTP 200** with error details in the JSON body (`ErrorResponse::toResponse()` calls `->setStatusCode(200)`). This is a deliberate design choice for the internal API protocol.

### HTTP Responses (`src/Http/Responses/`)
- `ok($data, $meta)` → `SuccessResponse` (Responsable, invokable)
- `error($message, $code, $type, $errors)` → `ErrorResponse` (fluent builder: `error()->unauthorized('msg')()`)
- Both implement `Responsable` and `__invoke` for flexible usage.

### Package Manager (`src/Services/Package.php`, `src/Package.php`)
- Manages Rosalana ecosystem packages via Composer CLI
- Known packages hardcoded in `Package::$packages`: `rosalana/core`, `rosalana/accounts`, `rosalana/roles`
- `Package` (entity) resolves install status, published status, version compatibility
- `PackageStatus` enum: `UP_TO_DATE`, `OLD_VERSION`, `NOT_PUBLISHED`, `NOT_INSTALLED`
- Version strategy: all packages in ecosystem share major version. `switchVersion()` updates all installed packages simultaneously.
- CLI commands: `rosalana:add`, `rosalana:remove`, `rosalana:list`, `rosalana:update`, `rosalana:publish`

### Middleware (`src/Http/Middleware/`)
- `ForceJson`: forces Accept: application/json on internal routes
- `RevizorCheckTicket`: verifies Revizor ticket from Bearer token on incoming requests
- Both registered in `internal` middleware group, applied to `/internal/*` routes

### Support Classes (`src/Support/`)
- `Cipher`: AES-256-CBC encrypt/decrypt (WARNING: method names are swapped, see Revizor section)
- `Signer`: abstract HMAC-SHA256 signer base class
- `Cryptor`: **LEGACY/DEPRECATED** -- duplicates Cipher + RequestSigner functionality. Uses `env()` directly. Should not be used for new code.
- `WildcardMatch`: utility for wildcard pattern matching against string collections

### Service Provider (`src/Providers/RosalanaCoreServiceProvider.php`)
- Registers all singletons and bindings
- Registers Basecamp sub-services (apps, tickets) via `resolving` callback
- Registers internal Outpost receiver for `context.refresh:request`
- Registers Trace rendering schemes
- Boots: middleware group, internal routes, console commands, publishable config/routes
- Extends exception handler with anonymous class decorator for internal route error handling

---

## Configuration

Config key: `rosalana` (file: `config/rosalana.php`, published to app's `config/rosalana.php`)

Expected structure after publishing:
```
rosalana.published.*          - installed package versions (auto-managed)
rosalana.basecamp.url         - Basecamp server URL (env: ROSALANA_BASECAMP_URL)
rosalana.basecamp.secret      - shared secret for HMAC signing (env: ROSALANA_APP_SECRET)
rosalana.basecamp.id          - app identifier (env: ROSALANA_APP_ID)
rosalana.basecamp.name        - app slug on Basecamp (env: ROSALANA_APP_NAME)
rosalana.basecamp.version     - API version prefix (default: "v1")
rosalana.outpost.connection   - Redis connection name (default: "outpost")
rosalana.outpost.listeners    - namespace prefix for Listener class resolution
rosalana.revizor.signature_ttl - seconds before signature expires (default: 60)
rosalana.revizor.cache_prefix - Redis cache key prefix for replay protection
rosalana.tracer.runtime.enabled - enable/disable trace system
```

---

## Global Helpers (`src/helpers.php`)

- `run(Action $action): mixed` -- execute an Action through Runner
- `action(Closure $callback): Action` -- wrap closure in Inline Action
- `matches(string $value): WildcardMatch` -- create wildcard matcher
- `ok(mixed $data, array $meta): SuccessResponse` -- success response
- `error(string $message, int $code, ...): ErrorResponse` -- error response builder

Additionally, `contains()` is available from the `hamcrest/hamcrest-php` dependency (used in Outpost Manager for checking if targets array contains `'*'`).

---

## Testing

Currently minimal: `tests/Unit/PipelineTest.php` (2 tests). Tests use Orchestra Testbench. The author has stated tests are intentionally deferred during the architectural phase.

---

## Known Issues and Gotchas

1. **Cipher method names are inverted**: `Cipher::encrypt()` decrypts, `Cipher::decrypt()` encrypts. All callers are consistently inverted so it works, but be extremely careful if using Cipher directly.
2. **Cryptor is legacy code**: duplicates functionality of Cipher + RequestSigner. Uses `env()` instead of `config()`. Do not use for new code.
3. **Static state in registries**: `Pipeline\Registry::$pipelines` and `Outpost\Registry::$listeners` are static. Be aware of state persistence in long-running processes (workers, Octane).
4. **ErrorResponse always returns HTTP 200**: error details are in JSON body, not HTTP status code. This is by design for the internal protocol.
5. **Worker exception handling**: Outpost Worker catches all Throwable silently without logging.
6. **ContextStore::decrement()** calls `registerScope()` instead of `requireScoped()`.
7. **ContextStore::shift()** actually prepends (array_unshift), doesn't remove from front.
8. **ContextStore::pop()** doesn't return the removed value.
