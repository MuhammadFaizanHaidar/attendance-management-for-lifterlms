# Phase 1 Cursor Command Pack

Use this file as the practical command and prompt sheet for building the working Phase 1 product inside Cursor.

This is written for a fresh monorepo build.

---

## 1. Terminal Commands to Bootstrap the Project

```bash
mkdir phase1-platform
cd phase1-platform
pnpm init
pnpm dlx create-turbo@latest .
pnpm dlx create-next-app@latest apps/web --ts --tailwind --eslint --app --src-dir --import-alias "@/*"
pnpm add -D @nestjs/cli
pnpm dlx @nestjs/cli new apps/api --package-manager pnpm
mkdir -p apps/worker/src packages/ui packages/types infra/docker scripts
pnpm add -w @prisma/client zod dotenv bullmq ioredis
pnpm add -w -D prisma typescript tsx turbo eslint prettier
pnpm prisma init
```

---

## 2. Local Infrastructure Commands

Example dev stack:
- postgres
- redis
- minio

```bash
docker compose up -d postgres redis minio
pnpm prisma migrate dev --name init
pnpm prisma generate
```

---

## 3. Everyday Dev Commands

```bash
pnpm install
pnpm dev
pnpm test
pnpm lint
pnpm prisma studio
pnpm tsx scripts/seed-demo.ts
```

---

## 4. Cursor Prompt - Repo Setup

```text
Set up a monorepo for a Phase 1 work verification platform.
Stack:
- Next.js frontend
- NestJS API
- worker service using BullMQ
- Prisma with PostgreSQL
- shared packages for types and UI
Tasks:
- organize folders
- add root scripts
- add environment variable templates
- add Docker Compose for postgres, redis, and minio
- do not add unnecessary complexity
Return the exact file changes needed.
```

---

## 5. Cursor Prompt - Prisma Schema

```text
Create a Prisma schema for a multi-tenant Phase 1 work verification platform.
Models needed:
- Workspace
- User
- WorkspaceMember
- Integration
- ExternalIdentity
- Project
- Task
- TimeEntry
- ActivityEvent
- VerificationResult
- InvoiceDraft
- InvoiceLine
- AuditLog
Requirements:
- every record must be workspace-scoped where appropriate
- include timestamps
- include provider and externalId fields where imported data is used
- add useful indexes and unique constraints
- optimize for Jira/Harvest-style integrations
```

---

## 6. Cursor Prompt - NestJS Base Modules

```text
Generate Phase 1 NestJS modules for:
- workspaces
- integrations
- projects
- tasks
- time-entries
- verification
- invoices
- audit
Requirements:
- follow NestJS module/service/controller patterns
- keep all queries workspace-scoped
- use Prisma as the persistence layer
- add DTOs and validation
- do not touch unrelated files
```

---

## 7. Cursor Prompt - Integration Provider Framework

```text
Build a provider-based integration framework for Phase 1.
Requirements:
- provider enum
- provider interface
- registry to resolve providers
- one service entry point to trigger syncs
- token/config lookup per workspace
- idempotent sync boundaries
- easy extension for Jira, Harvest, and QuickBooks/Xero
Keep code production-oriented but Phase 1 simple.
```

---

## 8. Cursor Prompt - Verification Engine

```text
Implement a deterministic Phase 1 verification engine.
Rules:
- linked task present => positive score
- task updated within the work window => positive score
- activity/comments present => positive score
- duplicate overlapping time entries => negative score
- task closed before time log => flag
- unmapped external identity => flag
Outputs:
- score
- status (verified, needs_review, flagged)
- reasons array for explainability
Add unit tests for the core rule set.
```

---

## 9. Cursor Prompt - Dashboard UI

```text
Create a clean Phase 1 dashboard in Next.js.
Include:
- total hours
- verified hours
- flagged hours
- invoiceable hours
- top anomalies panel
- recent sync status panel
- pending review preview
Use Tailwind and reusable components.
Optimize the page for demos and pitch meetings.
```

---

## 10. Cursor Prompt - Review Queue UI

```text
Build the verification review queue page.
Requirements:
- data table with filters
- workspace-scoped API calls
- columns for user, project, task, date, hours, status, score
- detail drawer showing reasons and evidence summary
- actions for approve, reject, and leave note
- loading and empty states
```

---

## 11. Cursor Prompt - Invoice Drafting

```text
Implement the Phase 1 invoice draft flow.
Requirements:
- collect approved time entries
- group by project/client
- create line items using user or role hourly rate
- calculate total hours, rate, amount
- attach line items back to source time entries
- expose API endpoints and UI to review draft invoices
Keep billing logic intentionally simple for Phase 1.
```

---

## 12. Cursor Prompt - Audit Trail

```text
Add an audit trail system for Phase 1.
Track:
- sync starts/completions/failures
- verification status changes
- review approvals/rejections
- invoice draft creation
Requirements:
- workspace scope
- actor where known
- event type
- summary payload
```

---

## 13. Cursor Prompt - Demo Seed Script

```text
Create a realistic Phase 1 demo seed script.
Seed:
- one workspace
- 5 users
- 3 projects
- 20 tasks
- 50 time entries
- mixed verified / needs_review / flagged results
- one invoice draft with line items
The data should support a polished investor and customer demo.
```

---

## 14. Cursor Prompt - Production Hardening Review

```text
Review the Phase 1 codebase and identify the top risks before beta.
Focus on:
- multi-tenancy safety
- integration idempotency
- verification explainability
- invoice traceability
- UI clarity
- missing tests
Return findings in priority order with file references and suggested fixes.
```

---

## 15. Recommended Human Workflow With Cursor

1. ask Cursor to scaffold one module at a time
2. review the code
3. run the app/tests
4. fix issues
5. move to the next module
6. keep prompts small and precise

Best results come from short loops, not giant one-shot prompts.
