// Component to display a list of translation units and their translations.

import React, { useState, useEffect } from 'react';
import './TranslationList.css';

const TranslationList = () => {
    const [translationUnits, setTranslationUnits] = useState([]);
    const [loading, setLoading] = useState(true);
    const [error, setError] = useState(null);
    const [editingUnit, setEditingUnit] = useState(null);
    const [newTranslation, setNewTranslation] = useState('');
    const [filters, setFilters] = useState({
        status: '',
        source_language: '',
        target_language: ''
    });

    const API_BASE_URL = 'http://localhost/robert-php-dev-test/api/translations.php';

    // Fetch translation units from API
    const fetchTranslationUnits = async () => {
        try {
            setLoading(true);
            const queryParams = new URLSearchParams();
            
            // Add filters to query parameters
            Object.entries(filters).forEach(([key, value]) => {
                if (value) queryParams.append(key, value);
            });
            
            const response = await fetch(`${API_BASE_URL}?${queryParams}`);
            const result = await response.json();
            
            if (result.error) {
                throw new Error(result.message);
            }
            
            setTranslationUnits(result.data.units || []);
        } catch (err) {
            setError(err.message);
            console.error('Error fetching translation units:', err);
        } finally {
            setLoading(false);
        }
    };

    // Update translation unit
    const updateTranslation = async (unitId, translatedText) => {
        try {
            const response = await fetch(`${API_BASE_URL}/${unitId}`, {
                method: 'PUT',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    translated_text: translatedText,
                    translation_method: 'human',
                    confidence_score: 1.0,
                    change_reason: 'Updated via React interface'
                })
            });

            const result = await response.json();
            
            if (result.error) {
                throw new Error(result.message);
            }
            
            // Refresh the list
            fetchTranslationUnits();
            setEditingUnit(null);
            setNewTranslation('');
            
            return true;
        } catch (err) {
            setError(err.message);
            console.error('Error updating translation:', err);
            return false;
        }
    };

    // Handle edit button click
    const handleEdit = (unit) => {
        setEditingUnit(unit.id);
        setNewTranslation(unit.translated_text || '');
    };

    // Handle save translation
    const handleSave = async (unitId) => {
        if (!newTranslation.trim()) {
            setError('Translation text cannot be empty');
            return;
        }
        
        const success = await updateTranslation(unitId, newTranslation);
        if (success) {
            setError(null);
        }
    };

    // Handle cancel edit
    const handleCancel = () => {
        setEditingUnit(null);
        setNewTranslation('');
        setError(null);
    };

    // Handle filter change
    const handleFilterChange = (filterName, value) => {
        setFilters(prev => ({
            ...prev,
            [filterName]: value
        }));
    };

    // Apply filters
    const applyFilters = () => {
        fetchTranslationUnits();
    };

    // Clear filters
    const clearFilters = () => {
        setFilters({
            status: '',
            source_language: '',
            target_language: ''
        });
    };

    // Get status badge color
    const getStatusColor = (status) => {
        switch (status) {
            case 'pending': return 'status-pending';
            case 'in_progress': return 'status-progress';
            case 'completed': return 'status-completed';
            case 'reviewed': return 'status-reviewed';
            default: return 'status-pending';
        }
    };

    // Format date
    const formatDate = (dateString) => {
        return new Date(dateString).toLocaleDateString('en-US', {
            year: 'numeric',
            month: 'short',
            day: 'numeric',
            hour: '2-digit',
            minute: '2-digit'
        });
    };

    // Load translation units on component mount
    useEffect(() => {
        fetchTranslationUnits();
    }, []);

    // Apply filters when filters change
    useEffect(() => {
        if (!loading) {
            fetchTranslationUnits();
        }
    }, [filters]);

    if (loading) {
        return (
            <div className="translation-list-container">
                <div className="loading-spinner">
                    <div className="spinner"></div>
                    <p>Loading translation units...</p>
                </div>
            </div>
        );
    }

    return (
        <div className="translation-list-container">
            <div className="translation-list-header">
                <h2>Translation Units</h2>
                <div className="refresh-button">
                    <button onClick={fetchTranslationUnits} className="btn btn-secondary">
                        ↻ Refresh
                    </button>
                </div>
            </div>

            {/* Filters */}
            <div className="filters-section">
                <h3>Filters</h3>
                <div className="filters-grid">
                    <div className="filter-group">
                        <label htmlFor="status-filter">Status:</label>
                        <select
                            id="status-filter"
                            value={filters.status}
                            onChange={(e) => handleFilterChange('status', e.target.value)}
                        >
                            <option value="">All Statuses</option>
                            <option value="pending">Pending</option>
                            <option value="in_progress">In Progress</option>
                            <option value="completed">Completed</option>
                            <option value="reviewed">Reviewed</option>
                        </select>
                    </div>

                    <div className="filter-group">
                        <label htmlFor="source-language-filter">Source Language:</label>
                        <select
                            id="source-language-filter"
                            value={filters.source_language}
                            onChange={(e) => handleFilterChange('source_language', e.target.value)}
                        >
                            <option value="">All Languages</option>
                            <option value="en">English</option>
                            <option value="es">Spanish</option>
                            <option value="fr">French</option>
                            <option value="de">German</option>
                            <option value="it">Italian</option>
                        </select>
                    </div>

                    <div className="filter-group">
                        <label htmlFor="target-language-filter">Target Language:</label>
                        <select
                            id="target-language-filter"
                            value={filters.target_language}
                            onChange={(e) => handleFilterChange('target_language', e.target.value)}
                        >
                            <option value="">All Languages</option>
                            <option value="en">English</option>
                            <option value="es">Spanish</option>
                            <option value="fr">French</option>
                            <option value="de">German</option>
                            <option value="it">Italian</option>
                        </select>
                    </div>

                    <div className="filter-actions">
                        <button onClick={clearFilters} className="btn btn-outline">
                            Clear Filters
                        </button>
                    </div>
                </div>
            </div>

            {/* Error Message */}
            {error && (
                <div className="error-message">
                    <span>⚠️ {error}</span>
                    <button onClick={() => setError(null)} className="error-close">×</button>
                </div>
            )}

            {/* Translation Units List */}
            <div className="translation-units-list">
                {translationUnits.length === 0 ? (
                    <div className="no-units">
                        <p>No translation units found.</p>
                        <p>Try adjusting your filters or add some translation units.</p>
                    </div>
                ) : (
                    translationUnits.map((unit) => (
                        <div key={unit.id} className="translation-unit-card">
                            <div className="unit-header">
                                <div className="unit-info">
                                    <span className="unit-id">#{unit.id}</span>
                                    <span className={`status-badge ${getStatusColor(unit.status)}`}>
                                        {unit.status.replace('_', ' ')}
                                    </span>
                                    <span className="word-count">{unit.word_count} words</span>
                                </div>
                                <div className="unit-meta">
                                    <span className="language-pair">
                                        {unit.source_language.toUpperCase()} → {unit.target_language.toUpperCase()}
                                    </span>
                                    <span className="created-date">
                                        {formatDate(unit.created_at)}
                                    </span>
                                </div>
                            </div>

                            <div className="unit-content">
                                <div className="source-text">
                                    <h4>Source Text:</h4>
                                    <p>{unit.source_text}</p>
                                </div>

                                {unit.context && (
                                    <div className="context">
                                        <h4>Context:</h4>
                                        <p>{unit.context}</p>
                                    </div>
                                )}

                                <div className="translation-section">
                                    <h4>Translation:</h4>
                                    {editingUnit === unit.id ? (
                                        <div className="edit-translation">
                                            <textarea
                                                value={newTranslation}
                                                onChange={(e) => setNewTranslation(e.target.value)}
                                                placeholder="Enter translation..."
                                                rows="3"
                                            />
                                            <div className="edit-actions">
                                                <button
                                                    onClick={() => handleSave(unit.id)}
                                                    className="btn btn-primary"
                                                >
                                                    Save
                                                </button>
                                                <button
                                                    onClick={handleCancel}
                                                    className="btn btn-outline"
                                                >
                                                    Cancel
                                                </button>
                                            </div>
                                        </div>
                                    ) : (
                                        <div className="translation-display">
                                            {unit.translated_text ? (
                                                <p className="translated-text">{unit.translated_text}</p>
                                            ) : (
                                                <p className="no-translation">No translation yet</p>
                                            )}
                                            <button
                                                onClick={() => handleEdit(unit)}
                                                className="btn btn-secondary btn-sm"
                                            >
                                                {unit.translated_text ? 'Edit' : 'Add'} Translation
                                            </button>
                                        </div>
                                    )}
                                </div>

                                {unit.translation_method && (
                                    <div className="translation-meta">
                                        <span className="translation-method">
                                            Method: {unit.translation_method}
                                        </span>
                                        {unit.confidence_score && (
                                            <span className="confidence-score">
                                                Confidence: {(unit.confidence_score * 100).toFixed(0)}%
                                            </span>
                                        )}
                                    </div>
                                )}
                            </div>
                        </div>
                    ))
                )}
            </div>

            {/* Pagination Info */}
            {translationUnits.length > 0 && (
                <div className="pagination-info">
                    <p>Showing {translationUnits.length} translation units</p>
                </div>
            )}
        </div>
    );
};

export default TranslationList;
