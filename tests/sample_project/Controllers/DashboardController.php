<?php

declare(strict_types=1);

namespace Tests\Sample\Controllers;

use App\Core\Controller;
use App\Core\Cache;
use Tests\Sample\Models\User;
use Tests\Sample\Models\Order;

class DashboardController extends Controller
{
    /**
     * Display the sample project dashboard.
     */
    public function index(): string
    {
        $userModel = new User();
        $orderModel = new Order();

        // 1. Fetch Users and eager load their Orders to prevent N+1 queries
        $users = $userModel->table()->get();
        $usersWithOrders = $userModel->orders()->loadFor($users, 'orders');

        // 2. Fetch all orders with a raw INNER JOIN to show QueryBuilder JOIN capabilities
        $ordersJoined = $orderModel->table()
            ->join('test_users', 'test_orders.user_id', 'test_users.id')
            ->select('test_orders.*', 'test_users.name as customer_name')
            ->orderBy('test_orders.id', 'DESC')
            ->get();

        // 3. Cache statistics for 10 seconds to demonstrate Cache layer integration
        $stats = Cache::remember('dashboard_stats', 10, function () use ($orderModel) {
            $data = $orderModel->table()->select('status', 'COUNT(*) as count', 'SUM(total) as revenue')->groupBy('status')->get();
            
            $totalCount = 0;
            $totalRevenue = 0.0;
            foreach ($data as $row) {
                $totalCount += (int)$row['count'];
                $totalRevenue += (float)($row['revenue'] ?? 0);
            }

            return [
                'total_orders' => $totalCount,
                'total_revenue' => $totalRevenue,
                'breakdown' => $data,
                'generated_at' => date('H:i:s'),
            ];
        });

        // 4. Render layout + view with params
        return $this->render('dashboard', [
            'users' => $usersWithOrders,
            'orders' => $ordersJoined,
            'stats' => $stats,
            'errors' => $this->session->getFlash('validation_errors', []),
            'old' => $this->session->getFlash('old_input', []),
        ]);
    }

    /**
     * Create a new order.
     */
    public function storeOrder(): void
    {
        $data = $this->request->getBody();

        // Validate input
        $v = $this->validate($data, [
            'user_id' => 'required|integer',
            'status'  => 'required|in:pending,completed',
            'total'   => 'required|regex:/^\d+(\.\d{1,2})?$/', // regex validation check
        ]);

        if ($v->fails()) {
            $this->session->setFlash('validation_errors', $v->errors());
            $this->session->setFlash('old_input', $data);
            $this->session->setFlash('error_message', 'Order validation failed!');
            $this->redirect('/');
            return;
        }

        // Insert using Order model (auto-injects created_at/updated_at timestamps)
        $orderModel = new Order();
        $orderId = $orderModel->create([
            'user_id' => (int)$data['user_id'],
            'status'  => $data['status'],
            'total'   => (float)$data['total'],
        ]);

        // Dispatch Placed Event (demonstrating EventDispatcher)
        $this->event('order.placed', ['order_id' => $orderId, 'total' => $data['total']]);

        // Clear dashboard statistics cache
        Cache::forget('dashboard_stats');

        $this->session->setFlash('success_message', "Order #{$orderId} created successfully!");
        $this->redirect('/');
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
            $this->session->setFlash('error_message', 'User validation failed!');
            $this->redirect('/');
            return;
        }

        // Create User
        $userModel = new User();
        $userId = $userModel->create([
            'name'  => $data['name'],
            'email' => $data['email'],
        ]);

        $this->session->setFlash('success_message', "Customer #{$userId} created successfully!");
        $this->redirect('/');
    }

    /**
     * Update an order's status (demonstrates PUT method spoofing & Model update).
     */
    public function updateOrder(int $id): void
    {
        $data = $this->request->getBody();

        $v = $this->validate($data, [
            'status' => 'required|in:pending,completed',
        ]);

        if ($v->fails()) {
            $this->session->setFlash('validation_errors', $v->errors());
            $this->session->setFlash('error_message', 'Order update validation failed!');
            $this->redirect('/');
            return;
        }

        $orderModel = new Order();
        // Update database record using QueryBuilder with safe where filter
        $orderModel->table()->where('id', $id)->update([
            'status' => $data['status'],
        ]);

        Cache::forget('dashboard_stats');

        $this->session->setFlash('success_message', "Order #{$id} status updated to {$data['status']}.");
        $this->redirect('/');
    }

    /**
     * Delete an order (demonstrates Router HTTP method spoofing).
     */
    public function destroyOrder(int $id): void
    {
        $orderModel = new Order();
        
        // Use QueryBuilder with write-guard (throws exception if no where is set)
        $orderModel->table()->where('id', $id)->delete();

        // Clear dashboard statistics cache
        Cache::forget('dashboard_stats');

        $this->session->setFlash('success_message', "Order #{$id} deleted successfully.");
        $this->redirect('/');
    }


    /**
     * Test response redirect protection against Open Redirect vulnerability.
     */
    public function redirectTest(): void
    {
        $target = $this->request->get('url', '/');
        
        // Open Redirect Mitigation will block external domains and fall back to '/'
        $this->redirect($target);
    }
}
