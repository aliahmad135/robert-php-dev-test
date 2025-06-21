-- Implement the database schema as discussed in the design.

-- Robert CAT Tool Database Schema
-- This schema supports multilingual translation management with version control

-- Create database
CREATE DATABASE IF NOT EXISTS robert_cat_tool CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE robert_cat_tool;

-- Languages table to store supported languages
CREATE TABLE languages (
    code VARCHAR(10) PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    native_name VARCHAR(100),
    is_rtl BOOLEAN DEFAULT FALSE,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Projects table to organize translation work
CREATE TABLE projects (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(255) NOT NULL,
    description TEXT,
    source_language VARCHAR(10) NOT NULL,
    target_language VARCHAR(10) NOT NULL,
    status ENUM('draft', 'active', 'completed', 'archived') DEFAULT 'draft',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (source_language) REFERENCES languages(code),
    FOREIGN KEY (target_language) REFERENCES languages(code),
    INDEX idx_project_status (status),
    INDEX idx_language_pair (source_language, target_language)
);

-- Translation units table - core entity for translation work
CREATE TABLE translation_units (
    id INT PRIMARY KEY AUTO_INCREMENT,
    project_id INT,
    source_text TEXT NOT NULL,
    source_language VARCHAR(10) NOT NULL,
    target_language VARCHAR(10) NOT NULL,
    context TEXT,
    segment_type ENUM('sentence', 'paragraph', 'phrase') DEFAULT 'sentence',
    status ENUM('pending', 'in_progress', 'completed', 'reviewed') DEFAULT 'pending',
    word_count INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE SET NULL,
    FOREIGN KEY (source_language) REFERENCES languages(code),
    FOREIGN KEY (target_language) REFERENCES languages(code),
    INDEX idx_languages (source_language, target_language),
    INDEX idx_status (status),
    INDEX idx_project (project_id),
    FULLTEXT idx_source_text (source_text)
);

-- Translations table - stores the actual translations
CREATE TABLE translations (
    id INT PRIMARY KEY AUTO_INCREMENT,
    translation_unit_id INT NOT NULL,
    translated_text TEXT NOT NULL,
    translator_id INT,
    translation_method ENUM('human', 'machine', 'hybrid') DEFAULT 'human',
    confidence_score DECIMAL(3,2),
    is_approved BOOLEAN DEFAULT FALSE,
    approved_by INT,
    approved_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (translation_unit_id) REFERENCES translation_units(id) ON DELETE CASCADE,
    INDEX idx_translation_unit (translation_unit_id),
    INDEX idx_translator (translator_id),
    INDEX idx_approved (is_approved)
);

-- Translation history table - version control for translations
CREATE TABLE translation_history (
    id INT PRIMARY KEY AUTO_INCREMENT,
    translation_id INT NOT NULL,
    translated_text TEXT NOT NULL,
    translator_id INT,
    change_reason VARCHAR(255),
    version_number INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (translation_id) REFERENCES translations(id) ON DELETE CASCADE,
    INDEX idx_translation (translation_id),
    INDEX idx_version (version_number)
);

-- Users table for authentication and user management
CREATE TABLE users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(50) UNIQUE NOT NULL,
    email VARCHAR(255) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    role ENUM('translator', 'reviewer', 'admin') DEFAULT 'translator',
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_username (username),
    INDEX idx_email (email),
    INDEX idx_role (role)
);

-- User language preferences
CREATE TABLE user_languages (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    language_code VARCHAR(10) NOT NULL,
    proficiency_level ENUM('beginner', 'intermediate', 'advanced', 'native') DEFAULT 'intermediate',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (language_code) REFERENCES languages(code),
    UNIQUE KEY unique_user_language (user_id, language_code)
);

-- Insert sample data for testing
INSERT INTO languages (code, name, native_name, is_rtl) VALUES
('en', 'English', 'English', FALSE),
('es', 'Spanish', 'Español', FALSE),
('fr', 'French', 'Français', FALSE),
('de', 'German', 'Deutsch', FALSE),
('it', 'Italian', 'Italiano', FALSE),
('pt', 'Portuguese', 'Português', FALSE),
('ar', 'Arabic', 'العربية', TRUE),
('zh', 'Chinese', '中文', FALSE),
('ja', 'Japanese', '日本語', FALSE),
('ko', 'Korean', '한국어', FALSE);

-- Insert sample project
INSERT INTO projects (name, description, source_language, target_language) VALUES
('Sample Translation Project', 'A sample project for testing the CAT tool', 'en', 'es');

-- Insert sample translation units
INSERT INTO translation_units (project_id, source_text, source_language, target_language, context, segment_type, word_count) VALUES
(1, 'Hello, how are you today?', 'en', 'es', 'Greeting context', 'sentence', 5),
(1, 'The weather is beautiful today.', 'en', 'es', 'Weather context', 'sentence', 6),
(1, 'Please submit your report by Friday.', 'en', 'es', 'Work context', 'sentence', 8),
(1, 'We are looking forward to your visit.', 'en', 'es', 'Social context', 'sentence', 8),
(1, 'The meeting has been rescheduled for next week.', 'en', 'es', 'Business context', 'sentence', 9),
(1, 'Thank you for your cooperation.', 'en', 'es', 'Professional context', 'sentence', 6),
(1, 'The new software update includes many improvements.', 'en', 'es', 'Technical context', 'sentence', 9),
(1, 'Please remember to save your work frequently.', 'en', 'es', 'Instruction context', 'sentence', 8),
(1, 'The deadline for this project is approaching.', 'en', 'es', 'Project context', 'sentence', 8),
(1, 'We appreciate your feedback on this matter.', 'en', 'es', 'Feedback context', 'sentence', 8);

-- Insert sample translations
INSERT INTO translations (translation_unit_id, translated_text, translation_method, confidence_score) VALUES
(1, 'Hola, ¿cómo estás hoy?', 'human', 1.00),
(2, 'El clima está hermoso hoy.', 'human', 1.00),
(3, 'Por favor, envía tu informe antes del viernes.', 'human', 1.00),
(4, 'Esperamos con ansias tu visita.', 'human', 1.00),
(5, 'La reunión ha sido reprogramada para la próxima semana.', 'human', 1.00);

-- Insert sample translation history
INSERT INTO translation_history (translation_id, translated_text, change_reason, version_number) VALUES
(1, 'Hola, ¿cómo estás hoy?', 'Initial translation', 1),
(2, 'El clima está hermoso hoy.', 'Initial translation', 1),
(3, 'Por favor, envía tu informe antes del viernes.', 'Initial translation', 1);
