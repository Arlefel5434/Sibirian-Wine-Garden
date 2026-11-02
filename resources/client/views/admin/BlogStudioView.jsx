import React, { useEffect, useState } from 'react';
import { createRoot } from 'react-dom/client';
import { PostCard, PostEditor } from '../../components/admin';
import { BrandMark, Button, Notice, PageLoader } from '../../components/common';
import '../../styles/common.css';
import { apiRequest, refreshSession } from '../../lib/api';

const emptyForm = {
    title: '',
    preview_img: '',
    preview_text: '',
    status: 'draft',
    content: '',
};

function bodyText(body) {
    return (body || [])
        .filter((block) => ['heading', 'paragraph'].includes(block.type))
        .map((block) => block.data?.text || '')
        .filter(Boolean)
        .join('\n\n');
}

function postForm(post) {
    return {
        title: post.title,
        preview_img: post.preview_img || '',
        preview_text: post.preview_text || '',
        status: post.status,
        content: bodyText(post.body),
    };
}

function payloadFrom(form) {
    return {
        title: form.title,
        preview_img: form.preview_img || null,
        preview_text: form.preview_text || null,
        status: form.status,
        body: [{ type: 'paragraph', data: { text: form.content } }],
    };
}

function BlogStudio() {
    const [user, setUser] = useState(null);
    const [posts, setPosts] = useState([]);
    const [meta, setMeta] = useState(null);
    const [loading, setLoading] = useState(true);
    const [editorOpen, setEditorOpen] = useState(false);
    const [editingPost, setEditingPost] = useState(null);
    const [form, setForm] = useState(emptyForm);
    const [errors, setErrors] = useState({});
    const [notice, setNotice] = useState('');
    const [saving, setSaving] = useState(false);

    async function loadPosts(page = 1) {
        const payload = await apiRequest(`/api/posts?page=${page}`);
        setPosts(payload.data);
        setMeta(payload.meta);
    }

    useEffect(() => {
        refreshSession()
            .then(async (session) => {
                setUser(session.user);
                await loadPosts();

                const editId = new URLSearchParams(window.location.search).get('edit');

                if (editId) {
                    const payload = await apiRequest(`/api/posts/${editId}`);
                    openEdit(payload.data);
                }
            })
            .catch(() => {
                window.location.assign('/login?redirect=/posts');
            })
            .finally(() => setLoading(false));
    }, []);

    function openCreate() {
        setEditingPost(null);
        setForm(emptyForm);
        setErrors({});
        setEditorOpen(true);
    }

    function openEdit(post) {
        setEditingPost(post);
        setForm(postForm(post));
        setErrors({});
        setEditorOpen(true);
    }

    function closeEditor() {
        setEditorOpen(false);
        setEditingPost(null);
        setErrors({});
    }

    async function savePost(event) {
        event.preventDefault();
        setSaving(true);
        setErrors({});

        try {
            const path = editingPost ? `/api/posts/${editingPost.id}` : '/api/posts';
            await apiRequest(path, {
                method: editingPost ? 'PUT' : 'POST',
                body: JSON.stringify(payloadFrom(form)),
            });
            setNotice(editingPost ? 'Story updated.' : 'Story created.');
            closeEditor();
            await loadPosts(meta?.current_page || 1);
        } catch (error) {
            setErrors(error.errors || {});
            if (!Object.keys(error.errors || {}).length) {
                setNotice(error.message);
            }
        } finally {
            setSaving(false);
        }
    }

    async function deletePost(post) {
        if (!window.confirm(`Delete “${post.title}”? This cannot be undone.`)) {
            return;
        }

        try {
            await apiRequest(`/api/posts/${post.id}`, { method: 'DELETE' });
            setNotice('Story deleted.');
            await loadPosts(posts.length === 1 && meta?.current_page > 1 ? meta.current_page - 1 : meta?.current_page || 1);
        } catch (error) {
            setNotice(error.message);
        }
    }

    if (loading) {
        return <PageLoader className="studio-loading" markClassName="studio-mark">Opening the journal studio…</PageLoader>;
    }

    return (
        <div className="studio-shell">
            <header className="studio-header">
                <a href="/" className="studio-brand"><BrandMark className="studio-mark" /><span>Siberian Wine Garden</span></a>
                <div className="user-chip"><span>{user?.name}</span><small>{user?.role || 'customer'}</small></div>
            </header>

            <main className="studio-main">
                <section className="page-heading">
                    <div><p className="kicker">Editorial desk</p><h1>Garden journal</h1><p>Shape field notes, harvest stories, and northern wine culture.</p></div>
                    <Button onClick={openCreate}>Create story</Button>
                </section>

                <Notice className="notice" onClose={() => setNotice('')}>{notice}</Notice>

                {posts.length ? (
                    <section className="posts-grid" aria-label="Journal posts">
                        {posts.map((post) => <PostCard key={post.id} post={post} onEdit={openEdit} onDelete={deletePost} />)}
                    </section>
                ) : (
                    <section className="empty-state"><BrandMark className="studio-mark" /><h2>Your journal is waiting.</h2><p>Create the first story from the garden.</p><Button onClick={openCreate}>Write first story</Button></section>
                )}

                {meta?.last_page > 1 && <nav className="pagination" aria-label="Post pages">
                    <button type="button" disabled={meta.current_page === 1} onClick={() => loadPosts(meta.current_page - 1)}>Previous</button>
                    <span>{meta.current_page} / {meta.last_page}</span>
                    <button type="button" disabled={meta.current_page === meta.last_page} onClick={() => loadPosts(meta.current_page + 1)}>Next</button>
                </nav>}
            </main>

            {editorOpen && <><button type="button" className="editor-backdrop" onClick={closeEditor} aria-label="Close editor" /><PostEditor post={editingPost} form={form} errors={errors} saving={saving} onChange={(name, value) => setForm((current) => ({ ...current, [name]: value }))} onCancel={closeEditor} onSubmit={savePost} /></>}
        </div>
    );
}

createRoot(document.getElementById('blog-root')).render(<BlogStudio />);
