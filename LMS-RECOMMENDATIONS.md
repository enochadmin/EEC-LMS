# EEC LMS — Gap Analysis Against Standard LMS Practice

Reference points are the accepted international standards for learning systems, not opinion:
SCORM 1.2 / SCORM 2004 (ADL), xAPI + cmi5 (ADL), LTI 1.3 and OneRoster 1.2 (1EdTech),
WCAG 2.2 AA (W3C Recommendation, Oct 2023, also ISO/IEC 40500), SCIM 2.0 (RFC 7643/7644),
SAML 2.0 / OpenID Connect, Open Badges 3.0 (1EdTech), and ISO/IEC 27001 for the security controls.

Priority: **P0** = expected by any buyer in a first demo · **P1** = separates you from competitors ·
**P2** = differentiator or later stage.

---

## 1. Content interoperability — the biggest structural gap

| ID | Recommendation | Why it matters | Effort |
|---|---|---|---|
| R1 | **SCORM 1.2 + 2004 package import** (zip upload, launch in an iframe, read `cmi.core.lesson_status` / `cmi.completion_status` back into progress) | Still the most widely used eLearning standard. Without it you cannot ingest purchased or vendor-authored courses — every module must be rebuilt by hand. This is normally a *must-have* in an LMS tender. | High (2–3 weeks) |
| R2 | **xAPI / cmi5 statement store** (optional, after R1) | Captures learning outside a lesson: video seeks, simulations, on-the-job evidence. cmi5 is "SCORM for xAPI" and is the modern replacement path. | High |
| R3 | **LTI 1.3** (launch external tools as activities) | Lets you plug in H5P, Zoom, Teams, Mentimeter, a question-bank vendor — without building each one. 1EdTech's current version; replaces the deprecated LTI 1.1. | Medium |
| R4 | **OneRoster 1.2 / HR CSV sync** | You already model departments and employee IDs. A nightly feed from HR removes manual enrolment entirely and keeps leavers out. | Medium |
| R5 | **Audio, embedded slides (PPT/PDF viewer), and code/HTML blocks** as lesson types | Today: rich text, PDF, MP4, YouTube. Corporate buyers usually assume at least audio + slide deck. | Low |
| R6 | **Downloadable module export (own format or Common Cartridge)** | Portability/backup of authored content; also an answer to "can we get our data out?". | Medium |

## 2. Assessment rigour

| ID | Recommendation | Why | Effort |
|---|---|---|---|
| R7 | **Question bank + random draw per attempt** (pull N of M, per-topic quotas) | Prevents answer sharing, which is the #1 complaint about compliance quizzes at your scale. | Medium |
| R8 | **Shuffle answers + per-quiz time limit + attempt cap with cooldown** | Standard exam hygiene. Currently attempts are unlimited. | Low |
| R9 | **Manual grading queue for short answers** (grade, comment, re-grade, notify) | Short-answer capture exists but is not a complete loop; a quiz can sit ungraded forever. | Medium |
| R10 | **Prerequisites / sequencing** (module B unlocks after A; lesson N+1 after N) | Required for any curriculum that has an order. | Medium |
| R11 | **Learning paths / curricula** (bundle modules into a program with its own completion rule) | "Onboarding path", "Safety certification track" — the unit enterprises actually assign. | Medium |
| R12 | **Course feedback survey** (Kirkpatrick level 1) | Cheap to add, and gives instructors a quality signal they currently have none of. | Low |

## 3. Credentials

| ID | Recommendation | Why | Effort |
|---|---|---|---|
| R13 | **Public certificate verification page + QR code** (`/verify/EEC-2026-0001`) | Today a certificate PDF is a picture. A verifiable number is what makes it evidence in an audit. Cheap and high perceived value. | Low |
| R14 | **Open Badges 3.0 / verifiable credential export** | Machine-verifiable, portable credential; the emerging standard for skills evidence. | Medium |
| R15 | **Recertification rules** (expiry + automatic re-enrolment + reminder cadence) | You store `valid_until` but nothing acts on it. Compliance training lives or dies on renewal. | Medium |

## 4. Accessibility & compliance — currently unaddressed

| ID | Recommendation | Why | Effort |
|---|---|---|---|
| R16 | **WCAG 2.2 AA pass**: keyboard navigation, visible focus, contrast, form labels, ARIA on the bell/menus, no colour-only status | Public-sector and most enterprise procurement ask for a VPAT/accessibility statement. Retrofitting later is far more expensive than fixing now. | Medium |
| R17 | **Captions + transcripts for video** (WCAG 1.2.2 / 1.2.1) | Legally required in many jurisdictions once video is training content. | Medium |
| R18 | **Data-subject controls**: consent record, export-my-data, delete-my-data, retention policy | GDPR-shaped expectation even where GDPR does not strictly apply. | Medium |
| R19 | **Security hardening**: MFA for admins, login throttling, session timeout, password policy, signed URLs for private files | ISO/IEC 27001-aligned baseline. Your files are currently served from the public disk. | Medium |

## 5. Learner experience

| ID | Recommendation | Why | Effort |
|---|---|---|---|
| R20 | **"Continue learning" resume point** on the dashboard (last lesson, % left, estimated minutes from lesson durations) | The single most requested learner feature in every LMS review I have seen. | Low |
| R21 | **Catalogue with search/filter** (category, duration, language, mandatory vs optional) | Learners currently only see what is assigned; self-development content has nowhere to live. | Low |
| R22 | **Due dates + reminder/escalation ladder** (learner → manager → admin) | Compliance deadlines are the reason people log in at all. | Medium |
| R23 | **Localisation (English + Amharic)**, RTL-safe layout, date/number formats | An Ethiopian workforce LMS that only speaks English will lose adoption at the shop-floor level. Laravel's lang files make this tractable. | Medium |
| R24 | **Mobile-first pass / PWA shell** | Shift staff will do training on a phone, not a desktop. | Medium |

## 6. Administration & analytics

| ID | Recommendation | Why | Effort |
|---|---|---|---|
| R25 | **Scheduled report emails** (weekly digest to managers: overdue, completion %, at-risk learners) | Turns the new Reports tab from "something you visit" into "something that arrives". | Low |
| R26 | **CSV/XLSX export on every report** | Finance and auditors always ask. | Low |
| R27 | **Bulk enrolment by CSV** (user × module matrix) | Assigning 400 people one dropdown at a time is the current ceiling. | Low |
| R28 | **Supervisor/manager role** scoped to a department, seeing only their team | The most common RBAC request after admin/instructor/learner. | Medium |
| R29 | **Lesson-level drop-off funnel** (where learners stop inside a module) | You now have time-on-task; the funnel turns it into an actionable insight. | Low |
| R30 | **Data retention & archive policy** (old attempts, logs, certificates) | Keeps reports fast and satisfies "we do not keep data forever". | Low |

---

## What I would do first (if you want a sequence)

1. **R13** certificate verification + **R20** resume point + **R26** exports — small, visible, no schema risk.
2. **R16/R17** accessibility and captions — cheapest while the UI is still changing.
3. **R1** SCORM import — the only item on this list that changes what the product *is*, and usually the first question in a procurement.
4. **R7/R8** assessment rigour, then **R15** recertification.
5. **R25/R27/R28** admin leverage, once usage grows past a few hundred learners.

## What I deliberately did **not** recommend

- **Gamification (points/badges/leaderboards)** — high effort, low effect in mandatory compliance training.
- **AI course generation** — fashionable, but it writes content your compliance team then has to audit; not a gap, a distraction at this stage.
- **Native mobile apps** — a responsive PWA covers the same need at a fraction of the cost until you have real mobile telemetry.
