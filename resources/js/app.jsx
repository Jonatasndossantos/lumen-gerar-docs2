import './bootstrap';
import '../css/app.css';

import React from 'react';
import { createRoot } from 'react-dom/client';
import DocumentWizard from './components/DocumentWizard';

// Inicializa o app React
if (document.getElementById('app')) {
    const root = createRoot(document.getElementById('app'));
    root.render(
        <React.StrictMode>
            <DocumentWizard />
        </React.StrictMode>
    );
}