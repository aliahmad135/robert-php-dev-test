// Component to allow users to add/edit translations to a translation unit.

import React, { useState } from 'react';
import './TranslationForm.css';

const TranslationForm = ({ onTranslationAdded, onCancel }) => {
    const [formData, setFormData] = useState({
        source_text: '',
        source_language: 'en',
        target_language: 'es',
        context: '',
        segment_type: 'sentence',
        project_id: null
    });
    
    const [loading, setLoading] = useState(false);
    const [error, setError] = useState(null);
    const [success, setSuccess] = useState(false);

    const API_BASE_URL = 'http://localhost/robert-php-dev-test/api/translations.php';

    // Handle form input changes
    const handleInputChange = (e) => {
        const { name, value } = e.target;
        setFormData(prev => ({
            ...prev,
            [name]: value
        }));
    };

    // Validate form data
    const validateForm = () => {
        if (!formData.source_text.trim()) {
            setError('Source text is required');
            return false;
        }
        
        if (formData.source_text.length < 3) {
            setError('Source text must be at least 3 characters long');
            return false;
        }
        
        if (formData.source_language === formData.target_language) {
            setError('Source and target languages must be different');
            return false;
        }
        
        return true;
    };

    // Submit form
    const handleSubmit = async (e) => {
        e.preventDefault();
        
        if (!validateForm()) {
            return;
        }
        
        setLoading(true);
        setError(null);
        
        try {
            const response = await fetch(API_BASE_URL, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify(formData)
            });

            const result = await response.json();
            
            if (result.error) {
                throw new Error(result.message);
            }
            
            setSuccess(true);
            setFormData({
                source_text: '',
                source_language: 'en',
                target_language: 'es',
                context: '',
                segment_type: 'sentence',
                project_id: null
            });
            
            // Notify parent component
            if (onTranslationAdded) {
                onTranslationAdded(result.data);
            }
            
            // Reset success message after 3 seconds
            setTimeout(() => {
                setSuccess(false);
            }, 3000);
            
        } catch (err) {
            setError(err.message);
            console.error('Error creating translation unit:', err);
        } finally {
            setLoading(false);
        }
    };

    // Handle cancel
    const handleCancel = () => {
        if (onCancel) {
            onCancel();
        }
    };

    // Calculate word count
    const wordCount = formData.source_text.trim() ? formData.source_text.trim().split(/\s+/).length : 0;

    return (
        <div className="translation-form-container">
            <div className="form-header">
                <h2>Add New Translation Unit</h2>
                <p>Create a new translation unit to be translated</p>
            </div>

            {/* Success Message */}
            {success && (
                <div className="success-message">
                    <span>✅ Translation unit created successfully!</span>
                </div>
            )}

            {/* Error Message */}
            {error && (
                <div className="error-message">
                    <span>⚠️ {error}</span>
                    <button onClick={() => setError(null)} className="error-close">×</button>
                </div>
            )}

            <form onSubmit={handleSubmit} className="translation-form">
                <div className="form-grid">
                    {/* Source Text */}
                    <div className="form-group full-width">
                        <label htmlFor="source_text">
                            Source Text <span className="required">*</span>
                        </label>
                        <textarea
                            id="source_text"
                            name="source_text"
                            value={formData.source_text}
                            onChange={handleInputChange}
                            placeholder="Enter the text to be translated..."
                            rows="4"
                            required
                            maxLength="1000"
                        />
                        <div className="input-meta">
                            <span className="word-count-display">{wordCount} words</span>
                            <span className="char-count">{formData.source_text.length}/1000 characters</span>
                        </div>
                    </div>

                    {/* Language Selection */}
                    <div className="form-group">
                        <label htmlFor="source_language">
                            Source Language <span className="required">*</span>
                        </label>
                        <select
                            id="source_language"
                            name="source_language"
                            value={formData.source_language}
                            onChange={handleInputChange}
                            required
                        >
                            <option value="en">English</option>
                            <option value="es">Spanish</option>
                            <option value="fr">French</option>
                            <option value="de">German</option>
                            <option value="it">Italian</option>
                            <option value="pt">Portuguese</option>
                            <option value="ar">Arabic</option>
                            <option value="zh">Chinese</option>
                            <option value="ja">Japanese</option>
                            <option value="ko">Korean</option>
                        </select>
                    </div>

                    <div className="form-group">
                        <label htmlFor="target_language">
                            Target Language <span className="required">*</span>
                        </label>
                        <select
                            id="target_language"
                            name="target_language"
                            value={formData.target_language}
                            onChange={handleInputChange}
                            required
                        >
                            <option value="en">English</option>
                            <option value="es">Spanish</option>
                            <option value="fr">French</option>
                            <option value="de">German</option>
                            <option value="it">Italian</option>
                            <option value="pt">Portuguese</option>
                            <option value="ar">Arabic</option>
                            <option value="zh">Chinese</option>
                            <option value="ja">Japanese</option>
                            <option value="ko">Korean</option>
                        </select>
                    </div>

                    {/* Segment Type */}
                    <div className="form-group">
                        <label htmlFor="segment_type">Segment Type</label>
                        <select
                            id="segment_type"
                            name="segment_type"
                            value={formData.segment_type}
                            onChange={handleInputChange}
                        >
                            <option value="sentence">Sentence</option>
                            <option value="paragraph">Paragraph</option>
                            <option value="phrase">Phrase</option>
                        </select>
                    </div>

                    {/* Project ID */}
                    <div className="form-group">
                        <label htmlFor="project_id">Project ID (Optional)</label>
                        <input
                            type="number"
                            id="project_id"
                            name="project_id"
                            value={formData.project_id || ''}
                            onChange={handleInputChange}
                            placeholder="Enter project ID"
                            min="1"
                        />
                    </div>

                    {/* Context */}
                    <div className="form-group full-width">
                        <label htmlFor="context">Context (Optional)</label>
                        <textarea
                            id="context"
                            name="context"
                            value={formData.context}
                            onChange={handleInputChange}
                            placeholder="Provide context for better translation quality..."
                            rows="2"
                            maxLength="500"
                        />
                        <div className="input-meta">
                            <span className="char-count">{formData.context.length}/500 characters</span>
                        </div>
                    </div>
                </div>

                {/* Form Actions */}
                <div className="form-actions">
                    <button
                        type="submit"
                        className="btn btn-primary"
                        disabled={loading || !formData.source_text.trim()}
                    >
                        {loading ? (
                            <>
                                <span className="spinner-small"></span>
                                Creating...
                            </>
                        ) : (
                            'Create Translation Unit'
                        )}
                    </button>
                    
                    <button
                        type="button"
                        onClick={handleCancel}
                        className="btn btn-outline"
                        disabled={loading}
                    >
                        Cancel
                    </button>
                </div>
            </form>

            {/* Form Help */}
            <div className="form-help">
                <h4>Tips for better translations:</h4>
                <ul>
                    <li>Provide clear, complete sentences or phrases</li>
                    <li>Include context when the meaning might be ambiguous</li>
                    <li>Use proper punctuation and capitalization</li>
                    <li>Specify the segment type to help with translation memory</li>
                </ul>
            </div>
        </div>
    );
};

export default TranslationForm;
