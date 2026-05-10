# LK Redesign Product Backlog

## Scope
Редизайн рабочего кабинета `/lk` для ролей courier/executor/roadside с сохранением критичных бизнес-потоков (orders, status actions, wallet payouts, notifications, schedule, support, executor jobs, roadside jobs).

## Priority Model
- `P0`: обязательно для релиза (без этого редизайн не выпускаем)
- `P1`: высокий эффект, можно выпускать волнами после P0
- `P2`: улучшения качества и масштабирования

## Backlog

### P0 (Release Blockers)
| ID | Item | Why | DoD / Acceptance |
|---|---|---|---|
| P0-01 | Preserve functional contracts for all `/lk` actions | Нельзя сломать операционные процессы | Все текущие endpoints и payload-контракты работают: `orders.action`, `worker.status`, `wallet.request-payout`, `notifications.*`, `schedule.update-availability`, `support.*`, `roadside-jobs.action`, `executor.jobs.*` |
| P0-02 | Mobile-first shell for LK layout/navigation | Большая доля полевых пользователей с телефона | Навигация и ключевые CTA доступны на 360px+, без горизонтального скролла, все critical flows выполняются одной рукой |
| P0-03 | Critical user journeys smoke/E2E pack | Снижение риска регрессий | E2E покрывает 10 ключевых сценариев: статус смены, цикл заказа, payout request, mark notifications, update availability, create/reply support ticket, roadside actions, executor accept/finish |
| P0-04 | Unified error handling for all AJAX/forms | Сейчас ошибки неравномерно показываются | Для всех async действий есть одинаковый UX: loading, success toast, actionable error text, retry path |
| P0-05 | Performance budget + asset cleanup | Тяжёлый UI и нестабильная мобильная отзывчивость | Установлен budget и CI-check: JS/CSS budget, lazy loading второстепенных блоков, no blocking regressions |
| P0-06 | Accessibility baseline (WCAG AA practical) | Рабочий инструмент должен быть устойчивым | Keyboard navigation, visible focus, aria-label на action buttons, contrast на status badges, skip-link сохраняется |
| P0-07 | Observability baseline for `/lk` | Без метрик нельзя доказать эффект редизайна | Добавлены события и дашборд: latency, task completion, action errors, mobile UX signals |

### P1 (High Impact)
| ID | Item | Why | DoD / Acceptance |
|---|---|---|---|
| P1-01 | Dashboard information architecture by role | Сейчас перегружен и визуально шумный | Для courier/executor/roadside отдельные приоритетные блоки, скрыт вторичный шум, time-to-primary-action < 5s |
| P1-02 | Orders list/detail UX refactor | Самый частый рабочий экран | Фильтры статусов, action buttons, timeline и payout context становятся однозначными; сокращение ошибочных действий |
| P1-03 | Wallet payout UX hardening | Финансы критичны к доверию | Ясный available balance, валидации до отправки, понятные статусы payout history |
| P1-04 | Notifications center simplification | Важные события теряются в потоке | Чёткое разделение unread/read, массовые операции без перезагрузки, быстрый переход к источнику уведомления |
| P1-05 | Support ticket flow improvement | Канал эскалации проблем | Быстрое создание тикета по шаблонам, удобный диалог, статусы и SLA-подсказки |
| P1-06 | Schedule UX refresh | Планирование доступности влияет на supply | Toggle доступности с мгновенным фидбеком, ясное разделение today/upcoming/past |
| P1-07 | Consistent design system tokens for `/lk` | Снижение хаоса в стилях | Единые токены spacing/typography/color/status, переиспользуемые компоненты карточек и форм |

### P2 (Optimization & Scale)
| ID | Item | Why | DoD / Acceptance |
|---|---|---|---|
| P2-01 | Personalization and saved preferences UX | Рост удержания и скорости работы | Персональные quick actions и запоминание предпочтений отображения |
| P2-02 | Assistant widget task-oriented mode | Ускорение self-service | Для top intents (orders, earnings, schedule, stats) структурированные ответы и quick actions |
| P2-03 | Advanced mobile ergonomics | Полевой контекст использования | Reachability improvements, larger touch targets, reduced accidental taps |
| P2-04 | Progressive enhancement/offline-safe states | Нестабильный интернет в полях | Грейсфул деградация на слабой сети, кэширование критичных данных чтения |
| P2-05 | Experiment framework for LK UX | Непрерывное улучшение | A/B-ready hooks для CTA/flows без изменения backend контрактов |

## KPI Before/After

> `Before` = baseline неделя `W0` (замер до релиза редизайна).
> Если исторических данных нет, замеряем в первые 5 рабочих дней через добавленную observability.

### 1) Latency
| KPI | Before (W0 baseline) | After target (post-release +4 weeks) |
|---|---:|---:|
| `/lk` dashboard page load, p95 (mobile) | `TBD W0` | `<= 2.5s` |
| `/lk/orders` page load, p95 (mobile) | `TBD W0` | `<= 2.8s` |
| Action API latency p95 (`orders.action`, `worker.status`, `wallet.request-payout`) | `TBD W0` | `<= 700ms` |
| UI feedback latency (click -> visible loading state), p95 | `TBD W0` | `<= 100ms` |

### 2) Task Completion
| KPI | Before (W0 baseline) | After target |
|---|---:|---:|
| Успешное завершение сценария "взять заказ -> завершить" | `TBD W0` | `>= 88%` |
| Успешное создание payout request без ошибки/отката | `TBD W0` | `>= 95%` |
| Успешное создание support ticket с 1 попытки | `TBD W0` | `>= 97%` |
| Успешное обновление availability (today/tomorrow) | `TBD W0` | `>= 98%` |

### 3) Error Rate
| KPI | Before (W0 baseline) | After target |
|---|---:|---:|
| Frontend JS errors in `/lk` (per 1k sessions) | `TBD W0` | `<= 10` |
| 4xx/5xx rate на критичных `/lk` action endpoints | `TBD W0` | `<= 1.0%` |
| Validation failure rate on payout/support/profile forms | `TBD W0` | `<= 3.0%` |

### 4) Mobile UX
| KPI | Before (W0 baseline) | After target |
|---|---:|---:|
| Mobile task completion (core 5 scenarios) | `TBD W0` | `>= 85%` |
| Time-to-first-primary-action (mobile dashboard) | `TBD W0` | `<= 5s` |
| Rage tap sessions share | `TBD W0` | `<= 5%` |
| Mobile bounce rate for authenticated `/lk` sessions | `TBD W0` | `-20% vs W0` |

## Delivery Phasing
- **Phase 0 (1 week):** instrumentation + baseline KPI capture + UX audit freeze
- **Phase 1 (2-3 weeks):** P0 delivery and regression-safe release
- **Phase 2 (2 weeks):** P1 wave (orders/wallet/notifications/support/schedule)
- **Phase 3 (ongoing):** P2 experiments and optimization

## Governance (Project Shepherd)
- Weekly review: KPI deltas, incident log, regression report
- Release gate for redesign: no open P0, no Sev-1 regressions in critical flows, KPI trend non-degrading 7 дней подряд
- Owners:
  - Product: scope, KPI, prioritization
  - Design: UX/UI consistency and accessibility
  - Engineering: implementation and performance budget
  - QA: E2E/regression pack and release sign-off
