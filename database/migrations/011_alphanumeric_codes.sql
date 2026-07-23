-- Wider org/member codes for alphanumeric values (e.g. MEM1201, K7M29)
ALTER TABLE organizations MODIFY org_code VARCHAR(12) NOT NULL;
ALTER TABLE users MODIFY member_code VARCHAR(12) NULL DEFAULT NULL;
