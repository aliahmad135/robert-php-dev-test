import React, { useState } from 'react';
import TranslationList from './TranslationList';
import TranslationForm from './TranslationForm';
import './App.css';

const App = () => {
    const [currentView, setCurrentView] = useState('list'); // 'list' or 'form'
    const [refreshTrigger, setRefreshTrigger] = useState(0);

    const handleTranslationAdded = (newTranslation) => {
        // Trigger refresh of the translation list
        setRefreshTrigger(prev => prev + 1);
        // Switch back to list view
        setCurrentView('list');
    };

    const handleCancelForm = () => {
        setCurrentView('list');
    };

    return (
        <div className="app">
            <header className="app-header">
                <div className="header-content">
                    <h1>Robert CAT Tool</h1>
                    <p>Computer-Assisted Translation Management System</p>
                </div>
                <nav className="app-nav">
                    <button
                        className={`nav-button ${currentView === 'list' ? 'active' : ''}`}
                        onClick={() => setCurrentView('list')}
                    >
                        📋 Translation Units
                    </button>
                    <button
                        className={`nav-button ${currentView === 'form' ? 'active' : ''}`}
                        onClick={() => setCurrentView('form')}
                    >
                        ➕ Add New Unit
                    </button>
                </nav>
            </header>

            <main className="app-main">
                {currentView === 'list' ? (
                    <TranslationList key={refreshTrigger} />
                ) : (
                    <TranslationForm
                        onTranslationAdded={handleTranslationAdded}
                        onCancel={handleCancelForm}
                    />
                )}
            </main>

            <footer className="app-footer">
                <p>&copy; 2024 Robert CAT Tool. Built with React and PHP.</p>
            </footer>
        </div>
    );
};

export default App; 