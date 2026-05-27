<?php

declare(strict_types=1);

namespace Tests\Sample\Controllers;

use App\Core\Controller;
use App\Core\Cache;
use Tests\Sample\Models\User;
use Tests\Sample\Models\Post;
use Tests\Sample\Models\Comment;

class BloggerController extends Controller
{
    /**
     * Display the posts index.
     */
    public function index(): string
    {
        $postModel = new Post();
        $userModel = new User();

        // 1. Fetch Posts and eager load their Authors
        $posts = $postModel->table()->get();
        $postsWithAuthors = $postModel->author()->loadFor($posts, 'author');

        // 2. Eager load comments as well
        $postsWithAuthors = $postModel->comments()->loadFor($postsWithAuthors, 'comments');

        // 3. Fetch all registered users
        $users = $userModel->table()->get();

        // 4. Cache statistics for 10 seconds
        $stats = Cache::remember('blog_stats', 10, function () use ($postModel) {
            $totalPosts = $postModel->table()->count();
            return [
                'total_posts' => $totalPosts,
                'generated_at' => date('H:i:s'),
            ];
        });

        return $this->render('blog/index', [
            'posts' => $postsWithAuthors,
            'users' => $users,
            'stats' => $stats,
            'errors' => $this->session->getFlash('validation_errors', []),
            'old' => $this->session->getFlash('old_input', []),
        ]);
    }

    /**
     * Show a single post and its comments.
     */
    public function show(int $id): string
    {
        $postModel = new Post();
        $post = $postModel->findInstance($id);

        if (!$post) {
            $this->response->setStatusCode(404);
            return "Post not found";
        }

        // Fetch comments and load authors
        $comments = $post->comments()->for(['id' => $post->id]);
        $commentModel = new Comment();
        $commentsWithAuthors = $commentModel->author()->loadFor($comments, 'author');

        // Fetch all registered users to select author in comments
        $userModel = new User();
        $users = $userModel->table()->get();

        return $this->render('blog/show', [
            'post' => $post,
            'comments' => $commentsWithAuthors,
            'users' => $users,
            'errors' => $this->session->getFlash('validation_errors', []),
            'old' => $this->session->getFlash('old_input', []),
        ]);
    }

    /**
     * Store a new blog post.
     */
    public function storePost(): void
    {
        $data = $this->request->getBody();

        $v = $this->validate($data, [
            'user_id' => 'required|integer',
            'title'   => 'required|string|min:3',
            'body'    => 'required|string|min:5',
        ]);

        if ($v->fails()) {
            $this->session->setFlash('validation_errors', $v->errors());
            $this->session->setFlash('old_input', $data);
            $this->redirect('/');
            return;
        }

        $postModel = new Post();
        $postId = $postModel->create([
            'user_id' => (int)$data['user_id'],
            'title'   => $data['title'],
            'body'    => $data['body'],
        ]);

        // Dispatch a mock published event
        $this->event('post.published', ['post_id' => $postId, 'title' => $data['title']]);

        Cache::forget('blog_stats');

        $this->session->setFlash('success_message', "Post created successfully!");
        $this->redirect('/');
    }

    /**
     * Update an existing post (PUT request).
     */
    public function updatePost(int $id): void
    {
        $data = $this->request->getBody();

        $v = $this->validate($data, [
            'title' => 'required|string|min:3',
            'body'  => 'required|string|min:5',
        ]);

        if ($v->fails()) {
            $this->session->setFlash('validation_errors', $v->errors());
            $this->redirect('/');
            return;
        }

        $postModel = new Post();
        $postModel->table()->where('id', $id)->update([
            'title' => $data['title'],
            'body'  => $data['body'],
        ]);

        $this->session->setFlash('success_message', "Post updated successfully!");
        $this->redirect('/');
    }

    /**
     * Delete a post.
     */
    public function destroyPost(int $id): void
    {
        $postModel = new Post();
        $postModel->table()->where('id', $id)->delete();

        Cache::forget('blog_stats');

        $this->session->setFlash('success_message', "Post deleted successfully.");
        $this->redirect('/');
    }

    /**
     * Store a comment for a post.
     */
    public function storeComment(int $id): void
    {
        $data = $this->request->getBody();

        $v = $this->validate($data, [
            'user_id' => 'required|integer',
            'content' => 'required|string|min:3',
        ]);

        if ($v->fails()) {
            $this->session->setFlash('validation_errors', $v->errors());
            $this->redirect("/post/{$id}");
            return;
        }

        $commentModel = new Comment();
        $commentModel->create([
            'post_id' => $id,
            'user_id' => (int)$data['user_id'],
            'content' => $data['content'],
        ]);

        $this->session->setFlash('success_message', "Comment added successfully!");
        $this->redirect("/post/{$id}");
    }

    /**
     * Create a new user.
     */
    public function storeUser(): void
    {
        $data = $this->request->getBody();

        // Validate unique email
        $v = $this->validate($data, [
            'name'  => 'required|string|min:3',
            'email' => 'required|email|unique:test_users,email',
        ]);

        if ($v->fails()) {
            $this->session->setFlash('validation_errors', $v->errors());
            $this->session->setFlash('old_input', $data);
            $this->redirect('/');
            return;
        }

        $userModel = new User();
        $userId = $userModel->create([
            'name'  => $data['name'],
            'email' => $data['email'],
        ]);

        $this->session->setFlash('success_message', "User created successfully!");
        $this->redirect('/');
    }

    /**
     * Test Open Redirect mitigation.
     */
    public function redirectTest(): void
    {
        $target = $this->request->get('url', '/');
        $this->redirect($target);
    }

    /**
     * Live HTMX Post Search.
     */
    public function searchPosts(): string
    {
        $query = $this->request->post('query', '');
        $postModel = new Post();

        if (trim($query) === '') {
            $posts = [];
        } else {
            $posts = $postModel->table()
                ->where('title', '%' . $query . '%', 'LIKE')
                ->orWhere('body', '%' . $query . '%', 'LIKE')
                ->get();
        }

        return $this->renderViewOnly('blog/search_results', [
            'posts' => $posts
        ]);
    }
}
