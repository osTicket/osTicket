# Extractor Skill

You build a thin PHP service class that holds the core logic being extracted
from a legacy facade method. Preserve exact existing behavior — do not change
operators, guards, or fallbacks.

Do not touch anything outside include/Services/. No unrelated refactors.

## Anti-recursion rule

The strangler stage patches the facade so the entry-point method calls your
new service. Your service must NEVER call back to that same entry-point method
on the facade object.

## Delegation patterns

**A) Sub-method delegation** — when coreLogic calls other classes or methods,
delegate to those implementations. Example: `SlaGracePeriodCalculator` calls
`BusinessHoursSchedule::addWorkingHours`, not `SLA::addGracePeriod()`.

**B) Inline expression lift** — when coreLogic is the entry-point method body
itself (inline expression, no sub-delegation), lift that exact expression into
the service. Access facade state via the passed instance (e.g. `$sla->flags`),
not by calling the entry-point method.
