# API Reference

Every endpoint of the Task Management System API. For setup, see the
[README](../README.md).

## Conventions

**Base URL** — `http://localhost:8000/api/v1`, or wherever you are serving from.

**Headers** — send `Accept: application/json` on every request, and
`Content-Type: application/json` on anything with a body.

**Authenticating** — register or log in, take the `token` out of the response, and
send it on every other endpoint:

```
Authorization: Bearer 2|7QKsYcNnwU38kwVRzkSRvEpLl35O39MbcGMuncIC578bbe1e
```

Of the endpoints below, only `register` and `login` are reachable without one.

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
| `429` | Too many login or registration attempts |
| `500` | An unanticipated failure — a generic message, never a stack trace |

**Enumerations**

| | Values |
|---|---|
| Project status | `active`, `completed`, `archived` |
| Task priority | `low`, `medium`, `high` |
| Task status | `todo`, `in_progress`, `done` |

## Endpoints at a glance

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
| `GET` | `/dashboard` | ✔ | Six counts in one request |

---

## Authentication

### `POST /register`

Creates an account and issues its first token, so there is no need to log in straight
afterwards.

| Field | Rules |
|---|---|
| `name` | required, 2–255 characters |
| `email` | required, a valid address, not already registered |
| `password` | required, at least 8 characters, must match `password_confirmation` |
| `password_confirmation` | must be sent and match `password` |

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

**`429`** — more than 10 attempts from the same IP address inside a minute. Every
attempt counts, including rejected ones. Behind a load balancer, configure trusted
proxies, or every caller shares one limit.

```json
{ "success": false, "message": "Too many registration attempts. Please try again in 42 seconds." }
```

---

### `POST /login`

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

**`429`** — after five failed attempts on the same email address from the same caller
inside a minute, further attempts are refused rather than checked, even with the
correct password. A successful login and a validation failure do not count towards
the limit.

---

### `POST /logout`

Takes no body. Revokes only the token the request arrived with; tokens issued to the
same account elsewhere keep working.

**`200`**

```json
{ "success": true, "message": "Logged out successfully.", "data": null }
```

**`401`** — no usable token was sent.

---

## Projects

### `GET /projects`

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

### `POST /projects`

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

### `GET /projects/{id}`

**`200`** — the project, in the shape above.

**`401`** · **`404`**

```json
{ "success": false, "message": "Project not found." }
```

---

### `PUT` / `PATCH` `/projects/{id}`

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

### `DELETE /projects/{id}`

A soft delete. The project's tasks go with it, out of every list and every dashboard
count.

**`200`**

```json
{ "success": true, "message": "Project deleted successfully.", "data": null }
```

**`401`** · **`404`**

---

## Tasks

### `GET /tasks`

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

### `POST /tasks`

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

### `GET /tasks/{id}`

**`200`** — the task, in the shape above.

**`401`** · **`404`**

```json
{ "success": false, "message": "Task not found." }
```

---

### `PUT` / `PATCH` `/tasks/{id}`

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

### `DELETE /tasks/{id}`

A soft delete. The task leaves every list and every dashboard count; the project it
belonged to is untouched.

**`200`**

```json
{ "success": true, "message": "Task deleted successfully.", "data": null }
```

**`401`** · **`404`**

---

## Dashboard

### `GET /dashboard`

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

## Errors that belong to no endpoint

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
