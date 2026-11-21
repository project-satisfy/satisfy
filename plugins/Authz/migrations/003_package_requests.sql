-- Packages a site asked for but is not entitled to.
--
-- A flag, not a counter. One row per (site, package) regardless of how many
-- times it is requested — the question an admin needs answered is "is this site
-- trying to reach something it cannot have", not "how often". Writing once and
-- ignoring duplicates also keeps a denied build loop from hammering SQLite.
--
-- Rows here are the approval queue: approving one inserts into entitlements and
-- marks the request resolved, so the history of what was asked for survives.

CREATE TABLE IF NOT EXISTS package_requests (
  site       TEXT NOT NULL,
  package    TEXT NOT NULL,
  first_seen INTEGER NOT NULL,
  last_seen  INTEGER NOT NULL,
  resolved   INTEGER NOT NULL DEFAULT 0,  -- 1 once approved or dismissed
  PRIMARY KEY (site, package)
);

CREATE INDEX IF NOT EXISTS package_requests_open
  ON package_requests (resolved, site);
