import React from 'react';
import { Button, FormField, Input, Select, Textarea } from '../common';

export function PostEditor({ post, form, errors, saving, onChange, onCancel, onSubmit }) {
    return (
        <aside className="editor-panel" aria-labelledby="editor-title">
            <div className="editor-heading">
                <div>
                    <p className="kicker">{post ? 'Editing story' : 'New story'}</p>
                    <h2 id="editor-title">{post ? post.title : 'Create a journal entry'}</h2>
                </div>
                <Button variant="icon" className="close-button" onClick={onCancel} aria-label="Close editor">×</Button>
            </div>

            <form onSubmit={onSubmit} noValidate>
                <FormField label="Title" name="title" error={errors.title?.[0]}>
                    <Input id="title" value={form.title} onChange={(event) => onChange('title', event.target.value)} autoFocus />
                </FormField>

                <FormField label="Short introduction" name="preview_text" error={errors.preview_text?.[0]} hint={`${form.preview_text.length}/500`}>
                    <Textarea id="preview_text" rows="3" maxLength="500" value={form.preview_text} onChange={(event) => onChange('preview_text', event.target.value)} />
                </FormField>

                <FormField label="Cover image URL" name="preview_img" error={errors.preview_img?.[0]}>
                    <Input id="preview_img" type="url" placeholder="https://…" value={form.preview_img} onChange={(event) => onChange('preview_img', event.target.value)} />
                </FormField>

                <FormField label="Story" name="content" error={errors.body?.[0]}>
                    <Textarea id="content" rows="10" value={form.content} onChange={(event) => onChange('content', event.target.value)} />
                </FormField>

                <FormField label="Publication status" name="status" error={errors.status?.[0]}>
                    <Select id="status" value={form.status} onChange={(event) => onChange('status', event.target.value)}>
                        <option value="draft">Draft</option>
                        <option value="published">Published</option>
                        <option value="archived">Archived</option>
                    </Select>
                </FormField>

                <div className="section-title"><span>02</span><h3>Russian translation</h3></div>

                <FormField label="Заголовок" name="ru_title" error={errors['translations.0.title']?.[0]}>
                    <Input id="ru_title" value={form.ru_title} onChange={(event) => onChange('ru_title', event.target.value)} />
                </FormField>

                <FormField label="Краткое описание" name="ru_preview_text" error={errors['translations.0.preview_text']?.[0]}>
                    <Textarea id="ru_preview_text" rows="3" maxLength="500" value={form.ru_preview_text} onChange={(event) => onChange('ru_preview_text', event.target.value)} />
                </FormField>

                <FormField label="Текст" name="ru_content" error={errors['translations.0.body']?.[0]}>
                    <Textarea id="ru_content" rows="10" value={form.ru_content} onChange={(event) => onChange('ru_content', event.target.value)} />
                </FormField>

                <div className="editor-actions">
                    <Button variant="secondary" onClick={onCancel}>Cancel</Button>
                    <Button type="submit" disabled={saving}>{saving ? 'Saving…' : 'Save story'}</Button>
                </div>
            </form>
        </aside>
    );
}
