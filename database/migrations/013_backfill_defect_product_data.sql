-- Visszamenőleges kitöltés: meglévő selejt tételeknél termék név és ár hozzárendelése
UPDATE defect_items d
JOIN products p ON d.barcode = p.barcode
SET d.product_name = p.name,
    d.product_price = p.gross_price
WHERE d.product_name IS NULL;
