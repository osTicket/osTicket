-- Test-only seed for guard-empty-schedule golden fixture.
-- Run manually after install; does NOT run during normal setup.
-- Does not modify schedules 1-4 used by the demo and other fixtures.
--
--   docker compose exec db mysql -u root -posticket osticket < legacy/seed/verifier-empty-schedule.sql
--
-- Replace ost_ with your TABLE_PREFIX if different.

INSERT INTO ost_schedule (id, flags, name, timezone, description, created, updated)
VALUES (
  5,
  1,
  'Empty Business Hours (verifier fixture)',
  NULL,
  'Test-only schedule with no entries for guard-empty-schedule fixture',
  NOW(),
  NOW()
)
ON DUPLICATE KEY UPDATE
  flags = VALUES(flags),
  name = VALUES(name),
  description = VALUES(description),
  updated = NOW();
