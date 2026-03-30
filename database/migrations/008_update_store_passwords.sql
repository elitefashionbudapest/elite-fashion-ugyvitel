-- Migration 008: Bolti fiókok jelszó frissítés
UPDATE users SET password = '$2y$12$dFuLeRb4iuwQXwgMHwVNt.6aZb45YCkpVGX9K6SQDeV7RTEPy8rgG' WHERE role = 'bolt';
