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
                'resources/css/products.css',
                'resources/css/cart.css',
                'resources/client/views/home/HomeView.jsx',
                'resources/client/views/profile/ProfileView.jsx',
                'resources/client/views/cart/CartView.jsx',
                'resources/client/views/orders/OrdersView.jsx',
                'resources/client/views/favorites/FavoritesView.jsx',
                'resources/client/views/wallet/WalletView.jsx',
                'resources/client/views/payments/PaymentSimulationView.jsx',
                'resources/client/views/auth/LoginView.jsx',
                'resources/client/views/auth/RegisterView.jsx',
                'resources/client/views/admin/BlogStudioView.jsx',
                'resources/client/views/admin/AdminOrdersView.jsx',
                'resources/client/views/admin/AdminUsersView.jsx',
                'resources/client/views/posts/PostView.jsx',
                'resources/client/views/products/ProductCatalogView.jsx',
                'resources/client/views/products/ProductView.jsx',
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
