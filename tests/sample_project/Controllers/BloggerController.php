<?php

declare(strict_types=1);

namespace Tests\Sample\Controllers;

use App\Core\Controller;
use App\Core\Cache;
use App\Core\Gate;
use Tests\Sample\Models\User;
use Tests\Sample\Models\Post;
use Tests\Sample\Models\Comment;
use Tests\Sample\Services\AnalyticsService;
use Tests\Sample\Controllers\Requests\StorePostRequest;
use Tests\Sample\Controllers\Requests\StoreCommentRequest;
use Tests\Sample\Events\CommentPostedEvent;

class BloggerController extends Controller
{
    /**
     * Constructor Injection of AnalyticsService.
     */
    public function __construct(private AnalyticsService $analytics)
    {
        parent::__construct();
    }

    /**
     * Display the posts index.
     */
    public function index(): string
    {
        $postModel = new Post();
        $userModel = new User();

        // 1. Fetch Posts and eager load their Authors using query cache and dialect-aware joins
        $posts = Cache::remember('homepage_posts_cache', 60, function () use ($postModel) {
            return $postModel->table('posts')
                ->join('users', 'posts.user_id', '=', 'users.id')
                ->select('posts.id', 'posts.user_id', 'posts.title', 'posts.slug', 'posts.body', 'posts.cover_image', 'posts.created_at', 'users.name as author_name')
                ->orderBy('posts.created_at', 'DESC')
                ->get();
        });

        // 2. Eager load comments
        $postsWithComments = $postModel->comments()->loadFor($posts, 'comments');
        
        // Eager load authors for each comment
        $commentModel = new Comment();
        foreach ($postsWithComments as &$p) {
            if (!empty($p['comments'])) {
                $p['comments'] = $commentModel->author()->loadFor($p['comments'], 'author');
            }
        }
        unset($p);

        // 3. Fetch all registered users
        $users = $userModel->table()->get();

        // 4. Container DI stats
        $stats = [
            'total_posts' => $this->analytics->getPostCount(),
            'total_comments' => $this->analytics->getCommentCount(),
            'generated_at' => date('H:i:s'),
        ];

        return $this->render('blog/index', [
            'posts' => $postsWithComments,
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

        // Fetch all registered users
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
     * Show a single post and its comments by slug.
     */
    public function showBySlug(string $slug): string
    {
        $postModel = new Post();
        $post = $postModel->findInstanceBy('slug', $slug);

        if (!$post) {
            $this->response->setStatusCode(404);
            return "Post not found";
        }

        // Fetch comments and load authors
        $comments = $post->comments()->for(['id' => $post->id]);
        $commentModel = new Comment();
        $commentsWithAuthors = $commentModel->author()->loadFor($comments, 'author');

        // Fetch all registered users
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
    public function storePost(StorePostRequest $request): void
    {
        // Validation & Authorization are handled automatically by StorePostRequest
        $data = $request->getBody();
        $userId = auth()->id();

        $coverImagePath = null;
        $file = $request->file('cover_image');
        if ($file && $file['error'] === UPLOAD_ERR_OK && !empty($file['tmp_name'])) {
            if ($file['size'] > 2 * 1024 * 1024) {
                $this->session->setFlash('validation_errors', ['cover_image' => 'The cover image size must not exceed 2MB.']);
                $this->redirect('/');
                return;
            }
            $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
            if (!in_array($file['type'], $allowedTypes, true)) {
                $this->session->setFlash('validation_errors', ['cover_image' => 'The cover image must be a valid image file (JPEG, PNG, GIF, WEBP).']);
                $this->redirect('/');
                return;
            }

            $uploadsDir = dirname(__DIR__) . '/public/uploads';
            if (!is_dir($uploadsDir)) {
                mkdir($uploadsDir, 0755, true);
            }

            $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
            $filename = bin2hex(random_bytes(8)) . '.' . $extension;
            $destination = $uploadsDir . '/' . $filename;

            if (move_uploaded_file($file['tmp_name'], $destination)) {
                $coverImagePath = '/uploads/' . $filename;
            }
        }

        $postModel = new Post();
        $postId = $postModel->create([
            'user_id'     => (int)$userId,
            'title'       => $data['title'],
            'slug'        => $this->slugify($data['title']) . '-' . bin2hex(random_bytes(2)),
            'body'        => $data['body'],
            'cover_image' => $coverImagePath,
        ]);

        // Dispatch a mock published event
        $this->event('post.published', ['post_id' => $postId, 'title' => $data['title']]);

        // Invalidate Cache
        Cache::forget('homepage_posts_cache');

        $this->session->setFlash('success_message', "Post created successfully!");
        $this->redirect('/');
    }

    /**
     * Update an existing post (PUT request).
     */
    public function updatePost(int $id, StorePostRequest $request): void
    {
        // Validation & Authorization are handled automatically by StorePostRequest
        $postModel = new Post();
        $post = $postModel->findInstance($id);

        if (!$post) {
            $this->response->setStatusCode(404);
            echo "Post not found";
            exit;
        }

        // Check authorization policy
        if (Gate::denies('update', $post)) {
            $this->response->setStatusCode(403);
            echo "You are not authorized to update this post.";
            exit;
        }

        $data = $request->getBody();
        $coverImagePath = $post->cover_image ?? null;
        $file = $request->file('cover_image');
        if ($file && $file['error'] === UPLOAD_ERR_OK && !empty($file['tmp_name'])) {
            if ($file['size'] > 2 * 1024 * 1024) {
                $this->session->setFlash('validation_errors', ['cover_image' => 'The cover image size must not exceed 2MB.']);
                $this->redirect('/');
                return;
            }
            $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
            if (!in_array($file['type'], $allowedTypes, true)) {
                $this->session->setFlash('validation_errors', ['cover_image' => 'The cover image must be a valid image file (JPEG, PNG, GIF, WEBP).']);
                $this->redirect('/');
                return;
            }

            $uploadsDir = dirname(__DIR__) . '/public/uploads';
            if (!is_dir($uploadsDir)) {
                mkdir($uploadsDir, 0755, true);
            }

            if ($coverImagePath) {
                $oldFile = dirname(__DIR__) . '/public' . $coverImagePath;
                if (file_exists($oldFile)) {
                    unlink($oldFile);
                }
            }

            $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
            $filename = bin2hex(random_bytes(8)) . '.' . $extension;
            $destination = $uploadsDir . '/' . $filename;

            if (move_uploaded_file($file['tmp_name'], $destination)) {
                $coverImagePath = '/uploads/' . $filename;
            }
        }

        $postModel->table()->where('id', $id)->update([
            'title'       => $data['title'],
            'slug'        => $this->slugify($data['title']) . '-' . bin2hex(random_bytes(2)),
            'body'        => $data['body'],
            'cover_image' => $coverImagePath,
        ]);

        // Invalidate Cache
        Cache::forget('homepage_posts_cache');

        $this->session->setFlash('success_message', "Post updated successfully!");
        $this->redirect('/');
    }

    /**
     * Delete a post.
     */
    public function destroyPost(int $id): void
    {
        if (!auth()->check()) {
            $this->session->setFlash('validation_errors', ['auth' => 'You must be logged in to delete a post.']);
            $this->redirect('/login');
            return;
        }

        $postModel = new Post();
        $post = $postModel->findInstance($id);

        if (!$post) {
            $this->response->setStatusCode(404);
            echo "Post not found";
            exit;
        }

        // Check authorization policy
        if (Gate::denies('delete', $post)) {
            $this->response->setStatusCode(403);
            echo "You are not authorized to delete this post.";
            exit;
        }

        if ($post->cover_image) {
            $file = dirname(__DIR__) . '/public' . $post->cover_image;
            if (file_exists($file)) {
                unlink($file);
            }
        }

        $postModel->table()->where('id', $id)->delete();

        // Invalidate Cache
        Cache::forget('homepage_posts_cache');

        $this->session->setFlash('success_message', "Post deleted successfully.");
        $this->redirect('/');
    }

    /**
     * Store a comment for a post.
     */
    public function storeComment(int $id, StoreCommentRequest $request): void
    {
        // Validation & Authorization are handled automatically by StoreCommentRequest
        $data = $request->getBody();

        $userId = auth()->id();
        if (!$userId) {
            // Fallback for testing suite or guests (use sample author user ID 2)
            $userId = isset($data['user_id']) ? (int)$data['user_id'] : 2;
        }

        $commentModel = new Comment();
        $commentId = $commentModel->create([
            'post_id' => $id,
            'user_id' => (int)$userId,
            'content' => $data['content'],
        ]);

        // Dispatch background async event
        $comment = $commentModel->find($commentId);
        if ($comment) {
            $this->event(CommentPostedEvent::class, $comment);
        }

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
            'email' => 'required|email|unique:users,email',
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
            'password' => password_hash('password123', PASSWORD_BCRYPT),
        ]);

        // Assign default 'user' role
        if ($userId) {
            $user = $userModel->findInstance($userId);
            if ($user) {
                $user->assignRole('user');
            }
        }

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
            $posts = $postModel->table('posts')
                ->where('title', '%' . $query . '%', 'LIKE')
                ->orWhere('body', '%' . $query . '%', 'LIKE')
                ->get();
        }

        return $this->renderViewOnly('blog/search_results', [
            'posts' => $posts
        ]);
    }

    /**
     * Generate URL-safe slug.
     */
    private function slugify(string $text): string
    {
        $text = preg_replace('~[^\pL\d]+~u', '-', $text);
        $text = preg_replace('~[^-\w]+~', '', $text);
        $text = trim($text, '-');
        $text = preg_replace('~-+~', '-', $text);
        $text = mb_strtolower($text, 'UTF-8');
        return empty($text) ? 'n-a' : $text;
    }
}
