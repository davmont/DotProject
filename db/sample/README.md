# Sample data: Brightline Consulting

A small consultancy and its clients, to show how dotProject is used day to day. Every company and person is fictional.

## Loading it

Into a fresh installation only (the script refuses if the database already has projects; `--force` adds the data anyway):

    php db/sample/sample-data.php          # from the dotProject directory, as the web server user
    make sample-data                       # the docker/ sandbox: reinstall the database, then load

Dates are relative to the day you load it, so the projects are always under way. Reload it (fresh database) to move "today".

## Who is who

| Login | Person | Role |
|---|---|---|
| `admin` (your administrator password) | Alex Rivera, Managing Partner | Administrator; owns the internal and audit projects |
| `priya` | Priya Nair, Project Manager | Runs the ACME and Dundler Mifflin projects |
| `marco` | Marco Bianchi, Solutions Architect | Owns the Harbor & Vine and forecasting projects |
| `hannah` | Hannah Okafor, Business Analyst | Owns the Quill City project |
| `tomas`, `lena` | Developer, UX Designer | Assigned to tasks |

Consultants' password: `demo1234`. Client contacts (Dana Whitfield and Raj Patel at ACME, Gloria Benton and Owen Price at Dundler Mifflin, Sofia Lindqvist, Martin Abara) are contacts, not users.

## The projects

| Client | Project | Shows |
|---|---|---|
| ACME Corporation | Warehouse Automation Rollout | A phased project in progress: dynamic parent tasks, milestones, dependencies, shared assignments, time logs |
| ACME Corporation | Safety Compliance Audit 2026 | A completed project |
| ACME Corporation | Customer Portal Redesign | A proposal (status Proposed, nothing started) |
| Dundler Mifflin Paper Company | Scranton Branch CRM Migration | A project behind schedule: overdue tasks (red), tasks that should have started (yellow), a logged problem |
| Dundler Mifflin Paper Company | Paper Inventory Forecasting | A project on hold |
| Harbor & Vine Logistics | Fleet Route Optimization | Early stage, with a go / no-go milestone |
| Quill City Library Network | Digital Catalogue Upgrade | In planning |
| Brightline Consulting | Consultant Knowledge Base | An internal project |

## A short tour

1. Log in as `priya`. **Projects** lists her three projects with progress, the late one's end date highlighted; **Gantt** shows the whole portfolio around today.
2. Open **Warehouse Automation Rollout**: the **Tasks** tab shows the phases (dynamic tasks take their dates and progress from their children), the **Gantt Chart** tab the dependencies between them, **Task Logs** the hours each consultant booked.
3. Open **Scranton Branch CRM Migration** to see how a slipping project looks, and its forum for the discussion about duplicate customers.
4. **Calendar** has the weekly ACME status meetings, workshops and the go-live readiness review; **Todo** lists Priya's own tasks and milestones.
5. Log a few hours on a task (**Log** in the task list) and watch the task and project progress change.
