import React from 'react';
import { Button, FavoriteButton, StatusBadge } from '../common';

function formatPrice(skus) {
    if (!skus?.length) {
        return 'No price';
    }

    const cheapest = [...skus].sort((first, second) => first.price - second.price)[0];

    return new Intl.NumberFormat(undefined, {
        style: 'currency',
        currency: cheapest.currency,
    }).format(cheapest.price / 100);
}

export function ProductCard({ product, canManage = false, favoriteBusy = false, onEdit, onArchive, onToggleFavorite }) {
    const stock = product.skus.reduce((total, sku) => total + sku.stock_quantity, 0);

    return (
        <article className="product-card">
            <div className="product-card-top">
                <span className="product-category">{product.category?.name || 'Uncategorised'}</span>
                <div className="product-card-controls"><StatusBadge status={product.status} />{onToggleFavorite && <FavoriteButton active={product.is_favorite} busy={favoriteBusy} onClick={() => onToggleFavorite(product)} />}</div>
            </div>
            <div className="product-symbol" aria-hidden="true">{product.name.slice(0, 1).toUpperCase()}</div>
            <div className="product-card-copy">
                <h2><a href={`/products/${product.slug}`}>{product.name}</a></h2>
                <p>{product.description || 'No product description yet.'}</p>
            </div>
            <div className="product-facts">
                <div><span>From</span><strong>{formatPrice(product.skus)}</strong></div>
                <div><span>Stock</span><strong>{stock}</strong></div>
                <div><span>Variants</span><strong>{product.skus.length}</strong></div>
            </div>
            <div className="product-card-actions">
                <a href={`/products/${product.slug}`}>View details</a>
                {canManage && <>
                    <Button variant="secondary" onClick={() => onEdit(product)}>Edit</Button>
                    {product.status !== 'archived' && <Button variant="secondary" className="danger-button" onClick={() => onArchive(product)}>Archive</Button>}
                </>}
            </div>
        </article>
    );
}
