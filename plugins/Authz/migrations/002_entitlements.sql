-- Per-package entitlements.
--
-- Deny by default: a site with no rows here is entitled to nothing. Granting a
-- package implicitly grants its dependency closure, which is computed at check
-- time from the satis metadata rather than stored, so it stays correct when a
-- package's requirements change.

CREATE TABLE IF NOT EXISTS entitlements (
  site    TEXT NOT NULL,
  package TEXT NOT NULL,          -- 'vendor/name', or 'vendor/*' for a whole vendor
  granted INTEGER NOT NULL,
  by_whom TEXT,
  PRIMARY KEY (site, package)
);

CREATE INDEX IF NOT EXISTS entitlements_site ON entitlements (site);

-- Per-site package enforcement, separate from the global credential flag.
--
-- 0 = observe: denials are recorded and flagged, but the request is allowed.
-- 1 = enforce: denials actually refuse with 403.
--
-- The column default is 1 so a NEWLY issued site is deny-by-default, which is
-- the intended workflow: issue a token, let the site's requests flag
-- themselves, approve what it legitimately needs.
--
-- Existing rows are set to 0 below. Flipping ~20 live sites to deny-all in a
-- migration would break every build at once, and there is no reason to couple
-- a schema change to a policy change.
ALTER TABLE sites ADD COLUMN package_enforce INTEGER NOT NULL DEFAULT 1;

UPDATE sites SET package_enforce = 0;
