# `/lk` Redesign Agents Orchestration

**Role:** Studio Producer  
**Scope:** `/lk` only  
**Mode:** living tracker for the 5-iteration redesign plan  

## Status Legend

- `Done` - deliverable already captured in the current plan set
- `In progress` - active ownership, still being shaped
- `Ready` - scoped and waiting for implementation
- `Planned` - not started yet
- `Pending` - needs final approval or trigger
- `Blocked` - cannot proceed until a dependency clears

## Tracker

| # | Role | Ownership | Deliverable | Status |
|---|---|---|---|---|
| 1 | Studio Producer | Program control, sequencing, scope freeze, rollout gates | Single source of truth for the 5-iteration plan and release decisions | In progress |
| 2 | Product Manager | User value, priority calls, scope tradeoffs | Prioritized backlog for `/lk` redesign slices | Ready |
| 3 | UX Researcher | Pain-point mapping, journey validation, issue severity | Research pack with 24 issues, CJM, and P0/P1/P2 split | Done |
| 4 | UX Architect | IA, shell contract, responsive behavior | Component architecture and 6-screen wireframe system | Done |
| 5 | UI Designer | Visual system, layout hierarchy, states | Design System v1 and key screen compositions | Done |
| 6 | Brand Guardian | Tone, visual consistency, anti-pattern prevention | Brand-safe ruleset and consistency checklist | Done |
| 7 | Content Designer | Copy, empty states, confirmations, microcopy | Final UI copy set for actions, errors, and empty states | Planned |
| 8 | Design System Owner | Tokens, components, variant rules | Shared token and component contract for `/lk` | Done |
| 9 | Frontend Lead | Blade/Tailwind/Alpine implementation | Shell, navigation, and page-level UI implementation plan | Ready |
| 10 | Backend Lead | Contract freeze, data shaping, route safety | Stable route/view-model contract for `/lk` | Ready |
| 11 | Accessibility Specialist | Keyboard, semantics, screen-reader support | WCAG 2.1 AA checklist and component-level a11y review | Ready |
| 12 | QA Lead | Acceptance gates, regression scope, release sign-off | Iteration-by-iteration QA gate and smoke suite | Ready |
| 13 | Test Automation Engineer | Route and flow automation | Regression tests for core `/lk` journeys | Planned |
| 14 | Motion / Interaction Designer | Motion budget, reduced-motion fallback | Functional interaction spec with reduced-motion mapping | Done |
| 15 | Analytics / Telemetry Lead | Event coverage, funnel visibility | Tracking spec for rollout, canary, and error monitoring | Planned |
| 16 | DevOps / Release Manager | Feature flags, cohorting, rollout control | Flag plan, staged rollout, and rollback path | Ready |
| 17 | Security / Privacy Reviewer | Auth/session risk, sensitive actions | Security review for confirm steps and sensitive flows | Ready |
| 18 | Customer Support / Ops Enablement | Support readiness, FAQ, escalation paths | Support brief for new `/lk` flows and known issues | Planned |
| 19 | Stakeholder Approver | Final business approval | Go/No-Go sign-off for each release gate | Pending |

## Iteration Mapping

- Iteration 1: roles 1, 4, 5, 6, 8, 9, 10, 11
- Iteration 2: roles 1, 4, 5, 7, 8, 9, 11, 12, 15
- Iteration 3: roles 1, 4, 9, 10, 11, 12, 13, 17
- Iteration 4: roles 1, 7, 10, 11, 12, 16, 17, 18
- Iteration 5: roles 1, 12, 13, 15, 16, 19

## Producer Notes

- Keep backend contracts frozen unless a dependency exception is explicitly approved.
- Treat QA gates as release blockers, not paperwork.
- When two roles overlap, Producer resolves ownership before implementation starts.

