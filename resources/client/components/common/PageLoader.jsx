import React from 'react';
import { BrandMark } from './BrandMark';

export function PageLoader({ children, markClassName = '', className = '' }) {
    return <main className={`ui-page-loader ${className}`.trim()} role="status"><BrandMark className={markClassName} /><span>{children}</span></main>;
}
