import React from 'react';
import { BrandMark, StatusBadge } from '../common';

function bodyText(body) {
    return (body || [])
        .filter((block) => ['heading', 'paragraph'].includes(block.type))
        .map((block) => block.data?.text || '')
        .filter(Boolean)
        .join('\n\n');
}

export function PostCard({ post, canManage, onEdit, onDelete }) {
    return (
        <article className="post-card">
            <div className="card-image">
                {post.preview_img ? <img src={post.preview_img} alt="" /> : <div className="image-placeholder"><BrandMark className="studio-mark" /></div>}
                <StatusBadge status={post.status} className="status" />
            </div>
            <div className="card-content">
                <p className="card-meta">{post.author?.name} · {new Date(post.updated_at).toLocaleDateString()}</p>
                <h2><a href={`/posts/${post.slug}`}>{post.title}</a></h2>
                <p>{post.preview_text || bodyText(post.body).slice(0, 150) || 'No introduction yet.'}</p>
                <div className="card-actions">
                    <a href={`/posts/${post.slug}`}>Open</a>
                    {canManage && <>
                        <button type="button" onClick={() => onEdit(post)}>Edit</button>
                        <button type="button" className="delete-button" onClick={() => onDelete(post)}>Delete</button>
                    </>}
                </div>
            </div>
        </article>
    );
}
