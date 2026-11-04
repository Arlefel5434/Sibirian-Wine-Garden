import React from 'react';
import { Footer, Header } from '../components/layout';

export function PublicLayout({ children, overlayHeader = false }) {
    return (
        <div className="global-public-layout">
            <Header overlay={overlayHeader} />
            {children}
            <Footer />
        </div>
    );
}
