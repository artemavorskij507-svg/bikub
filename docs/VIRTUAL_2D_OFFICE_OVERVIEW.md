# Virtual 2D Office Overview

## What It Is

Virtual 2D Office is an isometric office simulation where every agent is a digital teammate.
Instead of raw terminal logs, operators see live execution as office activity:

- agents move between zones,
- work at desks,
- gather in meeting rooms,
- exchange messages and assets,
- update task states in real time.

## Why It Matters

This interface turns a complex multi-agent workflow into an observable system.
It helps answer operational questions quickly:

- who is currently active,
- which team is blocked,
- where collaboration is happening,
- which tasks are progressing or failing.

## Practical Analogy

The concept aligns with visual-agent office patterns found in modern agentic tooling:

1. Workspace-style office visualizers (agents as workers with live actions).
2. AgentOffice-like ecosystems (team growth, sub-agents, internal coordination).
3. Pixel-office CLI visualizers (stateful behavior, queueing, memory cleanup, stress signals).

The goal in Bikube is not visual novelty alone, but actionable observability of autonomous execution.

## Agent Behavior Model

The runtime behavior follows a loop:

1. Perceive: agent receives context, tasks, and teammate signals.
2. Think: model decides next action using current memory and constraints.
3. Act: agent performs work (tool call, file update, movement, communication).
4. Remember: outcomes are persisted for future decisions and audits.

## Bikube Mapping

In Bikube, this model is mapped to existing modules:

- `agency_agents`: identity, role, zone, status, behavior metadata.
- `agency_agent_tasks`: task ownership, progress, completion/failure.
- `agency_agent_communications`: direct/team messaging and asset handoff trace.
- `agency_agent_activities`: timeline of movements, meetings, and work events.
- `agency_office_zones`: office topology (open space, meeting room, server room, kitchen, relax zone).

## Current UX Targets

- real-time office map with moving pixel agents,
- status indicators (`working`, `meeting`, `break`, `offline`),
- mini-map with full office occupancy,
- team board and sprint task board,
- live interaction feed (chat + notifications + activity timeline),
- clear audit trail for coordination events.

## Note On Models And Privacy

The architecture supports different inference backends.
When local models are used (for example via Ollama), data can remain on local infrastructure.
When cloud models are used, governance and data-handling policy must be applied accordingly.
