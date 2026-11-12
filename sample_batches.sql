-- ============================================================
-- Sample Batch Data (with expiry dates) for existing medicines
-- Run this AFTER sample_medicines.sql has been imported.
-- Adds a default supplier if none exists, then creates batches
-- with a mix of expired, expiring-soon, and far-future dates
-- so the Reports page (low-stock/expiry monitoring) has data
-- to display.
-- ============================================================

USE ayurveda_hospital_db;

-- Add a default supplier if the suppliers table is empty
INSERT INTO suppliers (supplier_name, contact_person, contact_number, email, address)
SELECT 'State Pharmaceutical Manufacturing Corporation', 'Stores Manager', '0112345678', 'stores@spmc.lk', 'Ratmalana, Sri Lanka'
WHERE NOT EXISTS (SELECT 1 FROM suppliers LIMIT 1);

-- Use the first available supplier for all sample batches
SET @supplier_id = (SELECT supplier_id FROM suppliers ORDER BY supplier_id LIMIT 1);

-- Already EXPIRED batches
INSERT INTO medicine_batches (medicine_id, batch_no, quantity, expiry_date, received_date, supplier_id)
SELECT medicine_id, 'B-2025-011', 5, '2026-07-15', '2025-07-15', @supplier_id FROM medicines WHERE medicine_name = 'Triphala Churna';

INSERT INTO medicine_batches (medicine_id, batch_no, quantity, expiry_date, received_date, supplier_id)
SELECT medicine_id, 'B-2025-014', 3, '2026-08-01', '2025-08-01', @supplier_id FROM medicines WHERE medicine_name = 'Sesha Thailaya';

-- EXPIRING SOON (within next 90 days of 2026-09-10)
INSERT INTO medicine_batches (medicine_id, batch_no, quantity, expiry_date, received_date, supplier_id)
SELECT medicine_id, 'B-2026-021', 10, '2026-09-30', '2025-09-30', @supplier_id FROM medicines WHERE medicine_name = 'Sithopaladi Churna';

INSERT INTO medicine_batches (medicine_id, batch_no, quantity, expiry_date, received_date, supplier_id)
SELECT medicine_id, 'B-2026-022', 8, '2026-10-15', '2025-10-15', @supplier_id FROM medicines WHERE medicine_name = 'Ksheerabala Thailaya';

INSERT INTO medicine_batches (medicine_id, batch_no, quantity, expiry_date, received_date, supplier_id)
SELECT medicine_id, 'B-2026-023', 12, '2026-11-20', '2025-11-20', @supplier_id FROM medicines WHERE medicine_name = 'Dasamoolakatutrayadi Kashayam';

INSERT INTO medicine_batches (medicine_id, batch_no, quantity, expiry_date, received_date, supplier_id)
SELECT medicine_id, 'B-2026-024', 6, '2026-12-05', '2025-12-05', @supplier_id FROM medicines WHERE medicine_name = 'Chandraprabha Vati';

-- FAR FUTURE (safe stock, 1-2 years out)
INSERT INTO medicine_batches (medicine_id, batch_no, quantity, expiry_date, received_date, supplier_id)
SELECT medicine_id, 'B-2026-030', 15, '2027-06-01', '2026-06-01', @supplier_id FROM medicines WHERE medicine_name = 'Dasamoola Churna';

INSERT INTO medicine_batches (medicine_id, batch_no, quantity, expiry_date, received_date, supplier_id)
SELECT medicine_id, 'B-2026-031', 10, '2027-08-15', '2026-08-15', @supplier_id FROM medicines WHERE medicine_name = 'Hingwastaka Churna';

INSERT INTO medicine_batches (medicine_id, batch_no, quantity, expiry_date, received_date, supplier_id)
SELECT medicine_id, 'B-2026-032', 20, '2027-12-01', '2026-08-01', @supplier_id FROM medicines WHERE medicine_name = 'Avipattikara Churna';

INSERT INTO medicine_batches (medicine_id, batch_no, quantity, expiry_date, received_date, supplier_id)
SELECT medicine_id, 'B-2026-033', 12, '2028-01-10', '2026-07-01', @supplier_id FROM medicines WHERE medicine_name = 'Maha Narayana Thailaya';

INSERT INTO medicine_batches (medicine_id, batch_no, quantity, expiry_date, received_date, supplier_id)
SELECT medicine_id, 'B-2026-034', 9, '2027-09-20', '2026-06-20', @supplier_id FROM medicines WHERE medicine_name = 'Chandanadi Thailaya';

INSERT INTO medicine_batches (medicine_id, batch_no, quantity, expiry_date, received_date, supplier_id)
SELECT medicine_id, 'B-2026-035', 14, '2027-11-01', '2026-05-01', @supplier_id FROM medicines WHERE medicine_name = 'Dhanwantharam Thailaya';

INSERT INTO medicine_batches (medicine_id, batch_no, quantity, expiry_date, received_date, supplier_id)
SELECT medicine_id, 'B-2026-036', 18, '2027-07-15', '2026-04-15', @supplier_id FROM medicines WHERE medicine_name = 'Dasamoolarishtaya';

INSERT INTO medicine_batches (medicine_id, batch_no, quantity, expiry_date, received_date, supplier_id)
SELECT medicine_id, 'B-2026-037', 11, '2027-10-10', '2026-03-10', @supplier_id FROM medicines WHERE medicine_name = 'Ashokarishtaya';

INSERT INTO medicine_batches (medicine_id, batch_no, quantity, expiry_date, received_date, supplier_id)
SELECT medicine_id, 'B-2026-038', 7, '2028-02-01', '2026-02-01', @supplier_id FROM medicines WHERE medicine_name = 'Chyawanaprasha';

INSERT INTO medicine_batches (medicine_id, batch_no, quantity, expiry_date, received_date, supplier_id)
SELECT medicine_id, 'B-2026-039', 16, '2027-05-15', '2026-01-15', @supplier_id FROM medicines WHERE medicine_name = 'Brahmi Ghritham';
