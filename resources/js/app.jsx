import React from 'react';
import { createRoot } from 'react-dom/client';
import AdminDashboard from './pages/admin/Dashboard.jsx';
import AdminLogin from './pages/admin/Login.jsx';
import { ResourceFormPage, ResourcePage } from './pages/admin/ContentManager.jsx';
import LiveHostBoard from './pages/admin/LiveHostBoard.jsx';
import DigitalBoard from './pages/display/DigitalBoard.jsx';
import Preview from './pages/admin/Preview.jsx';
import './app.css';
const pages={display:DigitalBoard,'admin/login':AdminLogin,'admin/dashboard':AdminDashboard,'admin/resource':ResourcePage,'admin/form':ResourceFormPage,'admin/preview':Preview,'admin/live-hosts':LiveHostBoard};
const rootElement=document.getElementById('app');const Page=pages[rootElement.dataset.page]??DigitalBoard;const props=JSON.parse(rootElement.dataset.props||'{}');
createRoot(rootElement).render(<React.StrictMode><Page {...props}/></React.StrictMode>);

