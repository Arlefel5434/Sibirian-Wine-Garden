<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Post;
use Illuminate\Contracts\View\View;

class PostPageController extends Controller
{
    public function __invoke(Post $post): View
    {
        return view('blog.show', compact('post'));
    }
}
