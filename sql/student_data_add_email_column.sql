ALTER TABLE `student_data`
ADD COLUMN `u_email` text COLLATE utf8_unicode_ci NOT NULL AFTER `u_mobile`;
