import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import react from '@vitejs/plugin-react';

export default defineConfig({
    plugins: [
        react(),
        laravel({
            input: [
                'resources/css/app.css',
                'resources/css/auth.css',
                'resources/css/blog.css',
                'resources/css/post.css',
                'resources/js/app.js',
                'resources/client/views/auth/LoginView.jsx',
                'resources/client/views/admin/BlogStudioView.jsx',
                'resources/client/views/posts/PostView.jsx',
            ],
            refresh: true,
        }),
    ],
    server: {
        host: '0.0.0.0',
        port: 5173,
        hmr: {
            host: 'localhost',
            clientPort: 5180,
        },
    },
});
