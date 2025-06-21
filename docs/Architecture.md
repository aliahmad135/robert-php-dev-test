# Robert CAT Tool - Architecture and Design Decisions

## System Architecture Overview

The Robert CAT tool follows a layered architecture pattern with clear separation of concerns:

```
┌─────────────────────────────────────────────────────────────┐
│                    Frontend (ReactJS)                       │
├─────────────────────────────────────────────────────────────┤
│                    API Layer (PHP)                          │
├─────────────────────────────────────────────────────────────┤
│                  Business Logic Layer                       │
├─────────────────────────────────────────────────────────────┤
│                    Data Access Layer                        │
├─────────────────────────────────────────────────────────────┤
│                    Database (MySQL)                         │
└─────────────────────────────────────────────────────────────┘
```

## 1. Multilingual Content Handling

### Strategy Pattern for Language Processing
- **Language Detection**: Uses language detection libraries to automatically identify source language
- **Text Segmentation**: Implements different segmentation strategies based on language characteristics
- **Translation Memory**: Caches previous translations for reuse across projects

### Internationalization (i18n) Support
- UTF-8 encoding throughout the system
- Locale-specific formatting for dates, numbers, and currencies
- Right-to-left (RTL) language support for Arabic, Hebrew, etc.

## 2. Design Patterns Implementation

### Repository Pattern
- **TranslationUnitRepository**: Handles all database operations for translation units
- **TranslationHistoryRepository**: Manages version control and history
- **LanguageRepository**: Manages supported languages and their metadata

### Factory Pattern
- **TranslationUnitFactory**: Creates translation units with appropriate segmentation
- **DocumentProcessorFactory**: Creates appropriate document processors based on file type

### Observer Pattern
- **TranslationEventSystem**: Notifies subscribers when translations are updated
- **ProgressTracking**: Real-time updates for translation progress

### Strategy Pattern
- **SegmentationStrategy**: Different algorithms for text segmentation
- **TranslationStrategy**: Various translation approaches (MT, human, hybrid)

## 3. Database Schema Design

### Core Tables

#### translation_units
```sql
CREATE TABLE translation_units (
    id INT PRIMARY KEY AUTO_INCREMENT,
    source_text TEXT NOT NULL,
    source_language VARCHAR(10) NOT NULL,
    target_language VARCHAR(10) NOT NULL,
    context TEXT,
    segment_type ENUM('sentence', 'paragraph', 'phrase') DEFAULT 'sentence',
    status ENUM('pending', 'in_progress', 'completed', 'reviewed') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_languages (source_language, target_language),
    INDEX idx_status (status)
);
```

#### translations
```sql
CREATE TABLE translations (
    id INT PRIMARY KEY AUTO_INCREMENT,
    translation_unit_id INT NOT NULL,
    translated_text TEXT NOT NULL,
    translator_id INT,
    translation_method ENUM('human', 'machine', 'hybrid') DEFAULT 'human',
    confidence_score DECIMAL(3,2),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (translation_unit_id) REFERENCES translation_units(id) ON DELETE CASCADE
);
```

#### translation_history
```sql
CREATE TABLE translation_history (
    id INT PRIMARY KEY AUTO_INCREMENT,
    translation_id INT NOT NULL,
    translated_text TEXT NOT NULL,
    translator_id INT,
    change_reason VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (translation_id) REFERENCES translations(id) ON DELETE CASCADE
);
```

#### languages
```sql
CREATE TABLE languages (
    code VARCHAR(10) PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    native_name VARCHAR(100),
    is_rtl BOOLEAN DEFAULT FALSE,
    is_active BOOLEAN DEFAULT TRUE
);
```

#### projects
```sql
CREATE TABLE projects (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(255) NOT NULL,
    source_language VARCHAR(10) NOT NULL,
    target_language VARCHAR(10) NOT NULL,
    status ENUM('draft', 'active', 'completed', 'archived') DEFAULT 'draft',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (source_language) REFERENCES languages(code),
    FOREIGN KEY (target_language) REFERENCES languages(code)
);
```

## 4. Version Control Implementation

### Git-like Version Control for Translations
- **Commit-based History**: Each translation update creates a new version
- **Branch Support**: Multiple translation variants can coexist
- **Merge Capabilities**: Automatic conflict resolution for translation conflicts
- **Rollback Functionality**: Ability to revert to previous versions

### Implementation Details
1. **Version Tracking**: Each translation change is stored in `translation_history`
2. **Change Metadata**: Tracks who made changes, when, and why
3. **Diff Generation**: Shows differences between translation versions
4. **Approval Workflow**: Multi-stage approval process for quality control

## 5. Scalability Considerations

### Horizontal Scaling
- **Load Balancing**: Multiple API servers behind a load balancer
- **Database Sharding**: Shard by project or language pair
- **Caching Layer**: Redis for frequently accessed translations

### Performance Optimization
- **Database Indexing**: Strategic indexes on frequently queried columns
- **Connection Pooling**: Efficient database connection management
- **CDN Integration**: Static assets served via CDN

## 6. Security Measures

### Authentication & Authorization
- **JWT Tokens**: Stateless authentication
- **Role-based Access Control**: Different permissions for translators, reviewers, admins
- **API Rate Limiting**: Prevent abuse and ensure fair usage

### Data Protection
- **Encryption**: Sensitive data encrypted at rest
- **Audit Logging**: Complete audit trail of all changes
- **Data Backup**: Regular automated backups with point-in-time recovery
