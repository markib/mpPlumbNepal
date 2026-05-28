import React from 'react';
import ReactDOM from 'react-dom/client';
import './i18n';
import AppComponent from './AppComponent';
import '../css/app.css';
import { configureEcho } from '@laravel/echo-react';

configureEcho({
    broadcaster: 'reverb',
});

ReactDOM.createRoot(document.getElementById('root') as HTMLElement).render(
  // <React.StrictMode>
    <AppComponent />
  // </React.StrictMode>
);
