-- ============================================================
-- Demo veri. Tüm demo kullanıcı şifreleri: 1234
-- ============================================================
USE kafe_yonetim;

INSERT INTO users (full_name, username, password_hash, role, discount_limit) VALUES
('Sistem Yöneticisi', 'admin',  '$2y$12$plxksmbltH8512POjHSinO0YZ0HPYCX1.UpUpmDJfETrDFayc0PcK', 'admin', 100),
('Mehmet Yönetici',   'yonetici','$2y$12$plxksmbltH8512POjHSinO0YZ0HPYCX1.UpUpmDJfETrDFayc0PcK', 'yonetici', 100),
('Ahmet Garson',      'garson', '$2y$12$plxksmbltH8512POjHSinO0YZ0HPYCX1.UpUpmDJfETrDFayc0PcK', 'garson', 5),
('Ayşe Kasa',         'kasa',   '$2y$12$plxksmbltH8512POjHSinO0YZ0HPYCX1.UpUpmDJfETrDFayc0PcK', 'kasa', 10),
('Mutfak Ekranı',     'mutfak', '$2y$12$plxksmbltH8512POjHSinO0YZ0HPYCX1.UpUpmDJfETrDFayc0PcK', 'mutfak', 0),
('Depo Sorumlusu',    'depo',   '$2y$12$plxksmbltH8512POjHSinO0YZ0HPYCX1.UpUpmDJfETrDFayc0PcK', 'depo', 0);

INSERT INTO settings (setting_key, setting_value) VALUES
('cafe_name', 'Lovable Kafe'),
('currency', '₺'),
('default_vat', '10');

INSERT INTO categories (name, sort_order) VALUES
('Sıcak İçecekler', 1),
('Soğuk İçecekler', 2),
('Kahvaltı', 3),
('Yiyecek', 4),
('Tatlı', 5),
('Atıştırmalık', 6);

INSERT INTO products (category_id, name, description, sale_price, cost_price, vat_rate, kitchen_station) VALUES
(1, 'Espresso', 'Tek shot espresso', 55, 12, 10, 'bar'),
(1, 'Latte', 'Espresso + süt', 90, 23, 10, 'bar'),
(1, 'Filtre Kahve', 'Günün kahvesi', 75, 15, 10, 'bar'),
(1, 'Çay', 'Demleme çay', 25, 4, 10, 'bar'),
(2, 'Ice Latte', 'Buzlu latte', 100, 25, 10, 'bar'),
(2, 'Limonata', 'Ev yapımı', 85, 18, 10, 'bar'),
(2, 'Cola', '330 ml kutu', 60, 22, 10, 'yok'),
(3, 'Serpme Kahvaltı', 'Kişi başı', 350, 140, 10, 'mutfak'),
(3, 'Menemen', 'Peynirli', 180, 55, 10, 'mutfak'),
(4, 'Hamburger', '180 gr köfte', 280, 95, 10, 'mutfak'),
(4, 'Patates Kızartması', 'Porsiyon', 120, 30, 10, 'mutfak'),
(4, 'Tost', 'Kaşarlı', 130, 40, 10, 'mutfak'),
(5, 'Cheesecake', 'Frambuazlı', 160, 45, 10, 'yok'),
(5, 'Brownie', 'Sıcak servis', 150, 40, 10, 'mutfak'),
(6, 'Cookie', 'Çikolatalı', 70, 18, 10, 'yok');

INSERT INTO cafe_tables (name, section, capacity, sort_order, qr_token) VALUES
('01', 'İç Alan', 4, 1, 'qr-ic-01'),
('02', 'İç Alan', 4, 2, 'qr-ic-02'),
('03', 'İç Alan', 2, 3, 'qr-ic-03'),
('04', 'İç Alan', 6, 4, 'qr-ic-04'),
('05', 'İç Alan', 4, 5, 'qr-ic-05'),
('06', 'İç Alan', 4, 6, 'qr-ic-06'),
('07', 'Teras', 4, 7, 'qr-ter-07'),
('08', 'Teras', 4, 8, 'qr-ter-08'),
('09', 'Teras', 8, 9, 'qr-ter-09'),
('10', 'Bahçe', 4, 10, 'qr-bah-10'),
('11', 'Bahçe', 4, 11, 'qr-bah-11'),
('12', 'Bahçe', 2, 12, 'qr-bah-12');

INSERT INTO stock_items (name, unit, current_qty, min_qty, cost_price) VALUES
('Kahve Çekirdeği', 'gr', 25000, 5000, 0.65),
('Süt', 'ml', 42000, 10000, 0.05),
('Şeker', 'gr', 18000, 4000, 0.02),
('Hamburger Köftesi', 'adet', 120, 30, 45),
('Hamburger Ekmeği', 'adet', 130, 30, 8),
('Patates', 'gr', 40000, 8000, 0.03),
('Kaşar Peyniri', 'gr', 12000, 3000, 0.35),
('Yumurta', 'adet', 200, 60, 5),
('Limon', 'adet', 80, 20, 7);

INSERT INTO recipes (product_id, name) VALUES
(2, 'Latte Reçetesi'),
(10, 'Hamburger Reçetesi'),
(11, 'Patates Kızartması Reçetesi');

INSERT INTO recipe_items (recipe_id, stock_item_id, quantity, unit) VALUES
(1, 1, 18, 'gr'),
(1, 2, 200, 'ml'),
(1, 3, 5, 'gr'),
(2, 4, 1, 'adet'),
(2, 5, 1, 'adet'),
(2, 7, 20, 'gr'),
(3, 6, 250, 'gr');

INSERT INTO suppliers (name, phone, email, address) VALUES
('Anadolu Gıda A.Ş.', '0212 000 00 00', 'satis@anadolugida.com', 'İstanbul'),
('Kahve Dünyası Toptan', '0216 111 11 11', 'toptan@kahve.com', 'İstanbul');
