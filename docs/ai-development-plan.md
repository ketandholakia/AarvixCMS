# Aarvix CMS AI Development Plan

**Status:** In progress  
**Target:** 2026 development cycle  
**Platform:** Laravel 13, PHP 8.3+, MySQL/MariaDB, Redis-capable queues, Alpine.js, Editor.js  
**Source:** Aarvix CMS AI Development Roadmap (2026)

## 1. Objective

Build a shared AI platform inside Aarvix CMS rather than separate provider-specific
features. Writer, image, retrieval, chat, automation, and future agent features must
use the same provider abstraction, prompt engine, usage controls, audit trail, and
authorization layer.

The first production outcome is an AI Writer embedded in the existing Post, Page,
and Entry editing flows. Later phases extend the same foundation to images, RAG,
chat, workflows, vision, tools, agents, and analytics.

## 2. Current-State Constraints

This plan accounts for the following repository realities:

- Aarvix is currently a single-site CMS. There is no tenant model or `tenant_id`
  boundary in users, content, settings, or permissions.
- Production targets WAMP with MySQL/MariaDB. PostgreSQL and `pgvector` are not
  currently platform dependencies.
- Post, Page, and Entry bodies use Editor.js JSON, with legacy HTML supported by
  `BlockParser`.
- RBAC is custom and permission sets are cached by `PermissionService`.
- Database and Redis queue drivers are available; tests use the synchronous queue.
- Existing media storage already supports images, dimensions, captions, and alt text.
- `laravel/pao` is a test-output package, not an AI provider SDK.

These constraints create two architecture gates:

1. **Tenancy gate:** do not add decorative `tenant_id` columns until a real site or
   tenant ownership model exists. Keep interfaces tenant-aware through an optional
   `AiScope` value object, but use the current site as the only scope initially.
2. **Vector-store gate:** benchmark a MySQL/MariaDB-compatible vector option against
   a dedicated vector service and PostgreSQL before Phase 3. Do not silently require
   PostgreSQL in a WAMP deployment.

## 3. Delivery Principles

1. Controllers and UI code never call model providers directly.
2. Provider credentials remain encrypted or environment-backed and are never logged.
3. Prompts are versioned, validated, and referenced by stable keys.
4. Every provider call returns one normalized result envelope.
5. Authorization, rate limits, token limits, and budget limits run before a request.
6. Provider usage is recorded without storing sensitive prompt content by default.
7. Long-running operations use queues; interactive writing and chat may stream.
8. AI output is a suggestion until the user explicitly applies or saves it.
9. RAG filtering occurs before retrieval, not after answer generation.
10. Each phase ships behind feature flags with unit, feature, policy, and failure tests.

## 4. Target Architecture

```text
Admin UI / API / Workflow Event
              |
       Authorization + Limits
              |
          AiManager
      /       |        \
 Provider  Prompt     Memory
 Registry  Engine     Store
      \       |        /
        AI Services
   /      /      \       \
Writer  Image  Retrieval  Chat
              |
       Normalized Result
              |
      Usage + Audit Logging
```

### 4.1 Core modules

```text
app/AI/
|-- Contracts/
|   |-- AiProvider.php
|   |-- EmbeddingProvider.php
|   |-- ImageProvider.php
|   `-- VectorStore.php
|-- DTOs/
|   |-- AiRequestData.php
|   |-- AiResult.php
|   |-- AiUsage.php
|   |-- AiScope.php
|   `-- StructuredSchema.php
|-- Enums/
|-- Exceptions/
|-- Jobs/
|-- Policies/
|-- Providers/
|-- Services/
|   |-- AiManager.php
|   |-- PromptService.php
|   |-- UsageService.php
|   |-- WriterService.php
|   |-- ImageService.php
|   |-- EmbeddingService.php
|   |-- RetrievalService.php
|   `-- ChatService.php
|-- Support/
`-- Tools/
```

Provider adapters may wrap an official Laravel AI package or provider SDK, but the
application-facing contracts above remain owned by Aarvix. Package selection is a
Phase 0 spike and must demonstrate streaming, structured output, usage reporting,
timeouts, retries, test fakes, and support for at least two providers.

### 4.2 Normalized result

All synchronous and completed asynchronous calls produce the equivalent of:

```php
new AiResult(
    status: AiStatus::Succeeded,
    response: $response,
    provider: 'openai',
    model: 'configured-model',
    usage: new AiUsage(
        promptTokens: 120,
        completionTokens: 80,
        totalTokens: 200,
        estimatedCost: '0.001240',
    ),
    latencyMs: 842,
    requestId: 'application-generated-uuid',
    providerRequestId: 'provider-generated-id',
);
```

The result must distinguish successful, rejected, rate-limited, timed-out, and failed
requests. Cost uses a decimal string, not floating-point arithmetic.

## 5. Configuration Strategy

Create `config/ai.php` for deploy-time defaults:

- enabled features
- default provider and fallback provider
- provider model mappings by capability
- temperature, maximum output tokens, timeout, and retry policy
- queue names
- logging and retention policy
- per-user and per-feature limits
- daily and monthly budget ceilings
- prompt-content logging policy

Store runtime administrator choices with the existing `SettingService`, using keys
such as `ai.default_provider` and `ai.writer.enabled`. Secrets must not be stored as
plain settings. Use encrypted model casts for database-managed credentials or
environment variables for deployment-managed credentials.

Configuration precedence:

```text
validated request option
  -> feature setting
  -> provider/model setting
  -> config/ai.php default
```

## 6. Data Model

The schema should be introduced only as each phase requires it.

| Table | Phase | Purpose |
|---|---:|---|
| `ai_providers` | 0 | Enabled providers, capabilities, encrypted credentials reference, and status |
| `ai_models` | 0 | Provider models, capabilities, context limits, and pricing metadata |
| `ai_prompts` | 0 | Stable prompt key, category, active version, and output schema |
| `ai_prompt_versions` | 0 | Immutable system/user templates and change history |
| `ai_requests` | 0 | Request status, feature, actor, model, usage, cost, latency, and error class |
| `ai_usage_daily` | 0 | Aggregated usage for limits and dashboards |
| `ai_image_assets` | 2 | AI metadata linked to an existing `media` record |
| `content_embeddings` | 3 | Source polymorph, chunk hash, text, metadata, vector reference, and visibility |
| `ai_embedding_jobs` | 3 | Index state, source version, attempts, and last error |
| `ai_conversations` | 4 | Conversation owner, scope, title, status, and model settings |
| `ai_messages` | 4 | Role, content, citations, usage, tool calls, and moderation state |
| `ai_workflows` | 5 | Trigger, conditions, versioned steps, owner, and enabled state |
| `ai_workflow_runs` | 5 | Run state, step outputs, actor, timings, and failures |
| `ai_tools` | 6 | Approved tool definitions and permission requirements |
| `ai_tool_calls` | 6 | Auditable input, result summary, actor, approval, and status |

### 6.1 Schema rules

- Use UUID request identifiers for traceability across HTTP, queue, and provider logs.
- Use a polymorphic `source_type/source_id` for Post, Page, Entry, and future content.
- Record `user_id`; add tenant ownership only after the tenancy ADR is accepted.
- Store token counts as unsigned integers and money as `decimal(14, 8)`.
- Store provider/model snapshots on request rows so historical reporting survives
  configuration changes.
- Store a prompt version reference, not a mutable prompt reference alone.
- Store sanitized error classes/messages; never persist API keys or raw HTTP headers.
- Default raw request/response content to off. If enabled for debugging, redact,
  encrypt, authorize access, and apply short retention.
- Use content hashes to avoid embedding unchanged chunks.

## 7. Phase Plan

### Phase 0: AI Foundation

**Duration:** 2 weeks  
**Dependency:** None  
**Outcome:** A tested provider-neutral AI engine with no required end-user feature.

#### Work packages

##### 0.1 Architecture and package spike

- [x] Write ADRs for provider SDK, tenant scope, vector-store candidates, and cost source.
- Prototype text generation, streaming, JSON output, cancellation, timeout, and one
  simulated provider failure.
- Select two initial text providers: one primary and one fallback/test target.
- Establish model capability flags instead of assuming every model supports every API.

##### 0.2 Core contracts and services

- [x] Implement `AiManager`, provider contracts, DTOs, enums, and typed exceptions.
- [x] Implement `generate`, `stream`, `chat`, `embedding`, `image`, `vision`, and
  `json` as capability-checked methods. Unsupported methods fail predictably.
- [x] Add dependency injection bindings in a dedicated service provider.
- [x] Add a deterministic fake provider for tests and local development.

##### 0.3 Prompt management

- [x] Create prompt and immutable prompt-version migrations/models.
- [x] Implement strict `{{variable}}` interpolation with required-variable validation.
- [x] Reject unknown variables and unresolved placeholders.
- [x] Seed Writer prompt keys without coupling seed data to provider-specific syntax.
- [x] Add admin CRUD for prompts with preview, version comparison, activate, and rollback.

##### 0.4 Usage, limits, and auditing

- [x] Create request and daily-usage models.
- [x] Enforce per-user request rate, input/output token caps, and daily cost ceilings.
- [x] Log feature, actor, provider/model, status, tokens, estimated cost, and latency.
- [x] Integrate material AI actions with the existing activity log.
- [x] Add retention and aggregation commands.

##### 0.5 Operations

- [x] Add `ai-high`, `ai-medium`, and `ai-low` queue configuration.
- [x] Add WAMP worker command examples and health-check documentation.
- [x] Add provider connectivity and configuration checks to an admin diagnostics screen.
- [x] Add feature flags and a global kill switch.

#### Acceptance criteria

- Switching configured provider requires no controller or feature-service change.
- Unit tests use a fake provider and never make a network request.
- Timeouts, retries, fallback policy, malformed JSON, and rate limits are tested.
- Every completed call has one `ai_requests` record and normalized usage.
- Secrets and full prompts do not appear in application logs.
- `php artisan test` and PHPStan pass.

### Phase 1: AI Writer MVP

**Duration:** 3 weeks  
**Dependency:** Phase 0  
**Outcome:** Editors can safely generate and apply writing suggestions.

#### Scope

- Rewrite by tone: formal, friendly, professional, technical, and marketing.
- Simplify, improve, expand, shorten, continue, explain, and grammar repair.
- Summarize as short, medium, detailed, or bullet points.
- Translate while preserving Editor.js block structure and inline formatting.
- Generate SEO title, description, keywords, slug, Open Graph, and Twitter metadata.
- Generate title candidates, excerpt, reading time, and keywords.

#### Integration plan

- [x] Add one reusable AI action panel to the Editor.js Blade component.
- [x] Support selected-block and whole-document operations.
- [x] Serialize Editor.js input into a safe intermediate document representation.
- [x] Ask the provider for structured block-level output and validate it before preview.
- [x] Render a diff/preview with `Replace`, `Insert`, and `Cancel`; never autosave.
- [x] Reuse the panel in Post, Page, and Entry forms.
- [x] Apply SEO output to existing meta fields; add missing social metadata only through a
  separate schema change after UI ownership is clear.
- [x] Applying AI output participates in the existing revision history.

#### API endpoints

```text
POST /admin/ai/writer/generate
GET  /admin/ai/requests/{request}
POST /admin/ai/requests/{request}/cancel
```

Use authorization middleware and Form Request validation. Streaming should use an
appropriate Laravel streaming response; queued requests use polling by request UUID.

#### Acceptance criteria

- Output preserves valid Editor.js JSON and supported block types.
- HTML and inline markup are sanitized before being saved.
- A user cannot invoke AI on content they cannot edit.
- Translation preserves links, code blocks, images, and document order.
- SEO output is schema-validated and length warnings are shown in the UI.
- Applying AI output participates in the existing revision history.
- Provider errors leave editor content unchanged and present a recoverable message.
- Feature, browser-level interaction, authorization, and malformed-output tests pass.

### Phase 2: AI Image and Vision Utilities

**Duration:** 2 weeks  
**Dependency:** Phase 0  
**Outcome:** AI-generated or transformed assets flow through the existing media library.

#### Scope

- Generate and edit images.
- Remove background, upscale, and resize when supported.
- [x] Generate captions, alt text, tags, and OCR text.
- Store generated files through `MediaUploadService`.
- Link provider, model, prompt hash, cost, seed, resolution, and operation metadata to
  the media record through `ai_image_assets`.

#### Guardrails

- Validate MIME type, dimensions, decoded size, and storage quota.
- Run normal media sanitization and conversion after provider download.
- Keep original and derived asset relationships.
- Require explicit confirmation before replacing an existing media asset.
- [x] Add provider capability checks for edit, mask, seed, and resolution options.
- [x] Define moderation and retention behavior before enabling public-facing generation.

#### Acceptance criteria

- Generated assets appear in the media library with provenance and cost.
- Failed jobs leave no partial database record or orphaned file.
- Alt text is editable and not presented as verified fact.
- Image operations are queued on `ai-low`.
- Storage, authorization, moderation, and provider-failure tests pass.

### Phase 3: Knowledge Base and RAG

**Duration:** 4 weeks after a vector-store spike  
**Dependencies:** Phases 0-1 and accepted vector-store ADR  
**Outcome:** Authorized CMS content can be indexed and cited in grounded answers.

#### 3.1 Vector-store decision

Benchmark using a representative corpus and WAMP deployment:

- MySQL/MariaDB-compatible vector search.
- A dedicated vector database or search service.
- PostgreSQL with `pgvector` as an explicit platform expansion.

- [x] Add a benchmark harness with a deterministic in-memory vector-store baseline and CLI reporting for index/search throughput.

Measure indexing throughput, top-k latency, filtering support, backup/restore,
operational burden, and cost. The selected store must support metadata filtering
before nearest-neighbor ranking.

#### 3.2 Ingestion pipeline

- [x] Create content embedding and embedding-job tables, models, and source
  summarizer for Post, Page, and Entry.
- [x] Add source adapters, queued sync job, and model hooks for Post, Page, and
  Entry embeddings.

```text
Post/Page/Entry/File
  -> authorization metadata
  -> Editor.js/HTML/document cleaner
  -> structure-aware chunker
  -> content hash and deduplication
  -> embedding provider
  -> vector store
  -> index status record
```

- Create source adapters for Post, Page, and Entry first.
- Add PDF and Word only after upload security and extraction quality tests exist.
- Dispatch indexing after committed content changes.
- [x] Delete vectors after source deletion and hide them immediately on unpublish.
- [x] Reindex when content, visibility, chunker version, or embedding model changes.
- [x] Make jobs idempotent and resumable.

#### 3.3 Retrieval pipeline

```text
Question
  -> authorize actor and requested scope
  -> derive allowed source filters
  -> embed question
  -> filtered similarity search
  -> optional reranking
  -> context budget assembly
  -> grounded answer with citations
```

Current authorization maps to content status, ownership, and existing policies. A
future tenant filter is added only when tenant ownership exists.

- [x] Add authorization-aware retrieval service with vector search, visibility filtering, and stable citations for Post, Page, and Entry.
- [x] Add a versioned RAG evaluation fixture set and CLI harness for recall, citation correctness, and injection safety.

#### Acceptance criteria

- Unpublished, private, deleted, or unauthorized sources cannot enter candidate chunks.
- Answers cite source type, title, stable admin/public URL where allowed, and chunk.
- Changed content is reindexed without duplicate active chunks.
- Deletion and unpublish events make content unavailable before background cleanup.
- Retrieval quality has a versioned evaluation set with target recall and citation
  correctness thresholds.
- Prompt-injection fixtures cannot override system instructions or tool permissions.

### Phase 4: AI Chat Assistant

**Duration:** 3 weeks  
**Dependency:** Phase 3  
**Outcome:** Users can have streaming, cited conversations grounded in authorized CMS data.

#### Scope

- Conversation and message persistence.
- Streaming answers with cancel and retry.
- Website/content search, summarization, and policy explanation.
- Conversation rename, archive, and delete.
- Explicit mode selection: knowledge answer or writing help.
- Citations that open only sources the user may access.

Chat is read-only in this phase. Requests such as "find my article" may return links,
but requests such as "update this article" cannot mutate content until the tool
framework and approval flow ship.

- [x] Add conversation and message persistence models and a transcript service for Phase 4 chat history.

#### Acceptance criteria

- Every retrieval turn repeats authorization; access is not inherited from old context.
- Conversation access is owner/admin restricted.
- Context windows are bounded through summarization and token budgeting.
- Streaming disconnects cancel or safely finish billing/logging.
- Hallucination evaluation requires the assistant to say when evidence is insufficient.

### Phase 5: Workflow Automation

**Duration:** 3 weeks  
**Dependencies:** Phases 1-4  
**Outcome:** Versioned, queue-driven workflows automate approved content tasks.

#### Initial workflows

- On publish: generate missing SEO suggestions and create an editor review task.
- On publish: draft social post variants.
- On request: translate selected content into configured locales.
- Scheduled: refresh stale generated metadata.

Do not enable a single workflow that automatically writes every generated output.
Each step declares whether it drafts, proposes, or applies a change.

#### Engineering requirements

- Versioned trigger, conditions, steps, and input/output schema.
- Idempotency key per trigger and workflow version.
- Per-step retries, timeout, compensation behavior, and dead-letter visibility.
- Human approval step for publishing, notifications, or destructive changes.
- Complete run audit linked to request, source, actor, and applied revision.
- Concurrency controls to prevent duplicate publish workflows.

#### Acceptance criteria

- Replayed events do not duplicate effects.
- A failed step can retry without repeating completed non-idempotent actions.
- Disabling a workflow prevents new runs without corrupting active runs.
- Generated content is traceable from source event to final revision.

### Phase 6: Tool Calling and Agents

**Duration:** 4-6 weeks  
**Dependency:** Phase 5  
**Outcome:** AI can invoke a narrow set of permission-aware CMS tools with auditability.

#### Tool framework first

Each tool declares:

- stable name and version
- input and output JSON schema
- required permission
- read or write risk classification
- confirmation policy
- idempotency behavior
- timeout and rate limit
- audit redaction policy

Initial tools should be narrow: search content, read an authorized content summary,
create a draft article, propose SEO metadata, search media, and create a report.
Avoid arbitrary SQL, filesystem, HTTP, shell, plugin installation, or unrestricted
model-driven controller calls.

#### Agent layer

Only after tools are proven, define SEO, Marketing, Translation, Documentation, and
Support agents as versioned configurations of prompt, tools, memory, permissions,
model policy, budgets, and maximum steps.

#### Acceptance criteria

- The server reauthorizes every tool call; model output never grants permission.
- Write tools require explicit user confirmation unless a reviewed workflow owns them.
- Tool inputs are schema-validated and outputs are size-limited.
- Maximum steps, time, token use, and cost are enforced.
- Every call is auditable and linked to the content revision it created.

### Phase 7: Vision AI

**Duration:** 2-3 weeks  
**Dependencies:** Phases 0 and 2  
**Outcome:** Approved image and document understanding workflows.

#### Scope

- Screenshot analysis.
- Document OCR and structured extraction.
- Invoice-field extraction as unverified data.
- Product/image classification.
- Image moderation support.
- Accessibility checks and alt-text suggestions.

High-impact extracted fields require confidence display and human verification.
Uploaded documents must follow file validation, malware-scanning policy, page limits,
and retention rules.

### Phase 8: Analytics and Optimization

**Duration:** 2 weeks for first release, then continuous  
**Dependency:** Instrumentation from all prior phases  
**Outcome:** Administrators can understand quality, usage, reliability, and cost.

#### Dashboard

- Requests and success/failure rate.
- Prompt, completion, and total tokens.
- Daily and monthly estimated cost.
- Cost and latency by provider, model, feature, and user.
- Most-used features and active users.
- Queue wait and execution time.
- RAG citation and no-answer metrics.
- Workflow and tool-call success rates.

Analytics access requires a dedicated permission. User-level reporting should expose
only what is operationally necessary, with retention and export rules documented.

## 8. Queue and Reliability Plan

| Queue | Workloads | Target behavior |
|---|---|---|
| `ai-high` | Interactive chat, writer, SEO | Low wait time; short timeout; limited retries |
| `ai-medium` | Translation, summary, workflows | Moderate concurrency; resumable jobs |
| `ai-low` | Embeddings, image, OCR, aggregation | Cost-controlled; batch-friendly |

Implementation rules:

- Use explicit queue names and job-specific retry/backoff values.
- Generate an application request UUID before dispatch.
- Use `afterCommit()` for indexing and workflows triggered by saved content.
- Make each job idempotent and detect already-completed work.
- Separate provider retryable errors from validation and policy failures.
- Add circuit breaking or temporary provider disablement after repeated outages.
- Reconcile stuck `pending/running` requests with a scheduled command.
- Document Windows worker supervision and graceful restart procedures.

## 9. Security and Privacy Workstream

Security is a release gate in every phase, not a final hardening task.

### Required controls

- Dedicated permissions: `use_ai_writer`, `use_ai_image`, `use_ai_chat`,
  `manage_ai_prompts`, `manage_ai_providers`, `view_ai_usage`, and
  `manage_ai_workflows`.
- Existing content policies enforced before content is sent to a provider.
- Per-user and global rate, token, concurrency, and cost limits.
- API credentials encrypted at rest or injected through environment configuration.
- Sensitive-field redaction before logging or external transmission.
- Prompt injection treated as untrusted content, especially retrieved text.
- Structured output schema validation before any application.
- Existing HTML/Editor.js sanitization retained after AI generation.
- CSRF, authentication, authorization, and request-size limits on every endpoint.
- Provider data-retention and training settings documented for administrators.
- Configurable data residency/provider allowlist for sensitive deployments.
- Audit events for configuration, prompt activation, generation, application, tools,
  workflow changes, and budget overrides.

### Threat-model checkpoints

Complete a lightweight threat model before Phase 1, Phase 3, and Phase 6 releases:

- Phase 1: content leakage, stored XSS, malformed Editor.js, and budget abuse.
- Phase 3: cross-scope retrieval, indirect prompt injection, and poisoned content.
- Phase 6: confused deputy, excessive agency, duplicate writes, and approval bypass.

## 10. Test and Evaluation Strategy

### Automated tests

- **Unit:** prompt rendering, DTO validation, token/cost math, chunking, capability
  checks, structured output, redaction, and limit calculation.
- **Contract:** run each provider adapter against shared fake-response fixtures.
- **Feature:** routes, permissions, settings, request logs, queue dispatch, and UI apply.
- **Policy:** content ownership/status and conversation/tool access.
- **Failure:** timeout, rate limit, malformed JSON, unavailable provider, retry exhaustion,
  cancellation, and partial stream.
- **Security:** injection fixtures, unauthorized retrieval, XSS payloads, oversized
  inputs, and secret redaction.
- **Browser:** Editor.js selection, preview, apply/cancel, streaming, and error recovery.

Network calls are prohibited in the default test suite. Optional provider smoke tests
must use a separate PHPUnit group and explicit credentials.

### Quality evaluations

Maintain versioned fixtures under `tests/Fixtures/AI/` and evaluation commands for:

- Writer meaning preservation and Editor.js validity.
- Translation markup preservation.
- SEO schema and length compliance.
- RAG retrieval recall, authorization leakage, citation correctness, and groundedness.
- Tool selection, permission denial, confirmation behavior, and maximum-step handling.

Prompt or model changes cannot activate if they regress mandatory safety tests or the
agreed evaluation threshold.

## 11. Rollout Strategy

Each feature follows:

```text
disabled
  -> local fake provider
  -> internal administrators
  -> selected roles/users
  -> general availability
```

- Keep a global AI kill switch and per-feature switches.
- Start with strict budgets and one provider.
- Show estimated/actual usage in admin diagnostics before broad rollout.
- Roll back by disabling the feature or prompt version, not by reverting migrations.
- Retain provider fallback only where duplicate billing and inconsistent output are
  acceptable; do not retry non-idempotent image/tool operations blindly.

## 12. Timeline and Dependencies

| Phase | Focus | Duration | Earliest dependency |
|---|---|---:|---|
| 0 | Foundation and provider abstraction | 2 weeks | None |
| 1 | Writer MVP | 3 weeks | Phase 0 |
| 2 | Image utilities | 2 weeks | Phase 0 |
| 3 | Knowledge base and RAG | 4 weeks | Phase 0-1, vector ADR |
| 4 | Chat assistant | 3 weeks | Phase 3 |
| 5 | Workflow automation | 3 weeks | Phase 1-4 |
| 6 | Tools and agents | 4-6 weeks | Phase 5 |
| 7 | Vision AI | 2-3 weeks | Phase 0, Phase 2 |
| 8 | Analytics first release | 2 weeks | Cross-phase telemetry |

Phases 1 and 2 can overlap after Phase 0 if separate engineers own them. Analytics
instrumentation begins in Phase 0 even though the full dashboard ships in Phase 8.
Expected sequential duration is 25-28 weeks; selective parallel work can reduce this
to approximately 20-23 weeks without removing release gates.

## 13. First Sprint Backlog

### Sprint goal

Prove the architecture with one text-generation use case and complete operational
logging without exposing a production Writer UI.

### Ordered backlog

1. [x] Write ADRs for provider SDK, tenancy scope, and vector-store investigation.
2. [x] Create `config/ai.php` and document environment variables.
3. [x] Add core contracts, DTOs, enums, typed exceptions, and service provider.
4. [x] Implement fake provider and one real text-provider adapter.
5. [x] Implement `AiManager::generate()` and `AiManager::json()`.
6. [x] Add prompt and prompt-version migrations, models, renderer, and seed prompts.
7. [x] Add request and daily-usage migrations, models, logger, and cost calculator.
8. [x] Add permission seeds and authorization middleware.
9. [x] Add limits, timeout, retry classification, and global kill switch.
10. [x] Build an internal diagnostics command that performs a minimal generation request.
11. [x] Add unit, contract, feature, authorization, and failure tests.
12. [x] Document queue workers, provider setup, secret handling, and rollback.

### Sprint exit criteria

- One real provider and the fake provider pass the same generation contract tests.
- A prompt version can be rendered and executed through `AiManager`.
- Usage, latency, status, actor, provider, and model are recorded.
- Disabled, unauthorized, over-budget, timed-out, and malformed responses are covered.
- No feature code depends directly on provider SDK classes.
- Test suite and static analysis pass.

## 14. Definition of Done

A phase is complete only when:

- migrations include a rollback path and have been tested on MySQL/MariaDB;
- permissions and navigation are seeded and covered by tests;
- services have fake-provider coverage and no default test makes network calls;
- expected provider, timeout, cancellation, and queue failures are handled;
- usage and audit records are complete and redact sensitive data;
- administrator setup, WAMP worker operation, and rollback are documented;
- feature flags and budget limits are present;
- accessibility and responsive UI behavior have been checked;
- security acceptance criteria and evaluation thresholds pass;
- `php artisan test`, PHPStan, Pint, and the frontend production build pass.

## 15. Open Decisions

Resolve these through ADRs before the dependent phase:

1. Which provider SDK and initial providers satisfy the Phase 0 contract?
2. Are provider credentials environment-owned, database-managed, or both?
3. Is Aarvix expected to become multi-tenant, and what model owns a tenant/site?
4. Which vector store meets WAMP, filtering, backup, and performance requirements?
5. Which content may leave the deployment, and what provider retention rules apply?
6. What are the initial per-user and site-wide budgets?
7. Which prompt/response fields, if any, may be retained for debugging?
8. Which AI changes require human approval versus direct application?
9. What evaluation thresholds block prompt and model activation?
10. What retention periods apply to requests, conversations, embeddings, and tool logs?
