# Tenant Stack

Starter kit for **multitenant** Laravel applications, with data isolation per **tenant** (company) using global scopes, context singleton, and middlewares.

## Requirements

- PHP 8.2+
- Composer
- MySQL, PostgreSQL or SQLite

## Installation

```bash
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate
```

## About the application

The application treats **each company (Company)** as a **tenant**. Users belong to one company (`company_id`). All authenticated routes operate in the context of the logged-in user's company: queries and creations are automatically restricted to that company.

### Flow overview

1. **Register/Login** — no tenant context (creates company + user or validates credentials).
2. **Authenticated routes** — the middleware sets the user's company in the context; scopes and traits ensure only that company's data is accessed or created.

---

## Architecture

The project uses a **modular** domain structure:

```
app/
├── Models/                    # Global models (e.g. User)
├── Modules/
│   ├── Core/                  # Contracts, traits and shared utilities
│   │   └── Domain/
│   │       ├── Contracts/     # ServiceContract, etc.
│   │       └── Traits/        # HasCompany, ServiceTrait
│   ├── Company/               # Tenant (company) module
│   │   └── Domain/
│   │       ├── Models/
│   │       ├── Scopes/        # CompanyScope
│   │       ├── Services/
│   │       └── Singletons/    # CompanySingleton
│   │   Http/
│   │       ├── Middlewares/   # InitializeCompany
│   │       ├── Controllers/
│   │       ├── Requests/
│   │       └── Resources/
│   └── User/
│       └── Domain/
│       └── Http/
└── Providers/
```

- **Core**: service contracts (`ServiceContract`), traits (`HasCompany`, `ServiceTrait`, `ControllerTrait`) and common rules.
- **Company**: tenant model, global scope, context singleton and middleware that initializes the tenant.
- **User** (and other modules): domain business rules and HTTP, reusing Core and Company.

Multitenant persistence is **row-based**: tables that belong to the tenant have `company_id`; scopes and traits ensure automatic filtering and assignment.

---

## Concepts

### 1. Tenant = Company

- **Tenant** is the entity that defines isolation (a company).
- The model `App\Modules\Company\Domain\Models\Company` represents the tenant.
- Users have `company_id` and belong to a single company.

### 2. Scope-based isolation (Global Scope)

- **CompanyScope** is an Eloquent **global scope**.
- On any query on models using the `HasCompany` trait, the scope automatically adds `WHERE company_id = ?` with the company ID from the context (when a company is set).
- Thus, listings, searches and `find` only return records for the current company.

### 3. Context singleton (Company Singleton)

- **CompanySingleton** holds the **current request's company** in memory.
- It is registered in the container as a **singleton** under the name `'company'` (`AppServiceProvider`).
- The `InitializeCompany` middleware gets the authenticated user's company and sets it in the context.
- During the request, `app('company')->company()` returns the same company instance, used by the scope and the `HasCompany` trait on create.

### 4. Tenant initialization middleware

- **InitializeCompany**:
  - Authenticates the user when a Bearer token is present (Sanctum).
  - Resolves the user's company (`$user->company`).
  - Sets the company in the context.
  - Returns 404 if no company is found (user without company or unauthenticated where tenant is required).

Routes that need tenant context should use `auth:sanctum` and `InitializeCompany`.

### 5. HasCompany trait

- **HasCompany** on a model:
  - Defines the `company()` relationship.
  - Applies **CompanyScope** on model boot.
  - On the `creating` event, sets `company_id` to `app('company')->company()?->id`, so new records are tied to the current tenant.

For **new multitenant entities**: add `company_id` in the migration, use the `HasCompany` trait and, if needed, include `company_id` in `$fillable` (or let the trait set it on `creating`).

---

## Using scopes

### Where CompanyScope is applied

- On every model that uses the `HasCompany` trait (e.g. `User`).
- The scope only filters when a company is set in the context (`app('company')->company()` is not null). Otherwise it does not add a `WHERE` (useful for tenant-less scenarios like login/register).

### Disabling the scope on a query

When you need to access data from any tenant (e.g. login by email across all companies):

```php
use App\Modules\Company\Domain\Scopes\CompanyScope;

User::withoutGlobalScope(CompanyScope::class)->where('email', $email)->first();
```

`UserService::login` uses this pattern to find the user by email before verifying the password.

### Scope on new models

1. Migration: `$table->foreignId('company_id')->constrained('companies');`
2. Model: `use HasCompany;` and relationship to `Company` if needed.
3. The rest (query filtering and `company_id` on create) is handled by the trait and the context.

---

## Using middlewares

### InitializeCompany

- **Registration**: applied to routes in `routes/api.php` in the group that already uses `auth:sanctum`.
- **Order**: must run **after** authentication so `Auth::user()` exists and `$user->company` can be resolved.
- **Behaviour**:
  - If a Bearer token is present and the user is not yet authenticated, the middleware loads the user via Sanctum token and sets it in `Auth`.
  - Then it reads the user's company, sets it in the context and requires that the company exists (404 if not).

Example protected group:

```php
Route::middleware(['auth:sanctum', InitializeCompany::class])->group(function () {
    // routes that need tenant (company) context
});
```

**Register** and **login** routes stay **outside** this group, since no tenant is defined yet (or login is what defines the user and, indirectly, the company).

---

## Using the singleton (Company)

The `company` binding in the container is an adapter that delegates to `CompanyContext`. Setting the company in the context is done **only by the infrastructure** (middleware, `CompanyAwareJob`, `CompanyRunner`). Do not call `registerCompany()` manually; see the [Company Context Architecture](#company-context-architecture) section.

### API of the `app('company')` adapter

- `company(): ?Company` — returns the current company or `null` (use in services, scopes and traits).

Typical use in code that only **reads** the context (already set by the infrastructure):

```php
$company = app('company')->company();
$companyId = app('company')->company()?->id;
```

---

## Services and controllers

- **ServiceContract** + **ServiceTrait**: domain services with `create`, `find`, `findOrFail`, `paginate`, `update`, `delete`, etc., using the model configured in `$model`. Since the model uses `HasCompany`, all queries respect CompanyScope and the current tenant.
- **ControllerTrait**: controllers that define `$service`, `$resource` and `$request` get standard `index`, `show`, `store`, `update`, `destroy`, using the injected service and responding with JSON Resource.

For new multitenant resources: create the model with `HasCompany`, the service implementing `ServiceContract` and the controller using `ControllerTrait` inside the route group with `InitializeCompany`.

---

## Routes and authentication

- **POST** `users/register`: creates company and first user (no auth; payload with `company` and `user`).
- **POST** `users/login`: email/password authentication; returns Sanctum token; uses `withoutGlobalScope(CompanyScope::class)` to find the user in any tenant.
- Routes in the `auth:sanctum` + `InitializeCompany` group: user CRUD (and future resources) in the context of the logged-in user's company.

Recommendation: send the token in the `Authorization: Bearer {token}` header on all requests that require a tenant.

---

## Developer summary

| Concept           | Role                                                                 |
|-------------------|----------------------------------------------------------------------|
| **CompanyScope**   | Automatically filters by `company_id` on models with `HasCompany`. |
| **HasCompany**     | Applies the scope and sets `company_id` on `creating`.               |
| **CompanyContext** | Centralizes set/get/clear and `runFor`/`runForAll`; do not set company manually. |
| **CompanySingleton** | Adapter `app('company')` that delegates to CompanyContext.      |
| **InitializeCompany** | Sets the company in the context (HTTP) and clears it at the end of the request. |
| **CompanyAwareJob**   | Jobs that run in a company context; implement only `execute()`.     |
| **CompanyRunner**     | `CompanyRunner::forAll()` to run logic per company in commands/Scheduler. |
| **ServiceContract / ServiceTrait** | Generic CRUD respecting the context tenant.        |

To add a new multitenant entity: migration with `company_id`, model with `HasCompany`, optional service and controller in routes that use `InitializeCompany`. Isolation and `company_id` assignment are centralized in the trait and the context.

---

## Company Context Architecture

The company context **must not be set manually** by the developer. All execution (HTTP, queues, CLI) should go through the infrastructure described below. This avoids context leakage and scattered responsibility.

### Why not set company manually

- **Context leakage**: in queue workers or long-running commands, forgetting to clear the context makes the next execution "see" the previous company.
- **Manual discipline**: Jobs and commands that depend on the company would each need to call `app('company')->registerCompany($company)` and clear at the end — error-prone.
- **Centralization**: the context lifecycle (set, run, clear) lives in one place, with isolation guarantees.

### CompanyContext

The class `App\Modules\Company\Domain\Context\CompanyContext` centralizes company context management:

| Method | Description |
|--------|-------------|
| `set(Company $company)` | Sets the current company (internal infrastructure use). |
| `get(): ?Company` | Returns the company in the context or `null`. |
| `id(): ?int` | Returns the current company ID or `null`. |
| `clear(): void` | Removes the company from the context. |
| `runFor(Company $company, Closure $callback)` | Sets the company, runs the callback and **always** clears the context in `finally`. |
| `runForAll(Closure $callback)` | Iterates all companies in chunks, running the callback inside `runFor` for each (avoids memory spikes). |

State is stored in the context instance (resolved from the container), and the context is cleared after each use in HTTP (middleware), Jobs and CLI, so there is no leakage between executions.

### CompanyAwareJob (queues)

Jobs that must run in a company context should extend `App\Modules\Company\Domain\Jobs\CompanyAwareJob`:

- The constructor receives `int $companyId`.
- In `handle()`, the infrastructure loads the company, calls `CompanyContext::runFor($company, ...)` and runs the abstract `execute()` method.
- The developer implements only `execute()`; no need to set or clear the context.

Example job:

```php
use App\Modules\Company\Domain\Jobs\CompanyAwareJob;

class MyReportJob extends CompanyAwareJob
{
    protected function execute(): void
    {
        // Context is already set here; models with HasCompany use this company.
        ReportService::generate();
    }
}
```

Dispatch (always pass the company ID):

```php
MyReportJob::dispatch($company->id);
```

The project includes an example job: `App\Modules\Company\Domain\Jobs\ExampleCompanyJob` (implements only an empty `execute()`). Dispatch: `ExampleCompanyJob::dispatch($company->id);`

### CompanyRunner (commands / Scheduler)

To run logic **per company** in Artisan commands or the Scheduler, use `CompanyRunner::forAll`:

```php
use App\Modules\Company\Domain\Runner\CompanyRunner;

CompanyRunner::forAll(function () {
    app(MyService::class)->process();
});
```

The callback runs for each company with context set and cleared after each (using chunks so all companies are not loaded into memory). No need to iterate companies manually or call `registerCompany`/`clear`.

Example in an Artisan command:

```php
use App\Modules\Company\Domain\Runner\CompanyRunner;
use Illuminate\Console\Command;

class ProcessAllCompaniesCommand extends Command
{
    public function handle(): int
    {
        CompanyRunner::forAll(function () {
            app(MyService::class)->process();
        });
        return self::SUCCESS;
    }
}
```

### Isolation guarantees

- **HTTP**: the `InitializeCompany` middleware sets the context and calls `clear()` in `finally` when the request ends.
- **Jobs**: `CompanyAwareJob::handle()` uses `runFor()`, which calls `clear()` in `finally` after `execute()`.
- **CLI**: `CompanyRunner::forAll()` uses `runFor()` per company, clearing after each callback.

Thus, the developer **must not** use `app('company')->registerCompany()` manually; all execution should go through this infrastructure (middleware, `CompanyAwareJob` or `CompanyRunner`).

---

## License

This project is open-source and may be used according to the base project license (e.g. MIT).
