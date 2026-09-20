-- Zusätzlicher Import nach database/schema.sql
-- Erweitert erlaubte Voucher-Beträge auf:
-- 5, 10, 25, 50, 100, 150, 200, 250

ALTER TABLE cryptovouchers DROP CHECK chk_voucher_amount;

ALTER TABLE cryptovouchers
    ADD CONSTRAINT chk_voucher_amount
    CHECK (amount IN (5,10,25,50,100,150,200,250));
