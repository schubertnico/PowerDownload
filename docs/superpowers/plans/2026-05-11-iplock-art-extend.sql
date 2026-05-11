-- Extends pdl3_iplock.art ENUM with 'register' and 'lostpw'
-- so registration and password-lost forms can be IP-rate-limited
-- without triggering STRICT_TRANS_TABLES truncation errors.
-- Idempotent: re-running keeps the column definition identical.
ALTER TABLE pdl3_iplock MODIFY art ENUM('comment','vote','login','register','lostpw') NOT NULL DEFAULT 'comment';
