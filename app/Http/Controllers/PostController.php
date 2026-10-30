<?php

namespace App\Http\Controllers;

use App\DTO\PostData;
use App\Models\Post;

class PostController extends Controller
{
    public function index()
    {
        $posts = Post::with('author')
            ->paginate(10)
            ->through(fn (Post $post) => PostData::fromModel($post));

        return view('blog.index', compact('posts'));
    }
}
