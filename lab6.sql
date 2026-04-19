-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- 主機： 127.0.0.1
-- 產生時間： 2026-03-08 15:55:41
-- 伺服器版本： 10.4.32-MariaDB
-- PHP 版本： 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+08:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- 資料庫： `lab6`
--

-- --------------------------------------------------------

--
-- 資料表結構 `cart`
--

CREATE TABLE `cart` (
  `cart_id` bigint(20) NOT NULL COMMENT '購物車項目唯一編號',
  `member_id` int(10) UNSIGNED NOT NULL COMMENT '會員帳號',
  `product_id` int(11) NOT NULL COMMENT '商品編號（參考 products 表）',
  `snapshot_name` varchar(100) NOT NULL COMMENT '加入時的商品名稱',
  `snapshot_price` decimal(10,2) NOT NULL COMMENT '加入時的價格',
  `snapshot_description` text DEFAULT NULL COMMENT '加入時的描述（可選）',
  `snapshot_type` enum('drinks','food','toy','e-things') NOT NULL COMMENT '加入時的類型',
  `qty` int(11) UNSIGNED NOT NULL DEFAULT 1 COMMENT '數量',
  `created_at` datetime DEFAULT current_timestamp() COMMENT '加入時間',
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp() COMMENT '最後修改時間'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='購物車（快照版）';

-- --------------------------------------------------------

--
-- 資料表結構 `member`
--

CREATE TABLE `member` (
  `Member_id` int(10) UNSIGNED NOT NULL,
  `loginname` varchar(50) NOT NULL COMMENT '登入帳號，註冊後不可更改',
  `pwd` varchar(255) NOT NULL COMMENT '密碼（建議使用雜湊）',
  `member_name` varchar(100) NOT NULL COMMENT '顯示名稱，可重複',
  `member_telno` varchar(20) DEFAULT NULL COMMENT '電話號碼，可為空',
  `member_addr` text DEFAULT NULL COMMENT '地址，可為空',
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='會員資料表';

--
-- 傾印資料表的資料 `member`
--

INSERT INTO `member` (`Member_id`, `loginname`, `pwd`, `member_name`, `member_telno`, `member_addr`, `created_at`, `updated_at`) VALUES
(1, 'aeoja', '$2y$10$qwhCEAPj5wE30a7GpIXqMeH6hNs1xhB.dqJG9JFK2Xddiq4yxEMWG', 'aeoja', NULL, NULL, '2026-03-06 21:28:44', '2026-03-06 21:28:44'),
(2, 'jackyhk', 'jackyhk99', 'Jacky HK', NULL, NULL, '2026-03-06 15:47:54', '2026-03-06 15:47:54'),
(3, 'kenny123', 'kenny1234', 'Kenny Wong', '91234567', 'Room 101, Block A', '2026-03-06 15:47:54', '2026-03-06 15:47:54'),
(4, 'lily_ho', 'lilyho2023', 'Lily Ho', '98765432', NULL, '2026-03-06 15:47:54', '2026-03-06 15:47:54'),
(5, 'mary_cheung', 'maryc123', 'Mary Cheung', '91288899', '88 Example St', '2026-03-06 15:47:54', '2026-03-06 15:47:54'),
(6, 'sophia_lam', 'sophia2026', 'Sophia Lam', '60123456', '168 Sample Rd', '2026-03-06 15:47:54', '2026-03-06 15:47:54'),
(7, 'tommychan', 'tommy888', 'Tommy Chan', NULL, 'Flat 8, Tower B', '2026-03-06 15:47:54', '2026-03-06 15:47:54');

-- --------------------------------------------------------

--
-- 資料表結構 `products`
--

CREATE TABLE `products` (
  `product_id` int(11) NOT NULL,
  `product_name` varchar(100) NOT NULL,
  `type` enum('drinks','food','toy','e-things') NOT NULL,
  `supplier` varchar(80) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `price` decimal(10,2) NOT NULL,
  `qty` int(11) UNSIGNED NOT NULL DEFAULT 1 COMMENT 'Stock level of product',
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- 傾印資料表的資料 `products`
--

INSERT INTO `products` (`product_id`, `product_name`, `type`, `supplier`, `description`, `price`, `qty`, `updated_at`, `created_at`) VALUES
(1, '可樂 Classic', 'drinks', '可口可樂公司', '經典碳酸飲料，冰涼爽口', 18.50, 0, '2026-03-08 14:14:07', '2026-03-06 15:57:28'),
(3, '礦泉水 600ml', 'drinks', '康師傅', '天然弱鹼性水', 8.00, 0, '2026-03-07 22:38:29', '2026-03-06 15:57:28'),
(5, '巧克力夾心餅乾', 'food', 'OREO', '酥脆可口夾心', 52.50, 0, '2026-03-08 20:06:36', '2026-03-06 15:57:28'),
(6, '牛肉乾原味', 'food', '老四川', '香辣軟嫩牛肉乾', 68.00, 3, '2026-03-08 22:54:12', '2026-03-06 15:57:28'),
(7, '泰國芒果乾', 'food', '泰國皇家', '天然果乾無添加', 55.00, 3, '2026-03-08 22:54:01', '2026-03-06 15:57:28'),
(8, '樂高經典積木 1000片', 'toy', 'LEGO', '基礎創意積木組', 399.00, 9, '2026-03-08 20:06:36', '2026-03-06 15:57:28'),
(9, '毛絨泰迪熊 50cm', 'toy', '迪士尼授權', '超軟Q彈抱抱熊', 280.00, 9, '2026-03-08 20:06:36', '2026-03-06 15:57:28'),
(10, '遙控賽車 1:16', 'toy', 'Double Eagle', '高速四驅越野車', 450.00, 9, '2026-03-08 20:06:36', '2026-03-06 15:57:28'),
(11, '泡泡槍 電動', 'toy', '玩具反斗城', '夏天戶外必備', 120.00, 9, '2026-03-08 20:06:36', '2026-03-06 15:57:28'),
(12, '無線藍牙耳機 TWS', 'e-things', '小米', '降噪入耳式', 499.00, 0, '2026-03-07 22:38:29', '2026-03-06 15:57:28'),
(13, '行動電源 20000mAh', 'e-things', 'Anker', '快充支援PD', 799.00, 9, '2026-03-08 20:06:36', '2026-03-06 15:57:28'),
(14, 'USB-C 充電線 1.2m', 'e-things', 'Belkin', '耐彎折編織線', 149.00, 9, '2026-03-08 20:06:36', '2026-03-06 15:57:28'),
(15, '智慧檯燈 護眼', 'e-things', 'Yeelight', '色溫可調 USB供電', 299.00, 9, '2026-03-08 20:06:36', '2026-03-06 15:57:28'),
(19, '無線充電板 15W', 'e-things', 'Baseus', '支援多協議快充', 299.00, 9, '2026-03-08 20:06:36', '2026-03-06 15:57:28'),
(20, '鳳梨酥禮盒 12入', 'food', '微熱山丘', '經典伴手禮', 380.00, 9, '2026-03-08 20:06:36', '2026-03-06 15:57:28'),
(21, 'Green Tea Latte', 'drinks', 'Starbucks', 'Creamy matcha with milk and whipped cream', 38.00, 44, '2026-03-08 20:06:36', '2026-03-07 14:16:57'),
(22, 'Espresso Shot', 'drinks', 'Illy', 'Single shot of strong Italian espresso', 28.50, 119, '2026-03-08 20:06:36', '2026-03-07 14:16:57'),
(23, 'Coconut Water', 'drinks', 'Vita Coco', 'Pure coconut water – no added sugar', 18.00, 79, '2026-03-08 20:06:36', '2026-03-07 14:16:57'),
(24, 'Almond Milk Original', 'drinks', 'Alpro', 'Unsweetened plant-based milk', 22.90, 59, '2026-03-08 20:06:36', '2026-03-07 14:16:57'),
(25, 'Strawberry Yogurt Drink', 'drinks', 'Yakult', 'Probiotic strawberry flavored yogurt drink', 12.50, 199, '2026-03-08 20:06:36', '2026-03-07 14:16:57'),
(26, 'Potato Chips Sea Salt', 'food', 'Lay\'s', 'Classic thin & crispy potato chips', 18.00, 149, '2026-03-08 20:06:36', '2026-03-07 14:16:57'),
(27, 'Dark Chocolate 70%', 'food', 'Lindt', 'Smooth Swiss dark chocolate bar', 45.00, 89, '2026-03-08 20:06:36', '2026-03-07 14:16:57'),
(28, 'Beef Jerky Spicy', 'food', 'Jack Link\'s', 'Premium tender beef jerky – hot & spicy', 68.00, 69, '2026-03-08 20:06:36', '2026-03-07 14:16:57'),
(29, 'Dried Mango Slices', 'food', 'Philippine Brand', 'Sweet & chewy natural dried mango', 55.00, 109, '2026-03-08 20:06:36', '2026-03-07 14:16:57'),
(30, 'Instant Ramen Spicy Chicken', 'food', 'Samyang', 'Extremely spicy Korean instant noodles', 15.90, 299, '2026-03-08 20:06:36', '2026-03-07 14:16:57'),
(31, 'LEGO Classic Creative Box', 'toy', 'LEGO', 'Large box with 790 colorful bricks', 299.00, 34, '2026-03-08 20:06:36', '2026-03-07 14:16:57'),
(32, 'Plush Unicorn 40cm', 'toy', 'Squishmallows', 'Super soft & cuddly pastel unicorn', 189.00, 49, '2026-03-08 20:06:36', '2026-03-07 14:16:57'),
(33, 'Remote Control Helicopter', 'toy', 'Syma', 'Mini indoor RC helicopter with LED lights', 249.00, 27, '2026-03-08 20:06:36', '2026-03-07 14:16:57'),
(34, 'Magnetic Building Tiles', 'toy', 'PicassoTiles', '64-piece translucent magnetic tiles set', 399.00, 41, '2026-03-08 20:06:36', '2026-03-07 14:16:57'),
(35, 'Wireless Gaming Mouse', 'e-things', 'Logitech', 'RGB gaming mouse with 16K DPI sensor', 299.00, 64, '2026-03-08 20:06:36', '2026-03-07 14:16:57'),
(36, 'USB-C Hub 7-in-1', 'e-things', 'Anker', 'HDMI, SD reader, USB ports & PD charging', 198.00, 87, '2026-03-08 20:06:36', '2026-03-07 14:16:57'),
(37, 'Portable SSD 1TB', 'e-things', 'Samsung T7', 'Fast USB 3.2 Gen2 external SSD – black', 799.00, 54, '2026-03-08 20:06:36', '2026-03-07 14:16:57'),
(38, 'Bluetooth Speaker Mini', 'e-things', 'JBL Go 3', 'Waterproof portable speaker with rich sound', 349.00, 94, '2026-03-08 20:06:36', '2026-03-07 14:16:57'),
(39, 'Smart LED Bulb E27', 'e-things', 'Philips Hue', 'Color & white ambiance – works with Alexa', 129.00, 139, '2026-03-08 20:06:36', '2026-03-07 14:16:57'),
(40, 'Mechanical Keyboard RGB', 'e-things', 'Keychron K2', 'Wireless hot-swappable mechanical keyboard', 599.00, 37, '2026-03-08 20:06:36', '2026-03-07 14:16:57');

--
-- 已傾印資料表的索引
--

--
-- 資料表索引 `cart`
--
ALTER TABLE `cart`
  ADD PRIMARY KEY (`cart_id`),
  ADD UNIQUE KEY `uk_member_product` (`member_id`,`product_id`),
  ADD KEY `product_id` (`product_id`);

--
-- 資料表索引 `member`
--
ALTER TABLE `member`
  ADD PRIMARY KEY (`loginname`),
  ADD UNIQUE KEY `mem_id` (`Member_id`);

--
-- 資料表索引 `products`
--
ALTER TABLE `products`
  ADD PRIMARY KEY (`product_id`);

--
-- 在傾印的資料表使用自動遞增(AUTO_INCREMENT)
--

--
-- 使用資料表自動遞增(AUTO_INCREMENT) `cart`
--
ALTER TABLE `cart`
  MODIFY `cart_id` bigint(20) NOT NULL AUTO_INCREMENT COMMENT '購物車項目唯一編號', AUTO_INCREMENT=52;

--
-- 使用資料表自動遞增(AUTO_INCREMENT) `member`
--
ALTER TABLE `member`
  MODIFY `Member_id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- 使用資料表自動遞增(AUTO_INCREMENT) `products`
--
ALTER TABLE `products`
  MODIFY `product_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=41;

--
-- 已傾印資料表的限制式
--

--
-- 資料表的限制式 `cart`
--
ALTER TABLE `cart`
  ADD CONSTRAINT `cart_ibfk_1` FOREIGN KEY (`member_id`) REFERENCES `member` (`Member_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `cart_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`product_id`) ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
