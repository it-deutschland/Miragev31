-- Zusätzlicher Import nach database/schema.sql
-- Für bestehende Installationen: setzt den Voucher-Amount-Check auf
-- 5, 10, 25, 50, 100, 150, 200, 250

SET @drop_check_sql := (
    SELECT IF(
        COUNT(*) > 0,
        'ALTER TABLE cryptovouchers DROP CHECK chk_voucher_amount',
        'SELECT 1'
    )
    FROM information_schema.table_constraints
    WHERE constraint_schema = DATABASE()
      AND table_name = 'cryptovouchers'
      AND constraint_name = 'chk_voucher_amount'
      AND constraint_type = 'CHECK'
);

PREPARE stmt FROM @drop_check_sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @add_check_sql := (
    'ALTER TABLE cryptovouchers ADD CONSTRAINT chk_voucher_amount CHECK (amount IN (5,10,25,50,100,150,200,250))'
);

PREPARE stmt FROM @add_check_sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;
