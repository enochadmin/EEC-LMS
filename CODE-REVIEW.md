# EEC LMS — End-to-End Code Review

**Reviewed:** full `app/`, `routes/`, `database/migrations`, `resources/views`, plus the live MySQL database
**Date:** 2026-10-21
**Scope:** Laravel 12 · Spatie Permission 6 · DomPDF 3 · role-based LMS (learner / instructor / admin)

---

## 1. Verdict

The application boots, routes resolve, and the bones are reasonable (Spatie roles, polymorphic retakes, DB notifications, DomPDF).
But it **does not work end to end today**. There are:

- **3 hard 500/404 errors** on shipped pages
- **1 silent workflow bypass** (lesson approvals never happen — the database default overrides your intended `pending`)
- **A progress engine that is arithmetically incapable of being correct** for half of all modules, and the live data already contains impossible values (a learner at 50% with zero work done)
- **6 missing notification events** out of the ~12 your requirement implies
- **A certificate pipeline that produces records nobody can see, on a page that throws**

None of this is architectural rot — it is ~15 localized defects. Almost all are fixable in the existing structure without a rewrite.

---

## 2. Issue 1 — Notifications

### What exists
9 notification classes, all `via(['database'])`, all good payloads. The bell dropdown in `layouts/navigation.blade.php` renders unread items and a count.

### Actual coverage matrix

| Event | Learner | Instructor / assigner | Admin | Verdict |
|---|---|---|---|---|
| Self-enrollment request submitted | flash only | **nobody** | **nobody** | ✗ broken |
| Enrollment approved | **nobody** | — | — | ✗ broken |
| Enrollment rejected | **nobody** | — | — | ✗ broken |
| Learner enrolled (manual assign) | **nobody** | notified (assigner) | only if module creator | ✗ half |
| Lesson marked complete (`pending`) | — | **nobody** | **nobody** | ✗ broken (`LessonController:122-129` just writes a log line) |
| Lesson approved | ✓ | — | — | ✓ |
| Lesson rejected | **nobody** | — | — | ✗ |
| Quiz attempted (pass/fail) | **nobody** | ✓ | ✓ via fallback | ✓ but learner gets nothing |
| Module completion requested | — | ✓ | creator fallback | ✓ |
| Module completion approved | **nobody** (only the certificate notice, if one is issued) | — | — | ✗ |
| Module completion rejected | **nobody** | — | — | ✗ |
| Certificate issued | ✓ | — | — | ✓ |
| Certificate revoked | **nobody** | — | — | ✗ |

### Structural problems
1. **No real-time delivery.** `layouts/navigation.blade.php:69` renders `count` once, server-side. Nothing polls; a learner must reload to see an approval. `notifications/unread` exists (`NotificationController:getUnread`) but no view calls it. The `my-progress` page has a 5s poller; notifications do not.
2. **Database channel only.** `MAIL_MAILER=log`, no mail driver on any notification. "Available for all users" currently means "visible after a page refresh, in-app only".
3. **Admin is not a first-class recipient.** Admin only receives what happens to land on the module creator. There is no "notify all admins" path anywhere.
4. **Notification fan-out is implicit.** Every call site re-implements "who do I notify" (`$assignment?->assigner ?? $module->creator`), repeated in 4 controllers. This is why the coverage table has holes — each new flow forgot it.

**Fix shape:** one `NotificationService` with explicit methods (`enrollmentRequested`, `enrollmentDecided`, `lessonSubmitted`, `lessonDecided`, `moduleSubmitted`, `moduleDecided`, `certificateIssued`, `certificateRevoked`) that resolve recipients once (assigner ∪ module creator ∪ admins-by-role) and fire DB + mail. Add a 30s polling fetch on the bell using the existing endpoint.

---

## 3. Issue 2 — Role classification

### Works
- Sidebar hides Management from learners, hides My Learning from non-learners (`sidebar.blade.php:94,137`).
- `UserController@enroll` refuses non-learners (`UserController.php:337`).
- The Enroll panel on the module page is inside `@role('learner')` (`modules/show.blade.php:138`).
- Route middleware for authoring/admin areas is broadly correct.

### Leaks found

| # | Leak | Where |
|---|---|---|
| 1 | **Admin and instructor appear in the "Assign Module" learner dropdown** — `User::orderBy('name')->get()`, not `User::role('learner')` | `TrainingModuleController.php:95-97` |
| 2 | **Admins and instructors can sit quizzes and accumulate progress.** Live DB: admin user id 1 has 4 quiz attempts and 2 `module_progress` rows | `QuizController@take/submit` — no role guard |
| 3 | `my-enrollments`, `my-progress`, `api/my-progress` have **no role middleware** — any user reaches them | `routes/web.php:171-174` |
| 4 | `lessons.index` is open to learners (lists every lesson in every module) | `LessonController:14` |
| 5 | "Assign Modules" sidebar item points at `modules.index?assignments=1` — a query string nothing reads | `sidebar.blade.php:84` |
| 6 | Learners can see **every learner's enrollment + status** in the Assignments panel on the module page | `modules/show.blade.php:234-251` (panel is outside any role gate) |

### Recommended capability matrix

| Capability | Learner | Instructor | Admin |
|---|---|---|---|
| Browse published catalogue | ✓ | ✓ (preview) | ✓ |
| Enroll self | ✓ | ✗ | ✗ |
| Consume lessons / sit quizzes | ✓ own enrollments | ✗ preview only | ✗ |
| Author modules / lessons / quizzes | ✗ | ✓ own | ✓ all |
| Assign learners | ✗ | ✓ own modules | ✓ all |
| Approve lesson / module completion | ✗ | ✓ own learners | ✓ all |
| Issue / revoke certificates | ✗ | ✓ (own `issued_by`) | ✓ all |
| Users, roles, departments, categories | ✗ | ✗ | ✓ |
| Reports | own | own modules | all |

Enforce with route middleware (`role:learner`, `role:instructor|admin`) **and** the existing policy checks — the matrix above is currently enforced only in Blade.

---

## 4. Issue 3 — Progress reporting (the most serious data defect)

### Root cause: a fixed 50/50 split
Three separate places compute progress, all hard-coding "lessons are worth 50 points, quizzes are worth 50 points":

- `LessonController.php:139-153`
- `LessonCompletionController.php:30-45`
- `QuizController.php:250-263`

Consequences:

1. **A module with lessons but no quiz can never exceed 50%.** Your `Test` module (1 lesson, 0 quizzes) can only reach 100% because `ModuleCompletionApprovalController::approve()` *hard-codes* `percentage = 100` (`:156`) — the formula itself would report 50%.
2. **A module with a quiz and no lessons starts at 0% and jumps to 50%**, never 100% without manual approval.
3. **Quizzes are binary**: pass = +50, fail = 0. A 69% near-miss and a 0% score are treated identically.
4. **Nothing recalculates on rejection.** `LessonCompletionController@reject` deletes the row (`:112`) and never touches `module_progress`, so the learner keeps credit for a rejected lesson.
5. **Nothing recalculates on content change.** Adding a lesson after enrollment never updates `total_lessons` for existing learners.

### Live evidence (queried from your database)

| Module | Learner | Approved lessons | Quiz passed | **Stored** | **Correct task-based** |
|---|---|---|---|---|---|
| 1 Workplace Safety | learner@example.com | 0 of 2 | no | **50%** ← *impossible* | 0% |
| 1 Workplace Safety | admin@example.com | 0 of 2 | yes | 50% | 33.3% |
| 2 Site Training | admin@example.com | 0 of 0 | yes | 50% | 100% |
| 2 Site Training | learner@example.com | 0 of 0 | yes | 50% | 100% |
| 3 Test | henok@email.com | 1 of 1 | n/a | 100% | 100% (only via approval override) |

Row 1 is the clearest proof: `lesson_percentage = 0`, `quiz_percentage = 0`, `percentage = 50`. The stored field and its own components disagree — that row was seeded (`DemoContentSeeder.php:155`), and no code path would ever recompute it.

### Related bug: module-completed notification never fires
`LessonController.php:158` reads `if ($percentage >= 100)` — **`$percentage` is never defined.** The value lives on `$progress->percentage` (`:153`). PHP 8 raises an undefined-variable warning and evaluates `null >= 100` as false, so `ModuleCompletedNotification` is unreachable from that path.

### What to build
A single `ProgressService::recalculate(int $userId, int $moduleId)`:

```
total_tasks = count(lessons) + count(quizzes)
done_tasks   = count(approved lesson_completions) + count(distinct quizzes passed)
percentage   = total_tasks > 0 ? round(done_tasks / total_tasks * 100, 2) : 0
```

Call it from exactly six places: lesson submit, lesson approve, lesson reject, quiz submit, module approve, retake reset — plus a model observer when lessons/quizzes are added or deleted. Expose `remaining_tasks`, `completed_tasks`, and per-type breakdown so the UI can say *"4 of 7 tasks done — 3 left"*, which is what your requirement describes.

Add columns `total_quizzes`, `completed_quizzes`, `computed_at` and treat the three percentages as derived output, never as independently written fields.

---

## 5. Issue 4 — Authoring flow and the learner view

### Authoring today
| Task | Current effort |
|---|---|
| Create module | Full page form → save → redirected to module show |
| Add lesson | Separate page, re-select the module, upload video/PDF/YouTube |
| Add quiz | Separate page → save → redirected to quiz edit |
| Add question | Inline form on quiz edit; **no edit, no delete, no reorder** |
| Order lessons | Numeric `order` field typed by hand, no drag & drop |

Problems:
- No single "build the course" surface — 3 screens + 2 redirects for one module.
- `quizzes/create` does not pre-select `module_id` from the query string even though `modules/show.blade.php:102` passes it.
- `QuestionController` has `destroy` but no `update`; the quiz edit view has no delete button. A typo means deleting via DB.
- `youtube_links` (plural) is stored as JSON (`LessonController:238`) and **never rendered** — `lessons/show.blade.php` only reads the singular `youtube_link`.

### Learner view today
- The module page is a **flat list of lesson titles and "Take Quiz" links** — no syllabus, no ordering context, no locked/unlocked state, no "continue where you left off", no completion ticks.
- No progress indicator anywhere on the module page (`my-progress` has one; the page learners actually study from does not).
- **A completed lesson becomes unreadable.** `LessonController@show:60-74` returns the "Content Locked" view for any approved completion — so a learner cannot re-read material they finished, and must file a retake request to view it again. This is the opposite of standard LMS behaviour (Coursera/Moodle/TalentLMS all leave content readable).
- `modules/show.blade.php:117` shows "Take Quiz" to everyone, including non-enrolled users and admins; the route then bounces or 403s.
- `quizzes/take.blade.php` "All quizzes" link → `quizzes.index` → **403 for learners** (that route requires `manage quizzes`).

**Fix shape:** a module player — left rail syllabus (lesson/quiz items with state icons), main content pane, sticky footer with "Mark complete / Next", and a course-level progress ring. Authors get one builder page: module meta + sortable lesson list + quiz/question editor, all on one screen with progressive save.

---

## 6. Issue 5 — Approval & certificate workflows (you asked what's broken)

```
learner enrolls ──▶ request (pending)
                     ├─ approver NOT notified ...................... ✗
                     └─ approve/reject ── learner NOT notified ...... ✗
learn lessons ─────▶ "Mark complete" ── saved as APPROVED .......... ✗ (status silently dropped)
                     └─ lesson locks immediately, no instructor step
take quiz ─────────▶ attempt + progress ── instructor notified ..... ✓
request completion ─▶ gates on lessons + a passed quiz .............. ✓
                     └─ if previously REJECTED, record stays
                        'rejected' and can never be resubmitted ..... ✗
instructor approves ▶ assignment = completed, progress = 100 ....... ✓
                     ├─ certificate created WITHOUT issued_by ...... ✗
                     ├─ learner NOT notified of approval ........... ✗
                     └─ progress components left inconsistent ...... ✗
certificate ───────▶ /admin/certificates ── 500 (undefined route) ... ✗
                     ├─ manual issue: no UI exists ................ ✗
                     └─ revoke: route not registered .............. ✗
```

### 5 confirmed breaks, in severity order

**B1 — The lesson approval workflow does not exist.**
`LessonCompletion`'s `$fillable` is `user_id, training_module_id, lesson_id` (`app/Models/LessonCompletion.php:18-23`) — **`status` is missing**. `LessonController::complete` passes `'status' => 'pending'` (`:118`), Eloquent silently discards it, and the migration default `->default('approved')` wins (`2025_11_29_070138_add_status_to_lesson_completions_table.php:78`). Live DB: 1 completion row, `approved`, 0 `pending`. `LessonCompletionController@approve` is therefore dead code, and the "pending completions" list on the retakes screen is always empty.

**B2 — `/admin/certificates` throws 500.**
`certificates/admin.blade.php:63` calls `route('admin.certificates.destroy', $certificate)`. That route is **not registered** (`routes/web.php:143-148` registers only `index` and `issue`). `route()` throws `RouteNotFoundException` during render, so the whole certificate management page is dead — for admin and instructor alike. `CertificateController@destroy` exists but was never wired.

**B3 — Certificates issued through approval are invisible to instructors.**
`ModuleCompletionApprovalController@approve:167-173` creates the certificate with `user_id, training_module_id, certificate_number, file_path, issued_at` — **no `issued_by`**. `CertificateController@adminIndex:44-46` filters instructors by `where('issued_by', $user->id)`, so an instructor who approves a completion sees nothing in their certificate list. Both existing certificates in your DB have `issued_by = null`.

**B4 — Rejection is a dead end.**
`ModuleCompletionApprovalController@request:66-78` uses `firstOrCreate`. After a rejection the row exists with `status = 'rejected'`; `wasRecentlyCreated` is false, the pending branch is skipped, the approved branch is skipped — so the learner gets "request submitted" but the record **never returns to the queue**. Silent failure. Same pattern in `RetakeRequestController@store`.

**B5 — Nothing notifies the learner about a decision.**
Approve sends only `CertificateIssuedNotification` (and only when a *new* certificate is created). Reject sends nothing at all (`:187-200`).

### Also in this area
- `EnrollmentApprovalController@approve` has no duplicate guard and `assigned_modules` has **no unique index** on `(training_module_id, user_id)` — a double-click or a race creates duplicate enrollments.
- Same method calls `ModuleProgress::updateOrCreate` with `percentage => 0`, **wiping** an existing learner's progress if an approval is replayed.
- Manual issue (`admin.certificates.issue`) has a route and a controller method but **no UI** — no screen offers a user + module picker.
- `valid_until` is never set by any issue path, so certificates never expire (fine if intentional — confirm).
- Certificate numbering is `'CERT-' . strtoupper(uniqid())` — not sequential, not human-quotable. Two different generators exist (`CertificateController:79` and `ModuleCompletionApprovalController:170`).

---

## 7. Additional defects (found while tracing the flows)

### P0 — user-visible errors
| # | Defect | File |
|---|---|---|
| 1 | **Learner evaluation page 500s** — `userEvaluation(TrainingModule, User $user)` but `User` is not imported (resolves to `App\Http\Controllers\User`) | `AssignmentController.php:1-7, 429` |
| 2 | **`GET /quizzes/{quiz}/results` 500s** — `view('quizzes.results')` does not exist (only `result.blade.php`) | `QuizController.php:293` |
| 3 | **`GET /users/template` 404s** — registered after the `users/{user}` resource route, so it binds `user = "template"` | `routes/web.php:154,157` |
| 4 | **All `->with('error', ...)` messages are invisible** — the layout renders `session('status')` only, never `session('error')`. Every guard in the app ("not enrolled", "only learners can enroll", "module not available") shows a blank page | `layouts/app.blade.php:68-72` |
| 5 | `categories.show` / `departments.show` routes exist with **no controller method** → 500 | `CategoryController`, `DepartmentController` |

### P1 — correctness
| # | Defect | File |
|---|---|---|
| 6 | `my-enrollments` prints `$enrollment->module->lessons_count` / `quizzes_count`, but the controller never `withCount()`s them → blank counts | `users/my-enrollments.blade.php:198-199` vs `UserController.php:405-408` |
| 7 | `AssignmentController@complete` marks the assignment completed with **no notification, no progress update, no certificate** | `AssignmentController.php:486-498` |
| 8 | Instructor approvals are **not ownership-scoped** — module approvals and retake requests list every learner's requests to every instructor | `ModuleCompletionApprovalController:101-119`, `RetakeRequestController:73-93` |
| 9 | Enrollment approvals are scoped by `created_by` only, so an instructor who was *assigned* a module cannot approve requests for it | `EnrollmentApprovalController:30-35` |
| 10 | Quiz submit records **no attempt and no progress** if the quiz has no MCQ questions (`$percentageAuto` stays null) | `QuizController.php:227-233` |
| 11 | Short-answer questions **cannot be answered** — `quizzes/take.blade.php` renders radios only | `quizzes/take.blade.php:41-52` |
| 12 | Quiz retakes are unlimited and unapproved — the "retake request" flow only kicks in *after* passing | `QuizController@take:107-138` |
| 13 | `User::createdLessons/createdQuizzes/createdQuestions/createdQuestionOptions/createdUserAnswers` reference a `created_by` column that **does not exist** on any of those tables → SQL error if ever used | `app/Models/User.php:60-64` |
| 14 | Dashboard `quizzes_taken` uses three stacked `distinct()` calls that collapse to a plain row count | `DashboardController.php:109-113` |
| 15 | `module_progress.percentage` is written as `100` by the approval path while `lesson_percentage`/`quiz_percentage` keep their old values — the row contradicts itself | `ModuleCompletionApprovalController:148-159` |

### P2 — polish / dead ends
- Notification bell never refreshes without a page load (§2).
- No attempt limit, no "attempts remaining" display.
- Dashboard `$departmentData` is referenced before assignment (line 35) — harmless only because of `??`.
- `debug_roles.php` sits in the project root.
- `web.php` carries ~70 lines of commented-out auth routes; `auth.php` is the live copy.

---

## 8. Suggested sequence

| Phase | Work | Why first |
|---|---|---|
| **1. Stop the bleeding** (small) | Cert destroy route + import `User` + create `quizzes/results` + reorder `users/template` + render `session('error')` + fix `$percentage` | Six tiny edits remove every 500 blank page |
| **2. Make progress truthful** (medium) | `ProgressService` + migration for quiz counters + call it from the 6 mutation points + backfill existing rows | Your #3, and it gates completion approval |
| **3. Repair the approval chain** (medium) | `status` in `$fillable` + decision on auto-approve + resubmit-after-reject + `issued_by` + certificate numbering + revoke route/UI | Your #5 |
| **4. Notifications** (medium) | `NotificationService` + 6 missing events + admin fan-out + bell polling + optional mail | Your #1 |
| **5. Role hardening** (small–medium) | Route middleware per matrix + learner-only assign list + block quiz/progress for non-learners + hide learner data on module page | Your #2 |
| **6. Course builder + learner player** (large) | One-page authoring, sortable lessons, question edit/reorder, syllabus + progress ring + resume + readable completed lessons | Your #4 |

Phases 1–3 are what make it "work end to end". 4–5 make it trustworthy. 6 is the experience layer.

---

## 9. What I need from you

**Approval policy**
1. Should a learner's "Mark lesson complete" require instructor approval, or be self-service? (Today it is *silently* self-service because of the `$fillable` bug — I need to know the intended rule before I fix it.)
2. After a module completion is rejected, may the learner resubmit immediately, and must the approver leave a written reason?

**Enrollment**
3. Self-enrollment: instant, or request-and-approve? If approval — module creator, the instructor the learner is assigned to, or any admin?
4. Must admins/instructors be *hard-blocked* from learning, or can someone hold two roles (e.g. an instructor who is also a learner in another department)?

**Certificates**
5. Auto-issue on approved completion, or manual issue only? Who may issue — admin only, or instructors too? Do certificates expire (`valid_until`)?
6. Certificate numbering format — e.g. `EEC-2026-0001` sequential, or keep the random `CERT-…`?
7. Do you have brand assets for the PDF: logo, signatory name + title, signature image, seal? (Current `certificates/pdf.blade.php` is a placeholder.)

**Progress**
8. Confirm the formula: `(approved lessons + passed quizzes) / (total lessons + total quizzes) × 100`. For a quiz, does *passed once* count, or the best score? Should a module with lessons but no quiz reach 100% on lessons alone? (Assume yes unless you say otherwise.)

**Notifications**
9. In-app only, or also email? If email, I need SMTP credentials/from-address. Is a 30-second bell refresh acceptable, or do you want instant (broadcasting)?
10. Should the admin receive *every* activity, or a digest of approvals + certificates only?

**Content & design**
11. Confirm the builder: one page for module + sortable lessons + quiz questions with edit/reorder. Any content type beyond rich text, PDF, MP4, YouTube (SCORM package, audio, embedded slides)?
12. Learner view: which LMS should it feel like (Coursera / Moodle / TalentLMS)? Any brand colours or logo I should use?

Answer 1, 2, 3, 5, 8 and 11 and I can start on phases 1–3 immediately without guessing at your policy.

---

# Phase log

## Locked decisions (from your answers, 2026-10-21)

| Topic | Decision |
|---|---|
| Lesson completion | Requires instructor approval. Instructor manages modules they created **or** were assigned by an admin. |
| Rejection | Reason is mandatory. Learner may request again after a rejection. |
| Self-enrollment | Request-and-approve. Approvers: module creator + assigned instructor. |
| Roles | Dual roles allowed (no hard lock). Learner features only appear once enrolled/requested. |
| Certificates | Issued by instructor or admin at completion approval. Optional expiry (empty = never expires). Optional signature. Numbering `EEC-<current year>-0001`. EEC logo, signatory name + title set by admin. |
| Notifications | In-app + email (SMTP credentials pending). 30s bell refresh. Admin receives every activity, backed by an `activity_logs` table. |
| Deferred | Progress formula confirmation (Q8), builder scope (Q11), learner-view reference + branding (Q12). |

## What changed in this pass

### Phase 1 — blockers
- `admin.certificates.destroy` route added; `/admin/certificates` no longer 500s.
- `AssignmentController` now imports `User`; the per-learner evaluation page resolves.
- Added the missing `quizzes/results.blade.php` (per-question review, score, correct answers).
- `users/template` and `users/import` now register **before** `users/{user}` (download works).
- `session('error')` is rendered in the app layout — every guard message is visible again.
- Fixed the undefined `$percentage` that made the module-completed notification unreachable.

### Phase 2 — progress engine
- New `ProgressService`: one formula, `completed tasks / total tasks`.
- New columns `module_progress.completed_quizzes`, `total_quizzes`, `computed_at`.
- Progress is recalculated from six write paths (lesson submit, lesson approve, lesson reject, quiz submit, module approve, retake reset) plus content changes (lesson add/remove).
- Backfill command: `php artisan lms:recalculate-progress [--module=ID]`.
- Every stored row now matches the task-based figure. The impossible 50%-with-zero-work row is gone.

### Phase 3 — approval chain
- `LessonCompletion` stores `status`, `rejection_reason`, `reviewed_by`, `reviewed_at`; the `status` column is now a string defaulting to `pending`, so submissions are no longer silently auto-approved.
- Lesson rejection keeps the record and requires a reason; resubmission reopens the same row.
- Lesson approvals are reachable from the Retake/Approval screen with an inline reason field.
- Module completion: rejection requires a reason, and a rejected request can be submitted again (row is reopened instead of being stuck).
- Completion gating now uses the task summary (`x of y tasks left`) instead of separate lesson/quiz checks.
- Certificates: `CertificateService` owns numbering (`EEC-2026-0001`), PDF generation and notification. `issued_by` is recorded, so instructors can see what they issued.
- New certificate PDF: EEC logo slot, signatory name/title, optional signature image, issue date, expiry (or "No expiry"), certificate number.
- New `settings` table + `Setting` model for signatory name, title, logo and signature paths.
- `assigned_modules` now has a unique index; enrollment approval is idempotent and notifies the learner.

## Still open (next passes)
1. `activity_logs` table + global model logging, admin activity feed.
2. Full notification matrix (enrollment requested/decided, lesson rejected already done, certificate revoked) + email channel + 30s bell polling.
3. Role hardening: learner-only assign list, block quiz/progress writes for non-learners, hide learner data on the module page, middleware on `my-progress`/`my-enrollments`.
4. Certificate admin screens: issue form (user + module + expiry), signature upload, logo upload, settings page.
5. Course builder + learner player (pending Q11/Q12).
6. Short-answer quizzes: answer inputs in the take view and manual grading before the quiz can count as passed.

## Phase log — second pass (2026-10-21)

### Certificate viewing
- `GET /certificates/{id}/view` streams the PDF inline (owner or issuer only); "View" opens it in a new tab.
- Buttons added to both the learner list and the admin/instructor list, next to Download.

### Activity log (admin only)
- `activity_logs` table + `ActivityLog` model.
- `LogActivity` middleware records every non-GET request: user, route name, path, status, IP, sanitised input (passwords and tokens stripped, uploads reduced to filenames).
- `GET /admin/activity` with filters (user, action, date range) — guarded by `role:admin`, so learners and instructors never reach it.
- Linked from the sidebar Management block for admins only.

### Role hardening
- Assignment dropdown lists learners only (admins/instructors can no longer be enrolled by mistake).
- The module page's Assignments panel is hidden from anyone who cannot assign modules (it used to expose every learner's name).
- Quizzes: `take` and `submit` both require the learner role **and** an active enrollment; instructors/admins are blocked from writing quiz progress.
- Sidebar "My Learning" appears only when the user is enrolled or has a pending request — no hard role lock, so a dual-role admin who enrolls still sees it.

### Signatures and logo
- `users.signature_path` — per-user signature.
- `GET /admin/branding` (admin only): EEC logo upload, signatory name + title, default signature, and a per-instructor signature upload with preview/remove.
- Certificate PDF resolves the signature from the issuing user's profile first, then the default; logo and signatory come from `settings`.
- EEC logo downloaded to `storage/app/public/branding/eec-logo.jpg` and registered; existing certificates regenerated with it.

### Notification matrix — rephrased for confirmation
For each event: **who is told**, **through which channel**, **what the message says**. Pending your confirmation, then implemented in one pass.

| Event | Learner | Instructor | Admin | Channels |
|---|---|---|---|---|
| Requests enrollment | "Request sent" | "X wants to join <module>" | activity feed only | bell + email |
| Enrollment approved | "You're enrolled" + link | — | activity feed | bell + email |
| Enrollment rejected | reason + link | — | activity feed | bell + email |
| Submits a lesson | "Waiting for review" | "X submitted <lesson>" | activity feed | bell only |
| Lesson approved | "Lesson approved" | — | activity feed | bell |
| Lesson rejected | reason + "resubmit" | — | activity feed | bell + email |
| Passes a quiz | score summary | "X passed <quiz> (85%)" | activity feed | bell |
| Requests module completion | "Request sent" | "X finished <module>" | activity feed | bell + email |
| Module approved | "Approved — certificate ready" | "You issued <cert no.>" | activity feed | bell + email |
| Module rejected | reason + link | — | activity feed | bell + email |
| Certificate issued | "Certificate EEC-2026-0001" | copy | **every certificate** | bell + email |

Open sub-decisions: (a) email to everyone or opt-in per user? (b) instant email or a daily digest? (c) admin receives every event by email, or only critical ones (rejections, certificate issues)?

### Still open
- Notification matrix implementation (above) once confirmed, plus 30s bell polling and SMTP.
- Certificate issue screen for admins (pick user + module + expiry) and a settings page for the default signatory — the branding page covers logo/signature, the issue form is still route-only.
- Course builder and learner player (Q11/Q12).
- Short-answer answers in the take view + manual grading.
