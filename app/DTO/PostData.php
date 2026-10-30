<?php

namespace App\DTO;

use App\Models\Post;

readonly class PostData
{
    public function __construct(
        public int $id,
        public string $title,
        public string $slug,
        public string $preview_img,
        public string $preview_text,
        public string $status,
        public array $body,
        public string $author,
    ) {}

    public static function fromModel(Post $post): self
    {
        return new self(
            id: $post->id,
            title: $post->title,
            slug: $post->slug,
            preview_img: $post->preview_img,
            preview_text: $post->preview_text,
            status: $post->status,
            body: $post->body,
            author: $post->author->name,
        );
    }
}
