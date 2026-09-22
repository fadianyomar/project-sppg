-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Sep 20, 2026 at 08:24 AM
-- Server version: 10.4.32-MariaDB-log
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `db_keuangan_sppg`
--

-- --------------------------------------------------------

--
-- Table structure for table `expense_categories`
--

CREATE TABLE `expense_categories` (
  `id` int(11) NOT NULL,
  `category_name` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `expense_categories`
--

INSERT INTO `expense_categories` (`id`, `category_name`) VALUES
(1, 'Bahan Pangan'),
(5, 'Lainnya'),
(2, 'Operasional'),
(4, 'Peralatan'),
(3, 'Transportasi');

-- --------------------------------------------------------

--
-- Table structure for table `expense_transactions`
--

CREATE TABLE `expense_transactions` (
  `id` int(11) NOT NULL,
  `transaction_number` varchar(50) NOT NULL,
  `transaction_date` date NOT NULL,
  `category_id` int(11) NOT NULL,
  `supplier_id` int(11) DEFAULT NULL,
  `purchase_order_id` int(11) DEFAULT NULL,
  `amount` decimal(15,2) NOT NULL,
  `description` text DEFAULT NULL,
  `evidence` varchar(255) DEFAULT NULL,
  `created_by` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `expense_transactions`
--

INSERT INTO `expense_transactions` (`id`, `transaction_number`, `transaction_date`, `category_id`, `supplier_id`, `purchase_order_id`, `amount`, `description`, `evidence`, `created_by`, `created_at`) VALUES
(1, 'EXP-202609-001', '2026-09-02', 1, 1, 12, 1500000.00, 'Pembelian 100 Kg beras', NULL, 1, '2026-09-07 06:47:15'),
(11, 'EXP-202609-002', '2026-09-13', 1, NULL, 18, 2050000.00, 'Pembelian Bahan Pangan', NULL, 1, '2026-09-13 11:39:27');

-- --------------------------------------------------------

--
-- Table structure for table `income_transactions`
--

CREATE TABLE `income_transactions` (
  `id` int(11) NOT NULL,
  `transaction_number` varchar(50) NOT NULL,
  `transaction_date` date NOT NULL,
  `source` varchar(150) NOT NULL,
  `category` varchar(100) DEFAULT NULL,
  `amount` decimal(15,2) NOT NULL,
  `description` text DEFAULT NULL,
  `evidence` varchar(255) DEFAULT NULL,
  `created_by` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `income_transactions`
--

INSERT INTO `income_transactions` (`id`, `transaction_number`, `transaction_date`, `source`, `category`, `amount`, `description`, `evidence`, `created_by`, `created_at`) VALUES
(1, 'DM-202609-001', '2026-09-01', 'BGN', 'Dana Operasional', 50000000.00, 'Dana operasional SPPG', NULL, 1, '2026-09-07 06:46:46'),
(2, 'DM-202609-002', '2026-09-05', 'BGN', 'Dana Operasional', 25000000.00, 'Tambahan dana operasional', NULL, 1, '2026-09-07 06:46:46'),
(11, 'DM-202609-003', '2026-09-13', 'BGN', 'Dana Operasional', 5000000.00, 'Tambahan Dana Masuk', NULL, 1, '2026-09-13 11:37:17');

-- --------------------------------------------------------

--
-- Table structure for table `items`
--

CREATE TABLE `items` (
  `id` int(11) NOT NULL,
  `item_code` varchar(30) NOT NULL,
  `item_name` varchar(150) NOT NULL,
  `unit` varchar(30) NOT NULL,
  `minimum_stock` decimal(12,2) NOT NULL DEFAULT 0.00,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `items`
--

INSERT INTO `items` (`id`, `item_code`, `item_name`, `unit`, `minimum_stock`, `created_at`) VALUES
(1, 'BRG001', 'Beras', 'Kg', 50.00, '2026-09-07 05:25:57'),
(2, 'BRG002', 'Telur', 'Butir', 500.00, '2026-09-07 05:25:57'),
(3, 'BRG003', 'Daging Ayam', 'Kg', 30.00, '2026-09-07 05:25:57'),
(4, 'BRG004', 'Minyak Goreng', 'Liter', 20.00, '2026-09-07 05:25:57'),
(5, 'BRG005', 'Sayur', 'Kg', 20.00, '2026-09-07 05:25:57');

-- --------------------------------------------------------

--
-- Table structure for table `purchase_orders`
--

CREATE TABLE `purchase_orders` (
  `id` int(11) NOT NULL,
  `po_number` varchar(50) NOT NULL,
  `supplier_id` int(11) NOT NULL,
  `po_date` date NOT NULL,
  `status` enum('draft','diproses','diterima','dibatalkan') NOT NULL DEFAULT 'draft',
  `notes` text DEFAULT NULL,
  `created_by` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `purchase_orders`
--

INSERT INTO `purchase_orders` (`id`, `po_number`, `supplier_id`, `po_date`, `status`, `notes`, `created_by`, `created_at`) VALUES
(12, 'PO-202609-001', 1, '2026-09-02', 'diterima', 'Pengadaan beras', 1, '2026-09-09 02:36:53'),
(18, 'PO-202609-002', 2, '2026-09-13', 'diterima', 'pengadaaan bahan pangan', 1, '2026-09-13 11:33:22'),
(19, 'PO-202609-003', 1, '2026-09-19', 'dibatalkan', '', 1, '2026-09-19 11:06:37');

-- --------------------------------------------------------

--
-- Table structure for table `purchase_order_details`
--

CREATE TABLE `purchase_order_details` (
  `id` int(11) NOT NULL,
  `purchase_order_id` int(11) NOT NULL,
  `item_id` int(11) NOT NULL,
  `quantity` decimal(12,2) NOT NULL,
  `unit_price` decimal(15,2) NOT NULL,
  `subtotal` decimal(15,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `purchase_order_details`
--

INSERT INTO `purchase_order_details` (`id`, `purchase_order_id`, `item_id`, `quantity`, `unit_price`, `subtotal`) VALUES
(6, 12, 1, 100.00, 15000.00, 1500000.00),
(22, 18, 3, 30.00, 30000.00, 900000.00),
(23, 18, 4, 15.00, 18000.00, 270000.00),
(24, 18, 5, 40.00, 7000.00, 280000.00),
(25, 18, 2, 300.00, 2000.00, 600000.00),
(27, 19, 3, 5.00, 20000.00, 100000.00);

-- --------------------------------------------------------

--
-- Table structure for table `stock_movements`
--

CREATE TABLE `stock_movements` (
  `id` int(11) NOT NULL,
  `item_id` int(11) NOT NULL,
  `movement_date` date NOT NULL,
  `movement_type` enum('IN','OUT','ADJUSTMENT') NOT NULL,
  `quantity` decimal(12,2) NOT NULL,
  `reference_type` varchar(50) DEFAULT NULL,
  `reference_id` int(11) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `created_by` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `stock_movements`
--

INSERT INTO `stock_movements` (`id`, `item_id`, `movement_date`, `movement_type`, `quantity`, `reference_type`, `reference_id`, `description`, `created_by`, `created_at`) VALUES
(1, 1, '2026-09-02', 'IN', 100.00, 'PURCHASE_ORDER', 1, 'Penerimaan 100 Kg beras', 1, '2026-09-07 06:47:24'),
(2, 1, '2026-09-03', 'OUT', 25.00, 'PEMAKAIAN', NULL, 'Pemakaian beras untuk produksi makanan', 1, '2026-09-07 06:47:34'),
(9, 3, '2026-09-13', 'IN', 30.00, 'PURCHASE_ORDER', 18, 'Penerimaan barang dari PO PO-202609-002', 1, '2026-09-13 11:36:17'),
(10, 4, '2026-09-13', 'IN', 15.00, 'PURCHASE_ORDER', 18, 'Penerimaan barang dari PO PO-202609-002', 1, '2026-09-13 11:36:17'),
(11, 5, '2026-09-13', 'IN', 40.00, 'PURCHASE_ORDER', 18, 'Penerimaan barang dari PO PO-202609-002', 1, '2026-09-13 11:36:17'),
(12, 2, '2026-09-13', 'IN', 300.00, 'PURCHASE_ORDER', 18, 'Penerimaan barang dari PO PO-202609-002', 1, '2026-09-13 11:36:17'),
(13, 1, '2026-09-14', 'OUT', 50.00, 'PEMAKAIAN', NULL, 'Pemakaian beras untuk produksi MBG', 1, '2026-09-14 06:01:33'),
(14, 3, '2026-09-14', 'OUT', 30.00, 'PEMAKAIAN', NULL, '', 1, '2026-09-14 06:26:50');

-- --------------------------------------------------------

--
-- Table structure for table `suppliers`
--

CREATE TABLE `suppliers` (
  `id` int(11) NOT NULL,
  `supplier_name` varchar(150) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `suppliers`
--

INSERT INTO `suppliers` (`id`, `supplier_name`, `phone`, `address`, `created_at`) VALUES
(1, 'Supplier Beras Sejahtera', '081234567890', 'Jl. Merdeka No. 10', '2026-09-07 05:25:57'),
(2, 'Supplier Bahan Pangan Makmur', '081298765432', 'Jl. Sudirman No. 20', '2026-09-07 05:25:57');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('admin','operator','kepala') NOT NULL DEFAULT 'operator',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `name`, `username`, `password`, `role`, `created_at`) VALUES
(1, 'Administrator SPPG', 'admin', 'admin123', 'admin', '2026-09-07 05:25:57');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `expense_categories`
--
ALTER TABLE `expense_categories`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `category_name` (`category_name`);

--
-- Indexes for table `expense_transactions`
--
ALTER TABLE `expense_transactions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `transaction_number` (`transaction_number`),
  ADD KEY `fk_expense_category` (`category_id`),
  ADD KEY `fk_expense_supplier` (`supplier_id`),
  ADD KEY `fk_expense_po` (`purchase_order_id`),
  ADD KEY `fk_expense_user` (`created_by`);

--
-- Indexes for table `income_transactions`
--
ALTER TABLE `income_transactions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `transaction_number` (`transaction_number`),
  ADD KEY `fk_income_user` (`created_by`);

--
-- Indexes for table `items`
--
ALTER TABLE `items`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `item_code` (`item_code`);

--
-- Indexes for table `purchase_orders`
--
ALTER TABLE `purchase_orders`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `po_number` (`po_number`),
  ADD KEY `fk_po_supplier` (`supplier_id`),
  ADD KEY `fk_po_user` (`created_by`);

--
-- Indexes for table `purchase_order_details`
--
ALTER TABLE `purchase_order_details`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_po_detail_po` (`purchase_order_id`),
  ADD KEY `fk_po_detail_item` (`item_id`);

--
-- Indexes for table `stock_movements`
--
ALTER TABLE `stock_movements`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_stock_item` (`item_id`),
  ADD KEY `fk_stock_user` (`created_by`);

--
-- Indexes for table `suppliers`
--
ALTER TABLE `suppliers`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `expense_categories`
--
ALTER TABLE `expense_categories`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `expense_transactions`
--
ALTER TABLE `expense_transactions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `income_transactions`
--
ALTER TABLE `income_transactions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `items`
--
ALTER TABLE `items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `purchase_orders`
--
ALTER TABLE `purchase_orders`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=20;

--
-- AUTO_INCREMENT for table `purchase_order_details`
--
ALTER TABLE `purchase_order_details`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=28;

--
-- AUTO_INCREMENT for table `stock_movements`
--
ALTER TABLE `stock_movements`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT for table `suppliers`
--
ALTER TABLE `suppliers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `expense_transactions`
--
ALTER TABLE `expense_transactions`
  ADD CONSTRAINT `fk_expense_category` FOREIGN KEY (`category_id`) REFERENCES `expense_categories` (`id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_expense_po` FOREIGN KEY (`purchase_order_id`) REFERENCES `purchase_orders` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_expense_supplier` FOREIGN KEY (`supplier_id`) REFERENCES `suppliers` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_expense_user` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON UPDATE CASCADE;

--
-- Constraints for table `income_transactions`
--
ALTER TABLE `income_transactions`
  ADD CONSTRAINT `fk_income_user` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON UPDATE CASCADE;

--
-- Constraints for table `purchase_orders`
--
ALTER TABLE `purchase_orders`
  ADD CONSTRAINT `fk_po_supplier` FOREIGN KEY (`supplier_id`) REFERENCES `suppliers` (`id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_po_user` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON UPDATE CASCADE;

--
-- Constraints for table `purchase_order_details`
--
ALTER TABLE `purchase_order_details`
  ADD CONSTRAINT `fk_po_detail_item` FOREIGN KEY (`item_id`) REFERENCES `items` (`id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_po_detail_po` FOREIGN KEY (`purchase_order_id`) REFERENCES `purchase_orders` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `stock_movements`
--
ALTER TABLE `stock_movements`
  ADD CONSTRAINT `fk_stock_item` FOREIGN KEY (`item_id`) REFERENCES `items` (`id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_stock_user` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
