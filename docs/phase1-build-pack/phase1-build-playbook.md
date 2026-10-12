# Phase 1 Build Playbook

## Build a Working, Pitchable MVP for the AI Work Verification Platform

This document is the execution playbook for building a **real, working Phase 1 product** that can be used in demos, customer conversations, and investor pitches.

It covers:
- exact Phase 1 scope
- recommended architecture
- coding plan
- UI plan
- database plan
- infrastructure plan
- delivery responsibilities for the team
- how Cursor should be used
- build commands and prompt/command sequences
- expected challenges and practical solutions

---

## 1. What Phase 1 Must Be

Phase 1 should not try to be the whole company vision.

It should be a **working operational wedge** that proves:

1. tools can connect
2. tasks, users, and time logs can be imported
3. logged work can be verified against activity signals
4. flagged vs approved work can be reviewed
5. invoice drafts can be generated from approved work

### Phase 1 promise
**Connect PM and time systems, verify hours against work evidence, and generate invoice drafts from approved records.**

### Why this is the correct Phase 1
- clear value
- easy to demo
- narrow enough to ship fast
- strong beta story
- strong investor story

---

## 2. The Exact Working Demo You Want

At the end of Phase 1, a demo should look like this:

1. Admin signs into the platform
2. Admin creates a workspace
3. Admin connects:
   - Jira or ClickUp
   - Harvest or Tempo
   - QuickBooks or Xero
4. Platform imports:
   - users
   - projects
   - tasks
   - time entries
5. Platform runs verification rules and assigns:
   - verified
   - needs review
   - flagged
6. Manager opens dashboard and sees:
   - total hours
   - verified hours
   - flagged hours
   - top anomalies
7. Finance user opens invoice draft screen
8. Approved hours are converted into invoice line items
9. Platform generates:
   - draft invoice
   - work summary
   - audit explanation for each line

If this works cleanly, the MVP is pitchable.

---

## 3. Recommended Phase 1 Tech Stack

The stack should prioritize:
- speed of delivery
- developer productivity
- clean demos
- simple maintainability
- low infrastructure burden

## Frontend
- Next.js
- TypeScript
- Tailwind CSS
- shadcn/ui
- React Query or TanStack Query

## Backend API
- NestJS
- TypeScript
- REST APIs
- Zod or class-validator for validation

## Database
- PostgreSQL
- Prisma ORM

## Background Jobs
- Redis
- BullMQ

## Auth
- Clerk or Auth0

## File Storage
- AWS S3 or MinIO for local/dev

## Deployment
- Vercel for web
- Railway/Render/Fly.io for API and workers
- Neon or RDS for PostgreSQL
- Upstash or Redis Cloud for Redis

## Why this stack
- monorepo-friendly
- modern and fast to build
- easy for Cursor to help with
- low DevOps complexity
- strong enough for real beta use

---

## 4. Architecture for Phase 1

## Core components

### Web App
Used by admins, managers, and finance users.

### API Service
Handles:
- auth context
- workspaces
- integrations
- sync orchestration
- verification logic
- invoice draft creation

### Worker Service
Handles:
- connector sync jobs
- webhook processing
- verification scoring jobs
- invoice generation jobs

### Database
Stores:
- organizations
- users
- mapped identities
- projects
- tasks
- time entries
- activity evidence
- verification results
- invoice drafts
- invoice lines
- audit logs

### Storage
Stores:
- imported files
- exported invoice PDFs later
- demo attachments if needed

---

## 5. Recommended Repository Structure

Use a monorepo.

```text
phase1-platform/
  apps/
    web/
    api/
    worker/
  packages/
    ui/
    config/
    types/
    sdk/
  infra/
    docker/
    scripts/
  docs/
```

### Why this matters
- shared types
- reusable UI
- clean code ownership
- easier Cursor-generated changes

---

## 6. Phase 1 Feature Scope

## Must build
- auth and workspace setup
- organization/project/user data model
- integration connection management
- PM sync
- time-entry sync
- identity mapping
- deterministic verification engine
- review queue
- invoice draft builder
- audit trail
- admin dashboard

## Nice to have only if time allows
- GitHub signal integration
- AI-generated work summaries
- invoice PDF export
- simple notifications

## Do not build in Phase 1
- OCR pipeline
- full email ingestion
- external market benchmarks
- complex contract billing rules
- full accounting write-back automation

---

## 7. Delivery Tracks for the Team

Split execution into workstreams.

## Track A - Product and UX
Owner: product/design

### Responsibilities
- user flows
- dashboard layout
- onboarding UX
- review queue UX
- invoice draft UX
- empty/loading/error states

## Track B - Platform and Infrastructure
Owner: platform/backend lead

### Responsibilities
- monorepo setup
- environments
- CI/CD
- database
- queues
- secrets
- deployment

## Track C - Backend and Data
Owner: backend team

### Responsibilities
- database schema
- APIs
- verification engine
- invoice engine
- audit trail
- tenant isolation

## Track D - Integrations
Owner: integrations/backend

### Responsibilities
- Jira/ClickUp sync
- Harvest/Tempo sync
- QuickBooks/Xero connector shell
- OAuth flow
- sync jobs
- idempotency

## Track E - Frontend App
Owner: frontend team

### Responsibilities
- auth flow
- setup screens
- dashboard
- verification review UI
- invoice draft UI
- workspace and integration screens

## Track F - Demo and Pitch Readiness
Owner: founder/product

### Responsibilities
- seed demo data
- build clean walkthrough
- prepare screenshots
- define core demo scenario

---

## 8. Exact Screens to Build

## 1. Sign-in / Workspace Setup
- sign in
- create workspace
- add company name
- choose PM tool
- choose time tool

## 2. Integrations Page
- connected status
- connect button
- sync status
- last sync time
- reconnect/error states

## 3. Dashboard
- total imported hours
- verified hours
- flagged hours
- pending review count
- invoiceable hours
- top anomalies

## 4. Verification Review Queue
- table of flagged items
- filters by user/project/date/status
- evidence drawer
- approve/reject/manual note

## 5. Project Detail Page
- project summary
- hours by user
- flagged items
- invoice-ready status

## 6. Invoice Draft Page
- invoice header
- line items
- rate
- hours
- amount
- supporting work summary
- approve/export action

## 7. Audit Log / Activity View
- what was imported
- what rules ran
- what changed
- who approved what

---

## 9. Database Design

Use a multi-tenant schema with strict workspace ownership.

## Core tables
- organizations
- workspaces
- users
- workspace_members
- external_accounts
- integrations
- external_identities
- projects
- tasks
- time_entries
- activity_events
- verification_results
- verification_reasons
- invoice_drafts
- invoice_lines
- audit_logs

## Important schema principles

### Principle 1 - Separate internal users from external identities
One user may appear differently in Jira, Harvest, and accounting tools.

### Principle 2 - Every imported record must keep source metadata
Store:
- provider
- external id
- sync timestamp
- raw payload snapshot if possible

### Principle 3 - Verification should be explainable
Do not store only a score.
Store:
- status
- score
- triggered rules
- explanation text

### Principle 4 - Invoice lines must reference source work
Every draft invoice line should map back to:
- approved time entries
- user/role
- project/task
- rate source

---

## 10. Core Backend Modules

Build the API using modules.

## Modules
- auth
- workspaces
- integrations
- sync
- users
- projects
- tasks
- time-entries
- verification
- invoices
- audit
- health

## Worker job groups
- sync-jira
- sync-harvest
- identity-mapping
- verification-run
- invoice-draft-build
- retry-failed-sync

---

## 11. Verification Engine Design

Do not start with AI-first verification.

Phase 1 should use deterministic rules.

## Example rules
- time entry has linked task -> +score
- task updated during work window -> +score
- task has comments/activity -> +score
- duplicate hours on same user/time block -> -score
- very high hours with low supporting activity -> -score
- task closed before time was logged -> flag
- missing user mapping -> flag

## Output statuses
- verified
- needs_review
- flagged

## Why this is the right approach
- explainable
- easier to debug
- credible to finance teams
- stronger for early demos

---

## 12. Invoice Draft Engine Design

Phase 1 invoice creation should be simple.

## Inputs
- approved time entries
- workspace billing rules
- project/client mapping
- user rate or role rate

## Outputs
- invoice draft
- invoice lines
- summary note
- total amount

## Keep billing logic simple in Phase 1
Support:
- hourly rate by user
- hourly rate by role
- basic project/client linkage

Do not support yet:
- advanced retainers
- milestone contracts
- complex blended pricing engines

---

## 13. Infrastructure Plan

## Local development
Use Docker Compose for:
- postgres
- redis
- minio

## Staging / demo
Use managed services:
- web on Vercel
- api on Railway or Render
- worker on Railway or Render
- postgres on Neon
- redis on Upstash
- storage on S3

## Production direction later
Can move to:
- AWS ECS/Fargate
- RDS
- ElastiCache
- S3
- CloudWatch / Datadog

### Why not overbuild infra now
Phase 1 needs speed, not premature enterprise complexity.

---

## 14. Coding Challenges and Solutions

## Challenge 1 - External identity mapping is messy

### Problem
The same person appears differently across tools.

### Solution
- create external_identities table
- allow manual merge/review
- match by email first
- fallback to display-name matching with review flag

---

## Challenge 2 - Connector sync is unreliable

### Problem
APIs fail, paginate differently, and rate-limit.

### Solution
- one provider adapter per tool
- store sync cursors
- make jobs idempotent
- use retry/backoff in workers
- keep last successful sync metadata

---

## Challenge 3 - Verification may look "magical" or untrustworthy

### Problem
Users will distrust opaque scores.

### Solution
- show triggered rules
- show evidence snippet summary
- allow manual override
- store audit log for every change

---

## Challenge 4 - Invoice logic can explode in complexity

### Problem
Billing rules vary heavily across customers.

### Solution
- support only two rate strategies in Phase 1
- design billing_rules table but keep UI simple
- postpone contract complexity to later phases

---

## Challenge 5 - UI can become too dense

### Problem
Verification + invoices + integrations creates clutter.

### Solution
- keep Phase 1 to 5 to 7 core screens
- use progressive disclosure
- show summary first, drill-down second
- keep review queue table central

---

## Challenge 6 - Multi-tenancy and data leakage risk

### Problem
Customer data must never leak across workspaces.

### Solution
- every query filtered by workspace id
- middleware/guards for tenant context
- row ownership checks
- audit admin actions

---

## Challenge 7 - Demo quality can fail if real data is messy

### Problem
The product may work, but demos can feel inconsistent.

### Solution
- seed a polished demo workspace
- use curated fake but realistic data
- preconnect demo integrations using mock provider mode
- prepare one golden-path walkthrough

---

## 15. UI Challenges and Solutions

## Challenge - Confidence must be understandable
### Solution
Show:
- status pill
- score
- top reasons
- link to evidence

## Challenge - Review workflow must be fast
### Solution
Single-click actions:
- approve
- reject
- mark as reviewed
- leave note

## Challenge - Dashboard must be pitch-ready
### Solution
Make the dashboard visually simple:
- big metrics cards
- one anomalies panel
- one verification trend chart
- one invoice-ready panel

---

## 16. Database Challenges and Solutions

## Challenge - Schema drift from many providers
### Solution
Normalize providers into common entities and keep raw provider payloads for debugging.

## Challenge - Duplicate imports
### Solution
Use unique constraints on:
- provider
- external id
- workspace id

## Challenge - Historical auditability
### Solution
Store status transitions and action logs, not only latest state.

## Challenge - Performance of dashboard queries
### Solution
- add summary/materialized counters later
- index workspace_id, project_id, user_id, status, provider
- precompute invoice totals in drafts

---

## 17. Suggested Build Sequence

Build in this order.

## Step 1 - Bootstrap repository

```bash
mkdir phase1-platform
cd phase1-platform
pnpm init
pnpm dlx create-turbo@latest .
```

If you want a lighter setup:

```bash
mkdir phase1-platform
cd phase1-platform
pnpm init
mkdir -p apps/web apps/api apps/worker packages/ui packages/types infra/docker
```

## Step 2 - Create web app

```bash
pnpm dlx create-next-app@latest apps/web --ts --tailwind --eslint --app --src-dir --import-alias "@/*"
```

## Step 3 - Create API app

```bash
pnpm add -D @nestjs/cli
pnpm dlx @nestjs/cli new apps/api --package-manager pnpm
```

## Step 4 - Create worker app

```bash
mkdir -p apps/worker/src
pnpm add bullmq ioredis
```

## Step 5 - Add shared tooling

```bash
pnpm add -w typescript tsx dotenv zod
pnpm add -w -D eslint prettier turbo
pnpm add -w @prisma/client
pnpm add -w -D prisma
```

## Step 6 - Start local infra

```bash
docker compose up -d postgres redis minio
```

## Step 7 - Initialize Prisma

```bash
pnpm prisma init
pnpm prisma migrate dev --name init
```

## Step 8 - Build auth and workspace setup
- auth guard
- workspace creation
- member roles

## Step 9 - Build integration framework
- provider registry
- OAuth/token storage
- sync job creation

## Step 10 - Build normalized entities
- users
- projects
- tasks
- time entries
- external identities

## Step 11 - Build verification engine
- rules
- score
- explanation output
- review actions

## Step 12 - Build invoice draft engine
- rate rules
- line-item generation
- totals
- summary notes

## Step 13 - Build UI flows
- onboarding
- integrations
- dashboard
- review queue
- invoice draft

## Step 14 - Seed demo data

```bash
pnpm tsx scripts/seed-demo.ts
```

## Step 15 - Deploy demo environment
- frontend
- api
- worker
- postgres
- redis
- storage

## Step 16 - Run a golden-path demo
- connect sample workspace
- run sync
- review flagged items
- approve entries
- generate invoice

---

## 18. Recommended Team Workflow

## Product/design team
1. define Phase 1 golden flow
2. create wireframes
3. define status language
4. define review actions
5. define invoice draft layout

## Backend team
1. build workspace model
2. build provider framework
3. build sync jobs
4. build verification engine
5. build invoice engine
6. build audit trail

## Frontend team
1. build app shell
2. build onboarding
3. build integration cards
4. build dashboard
5. build review queue
6. build invoice draft view

## Infra/platform team
1. create environments
2. set up secrets
3. CI/CD
4. logging and health checks
5. deployment scripts

## Founder/demo owner
1. define pitch flow
2. curate demo workspace
3. collect screenshots
4. record product walkthrough

---

## 19. How Cursor Should Be Used

Cursor should be used as an execution accelerator on small, clearly defined chunks.

### Good uses
- scaffold modules
- generate CRUD endpoints
- write Prisma schema drafts
- write UI tables/forms
- build queue handlers
- create validation schemas
- write tests for core rules
- refactor repeated code

### Bad uses
- inventing business rules
- inventing billing assumptions
- designing full architecture from zero without human review
- writing large integration code without API docs or real examples

---

## 20. Cursor Command and Prompt Strategy

Use Cursor in short execution loops:

1. explain exact target
2. point it to existing files
3. ask for one module or screen only
4. ask it to run or suggest tests
5. review output
6. iterate

### Example prompt structure
```text
Build the NestJS verification module for Phase 1.
Requirements:
- workspace-scoped
- deterministic rule engine
- statuses: verified, needs_review, flagged
- explanation payload must list triggered rules
- use Prisma models already present
- add service, controller, DTOs, and unit tests
Do not change unrelated files.
```

---

## 21. Concrete Cursor Build Commands and Prompts

These are the kinds of commands/instructions the team can run inside Cursor while building the real product.

## Bootstrap

```bash
pnpm install
docker compose up -d
pnpm prisma migrate dev
pnpm dev
```

## Prompt - Create Prisma schema
```text
Create a Phase 1 Prisma schema for a multi-tenant work verification platform.
Include:
- workspaces
- users
- workspace_members
- integrations
- external_identities
- projects
- tasks
- time_entries
- activity_events
- verification_results
- invoice_drafts
- invoice_lines
- audit_logs
Requirements:
- every business table must be workspace-scoped
- include createdAt/updatedAt
- include unique constraints for provider external ids
- optimize for Jira/Harvest style imports
```

## Prompt - Build integration registry
```text
Build a NestJS integration registry for Phase 1.
Requirements:
- provider interface
- provider enum
- registration by provider name
- sync entry point
- workspace-aware token lookup
- retry-safe service boundaries
Return code changes only for the integration module and related types.
```

## Prompt - Build verification engine
```text
Implement the Phase 1 verification engine.
Rules:
- linked task present => positive score
- task updated near work window => positive score
- comments/activity present => positive score
- duplicate overlapping time entries => negative score
- task closed before time log => flag
- unmapped user => flag
Output:
- score
- status
- explanation reasons array
Add unit tests for the rules.
```

## Prompt - Build dashboard UI
```text
Build the Phase 1 dashboard in Next.js.
Include:
- KPI cards for total hours, verified hours, flagged hours, invoiceable hours
- anomaly panel
- recent sync panel
- pending review table preview
Use Tailwind and shadcn-style cards/tables.
Keep the layout pitch-demo friendly and visually clean.
```

## Prompt - Build review queue
```text
Create a verification review queue page.
Requirements:
- data table with filters
- columns: user, project, task, date, hours, status, score
- side drawer with evidence and triggered rules
- actions: approve, reject, leave note
- optimistic UI where reasonable
```

## Prompt - Build invoice draft flow
```text
Build the invoice draft feature for Phase 1.
Requirements:
- group approved time entries by project/client
- create line items using user or role hourly rate
- show total hours, rate, amount
- include a generated work summary field
- maintain references back to time entries
Implement API, service layer, and Next.js UI.
```

## Prompt - Build seed demo
```text
Create a demo seed script for Phase 1.
Seed:
- one workspace
- 5 users
- 3 projects
- 20 tasks
- 50 time entries
- mixed verification outcomes
- one draft invoice
The data should look realistic and support a clean investor demo.
```

---

## 22. What "Done" Looks Like

Phase 1 is done when:

- a demo user can onboard a workspace
- sample integrations can sync realistic data
- the verification dashboard is understandable
- flagged items can be reviewed and approved
- invoice drafts can be generated from approved work
- the app looks credible enough for customer demos and fundraising conversations

That is the goal. Not perfection. A clean, working wedge.

---

## 23. Final Recommendation

Build the Phase 1 product as a **credible working wedge**, not as a full enterprise system.

Use:
- deterministic verification
- simple invoice rules
- curated demo data
- modern managed infra
- narrow but polished UI

That will give the team something real to pitch, something useful to test with early customers, and a strong base for later beta expansion.
