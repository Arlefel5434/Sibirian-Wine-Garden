import React from 'react';
import { Button, FormField, Input, Select, Textarea } from '../common';

function errorAt(errors, path) {
    return errors[path]?.[0];
}

export function ProductEditor({ product, form, options, errors, saving, onChange, onSkuChange, onAddSku, onRemoveSku, onAttributeChange, onAddAttribute, onRemoveAttribute, onCancel, onSubmit }) {
    return (
        <aside className="product-editor" aria-labelledby="product-editor-title">
            <div className="product-editor-heading">
                <div>
                    <p className="product-kicker">{product ? 'Editing product' : 'New product'}</p>
                    <h2 id="product-editor-title">{product?.name || 'Create a product'}</h2>
                </div>
                <Button variant="icon" className="product-close" onClick={onCancel} aria-label="Close editor">×</Button>
            </div>

            <form onSubmit={onSubmit} noValidate>
                <section className="editor-section">
                    <div className="section-title"><span>01</span><h3>Product information</h3></div>
                    <FormField label="Name" name="name" error={errorAt(errors, 'name')}>
                        <Input id="name" value={form.name} onChange={(event) => onChange('name', event.target.value)} autoFocus />
                    </FormField>
                    <FormField label="Description" name="description" error={errorAt(errors, 'description')}>
                        <Textarea id="description" rows="5" value={form.description} onChange={(event) => onChange('description', event.target.value)} />
                    </FormField>
                    <div className="editor-grid">
                        <FormField label="Category" name="category_id" error={errorAt(errors, 'category_id')}>
                            <Select id="category_id" value={form.category_id} onChange={(event) => onChange('category_id', event.target.value)}>
                                <option value="">No category</option>
                                {options.categories.map((category) => <option key={category.id} value={category.id}>{category.name}</option>)}
                            </Select>
                        </FormField>
                        <FormField label="Status" name="status" error={errorAt(errors, 'status')}>
                            <Select id="status" value={form.status} onChange={(event) => onChange('status', event.target.value)}>
                                <option value="draft">Draft</option>
                                <option value="active">Active</option>
                                <option value="archived">Archived</option>
                            </Select>
                        </FormField>
                    </div>
                </section>

                <section className="editor-section">
                    <div className="section-title section-title-action"><div><span>02</span><h3>Variants and stock</h3></div><Button variant="secondary" onClick={onAddSku}>Add variant</Button></div>
                    {errorAt(errors, 'skus') && <p className="section-error">{errorAt(errors, 'skus')}</p>}
                    <div className="variant-list">
                        {form.skus.map((sku, index) => <div className="variant-card" key={sku.key || sku.id}>
                            <div className="variant-card-heading"><strong>Variant {index + 1}</strong>{form.skus.length > 1 && <button type="button" onClick={() => onRemoveSku(index)}>Remove</button>}</div>
                            <div className="editor-grid">
                                <FormField label="SKU code" name={`sku-${index}`} error={errorAt(errors, `skus.${index}.sku`)}><Input id={`sku-${index}`} value={sku.sku} onChange={(event) => onSkuChange(index, 'sku', event.target.value)} /></FormField>
                                <FormField label="Variant name" name={`sku-name-${index}`} error={errorAt(errors, `skus.${index}.name`)}><Input id={`sku-name-${index}`} value={sku.name} onChange={(event) => onSkuChange(index, 'name', event.target.value)} /></FormField>
                                <FormField label="Price in cents" name={`price-${index}`} error={errorAt(errors, `skus.${index}.price`)}><Input id={`price-${index}`} type="number" min="0" value={sku.price} onChange={(event) => onSkuChange(index, 'price', event.target.value)} /></FormField>
                                <FormField label="Stock" name={`stock-${index}`} error={errorAt(errors, `skus.${index}.stock_quantity`)}><Input id={`stock-${index}`} type="number" min="0" value={sku.stock_quantity} onChange={(event) => onSkuChange(index, 'stock_quantity', event.target.value)} /></FormField>
                                <FormField label="Currency" name={`currency-${index}`} error={errorAt(errors, `skus.${index}.currency`)}><Input id={`currency-${index}`} maxLength="3" value={sku.currency} onChange={(event) => onSkuChange(index, 'currency', event.target.value.toUpperCase())} /></FormField>
                                <FormField label="Quantity unit" name={`unit-${index}`} error={errorAt(errors, `skus.${index}.quantity_unit`)}><Select id={`unit-${index}`} value={sku.quantity_unit} onChange={(event) => onSkuChange(index, 'quantity_unit', event.target.value)}><option value="piece">Piece</option><option value="gram">Gram</option><option value="millilitre">Millilitre</option></Select></FormField>
                            </div>
                        </div>)}
                    </div>
                </section>

                <section className="editor-section">
                    <div className="section-title section-title-action"><div><span>03</span><h3>Characteristics</h3></div><Button variant="secondary" onClick={onAddAttribute} disabled={!options.attributes.length}>Add characteristic</Button></div>
                    {!form.attributes.length && <p className="section-empty">No characteristics added.</p>}
                    {form.attributes.map((attribute, index) => <div className="attribute-row" key={attribute.key || attribute.attribute_id || index}>
                        <FormField label="Characteristic" name={`attribute-${index}`} error={errorAt(errors, `attributes.${index}.attribute_id`)}><Select id={`attribute-${index}`} value={attribute.attribute_id} onChange={(event) => onAttributeChange(index, 'attribute_id', event.target.value)}><option value="">Select</option>{options.attributes.map((option) => <option key={option.id} value={option.id}>{option.name}{option.unit ? ` (${option.unit})` : ''}</option>)}</Select></FormField>
                        <FormField label="Value" name={`attribute-value-${index}`} error={errorAt(errors, `attributes.${index}.value`)}><Input id={`attribute-value-${index}`} value={attribute.value} onChange={(event) => onAttributeChange(index, 'value', event.target.value)} /></FormField>
                        <Button variant="icon" className="row-remove" onClick={() => onRemoveAttribute(index)} aria-label="Remove characteristic">×</Button>
                    </div>)}
                </section>

                <section className="editor-section">
                    <div className="section-title"><span>04</span><h3>Russian translation</h3></div>
                    <FormField label="Название" name="ru_name" error={errorAt(errors, 'translations.0.name')}>
                        <Input id="ru_name" value={form.ru_name} onChange={(event) => onChange('ru_name', event.target.value)} />
                    </FormField>
                    <FormField label="Описание" name="ru_description" error={errorAt(errors, 'translations.0.description')}>
                        <Textarea id="ru_description" rows="5" value={form.ru_description} onChange={(event) => onChange('ru_description', event.target.value)} />
                    </FormField>
                </section>

                <div className="product-editor-actions"><Button variant="secondary" onClick={onCancel}>Cancel</Button><Button type="submit" disabled={saving}>{saving ? 'Saving…' : 'Save product'}</Button></div>
            </form>
        </aside>
    );
}
