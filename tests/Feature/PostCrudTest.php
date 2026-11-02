<?php

use App\Models\Post;
use App\Models\User;

function postPayload(array $overrides = []): array
{
    return [
        'title' => 'Northern harvest notes',
        'preview_img' => 'https://example.com/harvest.jpg',
        'preview_text' => 'A short walk through the first frost harvest.',
        'status' => 'draft',
        'body' => [
            ['type' => 'paragraph', 'data' => ['text' => 'The vines rested under the first snow.']],
        ],
        ...$overrides,
    ];
}

function tokenFor(User $user): string
{
    return auth('api')->login($user);
}

test('post write routes require authentication', function () {
    $post = Post::factory()->create(['status' => 'draft']);

    $this->postJson('/api/posts', postPayload())->assertUnauthorized();
    $this->putJson("/api/posts/{$post->id}", postPayload())->assertUnauthorized();
    $this->deleteJson("/api/posts/{$post->id}")->assertUnauthorized();
});

test('guests can read published posts but not drafts', function () {
    $published = Post::factory()->create(['status' => 'published']);
    $draft = Post::factory()->create(['status' => 'draft']);

    $this
        ->getJson('/api/posts')
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.id', $published->id);

    $this->getJson("/api/posts/{$published->id}")->assertOk();
    $this->getJson("/api/posts/{$draft->id}")->assertForbidden();
});

test('authenticated users can create posts assigned to themselves', function () {
    $author = User::factory()->create();
    $otherUser = User::factory()->create();

    $response = $this
        ->withToken(tokenFor($author))
        ->postJson('/api/posts', [
            ...postPayload(),
            'user_id' => $otherUser->id,
            'slug' => 'client-controlled-slug',
        ]);

    $response
        ->assertCreated()
        ->assertJsonPath('data.title', 'Northern harvest notes')
        ->assertJsonPath('data.slug', 'northern-harvest-notes')
        ->assertJsonPath('data.author.id', $author->id);

    $this->assertDatabaseHas('posts', [
        'id' => $response->json('data.id'),
        'user_id' => $author->id,
        'slug' => 'northern-harvest-notes',
    ]);
});

test('post creation validates content', function () {
    $user = User::factory()->create();

    $this
        ->withToken(tokenFor($user))
        ->postJson('/api/posts', postPayload([
            'title' => '',
            'preview_img' => 'not-a-url',
            'status' => 'unknown',
            'body' => [],
        ]))
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['title', 'preview_img', 'status', 'body']);
});

test('users see published posts and their drafts while administrators see every post', function () {
    $author = User::factory()->create();
    $otherUser = User::factory()->create();
    $admin = User::factory()->create();
    $admin->profile->forceFill(['role' => 'admin'])->save();
    $ownPost = Post::factory()->for($author, 'author')->create(['status' => 'draft']);
    $otherPost = Post::factory()->for($otherUser, 'author')->create(['status' => 'draft']);
    $publishedPost = Post::factory()->for($otherUser, 'author')->create(['status' => 'published']);

    $this
        ->withToken(tokenFor($author))
        ->getJson('/api/posts')
        ->assertOk()
        ->assertJsonCount(2, 'data')
        ->assertJsonFragment(['id' => $ownPost->id])
        ->assertJsonFragment(['id' => $publishedPost->id]);

    $this
        ->withToken(tokenFor($admin))
        ->getJson('/api/posts')
        ->assertOk()
        ->assertJsonCount(3, 'data')
        ->assertJsonFragment(['id' => $otherPost->id]);
});

test('post authors can view and update their posts', function () {
    $author = User::factory()->create();
    $post = Post::factory()->for($author, 'author')->create([
        'title' => 'Old title',
        'slug' => 'old-title',
    ]);

    $this
        ->withToken(tokenFor($author))
        ->getJson("/api/posts/{$post->id}")
        ->assertOk()
        ->assertJsonPath('data.id', $post->id);

    $this
        ->withToken(tokenFor($author))
        ->putJson("/api/posts/{$post->id}", postPayload(['title' => 'New title']))
        ->assertOk()
        ->assertJsonPath('data.title', 'New title')
        ->assertJsonPath('data.slug', 'new-title');
});

test('users cannot view update or delete posts belonging to another user', function () {
    $user = User::factory()->create();
    $post = Post::factory()->create(['status' => 'draft']);
    $token = tokenFor($user);

    $this->withToken($token)->getJson("/api/posts/{$post->id}")->assertForbidden();
    $this->withToken($token)->putJson("/api/posts/{$post->id}", postPayload())->assertForbidden();
    $this->withToken($token)->deleteJson("/api/posts/{$post->id}")->assertForbidden();
});

test('administrators can update and delete posts belonging to another user', function () {
    $admin = User::factory()->create();
    $admin->profile->forceFill(['role' => 'admin'])->save();
    $post = Post::factory()->create();
    $token = tokenFor($admin);

    $this
        ->withToken($token)
        ->putJson("/api/posts/{$post->id}", postPayload(['status' => 'published']))
        ->assertOk()
        ->assertJsonPath('data.status', 'published');

    $this->withToken($token)->deleteJson("/api/posts/{$post->id}")->assertNoContent();
    $this->assertDatabaseMissing('posts', ['id' => $post->id]);
});

test('duplicate post titles receive unique slugs', function () {
    $user = User::factory()->create();
    $token = tokenFor($user);

    $first = $this->withToken($token)->postJson('/api/posts', postPayload())->assertCreated();
    $second = $this->withToken($token)->postJson('/api/posts', postPayload())->assertCreated();

    expect($first->json('data.slug'))->toBe('northern-harvest-notes')
        ->and($second->json('data.slug'))->toBe('northern-harvest-notes-2');
});

test('post page can be opened by slug', function () {
    $post = Post::factory()->create();

    $this
        ->withoutVite()
        ->get("/posts/{$post->slug}")
        ->assertOk()
        ->assertSee($post->title)
        ->assertSee('data-post-id="'.$post->id.'"', false);
});

test('unknown post slugs return not found', function () {
    $this->get('/posts/missing-story')->assertNotFound();
});
