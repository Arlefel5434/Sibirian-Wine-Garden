import React, { useEffect, useState } from 'react';
import { createRoot } from 'react-dom/client';
import { BrandMark, PageLoader, StatusBadge } from '../../components/common';
import { PostContent } from '../../components/posts';
import '../../styles/common.css';
import { apiRequest, refreshSession } from '../../lib/api';
import { PublicLayout } from '../../layouts/PublicLayout';

function PostPage({ postId }) {
    const [post, setPost] = useState(null);
    const [user, setUser] = useState(null);
    const [error, setError] = useState('');

    useEffect(() => {
        refreshSession()
            .catch(() => null)
            .then((session) => {
                setUser(session?.user || null);
                return apiRequest(`/api/posts/${postId}`);
            })
            .then((payload) => setPost(payload.data))
            .catch((requestError) => {
                if (requestError.status === 403) {
                    setError('You do not have access to this story.');
                    return;
                }

                setError('The story could not be loaded.');
            });
    }, [postId]);

    if (error) {
        return <main className="article-state"><BrandMark className="article-mark" /><h1>Access unavailable</h1><p>{error}</p><a href="/posts">Back to journal</a></main>;
    }

    if (!post) {
        return <PageLoader className="article-state" markClassName="article-mark">Opening the story…</PageLoader>;
    }

    return (
        <PublicLayout>
            <div className="article-shell">
            <header className="article-nav">
                <a href="/posts" className="back-link">← Journal studio</a>
                {user && (user.role === 'admin' || user.id === post.author?.id) ? <a href={`/posts?edit=${post.id}`} className="edit-link">Edit story</a> : <span />}
            </header>

            <main>
                <article>
                    <header className="article-header">
                        <div className="article-meta"><StatusBadge status={post.status} className="article-status" /><span>{new Date(post.created_at).toLocaleDateString()}</span><span>By {post.author?.name}</span></div>
                        <h1>{post.title}</h1>
                        {post.preview_text && <p className="article-lead">{post.preview_text}</p>}
                    </header>

                    {post.preview_img && <div className="hero-image"><img src={post.preview_img} alt="" /></div>}

                    <PostContent blocks={post.body} />
                </article>
            </main>
            </div>
        </PublicLayout>
    );
}

const root = document.getElementById('post-root');
createRoot(root).render(<PostPage postId={root.dataset.postId} />);
