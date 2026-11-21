-- Sites and their credentials.
--
-- Pre-dates the migration runner, so everything is IF NOT EXISTS / guarded:
-- an existing deployment already has this table and must not be disturbed.

CREATE TABLE IF NOT EXISTS sites (
  name       TEXT PRIMARY KEY,   -- Pantheon site machine name, or other host id
  token_hash TEXT NOT NULL,      -- sha256(pepper || token)
  active     INTEGER NOT NULL DEFAULT 1,
  created    INTEGER NOT NULL,
  updated    INTEGER NOT NULL,
  note       TEXT
);
