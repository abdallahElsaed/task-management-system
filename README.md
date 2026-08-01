# Task Management System API

A REST API for managing projects and the tasks inside them. Each account holder sees
only their own data: projects, the tasks belonging to those projects, a dashboard that
counts both, and a daily email naming anything that has passed its due date.

Everything is JSON. There is no user interface.

**What it does**

- Register, log in and log out with Laravel Sanctum tokens.
- Full CRUD on projects, each one `active`, `completed` or `archived`.
- Full CRUD on tasks, each with a priority, a status and a due date, filterable by
  status, priority and project, and searchable by title.
- A dashboard endpoint returning six counts in one read.
- A queued job that emails each account holder their newly overdue tasks once a day.

**How it is put together**

- Controllers are thin: they take the request, hand it to a service, and return a
  resource. No business logic lives in them.
- Business rules live in services under `app/Services`.
- Every database read and write goes through a repository — an interface in
  `app/Contracts/Repositories` bound to an implementation in `app/Repositories`.
- Validation lives in form requests, output shape in API resources, and every
  response body is built by `App\Http\Support\ApiResponse` so the envelope is
  identical everywhere.
- One exception handler in `bootstrap/app.php` turns every failure into that same
  envelope, so nothing ever returns HTML or a stack trace.

---

## Requirements

The versions this was built and tested against:

| | Version |
|---|---|
| PHP | 8.4.22 (8.3 or newer) |
| Laravel | 13.23.0 |
| MySQL | 9.5.0 (nothing in the schema needs anything newer than MySQL 8.0) |
| Composer | 2.10.1 |
| Sanctum | 4.3.3 |
| Pest | 5.0.2 |

PHP needs the `pdo_mysql`, `pdo_sqlite`, `mbstring` and `openssl` extensions.
`pdo_sqlite` is only for the test suite, which runs on an in-memory SQLite database
and never touches MySQL.

Node is not needed. The API serves no assets.

---

## Installation Steps

From a clean clone, in order:

**1. Install the PHP dependencies**

```bash
composer install
```

**2. Create your environment file**

```bash
cp .env.example .env
```

**3. Generate the application key**

```bash
php artisan key:generate
```

**4. Create the database**

```bash
mysql -u root -p -e "CREATE DATABASE task_management_sys CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
```

Then open `.env` and set `DB_DATABASE`, `DB_USERNAME` and `DB_PASSWORD` to match.
See [Environment Setup](#environment-setup) for the values that matter.

**5. Build the schema and fill it with sample data**

```bash
php artisan migrate --seed
```

If you would rather load a database than run migrations, see
[Loading the SQL dump instead](#loading-the-sql-dump-instead).

**6. Start the API**

```bash
php artisan serve
```

The API is then at `http://localhost:8000/api/v1`. Every endpoint lives under that
prefix; the root URL is Laravel's stock welcome page and has nothing to do with the
API.

**7. Check it works**

```bash
curl -X POST http://localhost:8000/api/v1/login \
  -H "Accept: application/json" \
  -H "Content-Type: application/json" \
  -d '{"email":"demo@example.com","password":"password123"}'
```

A `200` carrying a token means you are done.

---

## Environment Setup

`.env.example` carries every variable the project reads and no real credential. Copy
it, then set the handful that matter:

| Variable | Set it to | Why |
|---|---|---|
| `APP_KEY` | whatever `php artisan key:generate` writes | Nothing runs without it |
| `APP_URL` | the address you serve on | Used when building absolute URLs |
| `APP_DEBUG` | `true` locally, `false` otherwise | With `false`, an unanticipated failure answers `500` with a generic message and no stack trace |
| `DB_DATABASE` | your database name | Defaults to `task_management_sys` |
| `DB_USERNAME` / `DB_PASSWORD` | your MySQL credentials | The example file ships with an empty password |
| `QUEUE_CONNECTION` | `database` | The overdue digest is queued. Set it to `sync` to send inline instead of running a worker |
| `MAIL_MAILER` | `log` | Writes the overdue email into `storage/logs/laravel.log` rather than sending it, which is what you want locally |

Nothing else needs changing to run the project.

### The queued overdue notification

The sweep that finds newly overdue tasks is a scheduled command, and the emails it
produces go through the queue.

```bash
php artisan queue:work        # process the queued digests
php artisan tasks:notify-overdue   # run the sweep by hand, any time
```

Scheduled, it runs daily at 08:00 in the application's timezone. Running it twice on
the same day is safe: a task is reported once and never again, so the second run finds
nothing and sends nothing.

With `MAIL_MAILER=log`, the resulting email lands in `storage/logs/laravel.log`.

---

## Seeder with sample data

```bash
php artisan db:seed
```

Running it a second time rebuilds the sample data rather than duplicating it, and the
demo login keeps working.

### Demo account

```
email:    demo@example.com
password: password123
```

Log in with it and you have data to look at immediately. A second account,
`other@example.com` / `password123`, owns data of its own so you can check the
ownership rule by hand: ask for one of its projects while logged in as the demo
account and you get a `404`, exactly as you would for a project that never existed.

### What the demo account holds

**4 projects**, covering all three statuses:

| Project | Status | Tasks |
|---|---|---|
| Website Redesign | `active` | 8 |
| Mobile App Launch | `active` | 7 |
| Q2 Marketing Campaign | `completed` | 6 |
| Legacy Portal Migration | `archived` | 4 |

**25 tasks**, covering all three priorities and all three statuses:

| | |
|---|---|
| By status | 9 done, 5 in progress, 11 todo |
| By priority | 9 high, 9 medium, 7 low |
| Overdue | 5 |

Some titles are written in Arabic, so you can check the search and the digest email
against a non-Latin script.

Due dates are seeded as offsets from the day you run the seeder, so the overdue tasks
stay overdue however long afterwards you look. The dashboard therefore returns six
numbers above zero on the first call:

```json
{
    "total_projects": 4,
    "active_projects": 2,
    "total_tasks": 25,
    "completed_tasks": 9,
    "pending_tasks": 16,
    "overdue_tasks": 5
}
```

### Loading the SQL dump instead

`database/dump/task_management_sys.sql` is a dump of that same seeded database —
the same schema the migrations build, with the sample data already in it.

```bash
mysql -u root -p task_management_sys < database/dump/task_management_sys.sql
```

Create the database first; the dump does not create it, so the name is yours to
choose. Either route — migrations or dump — leaves you with identical tables and
columns.

One difference in the data: the dump holds the due dates as they were on the day it
was exported, so the further in the future you load it the more tasks will have
drifted past their due date. Run `php artisan db:seed` afterwards if you want the
dates measured from today again.

---

## Running the tests

```bash
php artisan test
```

Or, for a compact summary:

```bash
php artisan test --compact
```

238 tests covering the rules of every module. They run against an in-memory SQLite
database, so they need no setup beyond the command above, they never touch your MySQL
database, and running them twice in a row gives the same result both times.

To run one file or one test:

```bash
php artisan test --filter=ShowDashboardTest
```

---

## API Documentation

### Conventions

**Base URL** — `http://localhost:8000/api/v1`, or wherever you are serving from.

**Headers** — send `Accept: application/json` on every request, and
`Content-Type: application/json` on anything with a body.

**Authenticating** — register or log in, take the `token` out of the response, and
send it on every other endpoint:

```
Authorization: Bearer 2|7QKsYcNnwU38kwVRzkSRvEpLl35O39MbcGMuncIC578bbe1e
```

Only `register` and `login` are reachable without one.

**Ownership** — you only ever see your own projects and tasks. Asking for a record
belonging to another account answers `404`, exactly as asking for one that never
existed does. Nothing distinguishes the two.

**Every success** looks like this:

```json
{ "success": true, "data": { } }
```

with `message` added when there is something to say, and `meta` on the paginated
lists:

```json
{
    "success": true,
    "data": [],
    "meta": { "current_page": 1, "per_page": 15, "total": 4, "last_page": 1 }
}
```

**Every failure** looks like this:

```json
{ "success": false, "message": "Project not found." }
```

with an `errors` object keyed by field name when the failure was validation. Every
failing field is reported at once, never one at a time:

```json
{
    "success": false,
    "message": "The name field must be at least 2 characters. (and 3 more errors)",
    "errors": {
        "name": ["The name field must be at least 2 characters."],
        "email": ["The email field must be a valid email address."],
        "password": [
            "The password must be at least 8 characters.",
            "The password confirmation does not match."
        ]
    }
}
```

**Status codes**

| Code | Means |
|---|---|
| `200` | A successful read, update, delete or logout |
| `201` | A successful create or register |
| `401` | No token, an invalid or revoked token, or a failed login |
| `404` | No such record of yours, or no such endpoint |
| `405` | The endpoint does not accept that method |
| `422` | Validation failed on a body field or a query filter |
| `429` | Login throttled |
| `500` | An unanticipated failure — a generic message, never a stack trace |

**Enumerations**

| | Values |
|---|---|
| Project status | `active`, `completed`, `archived` |
| Task priority | `low`, `medium`, `high` |
| Task status | `todo`, `in_progress`, `done` |

### Endpoints at a glance

| Method | Path | Auth | What it does |
|---|---|---|---|
| `POST` | `/register` | — | Create an account, get a token |
| `POST` | `/login` | — | Get a token |
| `POST` | `/logout` | ✔ | Revoke the token you sent |
| `GET` | `/projects` | ✔ | List your projects |
| `POST` | `/projects` | ✔ | Create a project |
| `GET` | `/projects/{id}` | ✔ | View a project |
| `PUT` `PATCH` | `/projects/{id}` | ✔ | Update a project |
| `DELETE` | `/projects/{id}` | ✔ | Delete a project |
| `GET` | `/tasks` | ✔ | List, filter and search your tasks |
| `POST` | `/tasks` | ✔ | Create a task |
| `GET` | `/tasks/{id}` | ✔ | View a task |
| `PUT` `PATCH` | `/tasks/{id}` | ✔ | Update a task |
| `DELETE` | `/tasks/{id}` | ✔ | Delete a task |
| `GET` | `/dashboard` | ✔ | Six counts in one read |

---

### Authentication

#### `POST /register`

Creates an account and issues its first token, so there is no need to log in straight
afterwards.

| Field | Rules |
|---|---|
| `name` | required, 2–255 characters |
| `email` | required, a valid address, not already registered |
| `password` | required, at least 8 characters, must match `password_confirmation` |
| `password_confirmation` | required, must match `password` |

```json
{
    "name": "Sara Ali",
    "email": "sara@example.com",
    "password": "secret123",
    "password_confirmation": "secret123"
}
```

**`201`**

```json
{
    "success": true,
    "data": {
        "user": {
            "id": 3,
            "name": "Sara Ali",
            "email": "sara@example.com",
            "created_at": "2026-08-01T17:14:10+00:00"
        },
        "token": "3|pFaSPJjm7CjYVlSv7VZ4jeOCWn9Bky2mUuLrZj7L9633bb61"
    }
}
```

**`422`** — a field was rejected. An address already registered answers
`"This email address is already registered."`

---

#### `POST /login`

| Field | Rules |
|---|---|
| `email` | required, a valid address |
| `password` | required |

```json
{ "email": "demo@example.com", "password": "password123" }
```

**`200`** — the same shape as `register`, with a freshly issued token.

**`401`**

```json
{ "success": false, "message": "These credentials do not match our records." }
```

The wording is identical for an unknown email address and for a wrong password, so
the response never reveals whether an account exists.

**`429`** — six failed attempts on the same email address from the same caller inside
a minute are refused rather than checked. A successful login and a validation failure
do not count towards the limit.

---

#### `POST /logout`

Takes no body. Revokes only the token the request arrived with; tokens issued to the
same account elsewhere keep working.

**`200`**

```json
{ "success": true, "message": "Logged out successfully.", "data": null }
```

**`401`** — no usable token was sent.

---

### Projects

#### `GET /projects`

One page of your projects, newest first, each carrying the number of tasks it holds.

| Query | Rules |
|---|---|
| `per_page` | optional, 1–100, defaults to 15 |

**`200`**

```json
{
    "success": true,
    "data": [
        {
            "id": 4,
            "name": "Legacy Portal Migration",
            "description": "Retire the old customer portal. Parked for now.",
            "status": "archived",
            "task_count": 4,
            "created_at": "2026-08-01T17:14:08+00:00",
            "updated_at": "2026-08-01T17:14:08+00:00"
        }
    ],
    "meta": { "current_page": 1, "per_page": 2, "total": 4, "last_page": 2 }
}
```

An account with no projects gets an empty `data` array and `total: 0`, not a `404`.

**`401`** · **`422`** — `per_page` outside 1–100.

---

#### `POST /projects`

The owner is taken from your token and is never read from the body.

| Field | Rules |
|---|---|
| `name` | required, 3–255 characters |
| `description` | optional, up to 2000 characters |
| `status` | optional, one of the three project statuses, defaults to `active` |

```json
{
    "name": "Website Redesign",
    "description": "Rebuild the marketing site on the new design system.",
    "status": "active"
}
```

**`201`**

```json
{
    "success": true,
    "data": {
        "id": 6,
        "name": "Website Redesign",
        "description": "Rebuild the marketing site on the new design system.",
        "status": "active",
        "task_count": 0,
        "created_at": "2026-08-01T17:14:10+00:00",
        "updated_at": "2026-08-01T17:14:10+00:00"
    }
}
```

**`401`** · **`422`**

---

#### `GET /projects/{id}`

**`200`** — the project, in the shape above.

**`401`** · **`404`**

```json
{ "success": false, "message": "Project not found." }
```

---

#### `PUT` / `PATCH` `/projects/{id}`

Send only the fields you are changing. Both methods behave identically.

| Field | Rules |
|---|---|
| `name` | optional, 3–255 characters when sent |
| `description` | optional, up to 2000 characters, may be `null` |
| `status` | optional, one of the three project statuses |

```json
{ "status": "completed" }
```

**`200`** — the project as it now stands.

**`422`** — a body carrying none of the three fields is rejected rather than treated
as a no-op:

```json
{
    "success": false,
    "message": "Send at least one of: name, description, status.",
    "errors": { "project": ["Send at least one of: name, description, status."] }
}
```

**`401`** · **`404`**

---

#### `DELETE /projects/{id}`

A soft delete. The project's tasks go with it, out of every list and every dashboard
count.

**`200`**

```json
{ "success": true, "message": "Project deleted successfully.", "data": null }
```

**`401`** · **`404`**

---

### Tasks

#### `GET /tasks`

One page of your tasks across every project you own, soonest due first. The project is
a filter rather than a path segment, so one call reaches all of them.

| Query | Rules |
|---|---|
| `status` | optional, one of the three task statuses |
| `priority` | optional, one of the three priorities |
| `project_id` | optional, must be a project you own |
| `search` | optional, up to 255 characters — matches anywhere in the title, ignoring case, in any script |
| `per_page` | optional, 1–100, defaults to 15 |

The filters combine: sending several narrows the list rather than widening it.

```
GET /tasks?status=todo&priority=high&search=beta
```

**`200`**

```json
{
    "success": true,
    "data": [
        {
            "id": 11,
            "project_id": 2,
            "title": "Ship the beta to TestFlight",
            "description": "Internal testers first, then the waiting list.",
            "priority": "high",
            "status": "todo",
            "due_date": "2026-07-31",
            "is_overdue": true,
            "created_at": "2026-08-01T17:14:08+00:00",
            "updated_at": "2026-08-01T17:14:08+00:00"
        }
    ],
    "meta": { "current_page": 1, "per_page": 2, "total": 1, "last_page": 1 }
}
```

An empty page still answers `200`, with a `message` saying why — either
`"You have no tasks yet."` or `"No tasks match these filters."`

**`422`** — a filter value outside its allowed set rejects the whole request rather
than being ignored, so no page comes back at all:

```json
{
    "success": false,
    "message": "The status must be one of: todo, in_progress, done.",
    "errors": { "status": ["The status must be one of: todo, in_progress, done."] }
}
```

**`401`**

---

#### `POST /tasks`

| Field | Rules |
|---|---|
| `project_id` | required, must be a project you own |
| `title` | required, 3–255 characters |
| `description` | optional, up to 2000 characters |
| `priority` | optional, one of the three priorities, defaults to `medium` |
| `status` | optional, one of the three task statuses, defaults to `todo` |
| `due_date` | required, `YYYY-MM-DD` |

A `project_id` belonging to another account is rejected as an unknown project, in the
same words as one that does not exist. A due date in the past is accepted: that is a
legitimate record of work already late.

```json
{
    "project_id": 1,
    "title": "Write the README",
    "priority": "high",
    "due_date": "2026-09-30"
}
```

**`201`**

```json
{
    "success": true,
    "data": {
        "id": 29,
        "project_id": 1,
        "title": "Write the README",
        "description": null,
        "priority": "high",
        "status": "todo",
        "due_date": "2026-09-30",
        "is_overdue": false,
        "created_at": "2026-08-01T17:14:10+00:00",
        "updated_at": "2026-08-01T17:14:10+00:00"
    }
}
```

**`401`** · **`422`**

---

#### `GET /tasks/{id}`

**`200`** — the task, in the shape above.

**`401`** · **`404`**

```json
{ "success": false, "message": "Task not found." }
```

---

#### `PUT` / `PATCH` `/tasks/{id}`

Send only the fields you are changing. The parent project is fixed at creation and
cannot be changed here.

| Field | Rules |
|---|---|
| `title` | optional, 3–255 characters when sent |
| `description` | optional, up to 2000 characters, may be `null` |
| `priority` | optional, one of the three priorities |
| `status` | optional, one of the three task statuses |
| `due_date` | optional, `YYYY-MM-DD` |

```json
{ "status": "done" }
```

**`200`** — the task as it now stands. Marking it `done` sets `is_overdue` to `false`
whatever its due date.

**`422`** — a body carrying none of the five fields is rejected:
`"Send at least one of: title, description, priority, status, due_date."`

**`401`** · **`404`**

---

#### `DELETE /tasks/{id}`

A soft delete. The task leaves every list and every dashboard count; the project it
belonged to is untouched.

**`200`**

```json
{ "success": true, "message": "Task deleted successfully.", "data": null }
```

**`401`** · **`404`**

---

### Dashboard

#### `GET /dashboard`

Six counts describing everything you own. Takes no input at all — your token is the
whole query.

**`200`**

```json
{
    "success": true,
    "data": {
        "total_projects": 4,
        "active_projects": 2,
        "total_tasks": 25,
        "completed_tasks": 9,
        "pending_tasks": 16,
        "overdue_tasks": 5
    }
}
```

| Count | Means |
|---|---|
| `total_projects` | Every project you own |
| `active_projects` | Those with status `active` |
| `total_tasks` | Every task across all of them |
| `completed_tasks` | Tasks with status `done` |
| `pending_tasks` | Tasks with status `todo` or `in_progress` |
| `overdue_tasks` | Tasks past their due date that are not `done` |

`pending_tasks` and `overdue_tasks` overlap on purpose: an overdue task is still
pending. A brand new account gets six zeroes rather than an empty body.

**`401`**

---

### Errors that belong to no endpoint

**An address that matches no endpoint** — `404`

```json
{ "success": false, "message": "Endpoint not found." }
```

**A method the endpoint does not accept** — `405`

```json
{ "success": false, "message": "This method is not allowed for this endpoint." }
```

**Anything unanticipated, with `APP_DEBUG=false`** — `500`

```json
{ "success": false, "message": "Something went wrong. Please try again." }
```

No stack trace, no file path, no HTML, and never a redirect to a login page.

---

## Postman Collection

`postman/task-management-system.postman_collection.json`

Import it into Postman with **Import → File**. It holds every endpoint above, grouped
into four folders — Authentication, Projects, Tasks and Dashboard.

**Point it at your API.** Open the collection's **Variables** tab and set `base_url`.
That single variable is the only thing you should ever need to change; every request
is built from it.

```
base_url = http://localhost:8000/api/v1
```

**The token carries itself.** Run **Authentication → Log in** first — the demo
credentials are already filled in. Its test script stores the returned token in a
collection variable, and every other request picks it up as a bearer token from the
collection's Authorization tab. You never copy a token by hand.

**Run it top to bottom.** The whole collection works in order, straight through:
requests that need an id pick one up from the list request before them, and logging
out fetches a fresh token so everything after it still works.

Several requests carry optional query parameters switched off in the query tab — tick
them on to see the filters combine.

---

## Project layout

```
app/
├── Console/Commands/        The scheduled overdue sweep
├── Constants/               One class per module holding its user-facing wording
├── Contracts/Repositories/  Repository interfaces, bound with #[Bind]
├── DTOs/                    Structured data passed between layers
├── Enums/                   Project status, task status, task priority
├── Exceptions/              Domain exceptions, mapped to status codes in bootstrap/app.php
├── Http/
│   ├── Controllers/Api/V1/  Thin: request in, service call, resource out
│   ├── Requests/            Validation
│   ├── Resources/           Output shape
│   └── Support/             ApiResponse — the one place a response body is built
├── Jobs/                    The queued overdue digest
├── Mail/                    The digest email
├── Models/                  User, Project, Task
├── Repositories/            The only place Eloquent is touched
└── Services/                The business rules

database/
├── dump/                    The seeded database as SQL
├── factories/               One per model, with named states
├── migrations/              The only thing that changes the schema
└── seeders/                 The demo account and its data

postman/                     The collection
tests/Feature/               238 tests, one folder per module
```
