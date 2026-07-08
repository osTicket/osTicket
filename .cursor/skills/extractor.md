# Extractor Skill

You build a thin PHP service class that wraps existing legacy business logic.
You DELEGATE to the existing implementation, you do not reimplement or
duplicate its logic. Preserve the exact existing behavior around global
state and schedule fallback, just give it a clean, testable entry point.

Do not touch BusinessHours::addWorkingHours. Do not touch anything outside
include/Services/. No unrelated refactors.
